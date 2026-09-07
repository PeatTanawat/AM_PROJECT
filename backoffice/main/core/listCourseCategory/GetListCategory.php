<?php
// หมวดหมู่ของคอร์สเรียน — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// คืน JSON { list, total, page, per_page } -> course_category.php นำไป render ผ่าน view/listCourseCategory/GetTable.php

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

$where  = ["a.delete_at IS NULL"];
$params = [];
if ($search !== '') {
    $where[] = "a.group_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_course_group a $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน
    $sql = "SELECT a.group_id, a.group_name,
                   COUNT(b.course_id) AS course_count
            FROM tbl_course_group a
            LEFT JOIN tbl_course b ON a.group_id = b.course_group AND b.delete_at IS NULL
            $where_sql
            GROUP BY a.group_id, a.group_name
            ORDER BY a.group_id ASC
            LIMIT :offset, :per_page";
    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $list = [];
    foreach ($rows as $row) {
        $list[] = [
            'group_id'     => $row['group_id'] ?? null,
            'group_name'   => $row['group_name'] ?? null,
            'course_count' => $row['course_count'] ?? null,
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListCategory Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
