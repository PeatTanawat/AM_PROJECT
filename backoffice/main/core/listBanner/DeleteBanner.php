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

try {
    // soft delete — ไม่ลบจริง
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_banner SET delete_at = NOW() WHERE banner_id = :id AND delete_at IS NULL"
    );
    $stmt->execute([':id' => $banner_id]);
    $rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($rows === 0) {
        Response::json(0, 'ไม่พบแบนเนอร์นี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, 'ลบแบนเนอร์สำเร็จ', ['banner_id' => $banner_id]);
} catch (Exception $e) {
    error_log('Delete Banner Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
