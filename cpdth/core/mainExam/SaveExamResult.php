<?php
use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    // 1. ตรวจสอบการเข้าสู่ระบบ
    $currentUser = Auth::requireUserToken();
    $user_id = (int)$currentUser->user_id;

    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $enroll_id_post = isset($_POST['enroll_id']) ? (int)$_POST['enroll_id'] : 0;
    $answers_raw = isset($_POST['answers']) ? $_POST['answers'] : null; // คาดหวังเป็น array ของ [exam_id => exam_choice_id]

    if ($course_id <= 0) {
        Response::json(0, 'ไม่พบรหัสคอร์สเรียน', null);
    }

    if (empty($answers_raw) || !is_array($answers_raw)) {
        Response::json(0, 'กรุณาทำข้อสอบก่อนส่ง', null);
    }

    $db_instance = new Connection();
    $db = $db_instance->getPdo();

    if (!$db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
    $db->beginTransaction();

    // 2. ดึงข้อมูลคอร์สเรียน เพื่อตรวจสอบเกณฑ์คะแนนขั้นต่ำ และจำนวนครั้งที่สอบได้
    $sql_course = "SELECT course_minimum_score, 
                          course_number_time,
                          approve_certificate_auto,
                          approver_certificate_type,
                          course_name,
                          course_instructor, 
                          course_code_cpd_1, 
                          course_code_cpa_1,
                          course_approval_date_1, 
                          course_cpd_hour, 
                          course_cpd_ethics, 
                          course_cpd_other,
                          course_number_exam
                   FROM tbl_course WHERE course_id = :course_id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_course);
    $stmt->execute([':course_id' => $course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$course) {
        $db->rollBack();
        Response::json(0, 'ไม่พบคอร์สเรียนนี้ในระบบ', null);
    }

    // ตรวจเช็คสิทธิ์การลงทะเบียนเรียนในคอร์สนี้
    $sql_enroll = "SELECT enroll_id, enroll_user_id FROM tbl_course_enrollment 
                   WHERE enroll_id = :enroll_id AND enroll_user_id = :user_id AND enroll_course_id = :course_id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql_enroll);
    $stmt->execute([
        ':enroll_id' => $enroll_id_post,
        ':user_id' => $user_id,
        ':course_id' => $course_id
    ]);
    $enroll = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$enroll || (int)$enroll['enroll_user_id'] !== (int)$user_id) {
        $db->rollBack();
        Response::json(0, 'คุณไม่มีสิทธิ์ส่งข้อสอบของคอร์สเรียนนี้เนื่องจากไม่ใช่เจ้าของสิทธิ์การลงทะเบียน', null);
    }

    $minimum_score = (int)($course['course_minimum_score'] ?? 0);
    $total_questions = count($answers_raw);
    $score = 0;

    // 3. ดึงเฉลยสำหรับช้อยส์ทั้งหมดที่ส่งมาในครั้งเดียวเพื่อหลีกเลี่ยง N+1 Query
    $choice_ids = array_values($answers_raw);
    $correct_map = [];

    if (!empty($choice_ids)) {
        $placeholders = implode(',', array_fill(0, count($choice_ids), '?'));
        $sql_choices = "SELECT exam_choice_id, exam_choice_correct FROM tbl_exam_choice 
                        WHERE exam_choice_id IN ($placeholders) AND delete_at IS NULL";
        $stmt_choices = $db->prepare($sql_choices);
        $stmt_choices->execute($choice_ids);
        $choices_res = $stmt_choices->fetchAll(PDO::FETCH_ASSOC);
        $stmt_choices->closeCursor();

        foreach ($choices_res as $row) {
            $correct_map[(int)$row['exam_choice_id']] = (string)$row['exam_choice_correct'];
        }
    }

    $insert_rows = [];
    $insert_values = [];

    foreach ($answers_raw as $exam_id => $choice_id) {
        $exam_id = (int)$exam_id;
        $choice_id = (int)$choice_id;

        // เช็คว่าช้อยส์นี้เป็นคำตอบที่ถูกหรือไม่ (ดึงจากอาเรย์ในแรมแทนการรัน SQL คิวรี่ใหม่)
        $choice_correct = $correct_map[$choice_id] ?? '0';
        $ans_correct = ($choice_correct === '1' || $choice_correct == 1) ? '1' : '0';
        
        if ($ans_correct === '1') {
            $score++;
        }

        // จัดเตรียมข้อมูลสำหรับ Bulk Insert เขียนข้อมูลรวดเดียว
        $insert_rows[] = "(?, ?, ?)";
        $insert_values[] = $exam_id;
        $insert_values[] = $choice_id;
        $insert_values[] = $ans_correct;
    }

    // ทำการรันคำสั่งเขียนคำตอบลง tbl_exam_answer พร้อมกันในคิวรี่เดียว
    if (!empty($insert_rows)) {
        $sql_bulk_insert = "INSERT INTO tbl_exam_answer (exam_id, exam_choice_id, ans_correct) 
                            VALUES " . implode(', ', $insert_rows);
        $stmt_bulk = $db->prepare($sql_bulk_insert);
        $stmt_bulk->execute($insert_values);
        $stmt_bulk->closeCursor();
    }

    $attempt_pass = ($score >= $minimum_score) ? '1' : '0';

    // คำนวณรอบการสอบ (attemp_new)
    $course_number_time = (int)($course['course_number_time'] ?? 0);
    $current_attemp_new = 0;
    $count_current_round = 0;
    
    if ($course_number_time > 0) {
        // หาค่า attemp_new ล่าสุด เฉพาะของการลงทะเบียนครั้งนี้
        $stmt_max = $db->prepare("SELECT MAX(attemp_new) FROM tbl_exam_attempt WHERE attempt_user_id = :u AND attempt_course_id = :c AND attempt_enroll_id = :e");
        $stmt_max->execute([':u' => $user_id, ':c' => $course_id, ':e' => $enroll_id_post]);
        $max_attemp = $stmt_max->fetchColumn();
        $stmt_max->closeCursor();
        
        $current_attemp_new = $max_attemp !== null ? (int)$max_attemp : 0;
        
        // นับจำนวนครั้งในรอบล่าสุด เฉพาะของการลงทะเบียนครั้งนี้
        $stmt_count = $db->prepare("SELECT COUNT(*) FROM tbl_exam_attempt WHERE attempt_user_id = :u AND attempt_course_id = :c AND attempt_enroll_id = :e AND attemp_new = :a");
        $stmt_count->execute([':u' => $user_id, ':c' => $course_id, ':e' => $enroll_id_post, ':a' => $current_attemp_new]);
        $count_current_round = (int)$stmt_count->fetchColumn();
        $stmt_count->closeCursor();
        
        // ถ้าเต็มลิมิตแล้ว แปลว่านี่คือการสอบในรอบถัดไป
        if ($count_current_round >= $course_number_time) {
            $current_attemp_new++;
            $count_current_round = 0; // รีเซ็ตตัวนับสำหรับรอบใหม่
        }
    }

    // 4. บันทึกประวัติการสอบลง tbl_exam_attempt
    $sql_insert_attempt = "INSERT INTO tbl_exam_attempt (attempt_user_id, attempt_course_id, attempt_score, attempt_pass, attemp_new, attempt_enroll_id) 
                           VALUES (:user_id, :course_id, :score, :pass, :attemp_new, :attempt_enroll_id)";
    $stmt_attempt = $db->prepare($sql_insert_attempt);
    $stmt_attempt->execute([
        ':user_id' => $user_id,
        ':course_id' => $course_id,
        ':score' => $score,
        ':pass' => $attempt_pass,
        ':attemp_new' => $current_attemp_new,
        ':attempt_enroll_id' => $enroll['enroll_id']
    ]);
    $stmt_attempt->closeCursor();

    // 5. เช็คจำนวนครั้งที่สอบในรอบปัจจุบัน หากครบหรือเกินให้ตัดสิทธิ์ (ล็อค)
    if ($course_number_time > 0) {
        $count_current_round++; // นับรวมครั้งล่าสุดที่เพิ่งบันทึกลงไป

        if ($count_current_round >= $course_number_time) {
            $stmt_update = $db->prepare("UPDATE tbl_course_enrollment SET is_locked = '1', locked_at = NOW() WHERE enroll_id = :enroll_id");
            $stmt_update->execute([':enroll_id' => $enroll['enroll_id']]);
        }
    }

    // 6. ตรวจสอบการออกใบรับรองอัตโนมัติเมื่อสอบผ่าน
    if ($attempt_pass === '1') {
        $approve_certificate_auto = isset($course['approve_certificate_auto']) ? (int)$course['approve_certificate_auto'] : 0;
        $approver_certificate_type = isset($course['approver_certificate_type']) ? (int)$course['approver_certificate_type'] : 0;

        if ($approve_certificate_auto === 1 && $approver_certificate_type === 0) {
            // อนุมัติการเรียนเสร็จสิ้น
            $stmt_up_enroll = $db->prepare("UPDATE tbl_course_enrollment SET enroll_is_completed = '1', enroll_completed_at = NOW() WHERE enroll_id = :enroll_id");
            $stmt_up_enroll->execute([':enroll_id' => $enroll_id_post]);
            $stmt_up_enroll->closeCursor();

            // ดึงข้อมูลผู้ใช้เพื่อใช้ในการทำ snapshot
            $sql_user_info = "SELECT user_firstname, user_lastname, user_citizen_id, user_cpd_no, user_cpa_no, id_card_image FROM tbl_user WHERE user_id = :user_id LIMIT 1";
            $stmt_user = $db->prepare($sql_user_info);
            $stmt_user->execute([':user_id' => $user_id]);
            $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
            $stmt_user->closeCursor();

            if ($user_info) {
                // กำหนดประเภทใบรับรองที่ต้องออกตามข้อมูลใบประกอบวิชาชีพของผู้ใช้และรหัสหลักสูตร
                $cert_types = [];
                $user_has_cpd = !empty(trim((string)($user_info['user_cpd_no'] ?? '')));
                $user_has_cpa = !empty(trim((string)($user_info['user_cpa_no'] ?? '')));

                if ($user_has_cpd && !empty($course['course_code_cpd_1'])) {
                    $cert_types[] = 'cpd';
                }
                if ($user_has_cpa && !empty($course['course_code_cpa_1'])) {
                    $cert_types[] = 'cpa';
                }
                
                // หากไม่มีรหัสเลย ให้มีค่าเริ่มต้นเป็น cpd
                if (empty($cert_types)) {
                    $cert_types[] = 'cpd';
                }

                $ts = time();
                $ym = date('ym', $ts);

                // นับจำนวนลำดับเพื่อสร้างรหัสใบรับรอง
                $cnt_stmt = $db->prepare("SELECT COUNT(DISTINCT enroll_id) FROM tbl_certificate_snapshot WHERE cert_no LIKE :ym");
                $cnt_stmt->execute([':ym' => $ym . '%']);
                $seq = ((int)$cnt_stmt->fetchColumn()) + 1;
                $cnt_stmt->closeCursor();

                $cert_no = $ym . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

                foreach ($cert_types as $cert_type) {
                    // ตรวจสอบว่าเคยทำ snapshot ของ enroll_id และ cert_type นี้ไปหรือยัง
                    $sel = $db->prepare("SELECT cert_id FROM tbl_certificate_snapshot WHERE enroll_id = :e AND cert_type = :t LIMIT 1");
                    $sel->execute([':e' => $enroll_id_post, ':t' => $cert_type]);
                    $snap_exists = $sel->fetchColumn();
                    $sel->closeCursor();

                    if (!$snap_exists) {
                        $lic_cpd = (string)($user_info['user_cpd_no'] ?? '');
                        $lic_cpa = (string)($user_info['user_cpa_no'] ?? '');

                        $code_raw_cpd = (string)($course['course_code_cpd_1'] ?? '');
                        $code_raw_cpa = (string)($course['course_code_cpa_1'] ?? '');

                        $quarter = str_pad((string)(int)ceil((int)date('n', $ts) / 3), 2, '0', STR_PAD_LEFT);
                        
                        $code_q_cpd = $code_raw_cpd !== '' ? preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $code_raw_cpd, 1) : '';
                        $course_code_cpd_frozen = $code_q_cpd !== '' ? str_replace(['[', ']'], '', $code_q_cpd) : '';

                        $code_q_cpa = $code_raw_cpa !== '' ? preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $code_raw_cpa, 1) : '';
                        $course_code_cpa_frozen = $code_q_cpa !== '' ? str_replace(['[', ']'], '', $code_q_cpa) : '';

                        $total_exam = (int)($course['course_number_exam'] ?? 0);
                        $percent = ($total_exam > 0) ? round($score / $total_exam * 100, 2) : null;

                        $approval_date = (!empty($course['course_approval_date_1']) && $course['course_approval_date_1'] !== '0000-00-00')
                            ? $course['course_approval_date_1'] : null;

                        try {
                            $ins = $db->prepare(
                                "INSERT INTO tbl_certificate_snapshot
                                   (enroll_id,
                                    user_id,
                                    course_id,
                                    cert_no,
                                    cert_type,
                                    user_firstname,
                                    user_lastname, 
                                    user_citizen_id, 
                                    user_license_no, 
                                    user_licensecpa_no, 
                                    id_card_image_snapshot,
                                    course_name, 
                                    course_instructor, 
                                    course_code_cpd,
                                    course_code_cpa, 
                                    course_approval_date,
                                    hours_account, 
                                    hours_ethics, 
                                    hours_other,
                                    exam_score, 
                                    exam_total, 
                                    score_percent, 
                                    issued_at, 
                                    issued_by)
                                 VALUES
                                   (:enroll_id,
                                    :user_id, 
                                    :course_id, 
                                    :cert_no, 
                                    :cert_type,
                                    :fn, 
                                    :ln, 
                                    :cid, 
                                    :lic_cpd, 
                                    :lic_cpa, 
                                    :idimg,
                                    :cname, 
                                    :cinstr, 
                                    :ccode_cpd, 
                                    :ccode_cpa, 
                                    :capprove,
                                    :h_acc, 
                                    :h_eth, 
                                    :h_oth,
                                    :score, 
                                    :total, 
                                    :percent, 
                                    :issued_at, 
                                    :issued_by)"
                            );
                            $ins->execute([
                                ':enroll_id' => $enroll_id_post,
                                ':user_id'   => $user_id,
                                ':course_id' => $course_id,
                                ':cert_no'   => $cert_no,
                                ':cert_type' => $cert_type,
                                ':fn'        => (string)($user_info['user_firstname'] ?? ''),
                                ':ln'        => (string)($user_info['user_lastname'] ?? ''),
                                ':cid'       => (string)($user_info['user_citizen_id'] ?? ''),
                                ':lic_cpd'   => $lic_cpd,
                                ':lic_cpa'   => $lic_cpa,
                                ':idimg'     => (string)($user_info['id_card_image'] ?? ''),
                                ':cname'     => (string)($course['course_name'] ?? ''),
                                ':cinstr'    => (string)($course['course_instructor'] ?? ''),
                                ':ccode_cpd' => $course_code_cpd_frozen,
                                ':ccode_cpa' => $course_code_cpa_frozen,
                                ':capprove'  => $approval_date,
                                ':h_acc'     => (float)($course['course_cpd_hour'] ?? 0),
                                ':h_eth'     => (float)($course['course_cpd_ethics'] ?? 0),
                                ':h_oth'     => (float)($course['course_cpd_other'] ?? 0),
                                ':score'     => $score,
                                ':total'     => $total_exam > 0 ? $total_exam : null,
                                ':percent'   => $percent,
                                ':issued_at' => date('Y-m-d H:i:s', $ts),
                                ':issued_by' => $user_id,
                            ]);
                            $ins->closeCursor();
                        } catch (\PDOException $ex) {
                            if ($ex->getCode() !== '23000') {
                                throw $ex;
                            }
                        }
                    }
                }
            }
        }
    }

    // Commit ข้อมูล
    $db->commit();

    // 7. ส่งอีเมลแบบสอบถามหลังการอบรมในพื้นหลัง (Background) เมื่อสอบผ่านเท่านั้น
    if ($attempt_pass === '1') {
        $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $app_base_dir = preg_replace('#/core(/[^/]+)*$#i', '', $script_dir);
        if ($app_base_dir === DIRECTORY_SEPARATOR || $app_base_dir === '/') {
            $app_base_dir = '';
        }
        $base_url = $protocol . "://" . $host . rtrim($app_base_dir, '/');
        $eval_bg_url = $base_url . "/core/mainExam/SendEvaluationEmailBackground.php";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $eval_bg_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'user_id'   => $user_id,
            'course_id' => $course_id,
        ]));
        // ป้องกัน deadlock หากมีการใช้ session ในระบบ
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // wait max 1 second
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        if ($result === false) {
            error_log("cURL Error (EvalEmail): " . curl_error($ch));
        }
        curl_close($ch);

        // 8. รันสคริปต์ Auto Approve แบบหน่วงเวลา 5 นาทีเบื้องหลัง
        $approve_certificate_auto = isset($course['approve_certificate_auto']) ? (int)$course['approve_certificate_auto'] : 0;
        $approver_certificate_type = isset($course['approver_certificate_type']) ? (int)$course['approver_certificate_type'] : 0;
        if ($approve_certificate_auto === 1 && $approver_certificate_type === 1) {
            $auto_bg_url = $base_url . "/core/mainExam/AutoApproveBackground.php";

            $ch_auto = curl_init();
            curl_setopt($ch_auto, CURLOPT_URL, $auto_bg_url);
            curl_setopt($ch_auto, CURLOPT_POST, 1);
            curl_setopt($ch_auto, CURLOPT_POSTFIELDS, http_build_query([
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'enroll_id' => $enroll_id_post,
            ]));
            curl_setopt($ch_auto, CURLOPT_TIMEOUT, 1); // wait max 1 second
            curl_setopt($ch_auto, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch_auto, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_auto, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch_auto, CURLOPT_SSL_VERIFYHOST, false);
            $result_auto = curl_exec($ch_auto);
            if ($result_auto === false) {
                error_log("cURL Error (AutoApprove): " . curl_error($ch_auto));
            }
            curl_close($ch_auto);
        }
    }

    Response::json(1, 'Success', [
        'score' => $score,
        'total_questions' => $total_questions,
        'minimum_score' => $minimum_score,
        'is_passed' => ($attempt_pass === '1')
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกคะแนน: ' . $e->getMessage(), null);
}
