<?php

use App\Database\Connection;
use App\Utility\Email;
use App\Utility\Response;

/**
 * ฟังก์ชัน Render HTML Template สำหรับอีเมลแจ้งเตือนคอร์สใกล้หมดอายุ
 * ดึงมาจากไฟล์ view/email/course_expiry_template.php เพื่อให้แก้ไขรูปแบบ HTML ได้ง่าย
 *
 * @param string $fullName
 * @param array $expiringCourses
 * @return string
 */
if (!function_exists('renderCourseExpiryEmail')) {
    function renderCourseExpiryEmail(string $fullName, array $expiringCourses): string
    {
        $templateFile = __DIR__ . '/../../view/email/course_expiry_template.php';

        if (file_exists($templateFile)) {
            ob_start();
            include $templateFile;
            return ob_get_clean();
        }

        return '';
    }
}

if (!function_exists('sendCourseExpiryNotification')) {
    function sendCourseExpiryNotification(PDO $pdo, string $userEmail, string $userFirstname, string $userLastname, array $expiringCourses): void
    {
        if (empty($userEmail) || empty($expiringCourses)) {
            return;
        }

        $notifiedEnrollIds = array_column($expiringCourses, 'enroll_id');
        $subject  = "แจ้งเตือน: คอร์สเรียนของคุณกำลังจะหมดอายุ - CPDTH";
        $fullName = trim($userFirstname . ' ' . $userLastname);

        $emailBody = renderCourseExpiryEmail($fullName, $expiringCourses);

        if (!empty($emailBody)) {
            $sent = Email::send($userEmail, $subject, $emailBody);

            if ($sent && !empty($notifiedEnrollIds)) {
                $inClause = implode(',', array_map('intval', $notifiedEnrollIds));
                $pdo->exec("UPDATE tbl_course_enrollment SET send_email = NOW() WHERE enroll_id IN ($inClause)");
            }
        }
    }
}

/**
 * ฟังก์ชันหลักสำหรับตรวจสอบคอร์สเรียนที่ใกล้หมดอายุ (ภายใน 10 วัน) ของผู้ใช้งาน
 * ส่งอีเมลแจ้งเตือน และบันทึกวันที่ส่งอีเมลลงคอลัมน์ send_email เพื่อไม่ให้เช็คซ้ำอีก
 *
 * @param PDO $pdo
 * @param int $userId
 * @param string $userEmail
 * @param string $userFirstname
 * @param string $userLastname
 * @return array
 */
if (!function_exists('runCheckCourseExpiry')) {
    function runCheckCourseExpiry(PDO $pdo, int $userId, string $userEmail, string $userFirstname = '', string $userLastname = ''): array
    {
        $expiringCourses = [];
        $notifiedEnrollIds = [];

        try {

            // ดึงคอร์สเรียนของผู้ใช้ที่มีระยะเวลาคงเหลือ 0 ถึง 10 วัน และยังไม่เคยส่งอีเมลแจ้งเตือนมาก่อน (send_email IS NULL)
            $sql = "
                SELECT 
                    e.enroll_id,
                    e.enroll_date,
                    e.enroll_expiry_date,
                    c.course_name,
                    DATEDIFF(DATE(e.enroll_expiry_date), CURRENT_DATE()) AS days_remaining
                FROM tbl_course_enrollment e
                JOIN tbl_course c ON e.enroll_course_id = c.course_id
                WHERE e.enroll_user_id = :user_id
                  AND e.delete_at IS NULL
                  AND c.delete_at IS NULL
                  AND (e.enroll_access = '1' OR e.enroll_payment_status = 'paid')
                  AND e.enroll_expiry_date IS NOT NULL
                  AND e.send_email IS NULL
                  AND DATEDIFF(DATE(e.enroll_expiry_date), CURRENT_DATE()) BETWEEN 0 AND 10
                ORDER BY days_remaining ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (empty($rows)) {
                return [];
            }

            foreach ($rows as $row) {
                $notifiedEnrollIds[] = (int) $row['enroll_id'];
                $expiringCourses[]   = [
                    'enroll_id'      => (int) $row['enroll_id'],
                    'course_name'    => $row['course_name'],
                    'enroll_date'    => !empty($row['enroll_date']) ? date('d/m/Y', strtotime($row['enroll_date'])) : '-',
                    'expiry_date'    => !empty($row['enroll_expiry_date']) ? date('d/m/Y', strtotime($row['enroll_expiry_date'])) : '-',
                    'days_remaining' => (int) $row['days_remaining']
                ];
            }

            // ส่งอีเมลแจ้งเตือนผู้ใช้งานโดยดึง HTML จาก Template
            if (!empty($userEmail) && !empty($expiringCourses)) {
                $subject  = "แจ้งเตือน: คอร์สเรียนของคุณกำลังจะหมดอายุ - CPDTH";
                $fullName = trim($userFirstname . ' ' . $userLastname);

                $emailBody = renderCourseExpiryEmail($fullName, $expiringCourses);

                if (!empty($emailBody)) {
                    $sent = Email::send($userEmail, $subject, $emailBody);

                    // หากส่งอีเมลสำเร็จ ให้อัปเดตวันที่ลงช่อง send_email เพื่อไม่ให้เช็ค/ส่งซ้ำอีกในการเข้าสู่ระบบครั้งถัดไป
                    if ($sent && !empty($notifiedEnrollIds)) {
                        $inClause = implode(',', array_map('intval', $notifiedEnrollIds));
                        $pdo->exec("UPDATE tbl_course_enrollment SET send_email = NOW() WHERE enroll_id IN ($inClause)");
                    }
                }
            }

        } catch (\Throwable $e) {
            error_log("CheckCourseExpiry Error: " . $e->getMessage());
        }

        return $expiringCourses;
    }
}

// หากถูกเรียกโดยตรงในฐานะ Core API Route (request_state = 'login', request_function = 'check_course_expiry')
if (isset($_POST['request_function']) && $_POST['request_function'] === 'check_course_expiry') {
    try {
        $db_instance = new Connection();
        $pdo_connect = $db_instance->getPdo();

        if (!$pdo_connect) {
            Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
        }

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($userId <= 0) {
            Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
        }

        $stmt = $pdo_connect->prepare("SELECT user_id, user_email, user_firstname, user_lastname FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$user) {
            Response::json(0, 'ไม่พบผู้ใช้นี้', null);
        }

        $expiring = runCheckCourseExpiry($pdo_connect, $user['user_id'], $user['user_email'] ?? '', $user['user_firstname'] ?? '', $user['user_lastname'] ?? '');

        Response::json(1, 'Success', ['expiring_courses' => $expiring]);
    } catch (Exception $e) {
        Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
    }
}
