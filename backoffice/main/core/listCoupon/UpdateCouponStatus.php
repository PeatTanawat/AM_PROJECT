<?php

// สลับสถานะเปิด/ปิดใช้งานคูปอง (coupon_status: 1 = เปิดใช้งาน, 0 = ปิดใช้งาน)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$coupon_id = isset($_POST['coupon_id']) ? (int) $_POST['coupon_id'] : 0;
$status    = isset($_POST['status']) && (string) $_POST['status'] === '1' ? '1' : '0';
if ($coupon_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคูปอง', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_coupon SET coupon_status = :status, update_at = NOW()
         WHERE coupon_id = :id AND delete_at IS NULL"
    );
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':id', $coupon_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบคูปองนี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, $status === '1' ? 'เปิดใช้งานคูปองแล้ว' : 'ปิดใช้งานคูปองแล้ว', [
        'coupon_id'     => $coupon_id,
        'coupon_status' => $status,
    ]);
} catch (Exception $e) {
    error_log('Update Coupon Status Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
