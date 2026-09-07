<?php
// รายการตารางที่อยู่ทั้งหมด (tbl_sub_district + tbl_district + tbl_province) พร้อม Pagination

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

$page = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['limit'] ?? 25));
$offset = ($page - 1) * $per_page;

$province_id = (int) ($_POST['province_id'] ?? 0);
$amphure_id = (int) ($_POST['amphure_id'] ?? 0);
$search = trim((string) ($_POST['search'] ?? ''));

$where = ["s.deleted_at IS NULL"];
$params = [];

if ($province_id > 0) {
    $where[] = "p.id = :province_id";
    $params[':province_id'] = $province_id;
}

if ($amphure_id > 0) {
    $where[] = "d.id = :amphure_id";
    $params[':amphure_id'] = $amphure_id;
}

if ($search !== '') {
    $where[] = "CAST(s.zip_code AS CHAR) LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // นับจำนวนทั้งหมด
    $sql_count = "SELECT COUNT(*)
                  FROM tbl_sub_district s
                  LEFT JOIN tbl_district d ON s.amphure_id = d.id
                  LEFT JOIN tbl_province p ON d.province_id = p.id
                  $where_sql";
    $stmt_count = $pdo_connect->prepare($sql_count);
    foreach ($params as $k => $v) {
        $stmt_count->bindValue($k, $v);
    }
    $stmt_count->execute();
    $total_count = (int) $stmt_count->fetchColumn();
    $stmt_count->closeCursor();

    // ดึงข้อมูลตามลิมิต
    $sql = "SELECT s.id AS sub_district_id, s.name_th AS sub_district_name_th, s.name_en AS sub_district_name_en, s.zip_code,
                   d.id AS district_id, d.name_th AS district_name_th, d.name_en AS district_name_en,
                   p.id AS province_id, p.name_th AS province_name_th, p.name_en AS province_name_en
            FROM tbl_sub_district s
            LEFT JOIN tbl_district d ON s.amphure_id = d.id
            LEFT JOIN tbl_province p ON d.province_id = p.id
            $where_sql
            ORDER BY p.name_th ASC, d.name_th ASC, s.name_th ASC
            LIMIT :offset, :per_page";

    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $total_pages = ceil($total_count / $per_page);

    Response::json(1, 'ดึงข้อมูลสำเร็จ', [
        'list' => $rows,
        'total' => $total_count,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ]);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
