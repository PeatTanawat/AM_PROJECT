<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database\Connection;
use App\Utility\Response;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
    if ($order_id <= 0) {
        throw new Exception("หมายเลขออเดอร์ไม่ถูกต้อง");
    }

    $db = (new Connection())->getPdo();
    if (!$db) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    $stmt = $db->prepare("SELECT payment_status FROM tbl_orders WHERE order_id = :id LIMIT 1");
    $stmt->execute([':id' => $order_id]);
    $status = $stmt->fetchColumn();
    $stmt->closeCursor();

    if ($status === false) {
        throw new Exception("ไม่พบรายการคำสั่งซื้อ");
    }

    // หาก payment_status = '1' แปลว่าได้รับการยืนยันการชำระเงินแล้ว
    $paid = ($status == '1');

    Response::json(1, "ดึงสถานะชำระเงินสำเร็จ", ['paid' => $paid]);

} catch (Exception $e) {
    Response::json(0, $e->getMessage(), null);
}
