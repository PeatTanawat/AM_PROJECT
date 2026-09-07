<?php
require 'vendor/autoload.php';
$db = new App\Database\Connection();
$pdo = $db->getPdo();
$stmt = $pdo->query("SHOW COLUMNS FROM tbl_orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
