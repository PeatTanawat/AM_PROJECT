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

$stmt = $pdo_connect->prepare(
    "SELECT exam_id, exam_text, exam_image, exam_file
     FROM tbl_exam
     WHERE course_id = :cid AND delete_at IS NULL
     ORDER BY exam_id ASC"
);
$stmt->execute([':cid' => $course_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// ดึงตัวเลือกของทุกข้อสอบในครั้งเดียว แล้วจัดกลุ่มตาม exam_id (กัน N+1)
$choicesByExamId = [];
$exam_ids = array_column($exams, 'exam_id');
if ($exam_ids) {
    $placeholders = [];
    $params = [];
    foreach ($exam_ids as $i => $eid) {
        $ph = ':id' . $i;
        $placeholders[] = $ph;
        $params[$ph] = $eid;
    }
    $cs = $pdo_connect->prepare(
        "SELECT exam_id, exam_choice_text, exam_choice_correct FROM tbl_exam_choice
         WHERE exam_id IN (" . implode(',', $placeholders) . ") AND delete_at IS NULL
         ORDER BY exam_id ASC, exam_choice_id ASC"
    );
    $cs->execute($params);
    foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $choicesByExamId[$row['exam_id']][] = [
            'exam_choice_text'    => $row['exam_choice_text'],
            'exam_choice_correct' => $row['exam_choice_correct'],
        ];
    }
    $cs->closeCursor();
}

foreach ($exams as &$e) {
    $choices = $choicesByExamId[$e['exam_id']] ?? [];

    $correct = 0;
    $correct_text = '';
    foreach ($choices as $i => $c) {
        if ((string)$c['exam_choice_correct'] === '1') {
            $correct = $i + 1;
            $correct_text = (string)$c['exam_choice_text'];
            break;
        }
    }
    $e['correct_index'] = $correct;
    $e['correct_text']  = $correct_text;   // ข้อความของคำตอบที่ถูก (โชว์ในตาราง)
    $e['choice_count']  = count($choices);
}
unset($e);

Response::json(1, 'Success', ['list_data' => $exams]);
