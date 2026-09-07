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
    "SELECT lesson_id, course_id, lesson_order, lesson_name, lesson_video,
            lesson_overview, lesson_question, lesson_question_limit, lesson_question_time
     FROM tbl_lesson
     WHERE course_id = :cid AND delete_at IS NULL
     ORDER BY lesson_order ASC, lesson_id ASC"
);
$stmt->execute([':cid' => $course_id]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

Response::json(1, 'Success', ['list_data' => $list]);
