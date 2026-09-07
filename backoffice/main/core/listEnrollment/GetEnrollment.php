<?php
// ดึงข้อมูล enrollment 1 รายการ (สำหรับ prefill โมดัลแก้ไขสิทธิ์)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$enroll_id = isset($_POST['enroll_id']) ? (int) $_POST['enroll_id'] : 0;
if ($enroll_id <= 0) {
    Response::json(0, 'ไม่พบรายการ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT e.enroll_id, e.enroll_expiry_date, e.enroll_is_completed, e.enroll_access, c.course_name,
            u.user_firstname, u.user_lastname
     FROM tbl_course_enrollment e
     LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
     LEFT JOIN tbl_user u ON e.enroll_user_id = u.user_id
     WHERE e.enroll_id = :id AND e.delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $enroll_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$row) {
    Response::json(0, 'ไม่พบรายการนี้ หรือถูกยกเลิกไปแล้ว', null);
}

Response::json(1, 'Success', [
    'enroll_id'  => (int) $row['enroll_id'],
    'status'     => (string) ($row['enroll_access'] ?? '1'),   // 1=ใช้งาน, 0=ยกเลิก
    'expiry'     => $row['enroll_expiry_date'] ? date('d/m/Y', strtotime($row['enroll_expiry_date'])) : '',
    'course'     => (string) ($row['course_name'] ?? '-'),
    'member'     => trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? '')),
]);
