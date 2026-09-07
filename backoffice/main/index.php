<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\Connection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt = $_COOKIE['bo_access_token'] ?? $_COOKIE['access_token'] ?? '';

if (empty($jwt)) {
    header("Location: logout");
    exit();
}

static $envLoaded = false;
if (!$envLoaded) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    $envLoaded = true;
}

$secretKey = $_ENV['JWT_SECRET'] ?? '';

try {
    $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
    $jti = $decoded->jti ?? '';
    
    if (empty($jti)) {
        throw new Exception("Invalid token");
    }

    $db = (new Connection())->getPdo();
    if (!$db) {
        throw new Exception("DB connection failed");
    }
    
    $sql = "SELECT u.user_id, u.admin_status, u.is_super_admin
            FROM tbl_login_token lt
            JOIN tbl_user u ON lt.user_id = u.user_id
            WHERE lt.token_code = :token_code AND u.user_status = 1 AND lt.end_datetime IS NULL AND lt.expire_datetime > NOW()
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':token_code' => $jti]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    if (!$user) {
        throw new Exception("User not found or inactive");
    }
    
    $user_id = $user['user_id'];
    $admin_status = (int)($user['admin_status'] ?? 0);
    $is_super_admin = (int)($user['is_super_admin'] ?? 0);
    
    file_put_contents(dirname(__DIR__) . '/debug_redirect.log', "[" . date('Y-m-d H:i:s') . "] UserID: $user_id, admin_status: $admin_status, is_super_admin: $is_super_admin\n", FILE_APPEND);

    if ($admin_status === 1 && $is_super_admin === 0) {
        $sql_menu = "SELECT s.menu_id, s.menu_name, s.url_path
                     FROM tbl_user_access ua
                     JOIN tbl_slidebar s ON s.menu_id = ua.menu_id
                     WHERE ua.user_id = :uid AND s.active_status = '1' AND s.menu_name <> 'หน้าแรก'
                     ORDER BY s.menu_id ASC
                     LIMIT 1";
        $stmt_menu = $db->prepare($sql_menu);
        $stmt_menu->execute([':uid' => $user_id]);
        $menu = $stmt_menu->fetch(PDO::FETCH_ASSOC);
        $stmt_menu->closeCursor();
        
        file_put_contents(dirname(__DIR__) . '/debug_redirect.log', "[" . date('Y-m-d H:i:s') . "] Menu: " . json_encode($menu) . "\n", FILE_APPEND);

        if ($menu) {
            $name_to_url_map = [
                'หน้าแรก' => 'home',
                'คอร์สเรียน' => 'course',
                'คอร์สเรียนคงเหลือ' => 'course_remaining',
                'คำสั่งซื้อคอร์สเรียน' => 'order',
                'ตอบคำถามผู้เรียน' => 'chat',
                'คำสั่งซื้อรอยืนยัน' => 'order_pending',
                'ใบรับรองผลการสอบ' => 'course_certificate',
                'ใบกำกับภาษี (E-Tax)' => 'etax',
                'ลิงก์ออกใบกำกับภาษี' => 'etax_link',
                'ผู้ใช้/ลูกค้า' => 'user',
                'ประวัติการยืนยันตัวตน' => 'verify_history',
                'ยืนยันตัวตนผู้ใช้งาน' => 'verify_request',
                'รายงาน/เอกสาร' => 'report',
                'คูปองส่วนลด' => 'coupon',
                'ตั้งค่าเว็บไซต์' => 'website_setting',
                'ตั้งค่าธนาคาร' => 'bank_setting',
                'ตั้งค่าที่อยู่' => 'address_setting',
                'รีวิวจากลูกค้า' => 'reviews',
                'แบนเนอร์' => 'banner',
                'ผู้ดูแลระบบ' => 'admin',
            ];

            $url_path = !empty($menu['url_path']) ? $menu['url_path'] : ($name_to_url_map[$menu['menu_name']] ?? '');
            if (strpos($url_path, ',') !== false) {
                $parts = explode(',', $url_path);
                $url_path = trim($parts[0]);
            }
            $target = trim(str_replace('.php', '', $url_path));
            file_put_contents(dirname(__DIR__) . '/debug_redirect.log', "[" . date('Y-m-d H:i:s') . "] Target: $target\n", FILE_APPEND);
            if ($target !== '') {
                header("Location: " . $target);
                exit();
            }
        }
    }
} catch (\Throwable $e) {
    file_put_contents(dirname(__DIR__) . '/debug_redirect.log', "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n", FILE_APPEND);
    header("Location: logout");
    exit();
}

header("Location: course");
exit();
?>