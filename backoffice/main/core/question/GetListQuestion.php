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

// คำถามของบทเรียนนี้
$stmt = $pdo_connect->prepare(
    "SELECT question_id, question_text, question_image, question_file
     FROM tbl_question
     WHERE lesson_id = :lid AND delete_at IS NULL
     ORDER BY question_id ASC"
);
$stmt->execute([':lid' => $lesson_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// ดึงตัวเลือกของทุกคำถามในครั้งเดียว แล้วจัดกลุ่มตาม question_id (กัน N+1)
$choicesByQuestionId = [];
$question_ids = array_column($questions, 'question_id');
if ($question_ids) {
    $placeholders = [];
    $params = [];
    foreach ($question_ids as $i => $qid) {
        $ph = ':id' . $i;
        $placeholders[] = $ph;
        $params[$ph] = $qid;
    }
    $cs = $pdo_connect->prepare(
        "SELECT question_id, question_choice_correct FROM tbl_question_choice
         WHERE question_id IN (" . implode(',', $placeholders) . ") AND delete_at IS NULL
         ORDER BY question_id ASC, question_choice_id ASC"
    );
    $cs->execute($params);
    foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $choicesByQuestionId[$row['question_id']][] = $row['question_choice_correct'];
    }
    $cs->closeCursor();
}

// หาลำดับ (1-based) ของตัวเลือกที่เป็นคำตอบถูกของแต่ละคำถาม
foreach ($questions as &$q) {
    $choices = $choicesByQuestionId[$q['question_id']] ?? [];

    $correct = 0;
    foreach ($choices as $i => $c) {
        if ((string)$c === '1') { $correct = $i + 1; break; }
    }
    $q['correct_index'] = $correct;     // 0 = ยังไม่ได้กำหนด
    $q['choice_count']  = count($choices);
}
unset($q);

Response::json(1, 'Success', ['list_data' => $questions]);
