<?php
require 'vendor/autoload.php';
$db = (new App\Database\Connection())->getPdo();
$stmt = $db->query("DESCRIBE tbl_user");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
