<?php
// ประวัติการยืนยันตัวตน — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// สถานะจาก tbl_identity_verification_log.action_type (1=ผ่านแล้ว, 2=ยกเลิกการยืนยัน)
// คืน JSON { list, total, page, per_page } -> หน้า verify_history นำไป render ผ่าน view/verifyHistory/ViewData.php

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

$search = trim((string) ($_POST['search'] ?? ''));

$joins = "FROM tbl_identity_verification_log l
          LEFT JOIN tbl_user u ON l.user_id = u.user_id
          LEFT JOIN tbl_user a ON l.create_user_id = a.user_id";

$where  = '';
$params = [];
if ($search !== '') {
    // ใช้ placeholder แยกกัน (:s1/:s2/:s3) — PDO ห้ามใช้ชื่อซ้ำเมื่อปิด emulate prepares
    $where = "WHERE (CONCAT_WS(' ', u.user_firstname, u.user_lastname) LIKE :s1
                  OR u.user_citizen_id LIKE :s2
                  OR l.remark LIKE :s3)";
    $like = '%' . $search . '%';
    $params = [':s1' => $like, ':s2' => $like, ':s3' => $like];
}

try {
    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) $joins $where");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน (เรียงคงที่ ใหม่สุดก่อน)
    $sql = "SELECT l.action_type, l.remark,
                   u.user_firstname, u.user_lastname, u.user_citizen_id,
                   a.user_firstname AS admin_firstname, a.user_lastname AS admin_lastname
            $joins
            $where
            ORDER BY l.log_id DESC
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
        $full  = trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''));
        $admin = trim(($r['admin_firstname'] ?? '') . ' ' . ($r['admin_lastname'] ?? ''));

        $list[] = [
            'full_name'   => $full !== '' ? $full : '',
            'citizen_id'  => $r['user_citizen_id'] ?? '',
            'remark'      => $r['remark'] ?? '',
            'action_type' => (string) ($r['action_type'] ?? '0'),
            'admin_name'  => $admin !== '' ? $admin : '',
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListHistory Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
