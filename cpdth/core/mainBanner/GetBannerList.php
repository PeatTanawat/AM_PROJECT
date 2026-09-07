<?php

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}

use App\Database\Connection;
use App\Utility\Response;
use Dotenv\Dotenv;
use App\Utility\AwsS3;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));

$dotenv->load();

$db_instance = new Connection();

$pdo_connect = $db_instance->getPdo();

if (! $pdo_connect) {

    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);

}

$stmt = $pdo_connect->prepare(
    "SELECT banner_id, banner_order, banner_url, banner_image
     FROM tbl_banner
     WHERE banner_status = '1'
       AND delete_at IS NULL
     ORDER BY banner_order ASC"
);

$stmt->execute();

$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($banners as &$banner) {
    if (!empty($banner['banner_image'])) {
        // ใช้ getFileUrl เพื่อสร้างลิงก์ชั่วคราวแบบ Base64
        $banner['banner_image'] = AwsS3::getFileUrl($banner['banner_image'], '+30 minutes', true);
    }
    $url = trim((string)($banner['banner_url'] ?? ''));
    $banner['banner_url']  = $url;
    $banner['has_link']    = ($url !== '');
    $banner['link_target'] = (preg_match('/^https?:\/\//i', $url)) ? '_blank' : '_self';
}
unset($banner);

$stmt->closeCursor();

$pdo_connect = null;

Response::json(1, 'ดึงข้อมูลแบนเนอร์สำเร็จ', ['banners' => $banners]);
