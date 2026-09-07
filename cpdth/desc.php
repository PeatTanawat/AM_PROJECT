<?php
require '../backoffice/vendor/autoload.php';
require '../backoffice/src/Database/Connection.php';
$db = new App\Database\Connection();
$pdo = $db->getPdo();
$q = $pdo->query('DESCRIBE tbl_certificate_snapshot');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
