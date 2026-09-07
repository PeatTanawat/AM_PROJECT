<?php
// อัปเดตข้อมูลทั่วไปของบทเรียน (แท็บ "ทั่วไป") — ไม่ยุ่งกับ lesson_video (ดู UpdateVideo)

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

$lesson_id   = $int('lesson_id');
$lesson_name = $str('lesson_name');

if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}
if ($lesson_name === null) {
    Response::json(0, 'กรุณากรอกชื่อบทเรียน', null);
}

try {
    $fields = [
        'lesson_order'          => $int('lesson_order'),
        'lesson_name'           => $lesson_name,
        'lesson_overview'       => $str('lesson_overview'),
        'lesson_question'       => ($_POST['lesson_question'] ?? '0') === '1' ? '1' : '0',
        'lesson_question_limit' => $int('lesson_question_limit'),
        'lesson_question_time'  => $int('lesson_question_time'),
    ];

    $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($fields)));
    $sql = "UPDATE tbl_lesson SET $set WHERE lesson_id = :lesson_id AND delete_at IS NULL";

    $stmt = $pdo_connect->prepare($sql);
    foreach ($fields as $col => $val) {
        $stmt->bindValue(':' . $col, $val);
    }
    $stmt->bindValue(':lesson_id', $lesson_id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'บันทึกการแก้ไขบทเรียนสำเร็จ', ['lesson_id' => $lesson_id]);
} catch (Exception $e) {
    error_log('Update Lesson Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
