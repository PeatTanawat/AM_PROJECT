<?php
// คอร์สเรียนคงเหลือในระบบ — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// คืน JSON { list, total, page, per_page } -> หน้า course_remaining นำไป render ผ่าน view/listEnrollment/ViewData.php

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

$f_course = trim((string) ($_POST['f_course'] ?? ''));   // enroll_course_id
$f_member = trim((string) ($_POST['f_member'] ?? ''));   // enroll_user_id หรือ ชื่อสมาชิก
$f_status = trim((string) ($_POST['f_status'] ?? ''));   // '' = ทั้งหมด, '1' = ใช้งาน, '0' = ยกเลิก

$joins = "FROM tbl_course_enrollment e
          LEFT JOIN tbl_user u   ON e.enroll_user_id = u.user_id
          LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id";

$where  = ["e.delete_at IS NULL"];
$params = [];
if ($f_course !== '' && ctype_digit($f_course)) {
    $where[] = "e.enroll_course_id = :f_course";
    $params[':f_course'] = (int) $f_course;
}
if ($f_member !== '') {
    if (ctype_digit($f_member)) {
        $where[] = "e.enroll_user_id = :f_member";
        $params[':f_member'] = (int) $f_member;
    } else {
        $where[] = "CONCAT_WS(' ', u.user_firstname, u.user_lastname) LIKE :f_member";
        $params[':f_member'] = '%' . $f_member . '%';
    }
}
if ($f_status === '0' || $f_status === '1') {
    $where[] = "e.enroll_access = :f_status";
    $params[':f_status'] = $f_status;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) $joins $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน (เรียงคงที่ ใหม่สุดก่อน)
    $sql = "SELECT e.enroll_id, e.enroll_user_id, e.enroll_course_id, e.enroll_payment_status, e.enroll_date, e.enroll_expiry_date,
                   e.enroll_is_completed, e.enroll_access, e.is_locked, e.create_at,
                   u.user_firstname, u.user_lastname, u.user_phone,
                   c.course_name, c.course_price, c.course_promotion
            $joins
            $where_sql
            ORDER BY e.enroll_id DESC
            LIMIT :offset, :per_page";
    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $fmt = fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '';
    $now = time();

    $list = [];
    foreach ($rows as $r) {
        $full  = trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''));
        $buy   = $r['create_at'] ?: $r['enroll_date'];
        $open  = $r['enroll_date'] ?: $r['create_at'];
        $exp   = $r['enroll_expiry_date'] ?? null;

        // อายุคงเหลือ (วัน) — ส่งค่าดิบให้ view ตัดสินใจ render (คำนวณความต่างระดับวันที่)
        $remain_days = null;
        if ($exp) {
            $exp_dt   = new DateTime(date('Y-m-d', strtotime($exp)));
            $today_dt = new DateTime(date('Y-m-d'));
            $remain_days = (int) $today_dt->diff($exp_dt)->format("%r%a");
        }

        // ราคา (ใช้ราคาโปรถ้ามี ไม่งั้นราคาปกติ)
        $promo = (float) ($r['course_promotion'] ?? 0);
        $price = ($promo > 0) ? $promo : (float) ($r['course_price'] ?? 0);

        $list[] = [
            'enroll_id'        => (int) $r['enroll_id'],
            'enroll_user_id'   => (int) $r['enroll_user_id'],
            'enroll_course_id' => (int) $r['enroll_course_id'],
            'member'           => $full !== '' ? $full : '',
            'phone'            => $r['user_phone'] ?? '',
            'course'           => $r['course_name'] ?? '',
            'buy_at'           => $fmt($buy),
            'open_at'          => $fmt($open),
            'expiry'           => $exp ? $fmt($exp) : '',
            'remain_days'      => $remain_days,
            'price'            => number_format($price, 2),
            'is_completed'     => (string) ($r['enroll_is_completed'] ?? '0'),
            'status'           => (string) ($r['enroll_access'] ?? '1'),   // 1=ใช้งาน, 0=ยกเลิก
            'is_locked'        => (string) ($r['is_locked'] ?? '0'),
            'expiry_raw'       => $exp ? date('d/m/Y', strtotime($exp)) : '',
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListEnrollment Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
