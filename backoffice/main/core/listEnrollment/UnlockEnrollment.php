<?php
// ปลดล็อกสิทธิ์การสอบ (is_locked = 0)

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
    // อัปเดต is_locked = 0, เคลียร์วันที่ล็อก, และบันทึกวันที่ปลดล็อก
    $stmt = $pdo_connect->prepare("UPDATE tbl_course_enrollment SET is_locked = '0', locked_at = NULL, unlock_at = NOW() WHERE enroll_id = :enroll_id");
    $stmt->execute([':enroll_id' => $enroll_id]);

    if ($stmt->rowCount() > 0) {
        // หากต้องการลบประวัติการสอบด้วย เพื่อให้เริ่มนับจำนวนครั้งใหม่ สามารถใส่โค้ดลบ tbl_exam_attempt ได้ที่นี่
        
        Response::json(1, 'ปลดล็อกสิทธิ์สำเร็จ', null);
    } else {
        Response::json(0, 'ไม่พบรายการที่ต้องการปลดล็อก หรือสถานะปกติอยู่แล้ว', null);
    }
} catch (\Throwable $e) {
    error_log('UnlockEnrollment Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการปลดล็อก', null);
}
