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
    'groups' => $groups,
    'types'  => $types,
]);
