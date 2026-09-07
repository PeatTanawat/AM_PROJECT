<?php

// คูปองส่วนลด — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server ด้วย LIMIT/OFFSET)
// คืน JSON { list, total, page, per_page } -> หน้า coupon นำไป render ผ่าน view/listCoupon/GetTable.php

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

$search   = trim((string) ($_POST['search'] ?? ''));     // ค้นหาจาก code / รายละเอียด
$f_status = trim((string) ($_POST['f_status'] ?? ''));   // '1'=เปิดใช้งาน '0'=ปิดใช้งาน '' = ทั้งหมด

$where  = ["delete_at IS NULL"];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT_WS(' ', coupon_code, coupon_detail) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($f_status === '1' || $f_status === '0') {
    $where[] = "coupon_status = :f_status";
    $params[':f_status'] = $f_status;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง/ค้นหา
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_coupon $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน (พร้อม coupon_used + coupon_remaining จาก tbl_coupon_logs)
    $sql_data = "SELECT
                    c.coupon_id,
                    c.coupon_code,
                    c.coupon_detail,
                    c.coupon_type,
                    c.coupon_no,
                    c.coupon_status,
                    c.coupon_limit,
                    c.coupon_limit_person,
                    c.coupon_min,
                    c.coupon_max,
                    c.coupon_start,
                    c.coupon_end,
                    (SELECT COUNT(*) FROM tbl_coupon_logs l
                     WHERE l.coupon_id = c.coupon_id) AS coupon_used,
                    CASE
                        WHEN c.coupon_limit IS NULL THEN NULL
                        ELSE c.coupon_limit - (SELECT COUNT(*) FROM tbl_coupon_logs l
                                               WHERE l.coupon_id = c.coupon_id)
                    END AS coupon_remaining
                FROM tbl_coupon c
                $where_sql
                ORDER BY c.coupon_id DESC
                LIMIT :offset, :per_page";

    $stmt_data = $pdo_connect->prepare($sql_data);
    foreach ($params as $k => $v) { $stmt_data->bindValue($k, $v); }
    $stmt_data->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_data->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt_data->execute();
    $result_data = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
    $stmt_data->closeCursor();

    Response::json(1, 'สำเร็จ', [
        'list'     => $result_data,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
    ]);

} catch (\Throwable $e) {
    error_log('GetListCoupon Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
