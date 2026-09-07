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
$target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($target_id <= 0) {
    Response::json(0, 'ไม่พบรหัสผู้ดูแลระบบ', null);
}

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

$change_password = false;
if ($password !== null || $password_confirm !== null) {
    if ($password !== $password_confirm) {
        Response::json(0, 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน', null);
    }
    $change_password = true;
}

// ต้องเป็นผู้ดูแลระบบที่มีอยู่จริง
$check = $pdo_connect->prepare("SELECT user_id FROM tbl_user WHERE user_id = :id AND admin_status = '1' AND delete_at IS NULL LIMIT 1");
$check->execute([':id' => $target_id]);
$exists = $check->fetchColumn();
$check->closeCursor();
if (!$exists) {
    Response::json(0, 'ไม่พบผู้ดูแลระบบนี้ หรือถูกลบไปแล้ว', null);
}

// อีเมลห้ามซ้ำกับคนอื่น
$dup = $pdo_connect->prepare("SELECT user_id FROM tbl_user WHERE user_email = :email AND user_id <> :id AND delete_at IS NULL LIMIT 1");
$dup->execute([':email' => $email, ':id' => $target_id]);
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
    $sql = "UPDATE tbl_user SET
                user_firstname = :firstname,
                user_lastname  = :lastname,
                user_email     = :email";
    $params = [
        ':firstname' => $firstname,
        ':lastname'  => $lastname,
        ':email'     => $email,
        ':id'        => $target_id,
    ];

    if ($change_password) {
        $sql .= ", user_password = :password";
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE user_id = :id AND admin_status = '1' AND delete_at IS NULL";

    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    // บันทึกสิทธิ์เมนู: ล้างของเดิม แล้วใส่ตามที่ติ๊ก
    $menu_ids = (isset($_POST['menu_ids']) && is_array($_POST['menu_ids'])) ? array_unique(array_map('intval', $_POST['menu_ids'])) : [];
    $pdo_connect->prepare("DELETE FROM tbl_user_access WHERE user_id = :id")->execute([':id' => $target_id]);
    if ($menu_ids) {
        $insA = $pdo_connect->prepare("INSERT INTO tbl_user_access (user_id, menu_id) VALUES (:u, :m)");
        foreach ($menu_ids as $mid) {
            if ($mid > 0) { $insA->execute([':u' => $target_id, ':m' => $mid]); }
        }
    }

    Response::json(1, 'บันทึกข้อมูลสำเร็จ', ['user_id' => $target_id]);
} catch (Exception $e) {
    error_log('Update Admin Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
