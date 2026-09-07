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

$stmt = $pdo_connect->prepare(
    "SELECT * FROM tbl_question WHERE question_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$question) {
    Response::json(0, 'ไม่พบคำถามนี้', null);
}

$cs = $pdo_connect->prepare(
    "SELECT question_choice_id, question_choice_text, question_choice_correct
     FROM tbl_question_choice
     WHERE question_id = :qid AND delete_at IS NULL
     ORDER BY question_choice_id ASC"
);
$cs->execute([':qid' => $question_id]);
$choices = $cs->fetchAll(PDO::FETCH_ASSOC);
$cs->closeCursor();

Response::json(1, 'Success', ['question' => $question, 'choices' => $choices]);
