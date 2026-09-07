<?php
// สร้าง "งานอัปโหลด" บน Vimeo (tus) แล้วคืน upload_link ให้เบราว์เซอร์อัปไฟล์ตรงไป Vimeo เอง
// -> ได้ progress จริง + ไม่ต้องส่งไฟล์ผ่าน server เรา (เร็วกว่า/ไม่กิน bandwidth server)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use Vimeo\Vimeo;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
$size      = isset($_POST['size']) ? (int) $_POST['size'] : 0;
// ชื่อวิดีโอบน Vimeo = ชื่อบทเรียนที่ผู้ใช้พิมพ์ (ถ้าไม่ส่งมา fallback เป็น lesson-{id})
$lesson_name = isset($_POST['lesson_name']) ? trim((string) $_POST['lesson_name']) : '';
$video_name  = $lesson_name !== '' ? $lesson_name : ('lesson-' . $lesson_id);
if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}
if ($size <= 0) {
    Response::json(0, 'ขนาดไฟล์ไม่ถูกต้อง', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$token = trim($_ENV['VIMEO_ACCESS_TOKEN'] ?? '');
if ($token === '' || $token === '...') {
    Response::json(0, 'ยังไม่ได้ตั้งค่า VIMEO_ACCESS_TOKEN ใน .env', null);
}

// ตรวจว่าบทเรียนมีจริง
$check = $pdo_connect->prepare("SELECT lesson_id FROM tbl_lesson WHERE lesson_id = :id AND delete_at IS NULL LIMIT 1");
$check->execute([':id' => $lesson_id]);
if (!$check->fetchColumn()) {
    $check->closeCursor();
    Response::json(0, 'ไม่พบบทเรียนนี้', null);
}
$check->closeCursor();

try {
    $lib = new Vimeo($_ENV['VIMEO_CLIENT_ID'] ?? '', $_ENV['VIMEO_CLIENT_SECRET'] ?? '', $token);

    // privacy: ถ้าตั้ง VIMEO_EMBED_DOMAINS ใน .env -> embed-only (ซ่อนจาก vimeo.com + ฝังได้เฉพาะโดเมนที่กำหนด)
    //          ถ้าไม่ตั้ง (เช่น local dev) -> public (view=anybody) เพื่อให้เล่นบน localhost ได้
    $embed_domains = array_values(array_filter(array_map('trim', explode(',', (string) ($_ENV['VIMEO_EMBED_DOMAINS'] ?? '')))));
    $privacy = $embed_domains
        ? ['embed' => 'whitelist', 'view' => 'disable']
        : ['embed' => 'public', 'view' => 'anybody'];

    // สร้างวิดีโอแบบ tus (ยังไม่ส่งไฟล์) -> Vimeo คืน upload_link สำหรับ PATCH ไฟล์เข้าไป
    $res = $lib->request('/me/videos', [
        'upload'  => ['approach' => 'tus', 'size' => $size],
        'name'    => $video_name,
        'privacy' => $privacy,
    ], 'POST');

    if ((int) ($res['status'] ?? 0) >= 400) {
        $msg = $res['body']['error'] ?? ('HTTP ' . ($res['status'] ?? '?'));
        Response::json(0, 'สร้างงานอัปโหลดบน Vimeo ไม่สำเร็จ: ' . $msg, null);
    }

    $body        = $res['body'] ?? [];
    $video_uri   = $body['uri'] ?? '';
    $upload_link = $body['upload']['upload_link'] ?? '';

    if ($video_uri === '' || $upload_link === '') {
        Response::json(0, 'Vimeo ไม่ส่งลิงก์อัปโหลดกลับมา', null);
    }

    // embed-only: เพิ่มโดเมนที่อนุญาตให้ฝัง (Vimeo จะเล่นเฉพาะเมื่อถูกฝังบนโดเมนเหล่านี้) — ล้มก็แค่ log
    if ($embed_domains && preg_match('#/videos/(\d+)#', $video_uri, $vm)) {
        foreach ($embed_domains as $d) {
            try {
                $lib->request('/videos/' . $vm[1] . '/privacy/domains/' . rawurlencode($d), [], 'PUT');
            } catch (Exception $e) {
                error_log('Vimeo add embed domain failed (' . $d . '): ' . $e->getMessage());
            }
        }
    }

    Response::json(1, 'พร้อมอัปโหลด', [
        'video_uri'   => $video_uri,
        'upload_link' => $upload_link,
    ]);
} catch (Exception $e) {
    error_log('Vimeo CreateUpload Error: ' . $e->getMessage());
    Response::json(0, 'สร้างงานอัปโหลดไม่สำเร็จ', null);
} finally {
    $pdo_connect = null;
}
