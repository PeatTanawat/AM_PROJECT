<?php
// ดึงจุดที่ดูค้างไว้ของบทเรียน (สำหรับเล่นต่อ) จาก tbl_lesson_progress

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

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

$stmt = $pdo_connect->prepare(
    "SELECT progress_last_sec, progress_status
     FROM tbl_lesson_progress
     WHERE progress_user_id = :u AND progress_lesson_id = :l
     LIMIT 1"
);
$stmt->execute([':u' => $user_id, ':l' => $lesson_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

Response::json(1, 'Success', [
    'last_sec' => $row ? (int) $row['progress_last_sec'] : 0,
    'status'   => $row ? (string) $row['progress_status'] : '0',
]);
