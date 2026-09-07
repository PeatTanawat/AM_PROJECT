<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($target_id <= 0) {
    Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // soft delete — ไม่ลบจริง (ปรับ user_status = 0 ร่วมด้วย)
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_user SET delete_at = NOW(), user_status = 0 WHERE user_id = :id AND delete_at IS NULL"
    );
    $stmt->execute([':id' => $target_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบผู้ใช้นี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, 'ลบบัญชีผู้ใช้สำเร็จ', ['user_id' => $target_id]);
} catch (Exception $e) {
    error_log('Delete User Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
