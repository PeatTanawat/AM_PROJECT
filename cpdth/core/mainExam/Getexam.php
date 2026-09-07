<?php
use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    // 1. ตรวจสอบการเข้าสู่ระบบ
    $currentUser = Auth::requireUserToken();
    $user_id     = (int) $currentUser->user_id;

    $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    if ($course_id <= 0) {
        Response::json(0, 'ไม่พบรหัสคอร์สเรียน', null);
    }

    $db_instance = new Connection();
    $db          = $db_instance->getPdo();

    if (! $db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 2. ดึงข้อมูลคอร์สเรียน, สิทธิ์ลงทะเบียน, คำถาม และตัวเลือก ทั้งหมดจบใน Single Query (1 SQL Query เท่านั้น)
       $sql = "SELECT 
                c.*,
                e_user.enroll_id,
                ex.exam_id,
                ex.exam_text,
                ch.exam_choice_id,
                ch.exam_choice_text
            FROM tbl_course c
            LEFT JOIN (
                SELECT MAX(enroll_id) as enroll_id, enroll_course_id 
                FROM tbl_course_enrollment 
                WHERE enroll_user_id = :user_id AND delete_at IS NULL 
                GROUP BY enroll_course_id
            ) e_user 
                ON c.course_id = e_user.enroll_course_id 
            LEFT JOIN tbl_exam ex 
                ON c.course_id = ex.course_id 
               AND ex.delete_at IS NULL
            LEFT JOIN tbl_exam_choice ch 
                ON ex.exam_id = ch.exam_id 
               AND ch.delete_at IS NULL
            WHERE c.course_id = :course_id 
              AND c.delete_at IS NULL";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id'   => $user_id,
        ':course_id' => $course_id,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($rows)) {
        Response::json(0, 'ไม่พบคอร์สเรียนนี้ในระบบ', null);
    }

    // 3. ตรวจเช็คสิทธิ์การลงทะเบียนเรียนในคอร์สนี้
    if (empty($rows[0]['enroll_id'])) {
        Response::json(0, 'คุณไม่มีสิทธิ์เข้าทำข้อสอบของคอร์สเรียนนี้เนื่องจากไม่ใช่เจ้าของสิทธิ์การลงทะเบียน', null);
    }

    // 4. ตรวจเช็คว่ามีข้อสอบในระบบหรือไม่
    if (empty($rows[0]['exam_id'])) {
        Response::json(0, 'คอร์สเรียนนี้ยังไม่มีข้อสอบในระบบ', null);
    }

    // สร้างข้อมูล Course Array
    $course = $rows[0];
    unset(
        $course['enroll_id'],
        $course['exam_id'],
        $course['exam_text'],
        $course['exam_choice_id'],
        $course['exam_choice_text']
    );

    // 5. จัดกลุ่มคำถามและตัวเลือกด้วย PHP Memory Mapping
    $questions_map = [];
    foreach ($rows as $row) {
        $exam_id = (int) $row['exam_id'];
        if (! isset($questions_map[$exam_id])) {
            $questions_map[$exam_id] = [
                'question_id'   => $exam_id,
                'question_text' => trim(strip_tags($row['exam_text'] ?? '')),
                'choices'       => [],
            ];
        }

        if ($row['exam_choice_id'] !== null) {
            $questions_map[$exam_id]['choices'][] = [
                'choice_id'   => (int) $row['exam_choice_id'],
                'choice_text' => trim($row['exam_choice_text'] ?? ''),
            ];
        }
    }

    // สุ่มสลับลำดับตัวเลือกตอบ ก, ข, ค, ง ของแต่ละข้อ
    foreach ($questions_map as &$q) {
        shuffle($q['choices']);
    }
    unset($q);

    // แปลงเป็น Indexed Array และสุ่มข้อสอบ
    $questions = array_values($questions_map);
    shuffle($questions);

    // ตัดตามจำนวนข้อสอบที่คอร์สกำหนด
    $limit = (int) ($course['course_number_exam'] ?? 10);
    if ($limit > 0 && count($questions) > $limit) {
        $questions = array_slice($questions, 0, $limit);
    }

    Response::json(1, 'Success', [
        'course'            => $course,
        'questions'         => $questions,
        'exam_time_minutes' => (int) ($course['course_exam_time'] ?? 30),
        'minimum_score'     => (int) ($course['course_minimum_score'] ?? 0),
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
