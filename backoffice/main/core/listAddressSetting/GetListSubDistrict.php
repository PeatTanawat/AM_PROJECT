<?php
// รายการตำบลและรหัสไปรษณีย์ (tbl_sub_district) ตาม amphure_id

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$amphure_id = (int) ($_POST['amphure_id'] ?? 0);
$sub_district_id = (int) ($_POST['sub_district_id'] ?? 0);
$search = trim((string) ($_POST['search'] ?? ''));

if ($amphure_id <= 0 && $sub_district_id <= 0 && empty($search)) {
    Response::json(0, 'กรุณาระบุเงื่อนไขในการค้นหา', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$raw_limit = $_POST['limit'] ?? 25;
$page = max(1, (int) ($_POST['page'] ?? 1));
$is_no_limit = ($raw_limit === 0 || $raw_limit === '0' || $raw_limit === -1 || $raw_limit === '-1' || isset($_POST['no_limit']));

if ($is_no_limit) {
    $per_page = 999999;
    $limit_sql = "";
} else {
    $per_page = max(1, (int) $raw_limit);
    $offset = ($page - 1) * $per_page;
    $limit_sql = "LIMIT :offset, :per_page";
}

$where = ["s.deleted_at IS NULL"];
$params = [];

if ($amphure_id > 0) {
    $where[] = "s.amphure_id = :amphure_id";
    $params[':amphure_id'] = $amphure_id;
}

if ($sub_district_id > 0) {
    $where[] = "s.id = :sub_district_id";
    $params[':sub_district_id'] = $sub_district_id;
}

if ($search !== '') {
    $where[] = "CAST(s.zip_code AS CHAR) LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    $sql_count = "SELECT COUNT(*)
                  FROM tbl_sub_district s
                  LEFT JOIN tbl_district d ON s.amphure_id = d.id
                  $where_sql";
    $stmt_count = $pdo_connect->prepare($sql_count);
    foreach ($params as $k => $v) {
        $stmt_count->bindValue($k, $v);
    }
    $stmt_count->execute();
    $total_count = (int) $stmt_count->fetchColumn();
    $stmt_count->closeCursor();

    $sql = "SELECT s.id, s.name_th, s.name_en, s.zip_code, s.amphure_id, d.name_th AS amphure_name_th
            FROM tbl_sub_district s
            LEFT JOIN tbl_district d ON s.amphure_id = d.id
            $where_sql
            ORDER BY s.name_th ASC
            $limit_sql";

    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    if (!$is_no_limit) {
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $total_pages = ceil($total_count / $per_page);

    Response::json(1, 'ดึงข้อมูลสำเร็จ', [
        'rows' => $rows,
        'total' => $total_count,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ]);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
