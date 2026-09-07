<?php
// เรียกหลังเบราว์เซอร์อัปไฟล์ขึ้น Vimeo (tus) เสร็จ -> บันทึก embed URL (public, ไม่มี hash) ลง tbl_lesson
// ถ้าเป็นการอัปแทนที่วิดีโอเดิม -> ลบวิดีโอเก่าบน Vimeo ทิ้งด้วย (กันไฟล์กำพร้าค้างบน Vimeo)

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
$video_uri = isset($_POST['video_uri']) ? trim((string) $_POST['video_uri']) : '';
if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}
if (!preg_match('#/videos/(\d+)#', $video_uri, $m)) {
    Response::json(0, 'รหัสวิดีโอไม่ถูกต้อง', null);
}
$video_id = $m[1];

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$token = trim($_ENV['VIMEO_ACCESS_TOKEN'] ?? '');

// ลิงก์วิดีโอเดิมของบทเรียนนี้ (ถ้ามี) — ไว้ลบวิดีโอเก่าบน Vimeo หลังบันทึกตัวใหม่สำเร็จ
$old_stmt = $pdo_connect->prepare("SELECT lesson_video FROM tbl_lesson WHERE lesson_id = :id AND delete_at IS NULL LIMIT 1");
$old_stmt->execute([':id' => $lesson_id]);
$old_video = (string) $old_stmt->fetchColumn();
$old_stmt->closeCursor();

// วิดีโอเป็น public (view=anybody) ตั้งแต่ CreateUpload -> ฝัง URL พื้นฐาน "ไม่มี ?h=hash"
// เพราะ hash แบบ unlisted จะทำให้วิดีโอ public ขึ้น "This video does not exist" (ยืนยันจาก Vimeo oEmbed)
$embed = 'https://player.vimeo.com/video/' . $video_id;

try {
    $upd = $pdo_connect->prepare("UPDATE tbl_lesson SET lesson_video = :v WHERE lesson_id = :id AND delete_at IS NULL");
    $upd->execute([':v' => $embed, ':id' => $lesson_id]);
    $upd->closeCursor();

    // ลบวิดีโอเก่าบน Vimeo (เฉพาะกรณีอัปแทนที่ และเป็นคนละตัวกับวิดีโอใหม่) — ล้มก็แค่ log ไม่ทำให้ทั้ง request พัง
    if ($old_video !== '' && preg_match('#/video/(\d+)#', $old_video, $om) && $om[1] !== $video_id) {
        try {
            $lib = new Vimeo($_ENV['VIMEO_CLIENT_ID'] ?? '', $_ENV['VIMEO_CLIENT_SECRET'] ?? '', $token);
            $lib->request('/videos/' . $om[1], [], 'DELETE');
        } catch (Exception $e) {
            error_log('Vimeo delete old video failed (video ' . $om[1] . '): ' . $e->getMessage());
        }
    }

    Response::json(1, 'อัปโหลดวิดีโอขึ้น Vimeo สำเร็จ', ['lesson_video' => $embed, 'video_id' => $video_id]);
} catch (Exception $e) {
    error_log('FinishUpload DB Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
