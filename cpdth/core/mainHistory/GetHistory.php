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

    // หากส่ง user_id มาให้ใช้ค่าที่ส่งมา (เช่นแอดมินดู) หากไม่ได้ส่งมาให้ใช้ ID ของตัวเองที่เข้าสู่ระบบอยู่
    $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) $admin_id;
    if ($target_id <= 0) {
        Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (! $pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // Pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 10;
    $offset = ($page - 1) * $limit;

    // หาจำนวนทั้งหมด
    $stmtCount = $pdo_connect->prepare("SELECT COUNT(order_id) FROM tbl_orders WHERE user_id = :id");
    $stmtCount->execute([':id' => $target_id]);
    $totalItems = (int)$stmtCount->fetchColumn();
    $totalPages = ceil($totalItems / $limit);

    // ดึงประวัติคำสั่งซื้อแบบ Pagination
    $stmt = $pdo_connect->prepare(
        "SELECT order_id,
                total_price,
                payment_status,
                payment_method,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at,
                transaction_ref
         FROM tbl_orders
         WHERE user_id = :id
         ORDER BY tbl_orders.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':id', $target_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (! $orders) {
        $orders = []; // ไม่มีประวัติ
    }

    Response::json(1, 'Success', [
        'orders' => $orders,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
