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

$lineToken = isset($_POST['line_token']) ? trim($_POST['line_token']) : '';

if (empty($lineToken)) {
    Response::json(0, 'ไม่พบข้อมูล LINE Token', null);
}

// ตรวจสอบว่า line_token นี้ถูกใช้งานโดยบัญชีอื่นไปแล้วหรือยัง
$sql_check = "SELECT user_id FROM tbl_user WHERE line_token = :line_token AND user_id != :user_id AND delete_at IS NULL LIMIT 1";
$stmt_check = $pdo_connect->prepare($sql_check);
$stmt_check->execute([
    ':line_token' => $lineToken,
    ':user_id' => $currentUser->user_id
]);
if ($stmt_check->fetch()) {
    $stmt_check->closeCursor();
    Response::json(0, 'บัญชี LINE นี้ถูกเชื่อมต่อกับบัญชีอื่นในระบบแล้ว', null);
}
$stmt_check->closeCursor();

// อัปเดต line_token ใน tbl_user ของตนเอง
$sql_update = "UPDATE tbl_user SET line_token = :line_token, update_at = NOW() WHERE user_id = :user_id AND delete_at IS NULL";
$stmt_update = $pdo_connect->prepare($sql_update);
$result = $stmt_update->execute([
    ':line_token' => $lineToken,
    ':user_id' => $currentUser->user_id
]);
$stmt_update->closeCursor();

if ($result) {
    Response::json(1, 'เชื่อมต่อบัญชี LINE สำเร็จเรียบร้อยแล้ว', null);
} else {
    Response::json(0, 'ไม่สามารถบันทึกข้อมูลการเชื่อมต่อ LINE ได้ กรุณาลองใหม่อีกครั้ง', null);
}
