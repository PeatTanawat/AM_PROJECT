<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : (int) ($_POST['bank_id'] ?? 0);

if ($id <= 0) {
    Response::json(0, 'ไม่พบรายการที่ต้องการลบ', null);
}

try {
    $sql_delete = "DELETE FROM tbl_company_banks WHERE id = :id";
    $stmt_delete = $pdo_connect->prepare($sql_delete);
    $delete = $stmt_delete->execute([':id' => $id]);
    $stmt_delete->closeCursor();

    if (!$delete) {
        throw new Exception('เกิดข้อผิดพลาดในการลบข้อมูล');
    }

    Response::json(1, 'ลบข้อมูลบัญชีธนาคารสำเร็จ', null);

} catch (\Throwable $e) {
    error_log('Delete Bank Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
