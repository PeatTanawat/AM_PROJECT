<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
if ($course_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคอร์สเรียน', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // soft delete — ไม่ลบจริง
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_course SET delete_at = NOW() WHERE course_id = :id AND delete_at IS NULL"
    );
    $stmt->execute([':id' => $course_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบคอร์สเรียนนี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, 'ลบคอร์สเรียนสำเร็จ', ['course_id' => $course_id]);
} catch (Exception $e) {
    error_log('Delete Course Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
