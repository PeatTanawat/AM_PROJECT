<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;
use Minishlink\WebPush\VAPID;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
echo "Table tbl_web_push_subscriptions created/checked.\n";

// Generate VAPID if not already generated.
// To use them, you would normally save them in .env, but for now we just print them.
$vapid = VAPID::createVapidKeys();
echo "\n--- GENERATED VAPID KEYS ---\n";
echo "Public Key: " . $vapid['publicKey'] . "\n";
echo "Private Key: " . $vapid['privateKey'] . "\n";
echo "Please add these to your .env file or hardcode them temporarily.\n";
