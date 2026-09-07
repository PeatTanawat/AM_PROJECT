<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\Connection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: text/plain; charset=utf-8');

echo "--- COOKIES ---\n";
print_r($_COOKIE);

$jwt = $_COOKIE['bo_access_token'] ?? $_COOKIE['access_token'] ?? '';

if (empty($jwt)) {
    echo "No JWT token found in cookies.\n";
    exit();
}

static $envLoaded = false;
if (!$envLoaded) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    $envLoaded = true;
}

$secretKey = $_ENV['JWT_SECRET'] ?? '';
echo "\nJWT Secret Key length: " . strlen($secretKey) . "\n";

try {
    $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
    echo "\n--- DECODED JWT ---\n";
    print_r($decoded);
    
    $jti = $decoded->jti ?? '';
    
    $db = (new Connection())->getPdo();
    if (!$db) {
        throw new Exception("DB connection failed");
    }
    
    $sql = "SELECT u.user_id, u.admin_status, u.is_super_admin, u.user_email
            FROM tbl_login_token lt
            JOIN tbl_user u ON lt.user_id = u.user_id
            WHERE lt.token_code = :token_code AND u.user_status = 1 AND lt.end_datetime IS NULL AND lt.expire_datetime > NOW()
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':token_code' => $jti]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    echo "\n--- DB USER RECORD ---\n";
    print_r($user);
    
    if ($user) {
        $user_id = $user['user_id'];
        $admin_status = (int)($user['admin_status'] ?? 0);
        $is_super_admin = (int)($user['is_super_admin'] ?? 0);
        
        $sql_menu = "SELECT s.menu_id, s.menu_name, s.url_path
                     FROM tbl_user_access ua
                     JOIN tbl_slidebar s ON s.menu_id = ua.menu_id
                     WHERE ua.user_id = :uid AND s.active_status = '1' AND s.menu_name <> 'หน้าแรก'
                     ORDER BY s.menu_id ASC";
        $stmt_menu = $db->prepare($sql_menu);
        $stmt_menu->execute([':uid' => $user_id]);
        $menus = $stmt_menu->fetchAll(PDO::FETCH_ASSOC);
        $stmt_menu->closeCursor();
        
        echo "\n--- PERMITTED MENUS FOR USER ---\n";
        print_r($menus);

        $all_menus = $db->query("SELECT menu_id, menu_name, url_path, active_status FROM tbl_slidebar ORDER BY menu_id ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo "\n--- ALL MENUS IN TBL_SLIDEBAR ---\n";
        print_r($all_menus);
    }
} catch (\Throwable $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}
