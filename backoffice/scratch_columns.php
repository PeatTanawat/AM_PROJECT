<?php
require 'c:/xampp/htdocs/am/src/Database/Connection.php';
$pdo = (new \App\Database\Connection())->getPdo();
$stmt = $pdo->query('SHOW COLUMNS FROM tbl_order_detail');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
