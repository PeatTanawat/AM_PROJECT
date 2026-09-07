<?php

use App\Database\Connection;
use App\Utility\Response;
use App\Utility\Auth;

// ตรวจสอบ JWT Token ของผู้ใช้ที่ล็อกอินอยู่
$currentUser = Auth::getUser();
if (!$currentUser || empty($currentUser->user_id)) {
    Response::json(0, 'กรุณาเข้าสู่ระบบก่อนดำเนินการ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// อัปเดต line_token ใน tbl_user ของตนเองให้เป็น NULL เพื่อยกเลิกการเชื่อมต่อ
$sql_update = "UPDATE tbl_user SET line_token = NULL, update_at = NOW() WHERE user_id = :user_id AND delete_at IS NULL";
$stmt_update = $pdo_connect->prepare($sql_update);
$result = $stmt_update->execute([
    ':user_id' => $currentUser->user_id
]);
$stmt_update->closeCursor();

if ($result) {
    Response::json(1, 'ยกเลิกการเชื่อมต่อบัญชี LINE สำเร็จเรียบร้อยแล้ว', null);
} else {
    Response::json(0, 'ไม่สามารถดำเนินการได้ กรุณาลองใหม่อีกครั้ง', null);
}
