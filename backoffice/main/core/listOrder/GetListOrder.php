<?php
// คำสั่งซื้อทั้งหมด — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// คืน JSON { list, total, page, per_page } -> หน้า order นำไป render ผ่าน view/listOrder/ViewOrder.php

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

// ฟิลเตอร์จากฟอร์มด้านบน (ส่งมากับ ajax data)
$f_order    = trim((string) ($_POST['f_order'] ?? ''));     // หมายเลขคำสั่งซื้อ (transaction_ref)
$f_customer = trim((string) ($_POST['f_customer'] ?? ''));  // ชื่อลูกค้า
$f_status   = trim((string) ($_POST['f_status'] ?? ''));    // สถานะ/การชำระเงิน: 0/1/2 (tbl_orders มีคอลัมน์ payment_status เดียว)
$f_date     = trim((string) ($_POST['f_date'] ?? ''));      // วันที่สั่งซื้อ (Y-m-d หรือ d/m/Y)

$joins = "FROM tbl_orders o
          LEFT JOIN tbl_user u ON o.user_id = u.user_id";

// สร้างเงื่อนไข filter (placeholder ห้ามซ้ำเมื่อปิด emulate prepares)
$where  = [];
$params = [];
if ($f_order !== '') {
    $where[] = "o.transaction_ref LIKE :f_order";
    $params[':f_order'] = '%' . $f_order . '%';
}
if ($f_customer !== '') {
    $where[] = "CONCAT_WS(' ', u.user_firstname, u.user_lastname) LIKE :f_customer";
    $params[':f_customer'] = '%' . $f_customer . '%';
}
if ($f_status !== '' && in_array($f_status, ['0', '1', '2'], true)) {
    $where[] = "o.payment_status = :f_status";
    $params[':f_status'] = $f_status;
}
if ($f_date !== '') {
    // รองรับ d/m/Y -> Y-m-d
    $d = $f_date;
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $f_date, $m)) {
        $d = $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    $where[] = "DATE(o.created_at) = :f_date";
    $params[':f_date'] = $d;
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {

    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) $joins $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน (ดึงชื่อคอร์สด้วย subquery GROUP_CONCAT กันแถวซ้ำจากการ join detail)
    // เรียงคงที่ ใหม่สุดก่อน (สั่งซื้อเมื่อ)
    $sql = "SELECT o.order_id, o.transaction_ref, o.total_price, o.payment_status, o.payment_method, o.created_at,
                   u.user_firstname, u.user_lastname,
                   (SELECT GROUP_CONCAT(c.course_name SEPARATOR '\n')
                      FROM tbl_order_detail od
                      LEFT JOIN tbl_course c ON c.course_id = od.course_id
                     WHERE od.order_id = o.order_id) AS course_names
            $joins
            $where_sql
            ORDER BY o.created_at DESC
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

    $list = [];
    foreach ($rows as $r) {
        $full    = trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''));
        $courses = trim((string) ($r['course_names'] ?? ''));

        $list[] = [
            'order_id'  => (int) $r['order_id'],
            'order_no'  => trim((string) ($r['transaction_ref'] ?? '')),
            'customer'  => $full,
            'courses'   => $courses !== '' ? explode("\n", $courses) : [],
            'total'     => (float) ($r['total_price'] ?? 0),
            'status'    => (string) ($r['payment_status'] ?? '0'),
            'created'   => $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '',
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListOrder Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
