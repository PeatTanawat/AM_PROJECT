<?php
// อัปโหลดไฟล์วิดีโอขึ้น Vimeo ผ่าน API แล้วเก็บลิงก์ embed ใน tbl_lesson.lesson_video
// ต้องตั้งค่า VIMEO_ACCESS_TOKEN ใน .env (scope: upload, edit, video_files)

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
if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}

if (empty($_FILES['video_file']['name']) || ($_FILES['video_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    // ไฟล์ใหญ่เกิน post_max_size/upload_max_filesize ของ PHP ก็จะมาตกตรงนี้
    Response::json(0, 'กรุณาเลือกไฟล์วิดีโอ (หรือไฟล์ใหญ่เกินค่าที่ PHP กำหนด)', null);
}

$ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
$allowExt = ['mp4', 'mov', 'avi', 'wmv', 'mkv', 'webm', 'm4v', 'mpg', 'mpeg'];
if (!in_array($ext, $allowExt, true)) {
    Response::json(0, 'รองรับเฉพาะไฟล์วิดีโอ (mp4, mov, avi, wmv, mkv, webm)', null);
}

// สร้าง Connection ก่อน เพื่อให้ Dotenv โหลดค่า .env เข้าสู่ $_ENV
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

    // upload() ทำ resumable (tus) upload และคืน video URI เช่น "/videos/123456789"
    $uri = $lib->upload($_FILES['video_file']['tmp_name'], [
        'name'    => 'lesson-' . $lesson_id,
        'privacy' => ['embed' => 'public', 'view' => 'anybody'],
    ]);

    if (!preg_match('#/videos/(\d+)#', (string)$uri, $m)) {
        Response::json(0, 'อัปโหลดสำเร็จแต่ไม่พบรหัสวิดีโอจาก Vimeo', null);
    }
    $video_id = $m[1];

    // วิดีโอใหม่ของ Vimeo ต้องใช้ embed URL ที่มี privacy hash (?h=...) ไม่งั้นขึ้น "This video does not exist"
    // ดึง player_embed_url ของจริงจาก API (มี hash อยู่แล้ว) แทนการประกอบ URL เอง
    $embed = 'https://player.vimeo.com/video/' . $video_id;
    try {
        $info = $lib->request('/videos/' . $video_id, ['fields' => 'player_embed_url'], 'GET');
        if (!empty($info['body']['player_embed_url'])) {
            $embed = $info['body']['player_embed_url'];
        }
    } catch (Exception $e) {
        error_log('Vimeo get embed url failed: ' . $e->getMessage());
    }

    $upd = $pdo_connect->prepare("UPDATE tbl_lesson SET lesson_video = :v WHERE lesson_id = :id AND delete_at IS NULL");
    $upd->execute([':v' => $embed, ':id' => $lesson_id]);
    $upd->closeCursor();

    Response::json(1, 'อัปโหลดวิดีโอขึ้น Vimeo สำเร็จ', ['lesson_video' => $embed, 'video_id' => $video_id]);
} catch (Exception $e) {
    error_log('Vimeo Upload Error: ' . $e->getMessage());
    Response::json(0, 'อัปโหลดขึ้น Vimeo ไม่สำเร็จ', null);
} finally {
    $pdo_connect = null;
}
