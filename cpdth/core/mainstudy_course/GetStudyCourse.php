<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;

try {
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    $enroll_id = isset($_POST['enroll_id']) ? (int)$_POST['enroll_id'] : 0;
    if ($enroll_id <= 0) {
        Response::json(0, 'ไม่พบรหัสการลงทะเบียนเรียน', null);
    }

    $db_instance = new Connection();
    $db = $db_instance->getPdo();

    if (!$db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 1.5 ดึงสิทธิ์เรียนและเช็คเจ้าของสิทธิ์
    $sql_enroll = "SELECT enroll_id, enroll_user_id, enroll_course_id, enroll_expiry_date, enroll_is_completed, is_locked FROM tbl_course_enrollment 
                   WHERE enroll_id = :enroll_id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_enroll);
    $stmt->execute([':enroll_id' => $enroll_id]);
    $enroll = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$enroll || (int)$enroll['enroll_user_id'] !== (int)$user_id) {
        Response::json(0, 'คุณไม่มีสิทธิ์เข้าถึงคอร์สเรียนนี้ หรือไม่ใช่เจ้าของสิทธิ์การลงทะเบียน', null);
    }

    $course_id = (int)$enroll['enroll_course_id'];

    // 2. ดึงข้อมูลคอร์สเรียน
    $sql_course = "SELECT * FROM tbl_course WHERE course_id = :course_id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_course);
    $stmt->execute([':course_id' => $course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$course) {
        Response::json(0, 'ไม่พบคอร์สเรียนนี้ในระบบ', null);
    }

    if (!empty($course['course_cover_image'])) {
        $course['course_cover_image'] = AwsS3::getFileUrl($course['course_cover_image'], '+30 minutes', false);
    }

    // 3. ดึงบทเรียนทั้งหมดของคอร์สนี้ พร้อมเช็คความก้าวหน้าการเรียน และดึงไฟล์เอกสารประกอบบทเรียน
    $sql_lessons = "SELECT l.*,
                           p.progress_status AS progress_status,
                           f.lesson_file_name, 
                           f.lesson_file_type, 
                           f.lesson_file_id
                    FROM tbl_lesson l   
                    LEFT JOIN tbl_lesson_progress p ON l.lesson_id = p.progress_lesson_id AND p.progress_user_id = :user_id AND p.progress_enrollment_id = :enroll_id
                    LEFT JOIN tbl_lesson_file f ON l.lesson_id = f.lesson_id AND f.delete_at IS NULL
                        WHERE l.course_id = :course_id AND l.delete_at IS NULL 
                    ORDER BY l.lesson_order ASC";
    $stmt = $db->prepare($sql_lessons);
    $stmt->execute([
        ':course_id' => $course_id,
        ':user_id' => $user_id,
        ':enroll_id' => $enroll_id
    ]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $total_lessons = count($lessons);

    $expiry_text = '';
    $is_expired = false;
    if ($enroll && !empty($enroll['enroll_expiry_date'])) {
        $expiry_time = strtotime($enroll['enroll_expiry_date']);
        if ($expiry_time < time()) {
            $is_expired = true;
        }
        $expiry_text = date('d/m/Y H:i', $expiry_time) . ' น.';
    }

    // เช็คสิทธิ์การเข้าทำข้อสอบ (ต้องเรียนให้ครบทุกบทเรียนก่อน)
    $sql_done = "SELECT COUNT(p.progress_id) 
                 FROM tbl_lesson_progress p
                 JOIN tbl_lesson l ON p.progress_lesson_id = l.lesson_id   
                 WHERE l.course_id = :cid 
                 AND p.progress_user_id = :uid 
                 AND p.progress_enrollment_id = :enroll_id
                 AND p.progress_status = '1'";
    $stmt = $db->prepare($sql_done);
    $stmt->execute([':cid' => $course_id, ':uid' => $user_id, ':enroll_id' => $enroll_id]);
    $finished_lessons = $stmt->fetchColumn();
    $stmt->closeCursor();

    $can_take_exam = ($total_lessons > 0 && $total_lessons == $finished_lessons);

    // ดึงข้อมูลประวัติการสอบของ user สำหรับคอร์สนี้
    $sql_attempts = "SELECT * FROM tbl_exam_attempt 
                     WHERE attempt_user_id = :user_id AND attempt_course_id = :course_id AND attempt_enroll_id = :enroll_id
                     ORDER BY create_at ";
    $stmt = $db->prepare($sql_attempts);
    $stmt->execute([
        ':user_id' => $user_id,
        ':course_id' => $course_id,
        ':enroll_id' => $enroll_id
    ]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    Response::json(1, 'Success', [
        'course' => $course,
        'lessons' => $lessons,
        'course_instructor' => $course['course_instructor'] ?? 'ไม่ระบุ',
        'total_lessons' => $total_lessons,
        'expiry_text' => $expiry_text,
        'is_expired' => $is_expired,
        'course_id' => $course_id,
        'enroll_id' => $enroll_id,
        'is_approved' => (isset($enroll['enroll_is_completed']) && (string)$enroll['enroll_is_completed'] === '1'),
        'is_locked' => (isset($enroll['is_locked']) && (string)$enroll['is_locked'] === '1'),
        'can_take_exam' => $can_take_exam,
        'attempts' => $attempts
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
