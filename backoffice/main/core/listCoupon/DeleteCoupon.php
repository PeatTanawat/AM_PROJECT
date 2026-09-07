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

try {
    // soft delete — ไม่ลบจริง
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_coupon SET delete_at = NOW() WHERE coupon_id = :id AND delete_at IS NULL"
    );
    $stmt->execute([':id' => $coupon_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบคูปองนี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, 'ลบคูปองสำเร็จ', ['coupon_id' => $coupon_id]);
} catch (Exception $e) {
    error_log('Delete Coupon Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
