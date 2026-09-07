<?php
use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

try {
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    $lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
    $enroll_id = isset($_POST['enroll_id']) ? (int)$_POST['enroll_id'] : 0;
    $last_sec = isset($_POST['last_sec']) ? (int)$_POST['last_sec'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '0';

    if ($lesson_id <= 0) {
        Response::json(0, 'ไม่พบรหัสบทเรียน', null);
    }

    $db_instance = new Connection();
    $db = $db_instance->getPdo();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // ค้นหาคอร์สของบทเรียนนี้
    $sql_course = "SELECT course_id FROM tbl_lesson WHERE lesson_id = :l AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_course);
    $stmt->execute([':l' => $lesson_id]);
    $course_id = (int)$stmt->fetchColumn();
    $stmt->closeCursor();

    if ($course_id <= 0) {
        Response::json(0, 'ไม่พบคอร์สเรียนของบทเรียนนี้', null);
    }

    // ตรวจเช็คสิทธิ์การลงทะเบียนเรียนในคอร์สนี้
    if ($enroll_id <= 0) {
        Response::json(0, 'ไม่พบรหัสการลงทะเบียน (enroll_id) กรุณาเข้าสู่บทเรียนใหม่อีกครั้ง', null);
    }
    
    $sql_enroll = "SELECT enroll_id, enroll_user_id FROM tbl_course_enrollment 
                   WHERE enroll_id = :enroll_id AND enroll_user_id = :user_id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_enroll);
    $stmt->execute([
        ':enroll_id' => $enroll_id,
        ':user_id' => $user_id
    ]);
    
    $enroll = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$enroll || (int)$enroll['enroll_user_id'] !== (int)$user_id) {
        Response::json(0, 'คุณไม่มีสิทธิ์บันทึกความคืบหน้าของบทเรียนนี้เนื่องจากไม่ใช่เจ้าของสิทธิ์การลงทะเบียน', null);
    }

    // เช็คว่ามีบันทึกความคืบหน้าอยู่แล้วหรือไม่
    $sql_check = "SELECT progress_id, progress_last_sec, progress_status FROM tbl_lesson_progress WHERE progress_user_id = :u AND progress_lesson_id = :l AND progress_enrollment_id = :eid LIMIT 1";
    $stmt = $db->prepare($sql_check);
    $stmt->execute([':u' => $user_id, ':l' => $lesson_id, ':eid' => $enroll['enroll_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $reset = isset($_POST['reset']) ? (int)$_POST['reset'] : 0;

    if ($row) {
        // ไม่ให้ค่า last_sec ถอยหลัง ยกเว้นกรณีสั่งรีเซ็ตจากบทลงโทษ
        $new_sec = ($reset === 1) ? $last_sec : max((int)$row['progress_last_sec'], $last_sec);
        
        // ไม่ให้ status ถอยหลังจาก 1 เป็น 0 ยกเว้นรีเซ็ต
        $new_status = $status;
        if ($reset !== 1 && $row['progress_status'] === '1') {
            $new_status = '1';
        }
        
        $sql_upd = "UPDATE tbl_lesson_progress 
                    SET progress_last_sec = :s, progress_status = :st, updated_at = NOW() 
                    WHERE progress_id = :pid";
        $stmt = $db->prepare($sql_upd);
        $stmt->execute([
            ':s' => $new_sec,
            ':st' => $new_status,
            ':pid' => $row['progress_id']
        ]);
        $stmt->closeCursor();
    } else {
        $sql_ins = "INSERT INTO tbl_lesson_progress 
                    (progress_user_id, progress_lesson_id, progress_enrollment_id, progress_last_sec, progress_status, updated_at) 
                    VALUES (:u, :l, :eid, :s, :st, NOW())"; 
        $stmt = $db->prepare($sql_ins);
        $stmt->execute([
            ':u'   => $user_id,
            ':l'   => $lesson_id,
            ':eid' => $enroll['enroll_id'],
            ':s'   => $last_sec,
            ':st'  => $status
        ]);
        $stmt->closeCursor();

        // บันทึก start_date เฉพาะครั้งแรกที่เริ่มเรียน (ถ้า start_date ยังว่างอยู่)
        try {
            $sql_upd_enroll = "UPDATE tbl_course_enrollment SET start_date = NOW() WHERE enroll_id = :eid AND start_date IS NULL";
            $stmt_upd = $db->prepare($sql_upd_enroll);
            $stmt_upd->execute([':eid' => $enroll['enroll_id']]);
            $stmt_upd->closeCursor();
        } catch (Exception $e) {
            error_log("Update start_date error: " . $e->getMessage());
        }
    }

    Response::json(1, 'Success', null);

} catch (Exception $e) {
    error_log("SaveStudyProgress Error: " . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
