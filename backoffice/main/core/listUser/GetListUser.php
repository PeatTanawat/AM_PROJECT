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

$page = max(1, (int) ($_POST['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = trim((string) ($_POST['search'] ?? ''));
$filter_status = trim((string) ($_POST['filter_status'] ?? ''));
$filter_verify = trim((string) ($_POST['filter_verify'] ?? ''));

$where = [
    "u.delete_at IS NULL",
    "u.admin_status = 0",
    "u.is_super_admin = 0"
];
$params = [];

if ($filter_status !== '') {
    $where[] = "u.user_status = :filter_status";
    $params[':filter_status'] = $filter_status;
}

if ($filter_verify !== '') {
    $where[] = "u.identity_verified = :filter_verify";
    $params[':filter_verify'] = $filter_verify;
}

if ($search !== '') {
    $where[] = "(CONCAT_WS(' ',
                     u.user_firstname, u.user_lastname,
                     u.user_email, u.user_citizen_id, u.user_cpd_no, u.user_cpa_no
                 ) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง/ค้นหา
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_user u $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลเฉพาะหน้าปัจจุบัน
    $sql_data = "SELECT
                    u.user_id,
                    u.user_firstname,
                    u.user_lastname,
                    u.user_email,
                    u.user_phone,
                    u.user_citizen_id,
                    u.user_cpd_no,
                    u.user_cpa_no,
                    u.user_status,
                    u.identity_verified,
                    u.id_card_expiry_date,
                    (SELECT COALESCE(SUM(total_price), 0) FROM tbl_orders WHERE user_id = u.user_id) AS total_spent
                FROM tbl_user u 
                $where_sql  
                ORDER BY u.user_id DESC
                LIMIT :offset, :per_page";

    $stmt_data = $pdo_connect->prepare($sql_data);
    foreach ($params as $k => $v) {
        $stmt_data->bindValue($k, $v);
    }
    $stmt_data->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_data->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt_data->execute();
    $result_data = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
    $stmt_data->closeCursor();

    $list = [];
    foreach ($result_data as $row) {
        $list[] = [
            "user_id" => $row["user_id"] ?? null,
            "user_firstname" => $row["user_firstname"] ?? null,
            "user_lastname" => $row["user_lastname"] ?? null,
            "user_email" => $row["user_email"] ?? null,
            "user_phone" => $row["user_phone"] ?? null,
            "user_citizen_id" => $row["user_citizen_id"] ?? null,
            "user_cpd_no" => $row["user_cpd_no"] ?? null,
            "user_cpa_no" => $row["user_cpa_no"] ?? null,
            "user_status" => $row["user_status"] ?? null,
            "identity_verified" => (string) ($row["identity_verified"] ?? '0'),  // 0=ยังไม่ยืนยัน 1=รอตรวจสอบ 2=ยืนยันแล้ว
            "id_card_expiry_date" => $row["id_card_expiry_date"] ?? null,        // วันหมดอายุบัตรประชาชน (Y-m-d)
            "total_spent" => (float) ($row["total_spent"] ?? 0),
        ];
    }

    Response::json(1, 'สำเร็จ', [
        'list' => $list,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
    ]);

} catch (\Throwable $e) {
    error_log('GetListUser Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
