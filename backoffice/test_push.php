<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$title = isset($_POST['title']) ? $_POST['title'] : 'แจ้งเตือนทดสอบ';
$body = isset($_POST['body']) ? $_POST['body'] : 'นี่คือข้อความทดสอบจากระบบ PWA Web Push';
$url = isset($_POST['url']) ? $_POST['url'] : '/am/main/';

try {
    $db = (new Connection())->getPdo();

    // ดึง Subscription ของตัวเองมาทดสอบ
    $stmt = $db->prepare("SELECT * FROM tbl_web_push_subscriptions WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $admin_id]);
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subs)) {
        Response::json(0, 'ยังไม่ได้ลงทะเบียนรับการแจ้งเตือน (ยังไม่มี Subscription)', null);
    }

    $auth = [
        'VAPID' => [
            'subject' => $_ENV['VAPID_SUBJECT'] ?? 'mailto:admin@yourdomain.com',
            'publicKey' => $_ENV['VAPID_PUBLIC_KEY'] ?? '',
            'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '',
        ],
    ];

    $webPush = new WebPush($auth);
    $payload = json_encode([
        'title' => $title,
        'body' => $body,
        'url' => $url
    ]);

    $successCount = 0;
    foreach ($subs as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['public_key'],
            'authToken' => $sub['auth_token'],
        ]);

        $report = $webPush->sendOneNotification($subscription, $payload);
        
        if ($report->isSuccess()) {
            $successCount++;
        } else {
            // ถ้ายิงไม่ผ่าน (เช่น ยกเลิกรับการแจ้งเตือนไปแล้ว) อาจจะลบจาก DB ทิ้งได้
            if ($report->isSubscriptionExpired()) {
                $db->prepare("DELETE FROM tbl_web_push_subscriptions WHERE endpoint = ?")->execute([$sub['endpoint']]);
            }
        }
    }

    if ($successCount > 0) {
        Response::json(1, "ส่งแจ้งเตือนสำเร็จ ($successCount เครื่อง)", null);
    } else {
        Response::json(0, 'ส่งแจ้งเตือนไม่สำเร็จ อาจจะหมดอายุ', null);
    }

} catch (\Throwable $e) {
    error_log("Test Push Error: " . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
