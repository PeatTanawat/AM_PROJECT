<?php
// ส่งอีเมลแจ้งเตือนคอร์สเรียนใกล้หมดอายุ

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\Email;
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
    // ดึง user_id จาก enroll_id ที่เลือกก่อน
    $stmt_u = $pdo_connect->prepare(
        "SELECT enroll_user_id FROM tbl_course_enrollment WHERE enroll_id = :enroll_id AND delete_at IS NULL LIMIT 1"
    );
    $stmt_u->execute([':enroll_id' => $enroll_id]);
    $uid = $stmt_u->fetchColumn();
    $stmt_u->closeCursor();

    if (!$uid) {
        Response::json(0, 'ไม่พบข้อมูลสิทธิ์คอร์สเรียนนี้', null);
    }

    // ดึงข้อมูลคอร์สเรียนทั้งหมดของลูกค้ารายนี้ที่ใกล้หมดอายุใน 10 วัน
    $stmt_all = $pdo_connect->prepare(
        "SELECT e.enroll_id, e.enroll_expiry_date, e.enroll_date, e.create_at,
                u.user_firstname, u.user_lastname, u.user_email,
                c.course_name
         FROM tbl_course_enrollment e
         LEFT JOIN tbl_user u ON e.enroll_user_id = u.user_id
         LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
         WHERE e.enroll_user_id = :uid 
           AND e.enroll_access = '1'
           AND e.delete_at IS NULL 
           AND e.enroll_expiry_date IS NOT NULL
           AND e.enroll_expiry_date >= CURDATE()
           AND e.enroll_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 10 DAY)"
    );
    $stmt_all->execute([':uid' => $uid]);
    $all_enrolls = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    $stmt_all->closeCursor();

    if (empty($all_enrolls)) {
        Response::json(0, 'ไม่พบคอร์สเรียนที่ใกล้หมดอายุของผู้เรียนรายนี้', null);
    }

    $email = '';
    $fullName = '';
    $now = time();
    $expiringCourses = [];

    foreach ($all_enrolls as $row) {
        if ($email === '') {
            $email = trim((string) ($row['user_email'] ?? ''));
            $fullName = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        }
        $exp = $row['enroll_expiry_date'];
        $exp_dt   = new DateTime(date('Y-m-d', strtotime($exp)));
        $today_dt = new DateTime(date('Y-m-d'));
        $remain_days = (int) $today_dt->diff($exp_dt)->format("%r%a");

        $expiringCourses[] = [
            'course_name'    => $row['course_name'] ?? 'คอร์สเรียน',
            'enroll_date'    => $row['enroll_date'] ? date('d/m/Y', strtotime($row['enroll_date'])) : date('d/m/Y', strtotime($row['create_at'])),
            'expiry_date'    => date('d/m/Y', strtotime($exp)),
            'days_remaining' => $remain_days
        ];
    }

    if ($email === '') {
        Response::json(0, 'สมาชิกรายนี้ไม่มีอีเมลในระบบ', null);
    }

    if ($fullName === '') {
        $fullName = 'ผู้ใช้งาน';
    }

    // สร้างเนื้อหาอีเมลจาก Template
    ob_start();
    include dirname(__DIR__, 2) . '/view/email/course_expiry_template.php';
    $body = ob_get_clean();

    $subject = 'แจ้งเตือนคอร์สเรียนใกล้หมดอายุ - CPDTH';

    $ok = Email::send($email, $subject, $body, true);

    if ($ok) {
        Response::json(1, 'ส่งอีเมลแจ้งเตือนไปยัง ' . $email . ' สำเร็จ (' . count($expiringCourses) . ' คอร์ส)', null);
    } else {
        Response::json(0, 'ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่า SMTP', null);
    }

} catch (\Throwable $e) {
    error_log('SendExpiryEmail Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการส่งอีเมล', null);
}
