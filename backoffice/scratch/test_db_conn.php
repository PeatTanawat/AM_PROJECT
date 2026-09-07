<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Database\Connection;

try {
    // Test direct connection to 127.0.0.1
    $dsn = "mysql:host=127.0.0.1;dbname=bigdemo_cpa;charset=utf8mb4;port=3306";
    $pdo = new PDO($dsn, 'bigdemo_cpau', 'eUjxGhsKxKXr2XRqekh3', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connection to 127.0.0.1 successful!\n";
    
    // Check columns in tbl_course
    $stmt = $pdo->query("DESCRIBE tbl_course");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(', ', $cols) . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
