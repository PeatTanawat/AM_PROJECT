<?php
// สร้างใบเสร็จรับเงิน/ใบกำกับภาษีเป็นไฟล์ PDF จริง (mPDF) แล้วเปิดใน viewer ของเบราว์เซอร์
// ข้อมูลจาก tbl_orders + tbl_order_detail + tbl_user_address (สร้างผ่าน build_etax_pdf)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

// เปิดผ่าน form POST (ไม่มี header Authorization) -> รับ token จาก POST แทน
if (empty($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_POST['access_token'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_POST['access_token'];
}

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

require_once __DIR__ . '/build_etax_pdf.php';

try {
    $r = build_etax_pdf($pdo_connect, $order_id);
    if (!$r['ok']) {
        Response::json(0, $r['msg'], null);
    }

    $filename = 'tax_invoice_' . $r['doc_no'] . '.pdf';

    // โหมดพรีวิว: ส่ง base64 ผ่าน JSON (เลี่ยง download manager จับไฟล์ PDF)
    if (($_POST['mode'] ?? '') === 'base64') {
        Response::json(1, 'ok', ['filename' => $filename, 'pdf' => base64_encode($r['pdf'])]);
    }

    while (ob_get_level() > 0) { @ob_end_clean(); }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($r['pdf']));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $r['pdf'];
    exit;
} catch (\Throwable $e) {
    error_log('ExportEtax Error: ' . $e->getMessage());
    Response::json(0, 'สร้าง PDF ไม่สำเร็จ', null);
}
