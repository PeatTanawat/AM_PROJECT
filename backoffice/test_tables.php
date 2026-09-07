<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Database\Connection;
try {
    $db = new Connection();
    $pdo = $db->getPdo();
    $stmt = $pdo->query("SHOW TABLES");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (\Throwable $e) { echo $e->getMessage(); }
