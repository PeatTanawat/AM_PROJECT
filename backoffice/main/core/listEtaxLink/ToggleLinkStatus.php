<?php
// เปิด/ปิดการใช้งานลิ้งค์ (link_status: '1'=ใช้งานได้ <-> '0'=ปิดใช้งาน)

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
    $stmt = $pdo->prepare("SELECT link_status FROM tbl_etax_link WHERE id = :id AND delete_at IS NULL LIMIT 1");
    $stmt->execute([':id' => $link_id]);
    $cur = $stmt->fetchColumn();
    $stmt->closeCursor();
    if ($cur === false) {
        Response::json(0, 'ไม่พบลิ้งค์ใบกำกับภาษีนี้', null);
    }

    $new = ((string) $cur === '1') ? '0' : '1';
    $upd = $pdo->prepare("UPDATE tbl_etax_link SET link_status = :s WHERE id = :id AND delete_at IS NULL");
    $upd->execute([':s' => $new, ':id' => $link_id]);
    $upd->closeCursor();

    Response::json(1, $new === '1' ? 'เปิดใช้งานลิ้งค์แล้ว' : 'ปิดใช้งานลิ้งค์แล้ว', ['link_status' => $new]);
} catch (\Throwable $e) {
    error_log('ToggleLinkStatus Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo = null;
}
