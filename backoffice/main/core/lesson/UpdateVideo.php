<?php
// อัปเดตวิดีโอของบทเรียน (แท็บ "วีดีโอ") — เก็บเป็นลิงก์/embed URL ใน lesson_video

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$lesson_id    = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
$lesson_video = isset($_POST['lesson_video']) ? trim($_POST['lesson_video']) : '';

if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_lesson SET lesson_video = :video WHERE lesson_id = :id AND delete_at IS NULL"
    );
    $stmt->bindValue(':video', $lesson_video === '' ? null : $lesson_video);
    $stmt->bindValue(':id', $lesson_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'บันทึกวิดีโอสำเร็จ', ['lesson_id' => $lesson_id]);
} catch (Exception $e) {
    error_log('Update Video Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
