<?php
// ยืนยันการชำระเงิน (เฉพาะออเดอร์โอนเงินที่รอยืนยัน):
//   payment_status '0' -> '1' + สร้างสิทธิ์เข้าเรียน (enrollment) ต่อคอร์ส แล้วส่งอีเมลแจ้งลูกค้า (best-effort)
// ลอกตรรกะการให้สิทธิ์จาก cpdth/gb_webhook.php (เมื่อชำระเงินสำเร็จ) + อีเมลจาก listEnrollment/AddEnrollment.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\Email;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
if ($order_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำสั่งซื้อ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $pdo_connect->beginTransaction();

    // 1) ดึงออเดอร์ + ตรวจสถานะ/วิธีชำระ
    $stmt = $pdo_connect->prepare(
        "SELECT order_id, user_id, payment_status, payment_method, transaction_ref, total_price
         FROM tbl_orders WHERE order_id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$order) {
        $pdo_connect->rollBack();
        Response::json(0, 'ไม่พบคำสั่งซื้อนี้', null);
    }
    if ((string) $order['payment_status'] !== '0') {
        $pdo_connect->rollBack();
        Response::json(0, 'คำสั่งซื้อนี้ชำระเงินหรือยกเลิกไปแล้ว ไม่สามารถยืนยันซ้ำได้', null);
    }
    if ((string) $order['payment_method'] !== '3') {
        $pdo_connect->rollBack();
        Response::json(0, 'รองรับเฉพาะคำสั่งซื้อแบบโอนเงินผ่านธนาคารเท่านั้น', null);
    }

    $user_id = (int) $order['user_id'];

    // 2) อัปเดตสถานะ -> ชำระเงินสำเร็จ (กันชนกับการกดซ้ำด้วยเงื่อนไข payment_status='0')
    $upd = $pdo_connect->prepare(
        "UPDATE tbl_orders SET payment_status = '1'
         WHERE order_id = :id AND payment_status = '0'"
    );
    $upd->execute([':id' => $order_id]);
    $affected = $upd->rowCount();
    $upd->closeCursor();

    if ($affected === 0) {
        $pdo_connect->rollBack();
        Response::json(0, 'ไม่สามารถอัปเดตสถานะคำสั่งซื้อได้ (อาจถูกดำเนินการไปแล้ว)', null);
    }

    // 3) ดึงรายการคอร์สในออเดอร์ + ระยะเวลาเรียน
    $it = $pdo_connect->prepare(
        "SELECT od.course_id, c.course_period, c.course_name, od.price_at_purchase
         FROM tbl_order_detail od
         LEFT JOIN tbl_course c ON c.course_id = od.course_id
         WHERE od.order_id = :id
         ORDER BY od.list_order ASC, od.detail_id ASC"
    );
    $it->execute([':id' => $order_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
    $it->closeCursor();

    if (empty($items)) {
        $pdo_connect->rollBack();
        Response::json(0, 'ไม่พบรายการคอร์สในคำสั่งซื้อนี้', null);
    }

    // 4) ให้สิทธิ์เข้าเรียน (enrollment) ต่อคอร์ส — expiry = วันนี้ + course_period วัน (ถ้า > 0)
    //    ปกติแถว enrollment ถูกสร้างไว้ตั้งแต่ตอนลูกค้าสั่งซื้อแล้ว (cpdth CreateOrder: payment_status='pending', enroll_access='0')
    //    ยืนยันการชำระเงิน = UPDATE แถวเดิมให้ paid + เปิดสิทธิ์ (enroll_access='1') + เริ่มนับ expiry จากวันนี้
    //    ถ้าไม่พบแถวเดิม (เช่นออเดอร์เก่า) ค่อย INSERT ใหม่ — และต้องใส่ enroll_access ให้ครบ (NOT NULL ไม่มี default; ล้มบน strict mode ถ้าไม่ส่ง)
    $now = date('Y-m-d H:i:s');
    $updEnr = $pdo_connect->prepare(
        "UPDATE tbl_course_enrollment
            SET enroll_payment_status = 'paid',
                enroll_access         = '1',
                enroll_expiry_date    = :expiry_date,
                update_at             = :now
          WHERE enroll_user_id = :uid AND enroll_course_id = :cid
            AND enroll_payment_status = 'pending' AND delete_at IS NULL"
    );
    $insEnr = $pdo_connect->prepare(
        "INSERT INTO tbl_course_enrollment
            (enroll_user_id, enroll_course_id, enroll_payment_status,
             enroll_date, enroll_expiry_date, enroll_is_completed, enroll_access)
         VALUES (:uid, :cid, 'paid', :enroll_date, :expiry_date, '0', '1')"
    );
    $course_names = [];
    foreach ($items as $item) {
        $course_id = (int) $item['course_id'];
        $period    = isset($item['course_period']) ? (int) $item['course_period'] : 0;
        $expiry    = $period > 0 ? date('Y-m-d H:i:s', strtotime("$now +$period days")) : null;

        // เปิดสิทธิ์แถวเดิมก่อน; ถ้าไม่มีแถว pending เดิม (0 rows) ค่อยสร้างใหม่ (กันแถวซ้ำ)
        $updEnr->execute([
            ':uid'         => $user_id,
            ':cid'         => $course_id,
            ':expiry_date' => $expiry,
            ':now'         => $now,
        ]);
        $affectedEnr = $updEnr->rowCount();
        $updEnr->closeCursor();

        if ($affectedEnr === 0) {
            $insEnr->execute([
                ':uid'         => $user_id,
                ':cid'         => $course_id,
                ':enroll_date' => $now,
                ':expiry_date' => $expiry,
            ]);
            $insEnr->closeCursor();
        }

        $course_names[] = (string) ($item['course_name'] ?? '');
    }

    // 5) ดึงข้อมูลลูกค้าไว้ส่งอีเมล (ทำก่อน commit เพราะยังอยู่ใน transaction เดียวกัน)
    $u = $pdo_connect->prepare(
        "SELECT user_email, user_firstname, user_lastname
         FROM tbl_user WHERE user_id = :uid LIMIT 1"
    );
    $u->execute([':uid' => $user_id]);
    $user = $u->fetch(PDO::FETCH_ASSOC) ?: [];
    $u->closeCursor();

    $pdo_connect->commit();

    // 6) ส่งอีเมลแจ้งลูกค้า (best-effort — ไม่ทำให้รายการล้มเหลวถ้าส่งไม่ได้)
    $mail_sent = false;
    $mail_error = null;
    $email = trim((string) ($user['user_email'] ?? ''));
    if ($email !== '') {
        try {
            $name = trim(($user['user_firstname'] ?? '') . ' ' . ($user['user_lastname'] ?? ''));
            $order_num = htmlspecialchars($order['transaction_ref'] ?? '');
            
            $list = array_filter($items, fn($x) => trim($x['course_name'] ?? '') !== '');
            $course_html = '';
            foreach ($list as $index => $item) {
                $c_name = htmlspecialchars(trim($item['course_name'] ?? ''));
                $c_price = number_format((float)($item['price_at_purchase'] ?? 0), 2);
                $course_html .= '
                <tr>
                  <td style="padding-bottom:12px;color:#3a4459;font-size:13px;vertical-align:top;">' . ($index + 1) . '. ' . $c_name . '</td>
                  <td align="center" style="padding-bottom:12px;color:#3a4459;font-size:13px;vertical-align:top;">1</td>
                  <td align="right" style="padding-bottom:12px;color:#3a4459;font-size:13px;font-weight:600;vertical-align:top;">' . $c_price . ' ฿</td>
                </tr>';
            }
                
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';

            if (!empty($host)) {
                $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
                $base_dir = preg_replace('#/(core|scripts|src|main)(/[^/]+)*$#i', '', $script_dir);
                if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
                    $base_dir = '';
                }
                $base_url = $protocol . '://' . $host . $base_dir;
                if (strpos($base_url, '/backoffice') === false && strpos($host, 'localhost') !== false) {
                    $base_url = rtrim($base_url, '/') . '/backoffice';
                }
            } else {
                $base_url = 'https://bigsara-demo.com/am/backoffice';
            }

            $logo_url = rtrim($base_url, '/') . '/assets/images/G_AM_logo-01.jpg';
            $cpdth_base_url = str_replace('/backoffice', '/cpdth', $base_url);
            if (strpos($cpdth_base_url, '/cpdth') === false) {
                $cpdth_base_url = rtrim($cpdth_base_url, '/') . '/cpdth';
            }
            $history_url = rtrim($cpdth_base_url, '/') . '/profile-menu?tab=payment_history&order_id=' . $order_id;
                
            $body = '<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>CPDTH - ยืนยันการชำระเงินสำเร็จ</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0f3;font-family:\'Segoe UI\',Tahoma,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0f3;padding:40px 16px;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(20,30,50,0.06);">
  <tr>
    <td style="background-color:#ffffff;padding:20px;text-align:center;border-bottom:1px solid #e3e6ec;">
       <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
    </td>
  </tr>
  <tr>
    <td style="background-color:#eafaf1;padding:14px 36px;border-bottom:1px solid #c2f5d3;">
      <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
          <td style="color:#1e8449;font-size:13px;font-weight:600;">ยืนยันการชำระเงินสำเร็จ</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:36px;">
      <p style="margin:0 0 4px;color:#8a96ad;font-size:12px;letter-spacing:1px;text-transform:uppercase;">เรียน</p>
      <p style="margin:0 0 24px;color:#152238;font-size:18px;font-weight:600;">คุณ' . htmlspecialchars($name !== '' ? $name : 'ลูกค้า') . '</p>
      <p style="margin:0 0 24px;color:#3a4459;font-size:14px;line-height:1.7;">
        ระบบได้รับยืนยันการชำระเงินของคำสั่งซื้อเรียบร้อยแล้ว คุณสามารถเข้าเรียนได้ทันที<br>
        รายละเอียดมีดังนี้
      </p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f7f9;border-radius:8px;margin-bottom:24px;">
        <tr>
          <td style="padding:20px 22px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding-bottom:12px;color:#8a96ad;font-size:12px;vertical-align:top;">หมายเลขคำสั่งซื้อ</td>
                <td colspan="2" align="right" style="padding-bottom:12px;color:#152238;font-size:13px;font-weight:700;vertical-align:top;white-space:nowrap;">' . $order_num . '</td>
              </tr>
              <tr>
                <td style="border-top:1px solid #e3e6ec;padding-top:12px;padding-bottom:8px;color:#8a96ad;font-size:12px;vertical-align:top;">รายการที่สั่ง</td>
                <td align="center" style="border-top:1px solid #e3e6ec;padding-top:12px;padding-bottom:8px;color:#8a96ad;font-size:12px;vertical-align:top;width:60px;">จำนวน</td>
                <td align="right" style="border-top:1px solid #e3e6ec;padding-top:12px;padding-bottom:8px;color:#8a96ad;font-size:12px;vertical-align:top;width:100px;">ยอดเงิน</td>
              </tr>
              ' . $course_html . '
              <tr>
                <td colspan="2" style="border-top:1px solid #e3e6ec;padding-top:12px;color:#152238;font-size:13px;font-weight:700;vertical-align:top;">ยอดเงินรวม</td>
                <td align="right" style="border-top:1px solid #e3e6ec;padding-top:12px;color:#27ae60;font-size:14px;font-weight:700;vertical-align:top;">' . number_format((float)($order['total_price'] ?? 0), 2) . ' ฿</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10px; margin-bottom: 28px;" align="center">
        <tr>
          <td align="center">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto;">
              <tr>
                <td align="center" style="border-radius:8px;background-color:#152238;">
                  <a href="' . htmlspecialchars($history_url) . '" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;line-height:1.5;text-align:center;">ตรวจสอบประวัติการสั่งซื้อ</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:22px 36px;background-color:#f6f7f9;border-top:1px solid #e3e6ec;">
      <p style="margin:0 0 4px;color:#9aa3b5;font-size:11px;line-height:1.6;">
        อีเมลฉบับนี้ส่งจากระบบ CPDTH โดยอัตโนมัติ กรุณาอย่าตอบกลับอีเมลฉบับนี้
      </p>
      <p style="margin:0;color:#b5bccb;font-size:11px;">&copy; ' . date('Y') . ' CPDTH. สงวนลิขสิทธิ์.</p>
    </td>
  </tr>
</table>
</td>
</tr>
</table>
</body>
</html>';
            $mail_sent = (bool) Email::send($email, 'ยืนยันการชำระเงิน - CPDTH', $body, true);
        } catch (\Throwable $eMail) {
            $mail_error = $eMail->getMessage();
            error_log('ConfirmPayment mail error: ' . $mail_error);
            $mail_sent = false;
        }
    } else {
        $mail_error = 'ไม่มีอีเมลลูกค้าในระบบ';
    }

    Response::json(1, 'ยืนยันการชำระเงินสำเร็จ และสร้างสิทธิ์เข้าเรียนแล้ว', [
        'order_id'         => $order_id,
        'courses_enrolled' => count($items),
        'mail_sent'        => $mail_sent,
        'mail_error'       => $mail_error
    ]);

} catch (\Throwable $e) {
    if ($pdo_connect->inTransaction()) {
        $pdo_connect->rollBack();
    }
    error_log('ConfirmPayment Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
