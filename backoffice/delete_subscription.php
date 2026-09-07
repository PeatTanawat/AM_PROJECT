<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

// รับข้อมูลจาก JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['endpoint'])) {
    Response::json(0, 'ข้อมูลไม่ครบถ้วน', null);
}

$endpoint = $data['endpoint'];

try {
    $db = (new Connection())->getPdo();

    // ลบ subscription จาก endpoint
    $stmt = $db->prepare("DELETE FROM tbl_web_push_subscriptions WHERE endpoint = :endpoint AND user_id = :user_id");
    $stmt->execute([
        ':endpoint' => $endpoint,
        ':user_id' => $admin_id
    ]);

    Response::json(1, 'ลบสำเร็จ', null);
} catch (Exception $e) {
    error_log("Push Unsub Error: " . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
}
