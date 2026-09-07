<?php
// ส่งใบกำกับภาษีทางอีเมลให้ลูกค้า (HTML) ผ่าน PHPMailer (Gmail SMTP)

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

// ออเดอร์ + ลูกค้า
$stmt = $pdo_connect->prepare(
    "SELECT o.order_id, o.user_id, o.total_price, o.payment_method, o.created_at,
            u.user_firstname, u.user_lastname, u.user_email, u.user_citizen_id
     FROM tbl_orders o
     LEFT JOIN tbl_user u ON o.user_id = u.user_id
     WHERE o.order_id = :id AND o.payment_status = '1'
     LIMIT 1"
);
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
if (!$order) {
    Response::json(0, 'ไม่พบคำสั่งซื้อนี้ (หรือยังไม่ชำระเงิน)', null);
}

$email = trim((string) ($order['user_email'] ?? ''));
if ($email === '') {
    Response::json(0, 'ลูกค้ารายนี้ไม่มีอีเมล', null);
}

// ที่อยู่ใบกำกับภาษี
$ad = $pdo_connect->prepare(
    "SELECT addr_name, addr_tax_id, addr_detail, addr_subdistrict, addr_district, addr_province, addr_zipcode
     FROM tbl_user_address
     WHERE addr_user_id = :uid AND delete_at IS NULL
     ORDER BY addr_is_default DESC, addr_id DESC LIMIT 1"
);
$ad->execute([':uid' => (int) $order['user_id']]);
$addr = $ad->fetch(PDO::FETCH_ASSOC) ?: [];
$ad->closeCursor();

