<?php

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    $access_token = Auth::requireUserToken();
    $user_id      = $access_token->user_id ?? null;

    if (! $user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (! $pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // Pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 5;
    $offset = ($page - 1) * $limit;

    // Count Total
    $stmtCount = $pdo_connect->prepare("SELECT COUNT(id) FROM tbl_etax WHERE user_id = :id");
    $stmtCount->execute([':id' => $user_id]);
    $totalItems = (int)$stmtCount->fetchColumn();
    $totalPages = ceil($totalItems / $limit);

    $stmt = $pdo_connect->prepare(
        "SELECT e.id, e.order_id, e.addr_tax_id, e.addr_name, e.etax_address, e.user_email, e.etax_no, e.etax_type, e.created_at, e.updated_at, u.user_citizen_id
         FROM tbl_etax e
         LEFT JOIN tbl_user u ON e.user_id = u.user_id
         WHERE e.user_id = :id
         ORDER BY e.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $etax_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    Response::json(1, 'Success', [
        'etax_list' => $etax_list,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
