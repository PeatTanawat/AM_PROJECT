<?php
// เช็คสถานะการประมวลผล (transcode) ของวิดีโอบทเรียนบน Vimeo
// ใช้หลังอัปโหลด: หน้า lesson_manage จะ poll จนกว่า is_playable = true ค่อยฝังวิดีโอ

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

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare("SELECT lesson_video FROM tbl_lesson WHERE lesson_id = :id AND delete_at IS NULL LIMIT 1");
$stmt->execute([':id' => $lesson_id]);
$video = (string) $stmt->fetchColumn();
$stmt->closeCursor();

if ($video === '') {
    Response::json(0, 'บทเรียนนี้ยังไม่มีวิดีโอ', null);
}

// กรณีเป็นไฟล์ Local (อยู่ในโฟลเดอร์ upload/)
if (stripos($video, 'upload/') !== false || !preg_match('#(?:player\.)?vimeo\.com#i', $video)) {
    Response::json(1, 'Success', [
        'status'      => 'available',
        'is_playable' => true,
        'transcode'   => 'complete',
        'is_local'    => true,
        'width'       => 16,
        'height'      => 9,
    ]);
}

if (!preg_match('#/video/(\d+)#', $video, $m)) {
    Response::json(0, 'รูปแบบวิดีโอไม่ถูกต้อง', null);
}
$video_id = $m[1];

$token = trim($_ENV['VIMEO_ACCESS_TOKEN'] ?? '');

try {
    $lib  = new Vimeo($_ENV['VIMEO_CLIENT_ID'] ?? '', $_ENV['VIMEO_CLIENT_SECRET'] ?? '', $token);
    $info = $lib->request('/videos/' . $video_id, ['fields' => 'status,is_playable,transcode.status,width,height'], 'GET');
    $http = (int) ($info['status'] ?? 0);
    $b = $info['body'] ?? [];

    // วิดีโอถูกลบ/ไม่พบบน Vimeo (404) -> บอก frontend ให้หยุด poll แล้วโชว์ว่า "ไม่พบวิดีโอ"
    // (ไม่งั้น poll จะเข้าใจว่ายัง transcode ไม่เสร็จ แล้ววนไม่จบ)
    if ($http === 404) {
        Response::json(1, 'ไม่พบวิดีโอ (อาจถูกลบจาก Vimeo)', [
            'status'      => 'not_found',
            'is_playable' => false,
            'missing'     => true,
        ]);
    }

    Response::json(1, 'Success', [
        'status'      => $b['status'] ?? 'unknown',
        'is_playable' => !empty($b['is_playable']),
        'transcode'   => $b['transcode']['status'] ?? 'unknown',
        'width'       => (int) ($b['width'] ?? 0),   // ขนาดจริงของวิดีโอ -> ใช้ตั้งอัตราส่วนกรอบ (กันแถบดำ)
        'height'      => (int) ($b['height'] ?? 0),
    ]);
} catch (Exception $e) {
    error_log('GetVideoStatus Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
