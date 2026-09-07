<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

try {
    $access_token = Auth::requireUserToken();
    $admin_id = $access_token->user_id ?? null;

    if (!$admin_id) {
        Response::json(0, 'Unauthorized', null);
    }

    // หากส่ง user_id มาให้ใช้ค่าที่ส่งมา (เช่นแอดมินดู) หากไม่ได้ส่งมาให้ใช้ ID ของตัวเองที่เข้าสู่ระบบอยู่
    $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int)$admin_id;
    if ($target_id <= 0) {
        Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    if ($page < 1) $page = 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 2;
    if ($limit < 1) $limit = 2;

    $offset = ($page - 1) * $limit;

    $stmtCount = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_user_address WHERE addr_user_id = :id AND delete_at IS NULL");
    $stmtCount->execute([':id' => $target_id]);
    $total_count = $stmtCount->fetchColumn();
    $total_pages = ceil($total_count / $limit);

    // ข้อมูลผู้ใช้ (เฉพาะที่ยังไม่ถูกลบ) Sorted by default first, then newest first
    $stmt = $pdo_connect->prepare(
        "SELECT 
         addr_id ,
         addr_user_id ,
         addr_name,
         addr_type,
         addr_tax_id,
         addr_branch,
         addr_branch_name,
         addr_phone,
         addr_detail,
         addr_subdistrict,
         addr_district,
         addr_province,
         addr_zipcode,
         addr_is_default
         FROM tbl_user_address 
         WHERE addr_user_id = :id AND delete_at IS NULL 
         ORDER BY addr_is_default DESC, addr_id DESC 
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':id', $target_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    Response::json(1, 'Success', [
        'addresses' => $addresses,
        'total_count' => $total_count,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'limit' => $limit
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
