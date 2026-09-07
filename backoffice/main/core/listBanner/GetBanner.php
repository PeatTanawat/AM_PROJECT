<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$banner_id = isset($_POST['banner_id']) ? (int) $_POST['banner_id'] : 0;
if ($banner_id <= 0) {
    Response::json(0, 'ไม่พบรหัสแบนเนอร์', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT * FROM tbl_banner WHERE banner_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $banner_id]);
$banner = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$banner) {
    Response::json(0, 'ไม่พบแบนเนอร์นี้', null);
}

Response::json(1, 'Success', ['banner' => $banner]);
