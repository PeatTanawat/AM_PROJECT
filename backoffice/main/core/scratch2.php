<?php
$pdo = new PDO('mysql:host=localhost;dbname=am;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SHOW COLUMNS FROM tbl_etax');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
