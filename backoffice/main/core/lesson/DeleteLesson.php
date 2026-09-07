<?php

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

try {
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_lesson SET delete_at = NOW() WHERE lesson_id = :id AND delete_at IS NULL"
    );
    $stmt->execute([':id' => $lesson_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบบทเรียนนี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, 'ลบบทเรียนสำเร็จ', ['lesson_id' => $lesson_id]);
} catch (Exception $e) {
    error_log('Delete Lesson Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
