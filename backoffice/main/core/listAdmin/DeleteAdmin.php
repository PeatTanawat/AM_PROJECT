<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($target_id <= 0) {
    Response::json(0, 'ไม่พบรหัสผู้ดูแลระบบ', null);
}

// ห้ามลบบัญชีของตนเอง
if ($target_id === (int) $admin_id) {
    Response::json(0, 'ไม่สามารถลบบัญชีของตนเองได้', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// soft delete เฉพาะผู้ดูแลระบบ (ปรับ user_status = 0 ร่วมด้วย)
$stmt = $pdo_connect->prepare(
    "UPDATE tbl_user SET delete_at = NOW(), user_status = 0
     WHERE user_id = :id AND admin_status = '1' AND delete_at IS NULL"
);
$stmt->execute([':id' => $target_id]);
$affected = $stmt->rowCount();
$stmt->closeCursor();

if ($affected < 1) {
    Response::json(0, 'ไม่พบผู้ดูแลระบบนี้ หรือถูกลบไปแล้ว', null);
}

Response::json(1, 'ลบผู้ดูแลระบบสำเร็จ', null);
