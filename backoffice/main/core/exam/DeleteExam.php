<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$exam_id = isset($_POST['exam_id']) ? (int) $_POST['exam_id'] : 0;
if ($exam_id <= 0) {
    Response::json(0, 'ไม่พบรหัสข้อสอบ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $pdo_connect->beginTransaction();

    $stmt = $pdo_connect->prepare("UPDATE tbl_exam SET delete_at = NOW() WHERE exam_id = :id AND delete_at IS NULL");
    $stmt->execute([':id' => $exam_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    $cs = $pdo_connect->prepare("UPDATE tbl_exam_choice SET delete_at = NOW() WHERE exam_id = :id AND delete_at IS NULL");
    $cs->execute([':id' => $exam_id]);
    $cs->closeCursor();

    $pdo_connect->commit();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบข้อสอบนี้ หรือถูกลบไปแล้ว', null);
    }
    Response::json(1, 'ลบข้อสอบสำเร็จ', ['exam_id' => $exam_id]);
} catch (Exception $e) {
    if ($pdo_connect->inTransaction()) { $pdo_connect->rollBack(); }
    error_log('Delete Exam Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
