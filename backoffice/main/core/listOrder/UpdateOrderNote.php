<?php
// บันทึกหมายเหตุภายในของคำสั่งซื้อ

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$note     = isset($_POST['internal_note']) ? trim((string) $_POST['internal_note']) : '';
if ($order_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำสั่งซื้อ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare("UPDATE tbl_orders SET order_internal_note = :note WHERE order_id = :id");
    $stmt->execute([':note' => $note, ':id' => $order_id]);
    $stmt->closeCursor();
    Response::json(1, 'บันทึกหมายเหตุสำเร็จ', null);
} catch (Throwable $e) {
    error_log('UpdateOrderNote Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