// รายการ
$it = $pdo_connect->prepare(
    "SELECT od.price_at_purchase, c.course_name
     FROM tbl_order_detail od
     LEFT JOIN tbl_course c ON c.course_id = od.course_id
     WHERE od.order_id = :id ORDER BY od.list_order ASC, od.detail_id ASC"
);
$it->execute([':id' => $order_id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);
$it->closeCursor();

// คูปองส่วนลด (ถ้ามี)
$cl = $pdo_connect->prepare(
    "SELECT cl.discount_amount, c.coupon_code
     FROM tbl_coupon_logs cl
     LEFT JOIN tbl_coupon c ON c.coupon_id = cl.coupon_id
     WHERE cl.order_id = :id LIMIT 1"
);
$cl->execute([':id' => $order_id]);
$coupon_log = $cl->fetch(PDO::FETCH_ASSOC) ?: null;
$cl->closeCursor();

$full   = trim(($order['user_firstname'] ?? '') . ' ' . ($order['user_lastname'] ?? ''));
$name   = trim((string) ($addr['addr_name'] ?? '')) ?: ($full !== '' ? $full : '-');

$citizen_digits = preg_replace('/\D/', '', (string) ($order['user_citizen_id'] ?? ''));
$pdf_password   = strlen($citizen_digits) >= 4 ? substr($citizen_digits, -4) : $citizen_digits;
$pwd_display    = $pdf_password ?: 'ไม่ได้ตั้งค่าไว้';
$stmt_etx = $pdo_connect->prepare("SELECT etax_no, created_at FROM tbl_etax WHERE order_id = :oid ORDER BY id DESC LIMIT 1");
$stmt_etx->execute([':oid' => $order_id]);
$etx_row = $stmt_etx->fetch(PDO::FETCH_ASSOC);
$stmt_etx->closeCursor();

if ($etx_row) {
    $ts = strtotime($etx_row['created_at']);
    $doc_no = $etx_row['etax_no'] ?: 'ET' . date('ym', $ts) . str_pad((string) $order_id, 7, '0', STR_PAD_LEFT);
    $date_be = date('d/m/', $ts) . (date('Y', $ts) + 543);
} else {
    $ts     = $order['created_at'] ? strtotime($order['created_at']) : time();
    $doc_no = 'ET' . date('ym', $ts) . str_pad((string) $order_id, 7, '0', STR_PAD_LEFT);
    $date_be = date('d/m/', $ts) . (date('Y', $ts) + 543);
}

$total    = (float) ($order['total_price'] ?? 0);
$discount = (float) ($coupon_log['discount_amount'] ?? 0);
$subtotal = round($total + $discount, 2);
$before   = round($total / 1.07, 2);
$vat      = round($total - $before, 2);

$addr_text = trim(implode(' ', array_filter([
    $addr['addr_detail'] ?? '', $addr['addr_subdistrict'] ?? '', $addr['addr_district'] ?? '',
    $addr['addr_province'] ?? '', $addr['addr_zipcode'] ?? '',
]))) ?: '-';

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// สร้างเนื้อหา HTML
$rows_html = '';
$i = 1;
foreach ($items as $row) {
    $price = number_format((float) ($row['price_at_purchase'] ?? 0), 2);
    $rows_html .= '<tr>'
        . '<td style="border:1px solid #ccc;padding:6px;text-align:center;">' . ($i++) . '</td>'
        . '<td style="border:1px solid #ccc;padding:6px;">' . $esc($row['course_name']) . '</td>'
        . '<td style="border:1px solid #ccc;padding:6px;text-align:center;">1</td>'
        . '<td style="border:1px solid #ccc;padding:6px;text-align:right;">' . $price . '</td>'
        . '</tr>';
}
// แถวส่วนลดคูปอง (แสดงเฉพาะเมื่อมีการใช้คูปอง)
if ($coupon_log && $discount > 0) {
    $rows_html .= '<tr style="color:#c00;">'
        . '<td style="border:1px solid #ccc;padding:6px;text-align:center;">' . $i . '</td>'
        . '<td style="border:1px solid #ccc;padding:6px;">ส่วนลด</td>'
        . '<td style="border:1px solid #ccc;padding:6px;text-align:center;">1</td>'
        . '<td style="border:1px solid #ccc;padding:6px;text-align:right;">-' . number_format($discount, 2) . '</td>'
        . '</tr>';
}

$body = '
<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;color:#222;max-width:640px;margin:auto;">
  <h2 style="text-align:center;">ใบเสร็จรับเงิน/ใบกำกับภาษี</h2>
  <p><b>บริษัท เอ เอ็ม ซีพีดี จำกัด</b><br>เลขประจำตัวผู้เสียภาษีอากร 0105565002221 (สำนักงานใหญ่)</p>
  <table style="width:100%;font-size:14px;margin-bottom:12px;">
    <tr>
      <td style="vertical-align:top;">
        ชื่อลูกค้า: ' . $esc($name) . '<br>
        ที่อยู่: ' . $esc($addr_text) . '<br>
        เลขผู้เสียภาษี: ' . $esc($addr['addr_tax_id'] ?? '-') . '
      </td>
      <td style="vertical-align:top;text-align:right;">
        เลขที่: ' . $esc($doc_no) . '<br>
        วันที่: ' . $esc($date_be) . '
      </td>
    </tr>
  </table>
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="background:#f0f0f0;">
        <th style="border:1px solid #ccc;padding:6px;">ลำดับ</th>
        <th style="border:1px solid #ccc;padding:6px;">รายการ</th>
        <th style="border:1px solid #ccc;padding:6px;">จำนวน</th>
        <th style="border:1px solid #ccc;padding:6px;">จำนวนเงิน</th>
      </tr>
    </thead>
    <tbody>' . $rows_html . '</tbody>
  </table>
  <table style="width:100%;font-size:14px;margin-top:10px;">
    <tr><td style="text-align:right;">รวม:</td><td style="text-align:right;width:140px;">' . number_format($before, 2) . ' บาท</td></tr>
    <tr><td style="text-align:right;">ภาษีมูลค่าเพิ่ม 7%:</td><td style="text-align:right;">' . number_format($vat, 2) . ' บาท</td></tr>
    <tr><td style="text-align:right;"><b>ยอดเงินสุทธิ:</b></td><td style="text-align:right;"><b>' . number_format($total, 2) . ' บาท</b></td></tr>
  </table>

  <p style="margin-top:16px;background:#fff8e1;border:1px solid #ffe082;padding:10px 12px;border-radius:6px;font-size:13px;">
    <b>หมายเหตุ:</b> ไฟล์ใบกำกับภาษี (PDF) ที่แนบมามีการตั้งรหัสผ่านเพื่อความปลอดภัย<br>
    รหัสผ่านในการเปิดไฟล์คือ: <b style="font-size:15px;color:#d32f2f;">' . $esc($pwd_display) . '</b> (เลข 4 ตัวท้ายของรหัสบัตรประชาชน)
  </p>
  <p style="color:#888;font-size:12px;margin-top:16px;">อีเมลฉบับนี้ส่งจากระบบ CPDTH โดยอัตโนมัติ</p>
</div>';

$subject = 'ใบกำกับภาษี เลขที่ ' . $doc_no . ' - CPDTH';

// แนบไฟล์ PDF ใบกำกับภาษีจริง (สร้างด้วย mPDF ผ่านฟังก์ชันกลางเดียวกับหน้าดาวน์โหลด)
$attachments = [];
$tmp_pdf = null;
require_once __DIR__ . '/build_etax_pdf.php';
try {
    $built = build_etax_pdf($pdo_connect, $order_id);
    if ($built['ok'] && $built['pdf'] !== '') {
        $tmp_pdf = tempnam(sys_get_temp_dir(), 'etax_') . '.pdf';
        if (@file_put_contents($tmp_pdf, $built['pdf']) !== false) {
            $attachments[] = ['path' => $tmp_pdf, 'name' => 'tax_invoice_' . $doc_no . '.pdf'];
        }
    }
} catch (\Throwable $e) {
    error_log('SendEtaxEmail build PDF error: ' . $e->getMessage());
    // ส่งต่อแบบไม่มีไฟล์แนบ ดีกว่าส่งไม่ได้เลย
}

$ok = Email::send($email, $subject, $body, true, $attachments);

// ลบไฟล์ชั่วคราว
if ($tmp_pdf && is_file($tmp_pdf)) { @unlink($tmp_pdf); }

if ($ok) {
    Response::json(1, 'ส่งใบกำกับภาษี (พร้อมไฟล์ PDF) ไปยัง ' . $email . ' สำเร็จ', null);
} else {
    Response::json(0, 'ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่า SMTP', null);
}
