<?php
require_once __DIR__ . '/vendor/autoload.php'; 
require_once __DIR__ . '/src/Utility/Notification.php';

try {
    $result = \App\Utility\Notification::send("Test Title", "Test Message", "general", "#");
    echo "Success: " . ($result ? 'true' : 'false') . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
