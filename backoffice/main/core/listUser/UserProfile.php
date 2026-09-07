<?php

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
$full_name = $access_token->fullname ?? '';
$is_super_admin = $access_token->is_super_admin ?? 0;
$role_name = ($is_super_admin == 1) ? 'Super Admin' : (($access_token->access_level ?? '') !== '' ? $access_token->access_level : 'ผู้ดูแลระบบ');
$avatar = $access_token->profile_image ?? '';

// access_menus = เมนูที่ผู้ใช้มีสิทธิ์ (กรอง sidebar)
// menu_map     = เมนูทั้งหมด + url_path (สร้าง page->menu สำหรับ guard ฝั่ง client)
$access_menus = [];
$menu_map = [];
try {
    $pdo = (new Connection())->getPdo();
    if ($pdo && $user_id) {
        if ($is_super_admin == 1) {
            // Super admin เห็นทุกเมนูที่ active โดยไม่ต้องเช็ค tbl_user_access
            $stmt_all = $pdo->query("SELECT menu_name FROM tbl_slidebar WHERE active_status = '1'");
            $access_menus = array_map(fn($r) => (string) $r['menu_name'], $stmt_all->fetchAll(PDO::FETCH_ASSOC));
            $stmt_all->closeCursor();
            
            if (!in_array('หน้าแรก', $access_menus)) {
                $access_menus[] = 'หน้าแรก';
            }
        } else {
            // ดึงสิทธิ์จาก tbl_user_access สำหรับผู้ใช้ปกติ
            $stmt = $pdo->prepare(
                "SELECT s.menu_name
                     FROM tbl_user_access ua
                     JOIN tbl_slidebar s ON s.menu_id = ua.menu_id
                     WHERE ua.user_id = :uid AND s.active_status = '1'"
            );
            $stmt->execute([':uid' => $user_id]);
            $access_menus = array_map(fn($r) => (string) $r['menu_name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
            $stmt->closeCursor();

            // ป้องกันกรณีมีข้อมูลสิทธิ์ "หน้าแรก" ตกค้างเดิมใน tbl_user_access
            $access_menus = array_values(array_filter($access_menus, fn($m) => $m !== 'หน้าแรก'));
        }

        $ms = $pdo->query("SELECT menu_name, url_path FROM tbl_slidebar WHERE active_status = '1'");
        $menu_map = array_map(fn($r) => [
            'menu_name' => (string) $r['menu_name'],
            'url_path' => (string) ($r['url_path'] ?? ''),
        ], $ms->fetchAll(PDO::FETCH_ASSOC));
        $ms->closeCursor();
    }
} catch (\Throwable $e) {
    $access_menus = [];
    $menu_map = [];
}

Response::json(1, 'Success', [
    'full_name' => $full_name,
    'role_name' => $role_name,
    'avatar' => $avatar,
    'access_menus' => $access_menus,
    'menu_map' => $menu_map,
    'is_super_admin' => (int) $is_super_admin,
]);
