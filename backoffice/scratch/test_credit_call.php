<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utility\SMS;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$res = SMS::getCredit();
echo "RESULT FROM SMS::getCredit():\n";
print_r($res);
