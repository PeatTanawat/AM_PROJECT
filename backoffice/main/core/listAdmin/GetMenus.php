<?php
// คืนรายการเมนูทั้งหมด (tbl_slidebar) สำหรับ checkbox สิทธิ์การใช้งาน

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

$stmt = $pdo_connect->query(
    "SELECT menu_id, menu_name FROM tbl_slidebar WHERE active_status = '1' AND menu_name <> 'หน้าแรก' ORDER BY sort_order ASC, menu_id ASC"
);
$menus = array_map(fn($r) => [
    'menu_id'   => (int) $r['menu_id'],
    'menu_name' => (string) $r['menu_name'],
], $stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt->closeCursor();

Response::json(1, 'Success', ['menus' => $menus]);
