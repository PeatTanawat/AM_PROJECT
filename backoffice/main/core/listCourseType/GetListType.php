<?php

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

$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$search = trim((string) ($_POST['search'] ?? ''));

$where  = ["t.delete_at IS NULL"];
$params = [];
if ($search !== '') {
    $where[] = "t.type_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_course_type t $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน
    $sql_data = "SELECT t.type_id,
                        t.type_name,
                        COUNT(c.course_id) AS course_count
                 FROM tbl_course_type t
                 LEFT JOIN tbl_course c ON c.course_type = t.type_id AND c.delete_at IS NULL
                 $where_sql
                 GROUP BY t.type_id, t.type_name
                 ORDER BY t.type_id ASC
                 LIMIT :offset, :per_page";

    $stmt_data = $pdo_connect->prepare($sql_data);
    foreach ($params as $k => $v) { $stmt_data->bindValue($k, $v); }
    $stmt_data->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_data->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt_data->execute();
    $result_data = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
    $stmt_data->closeCursor();

    $list = [];
    foreach ($result_data as $row) {
        $list[] = [
            "type_id"      => $row["type_id"] ?? null,
            "type_name"    => $row["type_name"] ?? null,
            "course_count" => $row["course_count"] ?? 0,
        ];
    }

    Response::json(1, 'สำเร็จ', [
        'list'     => $list,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
    ]);

} catch (\Throwable $e) {
    error_log('GetListType Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
