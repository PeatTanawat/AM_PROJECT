<?php
// หน้า public สำหรับลูกค้าโหลดใบกำกับภาษีผ่าน token (ไม่ต้อง login)
//   ?token=xxx        -> หน้า HTML ครอบ (บอกรหัสเปิดไฟล์ + ฝัง PDF ใน iframe)
//   ?token=xxx&raw=1  -> สตรีม PDF binary จริง
// เสิร์ฟเฉพาะลิ้งค์ที่ link_status='1' และยังไม่ถูกลบ

date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;

$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
$raw   = isset($_GET['raw']) && $_GET['raw'] === '1';

function etax_link_error(string $msg): void
{
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>ไม่พบเอกสาร</title></head>'
        . '<body style="font-family:Tahoma,Arial,sans-serif;background:#f3f4f6;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">'
        . '<div style="background:#fff;border-radius:12px;padding:32px 40px;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center;max-width:420px;">'
        . '<div style="font-size:44px;">📄</div>'
        . '<h3 style="color:#dc3545;margin:12px 0;">ไม่สามารถเปิดเอกสารได้</h3>'
        . '<p style="color:#555;">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div></body></html>';
    exit;
}

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    etax_link_error('ลิงก์ไม่ถูกต้อง');
}

try {
    $pdo = (new Connection())->getPdo();
    if (!$pdo) { etax_link_error('ระบบไม่พร้อมใช้งานชั่วคราว'); }

    $stmt = $pdo->prepare(
        "SELECT id, link_status, customer_tax_id
         FROM tbl_etax_link
         WHERE public_token = :t AND delete_at IS NULL LIMIT 1"
    );
    $stmt->execute([':t' => $token]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$link) { etax_link_error('ไม่พบเอกสารหรือลิงก์ถูกลบแล้ว'); }
    if ((string) $link['link_status'] !== '1') { etax_link_error('ลิงก์นี้ถูกปิดการใช้งานแล้ว'); }

    if (!$raw) {
        // หน้า HTML ครอบ: แจ้งรหัสเปิดไฟล์ + ฝัง PDF
        $tax_digits = preg_replace('/\D/', '', (string) $link['customer_tax_id']);
        $pass = strlen($tax_digits) >= 4 ? substr($tax_digits, -4) : '';
        $iframe = htmlspecialchars('etax_link_pdf.php?token=' . $token . '&raw=1', ENT_QUOTES, 'UTF-8');
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>ใบกำกับภาษีอิเล็กทรอนิกส์ (E-Tax)</title>'
            . '<style>body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#f3f4f6;}'
            . '.bar{background:#fff;padding:12px 18px;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;}'
            . '.bar b{color:#1d3557;} .hint{font-size:14px;color:#444;} .hint .pw{font-weight:bold;font-size:1.2em;color:#c1121f;}'
            . 'iframe{display:block;width:100%;height:calc(100vh - 56px);border:0;}</style></head>'
            . '<body><div class="bar"><span><b>ใบกำกับภาษีอิเล็กทรอนิกส์ (E-Tax)</b></span>'
            . ($pass !== '' ? '<span class="hint">รหัสเปิดไฟล์ = <span class="pw">' . htmlspecialchars($pass, ENT_QUOTES, 'UTF-8') . '</span> (เลข 4 ตัวท้ายเลขผู้เสียภาษี)</span>' : '')
            . '</div><iframe src="' . $iframe . '"></iframe></body></html>';
        exit;
    }

    // raw=1 -> สตรีม PDF จริง
    require_once __DIR__ . '/main/core/listEtaxLink/build_etax_link_pdf.php';
    $r = build_etax_link_pdf($pdo, (int) $link['id']);
    if (!$r['ok']) { etax_link_error($r['msg'] !== '' ? $r['msg'] : 'สร้างเอกสารไม่สำเร็จ'); }

    while (ob_get_level() > 0) { @ob_end_clean(); }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="tax_invoice_' . $r['doc_no'] . '.pdf"');
    header('Content-Length: ' . strlen($r['pdf']));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $r['pdf'];
    exit;
} catch (\Throwable $e) {
    error_log('etax_link_pdf Error: ' . $e->getMessage());
    etax_link_error('เกิดข้อผิดพลาดในระบบ');
}
