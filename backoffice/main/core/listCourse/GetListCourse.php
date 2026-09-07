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
$f_type = trim((string) ($_POST['f_type'] ?? ''));
$f_category = trim((string) ($_POST['f_category'] ?? ''));

$joins = "FROM tbl_course c
          LEFT JOIN tbl_course_type  t ON c.course_type  = t.type_id
          LEFT JOIN tbl_course_group g ON c.course_group = g.group_id";

$where  = ["c.delete_at IS NULL"];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT_WS(' ',
                     c.course_name, t.type_name, g.group_name,
                     c.course_code_cpd_1, c.course_code_cpd_2, c.course_code_cpd_3, c.course_code_cpd_4,
                     c.course_code_cpa_1, c.course_code_cpa_2, c.course_code_cpa_3, c.course_code_cpa_4
                 ) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($f_type !== '') {
    $where[] = "c.course_type = :f_type";
    $params[':f_type'] = $f_type;
}
if ($f_category !== '') {
    $where[] = "c.course_group = :f_category";
    $params[':f_category'] = $f_category;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง/ค้นหา
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) $joins $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน (เรียงคงที่ ใหม่สุดก่อน)
    $sql_data = "SELECT
                    c.course_id,
                    c.course_cover_image,
                    c.course_name,
                    c.course_type,
                    t.type_name,
                    c.course_group,
                    g.group_name,
                    c.course_price,
                    c.course_promotion,
                    c.course_code_cpd_1, c.course_code_cpd_2, c.course_code_cpd_3, c.course_code_cpd_4,
                    c.course_code_cpa_1, c.course_code_cpa_2, c.course_code_cpa_3, c.course_code_cpa_4,
                    c.course_display,
                    c.course_status
                $joins
                $where_sql
                ORDER BY c.course_id DESC
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
            "course_id"          => $row["course_id"] ?? null,
            "course_cover_image" => $row["course_cover_image"] ?? null,
            "course_name"        => $row["course_name"] ?? null,
            "course_type"        => $row["course_type"] ?? null,
            "type_name"          => $row["type_name"] ?? null,
            "course_group"       => $row["course_group"] ?? null,
            "group_name"         => $row["group_name"] ?? null,
            "course_price"       => $row["course_price"] ?? null,
            "course_promotion"   => $row["course_promotion"] ?? null,
            "course_code_cpd_1"  => $row["course_code_cpd_1"] ?? null,
            "course_code_cpd_2"  => $row["course_code_cpd_2"] ?? null,
            "course_code_cpd_3"  => $row["course_code_cpd_3"] ?? null,
            "course_code_cpd_4"  => $row["course_code_cpd_4"] ?? null,
            "course_code_cpa_1"  => $row["course_code_cpa_1"] ?? null,
            "course_code_cpa_2"  => $row["course_code_cpa_2"] ?? null,
            "course_code_cpa_3"  => $row["course_code_cpa_3"] ?? null,
            "course_code_cpa_4"  => $row["course_code_cpa_4"] ?? null,
            "course_display"     => $row["course_display"] ?? null,
            "course_status"      => $row["course_status"] ?? null,
        ];
    }

    Response::json(1, 'สำเร็จ', [
        'list'     => $list,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
    ]);

} catch (\Throwable $e) {
    error_log('GetListCourse Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
