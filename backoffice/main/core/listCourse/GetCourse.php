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

// คอร์ส (เฉพาะที่ยังไม่ถูกลบ)
$stmt = $pdo_connect->prepare(
    "SELECT * FROM tbl_course WHERE course_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$course) {
    Response::json(0, 'ไม่พบคอร์สเรียนนี้', null);
}

// หมวดหมู่ (group)
$stmt_group = $pdo_connect->prepare(
    "SELECT group_id, group_name FROM tbl_course_group
     WHERE delete_at IS NULL ORDER BY group_id DESC"
);
$stmt_group->execute();
$groups = $stmt_group->fetchAll(PDO::FETCH_ASSOC);
$stmt_group->closeCursor();

// ประเภท (type) — ให้ "ทั่วไป" ขึ้นก่อนเสมอ
$stmt_type = $pdo_connect->prepare(
    "SELECT type_id, type_name FROM tbl_course_type
     WHERE delete_at IS NULL
     ORDER BY (type_name = 'ทั่วไป') DESC, type_id ASC"
);
$stmt_type->execute();
$types = $stmt_type->fetchAll(PDO::FETCH_ASSOC);
$stmt_type->closeCursor();

Response::json(1, 'Success', [
    'course' => $course,
    'groups' => $groups,
    'types'  => $types,
]);
