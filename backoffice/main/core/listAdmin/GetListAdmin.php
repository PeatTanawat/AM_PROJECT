<?php

// ผู้ดูแลระบบทั้งหมด — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// คืน JSON { list, total, page, per_page } -> หน้า admin นำไป render ผ่าน view/listAdmin/GetTable.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
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

$search = trim((string) ($_POST['search'] ?? '')); // ชื่อ-นามสกุล / อีเมล

// ผู้ดูแลระบบ = admin_status = '1'
$where  = ["delete_at IS NULL", "admin_status = '1'"];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT_WS(' ', user_firstname, user_lastname, user_email) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_user $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน
    $sql_data = "SELECT user_id, user_firstname, user_lastname, user_email
                 FROM tbl_user
                 $where_sql
                 ORDER BY user_id ASC
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
            "user_id"    => $row["user_id"] ?? null,
            "full_name"  => trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? '')),
            "user_email" => $row["user_email"] ?? null,
        ];
    }

    Response::json(1, 'สำเร็จ', [
        'list'             => $list,
        'total'            => $total,
        'page'             => $page,
        'per_page'         => $per_page,
        'current_admin_id' => (int) $admin_id, // ใช้ซ่อนปุ่มลบของตัวเอง
    ]);

} catch (\Throwable $e) {
    error_log('GetListAdmin Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
