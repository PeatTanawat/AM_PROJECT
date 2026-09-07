<?php
use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    // 1. ตรวจสอบการเข้าสู่ระบบ
    $currentUser = Auth::requireUserToken();
    $user_id = (int)$currentUser->user_id;

    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
    $enroll_id = isset($_POST['enroll_id']) ? (int)$_POST['enroll_id'] : 0;
    error_log("GetLesson: user=$user_id, course=$course_id, lesson=$lesson_id, post_enroll_id=$enroll_id");

    if ($course_id <= 0 || $lesson_id <= 0) {
        Response::json(0, 'ไม่พบรหัสคอร์สเรียนหรือบทเรียน', null);
    }

    $db_instance = new Connection();
    $db = $db_instance->getPdo();

    if (!$db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 1.5 ดึงข้อมูลคอร์สเรียนเพื่อเช็คการตั้งค่าคอร์ส (course_skip, course_otp, course_question, question_limit, question_time)
    $sql_course = "SELECT course_skip, course_otp, course_question, question_limit, question_time FROM tbl_course WHERE course_id = :course_id LIMIT 1";
    $stmt = $db->prepare($sql_course);
    $stmt->execute([':course_id' => $course_id]);
    $course_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $course_skip = $course_data ? (int)$course_data['course_skip'] : 0;
    $course_otp = $course_data ? (int)$course_data['course_otp'] : 0;
    $course_question = $course_data ? (int)$course_data['course_question'] : 1;
    $question_limit = $course_data && isset($course_data['question_limit']) ? (int)$course_data['question_limit'] : null;
    $question_time = $course_data && isset($course_data['question_time']) ? (int)$course_data['question_time'] : null;

    // 2. ดึงข้อมูลบทเรียน
    $sql_lesson = "SELECT * FROM tbl_lesson WHERE lesson_id = :lesson_id AND course_id = :course_id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_lesson);
    $stmt->execute([':lesson_id' => $lesson_id, ':course_id' => $course_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$lesson) {
        Response::json(0, 'ไม่พบบทเรียนนี้ในระบบ', null);
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
    error_log("GetLesson: fetched enroll_id = " . ($enroll ? $enroll['enroll_id'] : 'false'));

    if (!$enroll || (int)$enroll['enroll_user_id'] !== (int)$user_id) {
        Response::json(0, 'คุณไม่มีสิทธิ์เข้าถึงเนื้อหาบทเรียนนี้เนื่องจากไม่ใช่เจ้าของสิทธิ์การลงทะเบียน', null);
    }

    // 2.5 ดึงข้อมูลบทเรียนถัดไปและก่อนหน้า (รวมเป็น UNION เดียวเพื่อลด round-trip)
    // ใช้ index (course_id, delete_at, lesson_order, lesson_id) ให้เกิด range scan ตรงๆ แทน OR แบบเดิม
    $current_order = $lesson['lesson_order'] ?? 0;

    $sql_next = "
        (SELECT lesson_id, lesson_name, lesson_order FROM tbl_lesson
         WHERE course_id = :c1 AND delete_at IS NULL AND lesson_order = :o1 AND lesson_id > :id1
         ORDER BY lesson_id ASC LIMIT 1)
        UNION ALL
        (SELECT lesson_id, lesson_name, lesson_order FROM tbl_lesson
         WHERE course_id = :c2 AND delete_at IS NULL AND lesson_order > :o2
         ORDER BY lesson_order ASC, lesson_id ASC LIMIT 1)
        ORDER BY lesson_order ASC, lesson_id ASC LIMIT 1
    ";
    $stmt = $db->prepare($sql_next);
    $stmt->execute([
        ':c1' => $course_id, ':o1' => $current_order, ':id1' => $lesson_id,
        ':c2' => $course_id, ':o2' => $current_order
    ]);
    $next_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $next_lesson_id = $next_lesson ? (int)$next_lesson['lesson_id'] : null;
    $next_lesson_name = $next_lesson ? $next_lesson['lesson_name'] : null;
    $next_lesson_order = $next_lesson ? $next_lesson['lesson_order'] : null;

    // 2.6 ดึงข้อมูลบทเรียนก่อนหน้า (ถ้ามี)
    $sql_prev = "
        (SELECT lesson_id, lesson_name, lesson_order FROM tbl_lesson
         WHERE course_id = :c1 AND delete_at IS NULL AND lesson_order = :o1 AND lesson_id < :id1
         ORDER BY lesson_id DESC LIMIT 1)
        UNION ALL
        (SELECT lesson_id, lesson_name, lesson_order FROM tbl_lesson
         WHERE course_id = :c2 AND delete_at IS NULL AND lesson_order < :o2
         ORDER BY lesson_order DESC, lesson_id DESC LIMIT 1)
        ORDER BY lesson_order DESC, lesson_id DESC LIMIT 1
    ";
    $stmt = $db->prepare($sql_prev);
    $stmt->execute([
        ':c1' => $course_id, ':o1' => $current_order, ':id1' => $lesson_id,
        ':c2' => $course_id, ':o2' => $current_order
    ]);
    $prev_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $prev_lesson_id = $prev_lesson ? (int)$prev_lesson['lesson_id'] : null;
    $prev_lesson_name = $prev_lesson ? $prev_lesson['lesson_name'] : null;
    $prev_lesson_order = $prev_lesson ? $prev_lesson['lesson_order'] : null;
    $prev_lesson_key = $prev_lesson_id ? \App\Utility\Cipher::encryptLessonKey($prev_lesson_id, $user_id) : null;

    // 3. ดึงความคืบหน้าการเรียน
    $sql_progress = "SELECT progress_last_sec, progress_status FROM tbl_lesson_progress 
                     WHERE progress_user_id = :u AND progress_lesson_id = :l AND progress_enrollment_id = :eid LIMIT 1";
    $stmt = $db->prepare($sql_progress);
    $stmt->execute([':u' => $user_id, ':l' => $lesson_id, ':eid' => $enroll['enroll_id']]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $resume_sec = $progress ? (int)$progress['progress_last_sec'] : 0;

    // หากเรียนจบแล้ว (progress_status = 1) ให้ปิดคำถามระหว่างเรียน (ส่งค่า lesson_question เป็น '0')
    if ($progress && ($progress['progress_status'] == '1' || $progress['progress_status'] == 1)) {
        $lesson['lesson_question'] = '0';
    }

    // 4. ดึงคำถามของบทเรียน (query เดียว ดึงมาทั้งหมดไม่จำกัดจำนวน)
    $sql_questions = "SELECT * FROM tbl_question WHERE lesson_id = :l AND delete_at IS NULL";
    $stmt = $db->prepare($sql_questions);
    $stmt->execute([':l' => $lesson_id]);
    $questions_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $questions = [];

    if (!empty($questions_raw)) {
        // 4.1 ดึง choices ของ "ทุกคำถาม" มาในครั้งเดียวด้วย IN (...) แทนการวนลูปยิง query ทีละข้อ (แก้ N+1)
        $question_ids = array_map(fn($q) => (int)$q['question_id'], $questions_raw);
        $placeholders = implode(',', array_fill(0, count($question_ids), '?'));

        $sql_choices = "SELECT * FROM tbl_question_choice 
                         WHERE question_id IN ($placeholders) AND delete_at IS NULL 
                         ORDER BY question_id ASC, question_choice_id ASC";
        $stmt_choice = $db->prepare($sql_choices);
        $stmt_choice->execute($question_ids);
        $choices_raw = $stmt_choice->fetchAll(PDO::FETCH_ASSOC);
        $stmt_choice->closeCursor();

        // 4.2 จับกลุ่ม choices ตาม question_id ด้วย PHP (ไม่ยิง query ซ้ำอีก)
        $choices_by_question = [];
        foreach ($choices_raw as $c) {
            $choices_by_question[$c['question_id']][] = $c;
        }

        // 4.3 ประกอบผลลัพธ์คำถามแต่ละข้อ พร้อม choices ที่จับกลุ่มไว้แล้ว
        foreach ($questions_raw as $q) {
            $q_id = (int)$q['question_id'];
            $choices_for_q = $choices_by_question[$q_id] ?? [];

            $choices = [];
            $correct = 0;
            foreach ($choices_for_q as $i => $c) {
                $choices[] = $c['question_choice_text'] ?? '';
                if ($c['question_choice_correct'] == '1' || $c['question_choice_correct'] == 1) {
                    $correct = $i + 1;
                }
            }

            $qText = trim(strip_tags($q['question_text'] ?? ''));
            $qImage = null;
            if (!empty($q['question_image'])) {
                $qImage = \App\Utility\AwsS3::getFileUrl($q['question_image'], '+30 minutes', true);
            }

            if (!empty($choices)) {
                $questions[] = [
                    'question_id' => $q_id,
                    'text' => $qText,
                    'image' => $qImage,
                    'choices' => $choices
                ];
            }
        }
    }

    $already_completed = ($progress && ($progress['progress_status'] == '1' || $progress['progress_status'] == 1)) ? true : false;

    Response::json(1, 'Success', [
        'lesson' => $lesson,
        'resume_sec' => $resume_sec,
        'questions' => $questions,
        'course_id' => $course_id,
        'enroll_id' => (int)$enroll['enroll_id'],
        'enroll_key' => \App\Utility\Cipher::encrypt($enroll['enroll_id']),
        'lesson_id' => $lesson_id,
        'next_lesson_id' => $next_lesson_id,
        'next_lesson_key' => $next_lesson_id ? \App\Utility\Cipher::encryptLessonKey($next_lesson_id, $user_id) : null,
        'next_lesson_name' => $next_lesson_name,
        'next_lesson_order' => $next_lesson_order,
        'prev_lesson_id' => $prev_lesson_id,
        'prev_lesson_key' => $prev_lesson_key,
        'prev_lesson_name' => $prev_lesson_name,
        'prev_lesson_order' => $prev_lesson_order,
        'already_completed' => $already_completed,
        'course_skip' => ($course_skip === 1),
        'course_otp' => ($course_otp === 1),
        'course_question' => ($course_question === 1),
        'question_limit' => $question_limit,
        'question_time' => $question_time
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}