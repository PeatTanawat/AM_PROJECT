<?php
// เพิ่มสิทธิ์การเข้าถึงคอร์สเรียนให้สมาชิก -> INSERT tbl_course_enrollment

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\Email;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$user_id   = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
$expiry    = trim((string) ($_POST['expiry'] ?? '')); // d/m/Y หรือว่าง (= ไม่มีกำหนด)

if ($user_id <= 0)   { Response::json(0, 'กรุณาเลือกสมาชิก', null); }
if ($course_id <= 0) { Response::json(0, 'กรุณาเลือกคอร์สเรียน', null); }

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// แปลงวันหมดอายุ d/m/Y -> Y-m-d 23:59:59 (ว่าง = NULL)
$exp = null;
if ($expiry !== '') {
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $expiry, $m)) {
        $exp = $m[3] . '-' . $m[2] . '-' . $m[1] . ' 23:59:59';
    } else {
        $exp = $expiry;
    }
}

// ตรวจว่ามีสมาชิก + คอร์สจริง
$cu = $pdo_connect->prepare("SELECT user_id FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1");
$cu->execute([':id' => $user_id]);
if (!$cu->fetchColumn()) { $cu->closeCursor(); Response::json(0, 'ไม่พบสมาชิกนี้', null); }
$cu->closeCursor();

$cc = $pdo_connect->prepare("SELECT course_id FROM tbl_course WHERE course_id = :id AND delete_at IS NULL LIMIT 1");
$cc->execute([':id' => $course_id]);
if (!$cc->fetchColumn()) { $cc->closeCursor(); Response::json(0, 'ไม่พบคอร์สเรียนนี้', null); }
$cc->closeCursor();

// กันซ้ำ: สมาชิกมีสิทธิ์คอร์สนี้อยู่แล้ว (ยังไม่ลบ)
$dup = $pdo_connect->prepare("SELECT enroll_id FROM tbl_course_enrollment WHERE enroll_user_id = :u AND enroll_course_id = :c AND delete_at IS NULL LIMIT 1");
$dup->execute([':u' => $user_id, ':c' => $course_id]);
if ($dup->fetchColumn()) { $dup->closeCursor(); Response::json(0, 'สมาชิกรายนี้มีสิทธิ์คอร์สนี้อยู่แล้ว', null); }
$dup->closeCursor();

try {
    $sql = "INSERT INTO tbl_course_enrollment
                (enroll_user_id, enroll_course_id, enroll_payment_status, enroll_date, enroll_expiry_date, enroll_is_completed, enroll_access)
            VALUES (:u, :c, 'paid', NOW(), :exp, '0', '1')";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute([':u' => $user_id, ':c' => $course_id, ':exp' => $exp]);
    $stmt->closeCursor();

    // ส่งอีเมลแจ้งสมาชิก (best-effort ไม่ให้ล้มทั้งคำขอถ้าเมลส่งไม่ได้)
    $info = $pdo_connect->prepare(
        "SELECT u.user_email, u.user_firstname, u.user_lastname, c.course_name
         FROM tbl_user u, tbl_course c WHERE u.user_id = :u AND c.course_id = :c LIMIT 1"
    );
    $info->execute([':u' => $user_id, ':c' => $course_id]);
    $row = $info->fetch(PDO::FETCH_ASSOC);
    $info->closeCursor();

    $mail_sent = false;
    if ($row && !empty($row['user_email'])) {
        $name = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        $exp_txt = $exp ? date('d/m/Y', strtotime($exp)) : 'ไม่มีกำหนด';
        $body = '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;color:#222;">'
            . '<p>เรียน คุณ' . htmlspecialchars($name) . '</p>'
            . '<p>คุณได้รับสิทธิ์เข้าถึงคอร์สเรียน <b>' . htmlspecialchars((string) $row['course_name']) . '</b> เรียบร้อยแล้ว</p>'
            . '<p>วันหมดอายุ: ' . htmlspecialchars($exp_txt) . '</p>'
            . '<p>เข้าเรียนได้ที่เว็บไซต์ CPDTH</p>'
            . '<p style="color:#888;font-size:12px;">อีเมลฉบับนี้ส่งจากระบบ CPDTH โดยอัตโนมัติ</p></div>';
        $mail_sent = Email::send($row['user_email'], 'คุณได้รับสิทธิ์เข้าถึงคอร์สเรียน - CPDTH', $body, true);
    }

    Response::json(1, 'เพิ่มสิทธิ์สำเร็จ' . ($mail_sent ? ' และส่งอีเมลแจ้งสมาชิกแล้ว' : ' (ส่งอีเมลไม่สำเร็จ/ไม่มีอีเมล)'), null);
} catch (Throwable $e) {
    error_log('AddEnrollment Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
