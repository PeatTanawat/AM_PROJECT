<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$file_id = isset($_POST['lesson_file_id']) ? (int) $_POST['lesson_file_id'] : 0;
if ($file_id <= 0) {
    Response::json(0, 'ไม่พบรหัสเอกสาร', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // soft delete (เก็บไฟล์จริงไว้บนดิสก์)
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_lesson_file SET delete_at = NOW() WHERE lesson_file_id = :id AND delete_at IS NULL"
    );
    $stmt->execute([':id' => $file_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบเอกสารนี้ หรือถูกลบไปแล้ว', null);
    }
    Response::json(1, 'ลบเอกสารสำเร็จ', ['lesson_file_id' => $file_id]);
} catch (Exception $e) {
    error_log('Delete Lesson File Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
