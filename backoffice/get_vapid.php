<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

header('Content-Type: application/json');
echo json_encode([
    'publicKey' => $_ENV['VAPID_PUBLIC_KEY'] ?? ''
]);
