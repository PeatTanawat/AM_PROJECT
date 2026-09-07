<?php

// สลับสถานะใช้งาน/ไม่ใช้งานของผู้ใช้ (user_status: 1 = ใช้งาน, 0 = ไม่ใช้งาน)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$target_user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$status         = isset($_POST['status']) && (string) $_POST['status'] === '1' ? '1' : '0';
if ($target_user_id <= 0) {
    Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_user SET user_status = :status, update_at = NOW()
         WHERE user_id = :id AND delete_at IS NULL"
    );
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':id', $target_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบผู้ใช้นี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, $status === '1' ? 'เปิดใช้งานผู้ใช้แล้ว' : 'ปิดใช้งานผู้ใช้แล้ว', [
        'user_id'     => $target_user_id,
        'user_status' => $status,
    ]);
} catch (Exception $e) {
    error_log('Update User Status Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
