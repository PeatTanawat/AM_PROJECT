<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$c = new \App\Database\Connection();
$pdo = $c->getPdo();
$stmt = $pdo->query('SHOW COLUMNS FROM tbl_exam_attempt');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('cols.json', json_encode($cols));
