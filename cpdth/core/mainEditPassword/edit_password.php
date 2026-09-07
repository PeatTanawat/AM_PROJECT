<?php

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($new_password) || empty($confirm_password)) {
        Response::json(0, 'กรุณากรอกข้อมูลให้ครบถ้วน', null);
    }

    if (strlen($new_password) < 6) {
        Response::json(0, 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร', null);
    }

    if ($new_password !== $confirm_password) {
        Response::json(0, 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }


    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $update_stmt = $pdo_connect->prepare("UPDATE tbl_user SET user_password = :user_password, update_at = NOW() WHERE user_id = :user_id");
    $success = $update_stmt->execute([
        ':user_password' => $hashed_password,
        ':user_id' => $user_id
    ]);

    if ($success) {
        Response::json(1, 'อัปเดตรหัสผ่านสำเร็จ', null);
    } else {
        Response::json(0, 'ไม่สามารถอัปเดตรหัสผ่านได้ กรุณาลองใหม่อีกครั้ง', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
