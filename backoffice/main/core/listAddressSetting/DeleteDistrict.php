<?php
// ลบอำเภอ (tbl_district - soft delete)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    Response::json(0, 'ไม่พบรหัสอำเภอที่ต้องการลบ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE tbl_district SET deleted_at = :deleted_at WHERE id = :id AND deleted_at IS NULL";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->bindValue(':deleted_at', $now);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'ลบอำเภอสำเร็จ', null);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการลบอำเภอ: ' . $e->getMessage(), null);
}
