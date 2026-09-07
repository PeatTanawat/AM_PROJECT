<?php
// คำสั่งซื้อรอยืนยัน — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// ขอบเขตคงที่: payment_status='0' (รอชำระเงิน) AND payment_method='2' (โอนเงินผ่านธนาคาร)
// คืน JSON { list, total, page, per_page } -> หน้า order_pending นำไป render ผ่าน view/listOrder/ViewPending.php

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

$page = max(1, (int) ($_POST['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ฟิลเตอร์จากฟอร์มด้านบน (สถานะ/วิธีชำระถูกบังคับไว้แล้ว ไม่รับจากผู้ใช้)
$f_order = trim((string) ($_POST['f_order'] ?? ''));     // หมายเลขคำสั่งซื้อ (transaction_ref)
$f_customer = trim((string) ($_POST['f_customer'] ?? ''));  // ชื่อลูกค้า
$f_date = trim((string) ($_POST['f_date'] ?? ''));      // วันที่สั่งซื้อ (Y-m-d หรือ d/m/Y)

$joins = "FROM tbl_orders o
          LEFT JOIN tbl_user u ON o.user_id = u.user_id";

// เงื่อนไขฐาน (บังคับเสมอ) + ฟิลเตอร์ของผู้ใช้
$where = ["o.payment_status = '0'", "o.payment_method = '3'"];
$params = [];
if ($f_order !== '') {
    $where[] = "o.transaction_ref LIKE :f_order";
    $params[':f_order'] = '%' . $f_order . '%';
}
if ($f_customer !== '') {
    $where[] = "CONCAT_WS(' ', u.user_firstname, u.user_lastname) LIKE :f_customer";
    $params[':f_customer'] = '%' . $f_customer . '%';
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
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {

    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) $joins $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน (เรียงคงที่ ใหม่สุดก่อน; ดึงชื่อคอร์สด้วย subquery GROUP_CONCAT กันแถวซ้ำจากการ join detail)
    $sql = "SELECT o.order_id, o.transaction_ref, o.total_price, o.created_at,
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
        $full = trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''));

        // ชื่อคอร์ส (อาจหลายรายการ -> คั่นด้วย \n ให้ view จัดการ)
        $courses = trim((string) ($r['course_names'] ?? ''));

        $list[] = [
            'order_id' => (int) $r['order_id'],
            'ref' => trim((string) ($r['transaction_ref'] ?? '')),
            'customer' => $full !== '' ? $full : '',
            'courses' => $courses,
            'total' => number_format((float) ($r['total_price'] ?? 0), 2) . ' บาท',
            'created' => $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '',
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListPending Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
