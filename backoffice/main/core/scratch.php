<?php
require 'App/Database/Connection.php';
$c = new App\Database\Connection();
$pdo = $c->getPdo();
$stmt = $pdo->query('SHOW COLUMNS FROM tbl_etax');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
