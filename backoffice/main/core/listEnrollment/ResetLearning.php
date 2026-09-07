<?php
// รีเซ็ตการเรียน (is_locked = 0, เคลียร์ความคืบหน้าการเรียน)

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
    $pdo_connect->beginTransaction();

    // ดึงข้อมูล user_id และ course_id จาก enrollment
    $stmtSel = $pdo_connect->prepare("SELECT enroll_user_id, enroll_course_id FROM tbl_course_enrollment WHERE enroll_id = :enroll_id LIMIT 1");
    $stmtSel->execute([':enroll_id' => $enroll_id]);
    $enrollData = $stmtSel->fetch(PDO::FETCH_ASSOC);

    if ($enrollData) {
        // อัปเดต is_locked = 0, เคลียร์วันที่ล็อก, และบันทึกวันที่ปลดล็อก
        $stmt = $pdo_connect->prepare("UPDATE tbl_course_enrollment SET is_locked = '0', locked_at = NULL, unlock_at = NOW() WHERE enroll_id = :enroll_id");
        $stmt->execute([':enroll_id' => $enroll_id]);

        $user_id = $enrollData['enroll_user_id'];
        $course_id = $enrollData['enroll_course_id'];

        // ล้างข้อมูลการเรียนใน tbl_lesson_progress สำหรับผู้ใช้และคอร์สนี้
        $stmtDel = $pdo_connect->prepare("
            DELETE FROM tbl_lesson_progress 
            WHERE progress_user_id = :user_id 
            AND progress_lesson_id IN (SELECT lesson_id FROM tbl_lesson WHERE course_id = :course_id)
        ");
        $stmtDel->execute([':user_id' => $user_id, ':course_id' => $course_id]);
    }

    $pdo_connect->commit();
    Response::json(1, 'รีเซ็ตการเรียนสำเร็จ', null);

} catch (\Throwable $e) {
    $pdo_connect->rollBack();
    error_log('ResetLearning Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการรีเซ็ตการเรียน', null);
}
