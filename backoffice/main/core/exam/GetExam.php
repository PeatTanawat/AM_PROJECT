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

$stmt = $pdo_connect->prepare(
    "SELECT * FROM tbl_exam WHERE exam_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$exam) {
    Response::json(0, 'ไม่พบข้อสอบนี้', null);
}

$cs = $pdo_connect->prepare(
    "SELECT exam_choice_id, exam_choice_text, exam_choice_correct
     FROM tbl_exam_choice
     WHERE exam_id = :eid AND delete_at IS NULL
     ORDER BY exam_choice_id ASC"
);
$cs->execute([':eid' => $exam_id]);
$choices = $cs->fetchAll(PDO::FETCH_ASSOC);
$cs->closeCursor();

Response::json(1, 'Success', ['exam' => $exam, 'choices' => $choices]);
