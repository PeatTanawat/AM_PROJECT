<?php

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    $access_token = Auth::requireUserToken();
    $admin_id     = $access_token->user_id ?? null;

    if (! $admin_id) {
        Response::json(0, 'Unauthorized', null);
    }

    $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) $admin_id;
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;

    if ($target_id <= 0 || $order_id <= 0) {
        Response::json(0, 'ข้อมูลไม่ถูกต้อง', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (! $pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // ข้อมูลคำสั่งซื้อ
    $stmt = $pdo_connect->prepare(
        "SELECT order_id,
                total_price,
                payment_status,
                payment_method,
                created_at,
                transaction_ref
         FROM tbl_orders
         WHERE order_id = :order_id AND user_id = :user_id
         LIMIT 1"
    );
    $stmt->execute([':order_id' => $order_id, ':user_id' => $target_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (! $order) {
        Response::json(0, 'ไม่พบข้อมูลคำสั่งซื้อ', null);
    }

    // ข้อมูลรายละเอียด (สินค้า/คอร์ส)
    $stmt = $pdo_connect->prepare(
        "SELECT d.detail_id, d.course_id, d.price_at_purchase, c.course_name
         FROM tbl_order_detail d
         LEFT JOIN tbl_course c ON d.course_id = c.course_id
         WHERE d.order_id = :order_id
         ORDER BY d.list_order ASC"
    );
    $stmt->execute([':order_id' => $order_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $order['items'] = $details ?: [];

    Response::json(1, 'Success', [
        'order' => $order,
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
