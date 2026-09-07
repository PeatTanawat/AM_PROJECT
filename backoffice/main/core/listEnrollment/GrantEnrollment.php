<?php
// ให้สิทธิ์การใช้งาน (enroll_access = 1)

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
    Response::json(0, 'ข้อมูลไม่ถูกต้อง', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // อัปเดต enroll_access = 1
    $stmt = $pdo_connect->prepare("UPDATE tbl_course_enrollment SET enroll_access = '1' WHERE enroll_id = :enroll_id");
    $stmt->execute([':enroll_id' => $enroll_id]);

    if ($stmt->rowCount() > 0) {
        Response::json(1, 'ให้สิทธิ์การใช้งานสำเร็จ', null);
    } else {
        Response::json(0, 'ไม่พบรายการที่ต้องการ หรือมีสิทธิ์การใช้งานอยู่แล้ว', null);
    }
} catch (\Throwable $e) {
    error_log('GrantEnrollment Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการให้สิทธิ์', null);
}
