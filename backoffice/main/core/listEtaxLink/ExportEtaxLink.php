<?php
// สร้าง/ดาวน์โหลด PDF ใบกำกับภาษีจากลิ้งค์ (ฝั่งแอดมิน, ต้อง login)
// รองรับ mode=base64 (พรีวิวผ่าน pdf_preview) และสตรีม inline

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

$link_id = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
if ($link_id <= 0) {
    Response::json(0, 'ไม่พบรหัสลิ้งค์', null);
}

$db_instance = new Connection();
$pdo = $db_instance->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

require_once __DIR__ . '/build_etax_link_pdf.php';

try {
    $r = build_etax_link_pdf($pdo, $link_id);
    if (!$r['ok']) {
        Response::json(0, $r['msg'], null);
    }

    $filename = 'tax_invoice_' . $r['doc_no'] . '.pdf';

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
    error_log('ExportEtaxLink Error: ' . $e->getMessage());
    Response::json(0, 'สร้าง PDF ไม่สำเร็จ', null);
}
