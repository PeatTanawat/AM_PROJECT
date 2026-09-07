<?php
// เข้าถึงไฟล์นี้ผ่านเบราว์เซอร์ เช่น https://yourdomain.com/setup_push.php
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;
use Minishlink\WebPush\VAPID;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = (new Connection())->getPdo();

    $sql = "CREATE TABLE IF NOT EXISTS tbl_web_push_subscriptions (
        web_push_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        endpoint VARCHAR(500) NOT NULL,
        public_key VARCHAR(255) NOT NULL,
        auth_token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_endpoint (user_id, endpoint(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "<h3>1. Table `tbl_web_push_subscriptions` created successfully!</h3>";

    // ตรวจสอบ VAPID Keys ใน .env
    $pub = $_ENV['VAPID_PUBLIC_KEY'] ?? '';
    $pri = $_ENV['VAPID_PRIVATE_KEY'] ?? '';

    if (empty($pub) || empty($pri)) {
        $vapid = VAPID::createVapidKeys();
        echo "<h3>2. Please add these lines to your `.env` file:</h3>";
        echo "<pre style='background:#f4f4f4;padding:10px;border-radius:5px;'>";
        echo "VAPID_PUBLIC_KEY=" . $vapid['publicKey'] . "\n";
        echo "VAPID_PRIVATE_KEY=" . $vapid['privateKey'] . "\n";
        echo "VAPID_SUBJECT=mailto:admin@yourdomain.com\n";
        echo "</pre>";
        echo "<p>After adding these to `.env`, refresh this page to confirm.</p>";
    } else {
        echo "<h3>2. VAPID Keys found in `.env`! Ready to go.</h3>";
        echo "<p>Public Key: " . htmlspecialchars($pub) . "</p>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
