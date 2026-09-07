<?php

use App\Database\Connection;
use App\Utility\Response;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

if (empty($token)) {
    Response::json(0, 'ไม่พบโทเค็นสำหรับการรีเซ็ตรหัสผ่าน', null);
}

if (empty($new_password) || empty($confirm_password)) {
    Response::json(0, 'กรุณากรอกรหัสผ่านใหม่และยืนยันรหัสผ่านใหม่', null);
}

if (strlen($new_password) < 6) {
    Response::json(0, 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร', null);
}

if ($new_password !== $confirm_password) {
    Response::json(0, 'การยืนยันรหัสผ่านไม่ตรงกัน', null);
}

try {
    // Check if token exists and not expired
    $sql_check = "SELECT user_id FROM tbl_user WHERE reset_token = :token AND reset_token_expire >= NOW() AND delete_at IS NULL LIMIT 1";
    $stmt_check = $pdo_connect->prepare($sql_check);
    $stmt_check->execute([':token' => $token]);
    $user = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $stmt_check->closeCursor();

    if (!$user) {
        Response::json(0, 'ลิงก์การรีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว กรุณาทำรายการลืมรหัสผ่านใหม่อีกครั้ง', null);
    }

    // Hash the password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update password and clear token
    $sql_update = "UPDATE tbl_user SET user_password = :password, reset_token = NULL, reset_token_expire = NULL WHERE user_id = :user_id";
    $stmt_update = $pdo_connect->prepare($sql_update);
    $update_result = $stmt_update->execute([
        ':password' => $hashed_password,
        ':user_id' => $user['user_id']
    ]);
    $stmt_update->closeCursor();

    if ($update_result) {
        Response::json(1, 'รีเซ็ตรหัสผ่านของคุณเรียบร้อยแล้ว', null);
    } else {
        Response::json(0, 'ไม่สามารถบันทึกรหัสผ่านใหม่ได้ กรุณาลองใหม่อีกครั้งภายหลัง', null);
    }

} catch (\Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการประมวลผลคำขอ: ' . $e->getMessage(), null);
}
