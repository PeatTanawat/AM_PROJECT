<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$coupon_id = isset($_POST['coupon_id']) ? (int) $_POST['coupon_id'] : 0;
if ($coupon_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคูปอง', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT * FROM tbl_coupon WHERE coupon_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $coupon_id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$coupon) {
    Response::json(0, 'ไม่พบคูปองนี้', null);
}

Response::json(1, 'Success', ['coupon' => $coupon]);
