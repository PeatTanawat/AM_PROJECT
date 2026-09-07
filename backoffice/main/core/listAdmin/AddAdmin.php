<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
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

/* ---------- validate ---------- */
$fullname         = $str('admin_name');
$email            = $str('user_email');
$password         = $str('user_password');
$password_confirm = $str('user_password_confirm');

if ($fullname === null) {
    Response::json(0, 'กรุณากรอกชื่อ-นามสกุล', null);
}
if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::json(0, 'กรุณากรอกอีเมลให้ถูกต้อง', null);
}
if ($password === null) {
    Response::json(0, 'กรุณากรอกรหัสผ่าน', null);
}
if ($password !== $password_confirm) {
    Response::json(0, 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน', null);
}

// อีเมลห้ามซ้ำ
$dup = $pdo_connect->prepare("SELECT user_id FROM tbl_user WHERE user_email = :email AND delete_at IS NULL LIMIT 1");
$dup->execute([':email' => $email]);
$dup_id = $dup->fetchColumn();
$dup->closeCursor();
if ($dup_id) {
    Response::json(0, 'อีเมลนี้ถูกใช้งานแล้ว', null);
}

// แยกชื่อ-นามสกุล จากช่องเดียว (ตัดที่ช่องว่างแรก)
$parts     = preg_split('/\s+/', $fullname, 2);
$firstname = $parts[0];
$lastname  = $parts[1] ?? null;

try {
    $sql = "INSERT INTO tbl_user (user_firstname, user_lastname, user_email, user_password, user_status, admin_status)
            VALUES (:firstname, :lastname, :email, :password, 1, '1')";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute([
        ':firstname' => $firstname,
        ':lastname'  => $lastname,
        ':email'     => $email,
        ':password'  => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $new_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    // บันทึกสิทธิ์เมนูตามที่ติ๊ก (ค่าเริ่มต้นจากหน้าเพิ่ม: ทุกเมนูยกเว้น "ผู้ดูแลระบบ")
    $menu_ids = (isset($_POST['menu_ids']) && is_array($_POST['menu_ids'])) ? array_unique(array_map('intval', $_POST['menu_ids'])) : [];
    if ($menu_ids) {
        $insA = $pdo_connect->prepare("INSERT INTO tbl_user_access (user_id, menu_id) VALUES (:u, :m)");
        foreach ($menu_ids as $mid) {
            if ($mid > 0) { $insA->execute([':u' => $new_id, ':m' => $mid]); }
        }
    }

    Response::json(1, 'เพิ่มผู้ดูแลระบบสำเร็จ', ['user_id' => $new_id]);
} catch (Exception $e) {
    error_log('Add Admin Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
