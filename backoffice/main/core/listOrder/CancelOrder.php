<?php
// ยกเลิกคำสั่งซื้อ (เฉพาะออเดอร์ที่ยังรอชำระเงิน): payment_status '0' -> '2'
// ไม่แตะ enrollment เพราะออเดอร์ที่ยกเลิกได้ยังไม่ชำระ จึงยังไม่เคยให้สิทธิ์เข้าเรียน

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\Email;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
if ($order_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำสั่งซื้อ', null);
}

$remark = isset($_POST['remark']) ? trim($_POST['remark']) : null;

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // ตรวจสถานะปัจจุบันก่อนยกเลิก พร้อมดึงข้อมูลผู้ใช้เพื่อส่งอีเมล
    $stmt = $pdo_connect->prepare(
        "SELECT o.user_id,
                o.payment_status,
                o.transaction_ref,
                o.total_price,
                u.user_email, 
                u.user_firstname, 
                u.user_lastname 
        FROM tbl_orders o
        LEFT JOIN tbl_user u ON o.user_id = u.user_id
        WHERE o.order_id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($order === false) {
        Response::json(0, 'ไม่พบคำสั่งซื้อนี้', null);
    }
    
    $status = $order['payment_status'];
    
    if ((string) $status === '2') {
        Response::json(0, 'คำสั่งซื้อนี้ถูกยกเลิกไปแล้ว', null);
    }
    if ((string) $status === '1') {
        Response::json(0, 'ไม่สามารถยกเลิกคำสั่งซื้อที่ชำระเงินแล้วได้', null);
    }

    // ยกเลิก: '0' -> '2'
    $upd = $pdo_connect->prepare(
        "UPDATE tbl_orders SET 
            payment_status = '2', 
            remark_reject = :remark
        WHERE order_id = :id AND payment_status = '0'"
    );
    $upd->execute([':id' => $order_id, ':remark' => $remark]);
    $affected = $upd->rowCount();
    $upd->closeCursor();

    if ($affected === 0) {
        Response::json(0, 'ไม่สามารถยกเลิกคำสั่งซื้อได้ (อาจถูกดำเนินการไปแล้ว)', null);
    }

    // ดึงรายการคอร์สในออเดอร์
    $it = $pdo_connect->prepare(
        "SELECT c.course_id, c.course_name, od.price_at_purchase
         FROM tbl_order_detail od
         LEFT JOIN tbl_course c ON c.course_id = od.course_id
         WHERE od.order_id = :id
         ORDER BY od.list_order ASC, od.detail_id ASC"
    );
    $it->execute([':id' => $order_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
    $it->closeCursor();

    // อัปเดตสถานะในตาราง enrollment เป็น reject สำหรับคอร์สที่เกี่ยวข้อง
    $user_id = $order['user_id'] ?? 0;
    if ($user_id > 0 && count($items) > 0) {
        $now = date('Y-m-d H:i:s');
        $updEnr = $pdo_connect->prepare(
            "UPDATE tbl_course_enrollment
                SET enroll_payment_status = 'reject',
                    update_at             = :now
              WHERE enroll_user_id = :uid 
                AND enroll_course_id = :cid
                AND enroll_payment_status = 'pending' 
                AND delete_at IS NULL"
        );
        foreach ($items as $item) {
            $course_id = (int)($item['course_id'] ?? 0);
            if ($course_id > 0) {
                $updEnr->execute([
                    ':uid' => $user_id,
                    ':cid' => $course_id,
                    ':now' => $now
                ]);
            }
        }
    }

    $course_names = array_column($items, 'course_name');

    // ส่งอีเมลแจ้งลูกค้ายกเลิกคำสั่งซื้อ
    $mail_sent = false;
    $email_address = trim((string) ($order['user_email'] ?? ''));
    $mail_error = null;
    if ($email_address !== '') {
        try {
            $name = trim(($order['user_firstname'] ?? '') . ' ' . ($order['user_lastname'] ?? ''));
            $order_num = htmlspecialchars($order['transaction_ref'] ?? '');
            $reason = htmlspecialchars($remark !== '' && $remark !== null ? $remark : 'ไม่ระบุเหตุผล');

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
<title>CPDTH - อีเมลยกเลิกคำสั่งซื้อ</title>
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
    <td style="background-color:#fdecea;padding:14px 36px;border-bottom:1px solid #f5c6c2;">
      <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
          <td style="color:#a83226;font-size:14px;font-weight:600;">คำสั่งซื้อถูกยกเลิก</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:36px;">
      <p style="margin:0 0 4px;color:#8a96ad;font-size:12px;letter-spacing:1px;text-transform:uppercase;">เรียน</p>
      <p style="margin:0 0 24px;color:#152238;font-size:18px;font-weight:600;">คุณ' . htmlspecialchars($name !== '' ? $name : 'ลูกค้า') . '</p>
      <p style="margin:0 0 24px;color:#3a4459;font-size:14px;line-height:1.7;">
        คำสั่งซื้อของคุณถูกยกเลิกเนื่องจาก: <b>"' . htmlspecialchars($reason) . '"</b><br>
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
                <td align="right" style="border-top:1px solid #e3e6ec;padding-top:12px;color:#c0392b;font-size:14px;font-weight:700;vertical-align:top;">' . number_format((float)($order['total_price'] ?? 0), 2) . ' ฿</td>
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
        อีเมลฉบับนี้ส่งจากระบบ CPDTH โดยอัตโนมัติ กรุณาอย่าตอบกลับอีเมลฉบับนี้ หากมีขอสงสัยกรุณาติดต่อ admin CPDTH  
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
            
            $mail_sent = (bool) Email::send($email_address, 'ยกเลิกคำสั่งซื้อ/ปฏิเสธการชำระเงิน - CPDTH', $body, true);
        } catch (\Throwable $eMail) {
            $mail_error = $eMail->getMessage();
            error_log('CancelOrder mail error: ' . $mail_error);
        }
    } else {
        $mail_error = 'ไม่มีอีเมลลูกค้าในระบบ';
    }

    Response::json(1, 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว', [
        'order_id' => $order_id,
        'mail_sent' => $mail_sent,
        'mail_error' => $mail_error
    ]);
} catch (\Throwable $e) {
    error_log('CancelOrder Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', null);
} finally {
    $pdo_connect = null;
}
