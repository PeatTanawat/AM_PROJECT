<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Response;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (! $pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT text_1, youtube_id, image_path, text_2, facebook_link, x_link, line_link, about_us, contact_us
     FROM tbl_website_setting
     LIMIT 1"
);

$stmt->execute();
$setting = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
$pdo_connect = null;

if ($setting) {
    Response::json(1, 'ดึงข้อมูลสำเร็จ', ['setting' => $setting]);
} else {
    Response::json(0, 'ไม่พบข้อมูลการตั้งค่า', null);
}
