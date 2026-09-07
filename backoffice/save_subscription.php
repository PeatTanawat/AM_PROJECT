<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use Dotenv\Dotenv;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

// รับข้อมูลจาก JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['endpoint']) || !isset($data['keys']['p256dh']) || !isset($data['keys']['auth'])) {
    Response::json(0, 'ข้อมูลไม่ครบถ้วน', null);
}

$endpoint = $data['endpoint'];
$publicKey = $data['keys']['p256dh'];
$authToken = $data['keys']['auth'];

try {
    $db = (new Connection())->getPdo();

    // ลบอันเก่าถ้ามี (สำหรับ endpoint นี้) หรืออาจจะใช้ INSERT ... ON DUPLICATE KEY UPDATE
    $stmt = $db->prepare("
        INSERT INTO tbl_web_push_subscriptions (user_id, endpoint, public_key, auth_token) 
        VALUES (:user_id, :endpoint, :public_key, :auth_token)
        ON DUPLICATE KEY UPDATE 
            public_key = :public_key2, 
            auth_token = :auth_token2
    ");

    $stmt->execute([
        ':user_id' => $admin_id,
        ':endpoint' => $endpoint,
        ':public_key' => $publicKey,
        ':auth_token' => $authToken,
        ':public_key2' => $publicKey,
        ':auth_token2' => $authToken,
    ]);

    // ----------------------------------------------------------------------
    // แจ้งเตือนย้อนหลัง (Offline Notifications)
    // ----------------------------------------------------------------------
    // 1. หาเวลาที่แอดมินคนนี้ Logout ครั้งล่าสุด (end_datetime)
    $stmtLogout = $db->prepare("SELECT MAX(end_datetime) FROM tbl_login_token WHERE user_id = :user_id");
    $stmtLogout->execute([':user_id' => $admin_id]);
    $last_logout = $stmtLogout->fetchColumn();

    if ($last_logout) {
        // 2. ดึงข้อความแจ้งเตือนที่เกิดขึ้นหลังจากเวลา Logout ล่าสุด
        $stmtNotif = $db->prepare("SELECT * FROM tbl_notifications WHERE created_at > :last_logout ORDER BY created_at ASC");
        $stmtNotif->execute([':last_logout' => $last_logout]);
        $missed_notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        if (count($missed_notifications) > 0) {
            $authKeys = [
                'VAPID' => [
                    'subject' => $_ENV['VAPID_SUBJECT'] ?? 'mailto:admin@example.com',
                    'publicKey' => $_ENV['VAPID_PUBLIC_KEY'] ?? '',
                    'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '',
                ],
            ];
            $webPush = new WebPush($authKeys);
            $subObj = Subscription::create([
                'endpoint' => $endpoint,
                'publicKey' => $publicKey,
                'authToken' => $authToken,
            ]);

            foreach ($missed_notifications as $notif) {
                $payload = json_encode([
                    'title' => $notif['title'],
                    'body'  => $notif['message'],
                    'url'   => $notif['link_url'],
                    'icon'  => '../assets/images/favicon.png'
                ]);
                $webPush->queueNotification($subObj, $payload);
            }
            
            // สั่งยิงแจ้งเตือนที่ค้างอยู่
            try {
                $webPush->flush();
            } catch (Exception $e) {
                error_log("Missed Push Error: " . $e->getMessage());
            }
        }
    }
    // ----------------------------------------------------------------------

    Response::json(1, 'บันทึกสำเร็จ', null);
} catch (Exception $e) {
    error_log("Push Sub Error: " . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
}
