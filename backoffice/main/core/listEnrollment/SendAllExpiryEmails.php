<?php
// ส่งอีเมลแจ้งเตือนคอร์สเรียนใกล้หมดอายุให้ทุกคนที่มีรายชื่อคอร์สหมดอายุใน 10 วัน

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\Email;
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

try {
    // ดึงข้อมูลคอร์สเรียนทั้งหมดในระบบที่ใกล้หมดอายุใน 10 วัน และจัดกลุ่มตามรายผู้ใช้
    $sql = "SELECT e.enroll_id, e.enroll_expiry_date, e.enroll_date, e.create_at, e.enroll_user_id,
                   u.user_firstname, u.user_lastname, u.user_email,
                   c.course_name
            FROM tbl_course_enrollment e
            LEFT JOIN tbl_user u ON e.enroll_user_id = u.user_id
            LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
            WHERE e.enroll_access = '1'
              AND e.delete_at IS NULL 
              AND e.enroll_expiry_date IS NOT NULL
              AND e.enroll_expiry_date >= CURDATE()
              AND e.enroll_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 10 DAY)
            ORDER BY e.enroll_user_id ASC, e.enroll_id DESC";
            
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($rows)) {
        Response::json(0, 'ไม่พบคอร์สเรียนที่ใกล้หมดอายุในระบบ ณ ขณะนี้', null);
    }

    // จัดกลุ่มรายการคอร์สเรียนตาม User ID
    $grouped = [];
    foreach ($rows as $r) {
        $uid = (int) $r['enroll_user_id'];
        if (!isset($grouped[$uid])) {
            $grouped[$uid] = [
                'email'    => trim((string) ($r['user_email'] ?? '')),
                'fullName' => trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? '')),
                'courses'  => []
            ];
        }
        
        $exp = $r['enroll_expiry_date'];
        $exp_dt   = new DateTime(date('Y-m-d', strtotime($exp)));
        $today_dt = new DateTime(date('Y-m-d'));
        $remain_days = (int) $today_dt->diff($exp_dt)->format("%r%a");

        $grouped[$uid]['courses'][] = [
            'course_name'    => $r['course_name'] ?? 'คอร์สเรียน',
            'enroll_date'    => $r['enroll_date'] ? date('d/m/Y', strtotime($r['enroll_date'])) : date('d/m/Y', strtotime($r['create_at'])),
            'expiry_date'    => date('d/m/Y', strtotime($exp)),
            'days_remaining' => $remain_days
        ];
    }

    $success_count = 0;
    $total_users = 0;
    $errors = [];

    foreach ($grouped as $uid => $user_data) {
        $email = $user_data['email'];
        if ($email === '') {
            $errors[] = "User ID #{$uid} ({$user_data['fullName']}): ไม่มีอีเมลในระบบ";
            continue;
        }

        $fullName = $user_data['fullName'] !== '' ? $user_data['fullName'] : 'ผู้ใช้งาน';
        $expiringCourses = $user_data['courses'];
        $total_users++;

        // สร้างเนื้อหาอีเมลจาก Template
        ob_start();
        include dirname(__DIR__, 2) . '/view/email/course_expiry_template.php';
        $body = ob_get_clean();

        $subject = 'แจ้งเตือนคอร์สเรียนใกล้หมดอายุ - CPDTH';

        $ok = Email::send($email, $subject, $body, true);
        if ($ok) {
            $success_count++;
        } else {
            $errors[] = "ส่งเมลไปยัง {$email} ล้มเหลว (ตรวจสอบ SMTP)";
        }
    }

    if ($success_count > 0) {
        $msg = "ส่งอีเมลแจ้งเตือนสำเร็จทั้งหมด {$success_count} จาก {$total_users} คน";
        if (!empty($errors)) {
            $msg .= " (ล้มเหลว " . count($errors) . " รายการ)";
        }
        Response::json(1, $msg, ['success_count' => $success_count, 'errors' => $errors]);
    } else {
        Response::json(0, 'ส่งอีเมลแจ้งเตือนล้มเหลวทั้งหมด: ' . implode(', ', $errors), null);
    }

} catch (\Throwable $e) {
    error_log('SendAllExpiryEmails Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการส่งอีเมลแจ้งเตือนแบบกลุ่ม', null);
}
