<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$question_id = isset($_POST['question_id']) ? (int) $_POST['question_id'] : 0;
if ($question_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำถาม', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $pdo_connect->beginTransaction();

    $stmt = $pdo_connect->prepare("UPDATE tbl_question SET delete_at = NOW() WHERE question_id = :id AND delete_at IS NULL");
    $stmt->execute([':id' => $question_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    $cs = $pdo_connect->prepare("UPDATE tbl_question_choice SET delete_at = NOW() WHERE question_id = :id AND delete_at IS NULL");
    $cs->execute([':id' => $question_id]);
    $cs->closeCursor();

    $pdo_connect->commit();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบคำถามนี้ หรือถูกลบไปแล้ว', null);
    }
    Response::json(1, 'ลบคำถามสำเร็จ', ['question_id' => $question_id]);
} catch (Exception $e) {
    if ($pdo_connect->inTransaction()) { $pdo_connect->rollBack(); }
    error_log('Delete Question Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
