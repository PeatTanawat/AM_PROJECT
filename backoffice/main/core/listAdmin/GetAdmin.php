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

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT user_id, user_firstname, user_lastname, user_email
     FROM tbl_user
     WHERE user_id = :id AND admin_status = '1' AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $target_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$row) {
    Response::json(0, 'ไม่พบผู้ดูแลระบบนี้', null);
}

$admin = [
    'user_id'    => $row['user_id'],
    'full_name'  => trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? '')),
    'user_email' => $row['user_email'],
];

// เมนูทั้งหมด (สำหรับ checkbox สิทธิ์)
$ms = $pdo_connect->query("SELECT menu_id, menu_name FROM tbl_slidebar WHERE active_status = '1' AND menu_name <> 'หน้าแรก' ORDER BY sort_order ASC, menu_id ASC");
$menus = array_map(fn($r) => ['menu_id' => (int) $r['menu_id'], 'menu_name' => (string) $r['menu_name']], $ms->fetchAll(PDO::FETCH_ASSOC));
$ms->closeCursor();

// เมนูที่ admin คนนี้เข้าถึงได้
$ac = $pdo_connect->prepare("SELECT menu_id FROM tbl_user_access WHERE user_id = :id");
$ac->execute([':id' => $target_id]);
$access = array_map('intval', $ac->fetchAll(PDO::FETCH_COLUMN));
$ac->closeCursor();

Response::json(1, 'Success', ['admin' => $admin, 'menus' => $menus, 'access' => $access]);
