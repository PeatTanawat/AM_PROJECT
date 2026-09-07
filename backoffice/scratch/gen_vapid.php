<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\VAPID;

try {
    $vapid = VAPID::createVapidKeys();
    echo "PUBLIC: " . $vapid['publicKey'] . "\n";
    echo "PRIVATE: " . $vapid['privateKey'] . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
}
