<?php
// ลบลิ้งค์ใบกำกับภาษี (soft-delete: set delete_at) + ปิด items

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$link_id = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
if ($link_id <= 0) {
    Response::json(0, 'ไม่พบรหัสลิ้งค์', null);
}

$db_instance = new Connection();
$pdo = $db_instance->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM tbl_etax_link WHERE id = :id AND delete_at IS NULL LIMIT 1");
    $stmt->execute([':id' => $link_id]);
    $exists = $stmt->fetchColumn();
    $stmt->closeCursor();
    if ($exists === false) {
        Response::json(0, 'ไม่พบลิ้งค์ใบกำกับภาษีนี้', null);
    }

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE tbl_etax_link SET delete_at = NOW() WHERE id = :id")->execute([':id' => $link_id]);
    $pdo->prepare("UPDATE tbl_etax_link_item SET delete_at = NOW() WHERE link_id = :id AND delete_at IS NULL")->execute([':id' => $link_id]);
    $pdo->commit();

    Response::json(1, 'ลบลิ้งค์ใบกำกับภาษีเรียบร้อยแล้ว', ['link_id' => $link_id]);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('DeleteEtaxLink Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo = null;
}
