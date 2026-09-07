<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($target_id <= 0) {
    Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ข้อมูลผู้ใช้ (เฉพาะที่ยังไม่ถูกลบ)
$stmt = $pdo_connect->prepare(
    "SELECT user_id, user_prefix, user_firstname, user_lastname, user_email,
            user_phone, user_citizen_id, user_cpd_no, user_cpa_no, user_status, identity_verified
     FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $target_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$user) {
    Response::json(0, 'ไม่พบผู้ใช้นี้', null);
}

// Fetch all exam attempts for the user to group by enroll_id
$stmt_all_att = $pdo_connect->prepare(
    "SELECT *
     FROM tbl_exam_attempt
     WHERE attempt_user_id = :id
     ORDER BY attempt_id ASC"
);
$stmt_all_att->execute([':id' => $target_id]);
$all_attempts = $stmt_all_att->fetchAll(PDO::FETCH_ASSOC);
$stmt_all_att->closeCursor();

$attempts_by_enroll = [];
foreach ($all_attempts as $a) {
    $eid = $a['attempt_enroll_id'] ?? 0;
    if (!isset($attempts_by_enroll[$eid])) {
        $attempts_by_enroll[$eid] = [];
    }
    $attempts_by_enroll[$eid][] = $a;
}

// แท็บ "สิทธิ์เข้าคอร์สเรียน" — tbl_course_enrollment
$stmt_enroll = $pdo_connect->prepare(
    "SELECT e.enroll_id, e.enroll_course_id, e.enroll_date, e.enroll_expiry_date, e.is_locked, e.enroll_access, c.course_name,
            c.course_code_cpd_1, c.course_code_cpa_1, c.course_number_time
     FROM tbl_course_enrollment e
     LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
     WHERE e.enroll_user_id = :id AND e.delete_at IS NULL
     ORDER BY e.enroll_id DESC"
);
$stmt_enroll->execute([':id' => $target_id]);
$enroll_rows = $stmt_enroll->fetchAll(PDO::FETCH_ASSOC);
$stmt_enroll->closeCursor();

$enrollments = [];
foreach ($enroll_rows as $r) {
    $cpd = trim(str_replace(['[', ']'], '', (string)($r['course_code_cpd_1'] ?? '')));
    $cpa = trim(str_replace(['[', ']'], '', (string)($r['course_code_cpa_1'] ?? '')));
    $sku_parts = [];
    if ($cpd !== '') { $sku_parts[] = 'CPD: ' . $cpd; }
    if ($cpa !== '') { $sku_parts[] = 'CPA: ' . $cpa; }
    
    $eid = $r['enroll_id'];
    $cid = $r['enroll_course_id'];
    $enrollments[] = [
        'enroll_id'          => $eid,
        'course_name'        => $r['course_name'] ?? null,
        'sku'                => count($sku_parts) > 0 ? implode(' / ', $sku_parts) : null,
        'enroll_date'        => $r['enroll_date'] ?? null,
        'enroll_expiry_date' => $r['enroll_expiry_date'] ?? null,
        'course_number_time' => (int)($r['course_number_time'] ?? 0),
        'is_locked'          => (int)($r['is_locked'] ?? 0),
        'enroll_access'      => (int)($r['enroll_access'] ?? 1),
        'attempts'           => $attempts_by_enroll[$eid] ?? [],
    ];
}

// แท็บ "ประวัติการสอบ/ใบรับรอง" — tbl_exam_attempt
$stmt_exam = $pdo_connect->prepare(
    "SELECT a.attempt_id, a.attempt_score, a.attempt_pass, c.course_name
     FROM tbl_exam_attempt a
     LEFT JOIN tbl_course c ON a.attempt_course_id = c.course_id
     WHERE a.attempt_user_id = :id
     ORDER BY a.attempt_id DESC"
);
$stmt_exam->execute([':id' => $target_id]);
$exam_rows = $stmt_exam->fetchAll(PDO::FETCH_ASSOC);
$stmt_exam->closeCursor();

$exams = [];
foreach ($exam_rows as $r) {
    $exams[] = [
        'course_name' => $r['course_name'] ?? null,
        'score'       => $r['attempt_score'] ?? null,
        'pass'        => (string)($r['attempt_pass'] ?? '0'),
    ];
}

// แท็บ "ประวัติการยืนยันตัวตน" — tbl_identity_verification_log (action_type: 1=อนุมัติ, 2=ยกเลิก)
$stmt_verify = $pdo_connect->prepare(
    "SELECT l.action_type, l.remark, l.created_at,
            a.user_firstname AS admin_firstname, a.user_lastname AS admin_lastname
     FROM tbl_identity_verification_log l
     LEFT JOIN tbl_user a ON l.create_user_id = a.user_id
     WHERE l.user_id = :id
     ORDER BY l.log_id DESC"
);
$stmt_verify->execute([':id' => $target_id]);
$verify_rows = $stmt_verify->fetchAll(PDO::FETCH_ASSOC);
$stmt_verify->closeCursor();

$verify_history = [];
foreach ($verify_rows as $r) {
    $admin = trim(($r['admin_firstname'] ?? '') . ' ' . ($r['admin_lastname'] ?? ''));
    $verify_history[] = [
        'action_type' => (string) ($r['action_type'] ?? '0'),
        'remark'      => $r['remark'] ?? '',
        'admin_name'  => $admin !== '' ? $admin : 'ระบบ',
        'created_at'  => $r['created_at'] ?? '',
    ];
}

// แท็บ "คำสั่งซื้อ"
$stmt_order = $pdo_connect->prepare(
    "SELECT o.order_id, o.transaction_ref AS order_code, o.total_price, o.payment_status, c.course_name
     FROM tbl_orders o
     LEFT JOIN tbl_order_detail od ON o.order_id = od.order_id
     LEFT JOIN tbl_course c ON od.course_id = c.course_id
     WHERE o.user_id = :id
     ORDER BY o.order_id DESC"
);
$stmt_order->execute([':id' => $target_id]);
$order_rows = $stmt_order->fetchAll(PDO::FETCH_ASSOC);
$stmt_order->closeCursor();

$orders_grouped = [];
foreach ($order_rows as $r) {
    $oid = $r['order_id'];
    if (!isset($orders_grouped[$oid])) {
        $orders_grouped[$oid] = [
            'order_code' => $r['order_code'],
            'total_price' => (float)$r['total_price'],
            'payment_status' => (string)$r['payment_status'],
            'courses' => []
        ];
    }
    if (!empty($r['course_name'])) {
        $orders_grouped[$oid]['courses'][] = $r['course_name'];
    }
}
$orders = array_values($orders_grouped);

Response::json(1, 'Success', [
    'user'           => $user,
    'orders'         => $orders,
    'enrollments'    => $enrollments,
    'exams'          => $exams,
    'verify_history' => $verify_history,
]);
