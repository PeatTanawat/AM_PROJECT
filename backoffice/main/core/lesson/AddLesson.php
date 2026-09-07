<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$str = function (string $key): ?string {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? null : $v;
};
$int = function (string $key): int {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? 0 : (int) $v;
};

$course_id   = $int('course_id');
$lesson_name = $str('lesson_name');

if ($course_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคอร์สเรียน', null);
}
if ($lesson_name === null) {
    Response::json(0, 'กรุณากรอกชื่อบทเรียน', null);
}

try {
    $fields = [
        'course_id'             => $course_id,
        'lesson_order'          => $int('lesson_order'),
        'lesson_name'           => $lesson_name,
        'lesson_overview'       => $str('lesson_overview'),
        'lesson_question'       => ($_POST['lesson_question'] ?? '0') === '1' ? '1' : '0',
        'lesson_question_limit' => $int('lesson_question_limit'),
        'lesson_question_time'  => $int('lesson_question_time'),
    ];

    $columns      = array_keys($fields);
    $placeholders = array_map(fn($c) => ':' . $c, $columns);
    $sql = "INSERT INTO tbl_lesson (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = $pdo_connect->prepare($sql);
    foreach ($fields as $col => $val) {
        $stmt->bindValue(':' . $col, $val);
    }
    $stmt->execute();
    $lesson_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    $newKey = \App\Utility\Cipher::encrypt($lesson_id);
    Response::json(1, 'เพิ่มบทเรียนสำเร็จ', ['lesson_id' => $lesson_id, 'newKey' => $newKey]);
} catch (Exception $e) {
    error_log('Add Lesson Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
