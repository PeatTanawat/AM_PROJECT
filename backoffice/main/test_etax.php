<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$pdo = (new \App\Database\Connection())->getPdo();
$stmt = $pdo->query('SELECT * FROM tbl_etax');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
