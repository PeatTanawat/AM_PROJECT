<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Connection;

// 1. ป้องกันสคริปต์ตายเมื่อผู้ใช้ปิดหน้าเว็บ
ignore_user_abort(true);
set_time_limit(400); // อนุญาตให้ทำงานสูงสุด 400 วินาที (ป้องกัน timeout)

// 2. ตอบกลับ Request ทันที เพื่อไม่ให้หน้าเว็บผู้ใช้โหลดค้าง
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// 3. รับค่าตัวแปร
$enroll_id = isset($_POST['enroll_id']) ? (int)$_POST['enroll_id'] : 0;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

if ($enroll_id <= 0 || $user_id <= 0 || $course_id <= 0) {
    exit;
}

// 4. หน่วงเวลา 5 นาที (300 วินาที)
sleep(300);

try {
    $db_instance = new Connection();
    $db = $db_instance->getPdo();

    if (!$db) {
        exit;
    }

    // 5. ตรวจสอบว่าหลังจากผ่านไป 5 นาที รายการนี้ยังคงรอการอนุมัติอยู่หรือไม่
    $sql_pending = "SELECT e.enroll_id, e.enroll_user_id, e.enroll_course_id, e.enroll_is_completed,
                           c.course_name, c.course_instructor, 
                           c.course_code_cpd_1, c.course_code_cpd_2, c.course_code_cpd_3, c.course_code_cpd_4,
                           c.course_code_cpa_1, c.course_code_cpa_2, c.course_code_cpa_3, c.course_code_cpa_4,
                           c.course_approval_date_1, c.course_approval_date_2, c.course_approval_date_3, c.course_approval_date_4,
                           c.course_cpd_hour, c.course_cpd_ethics, c.course_cpd_other,
                           c.course_cpa_hour, c.course_cpa_ethics, c.course_cpa_other,
                           c.course_number_exam,
                           (SELECT a.attempt_score FROM tbl_exam_attempt a
                            WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
                            ORDER BY a.attempt_id DESC LIMIT 1) AS score
                    FROM tbl_course_enrollment e
                    JOIN tbl_course c ON e.enroll_course_id = c.course_id
                    WHERE e.enroll_id = :enroll_id
                      AND e.enroll_user_id = :user_id
                      AND e.enroll_is_completed = '0'
                      AND c.approve_certificate_auto = 1
                      AND c.approver_certificate_type = 1
                      AND e.delete_at IS NULL
                    LIMIT 1";

    $stmt = $db->prepare($sql_pending);
    $stmt->execute([
        ':enroll_id' => $enroll_id,
        ':user_id' => $user_id
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row) {
        exit;
    }

    $score = $row['score'] !== null ? (int)$row['score'] : 0;

    $db->beginTransaction();

    // มาร์กไว้ว่าทำการตรวจสอบออโต้แล้ว
    $stmt_mark = $db->prepare("UPDATE tbl_course_enrollment SET is_auto_checked = '1' WHERE enroll_id = :enroll_id");
    $stmt_mark->execute([':enroll_id' => $enroll_id]);
    $stmt_mark->closeCursor();

    // ดึงข้อมูลผู้ใช้เพื่อเช็ค CPD/CPA
    $sql_user_info = "SELECT user_firstname, user_lastname, user_citizen_id, user_cpd_no, user_cpa_no, id_card_image 
                      FROM tbl_user WHERE user_id = :user_id LIMIT 1";
    $stmt_user = $db->prepare($sql_user_info);
    $stmt_user->execute([':user_id' => $user_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $stmt_user->closeCursor();

    $has_cpd = !empty($user_info['user_cpd_no']);
    $has_cpa = !empty($user_info['user_cpa_no']);

    if ($user_info && ($has_cpd || $has_cpa)) {
        // 1. อนุมัติการเรียนเสร็จสิ้น
        $stmt_up_enroll = $db->prepare("UPDATE tbl_course_enrollment SET enroll_is_completed = '1', enroll_completed_at = NOW() WHERE enroll_id = :enroll_id");
        $stmt_up_enroll->execute([':enroll_id' => $enroll_id]);
        $stmt_up_enroll->closeCursor();

        // 2. ออกใบรับรอง snapshot
        $cert_type = $has_cpd ? 'cpd' : 'cpa';
        $ts = time();
        $ym = date('ym', $ts);

        // นับจำนวนเพื่อรัน cert_no
        $cnt_stmt = $db->prepare("SELECT COUNT(DISTINCT enroll_id) FROM tbl_certificate_snapshot WHERE cert_no LIKE :ym");
        $cnt_stmt->execute([':ym' => $ym . '%']);
        $seq = ((int)$cnt_stmt->fetchColumn()) + 1;
        $cnt_stmt->closeCursor();

        $cert_no = $ym . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        // ป้องกัน snapshot ซ้ำ
        $sel = $db->prepare("SELECT cert_id FROM tbl_certificate_snapshot WHERE enroll_id = :e LIMIT 1");
        $sel->execute([':e' => $enroll_id]);
        $snap_exists = $sel->fetchColumn();
        $sel->closeCursor();

        if (!$snap_exists) {
            $license_no = trim((string)($user_info['user_cpd_no'] ?? ''));
            $lic_cpa = trim((string)($user_info['user_cpa_no'] ?? ''));

            $q_idx = (int) ceil((int) date('n', $ts) / 3);
            $quarter_str = str_pad((string) $q_idx, 2, '0', STR_PAD_LEFT);

            $fmt_code = function ($raw, $q) {
                if (empty($raw)) return '';
                $cq = preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $q, $raw, 1);
                return str_replace(['[', ']'], '', $cq);
            };

            $raw_cpd = (string) ($row['course_code_cpd_' . $q_idx] ?? ($row['course_code_cpd_1'] ?? ''));
            $raw_cpa = (string) ($row['course_code_cpa_' . $q_idx] ?? ($row['course_code_cpa_1'] ?? ''));

            $ccode_cpd = $fmt_code($raw_cpd, $quarter_str);
            $ccode_cpa = $fmt_code($raw_cpa, $quarter_str);

            $total_exam = (int)($row['course_number_exam'] ?? 0);
            $percent = ($total_exam > 0) ? round($score / $total_exam * 100, 2) : null;

            $approval_date = (!empty($row['course_approval_date_' . $q_idx]) && $row['course_approval_date_' . $q_idx] !== '0000-00-00')
                ? $row['course_approval_date_' . $q_idx] : null;

            $h_acc = $has_cpd ? (float)($row['course_cpd_hour'] ?? 0) : (float)($row['course_cpa_hour'] ?? 0);
            $h_eth = $has_cpd ? (float)($row['course_cpd_ethics'] ?? 0) : (float)($row['course_cpa_ethics'] ?? 0);
            $h_oth = $has_cpd ? (float)($row['course_cpd_other'] ?? 0) : (float)($row['course_cpa_other'] ?? 0);

            try {
                $ins = $db->prepare(
                    "INSERT INTO tbl_certificate_snapshot
                       (enroll_id, user_id, course_id, cert_no, cert_type,
                        user_firstname, user_lastname, user_citizen_id, user_license_no, user_licensecpa_no, id_card_image_snapshot,
                        course_name, course_instructor, course_code_cpd, course_code_cpa, course_approval_date,
                        hours_account, hours_ethics, hours_other,
                        exam_score, exam_total, score_percent, issued_at, issued_by)
                     VALUES
                       (:enroll_id, :user_id, :course_id, :cert_no, :cert_type,
                        :fn, :ln, :cid, :lic, :lic_cpa, :idimg,
                        :cname, :cinstr, :ccode_cpd, :ccode_cpa, :capprove,
                        :h_acc, :h_eth, :h_oth,
                        :score, :total, :percent, :issued_at, :issued_by)"
                );
                $ins->execute([
                    ':enroll_id' => $enroll_id,
                    ':user_id'   => $user_id,
                    ':course_id' => $course_id,
                    ':cert_no'   => $cert_no,
                    ':cert_type' => $cert_type,
                    ':fn'        => (string)($user_info['user_firstname'] ?? ''),
                    ':ln'        => (string)($user_info['user_lastname'] ?? ''),
                    ':cid'       => (string)($user_info['user_citizen_id'] ?? ''),
                    ':lic'       => $license_no,
                    ':lic_cpa'   => $lic_cpa,
                    ':idimg'     => (string)($user_info['id_card_image'] ?? ''),
                    ':cname'     => (string)($row['course_name'] ?? ''),
                    ':cinstr'    => (string)($row['course_instructor'] ?? ''),
                    ':ccode_cpd' => $ccode_cpd,
                    ':ccode_cpa' => $ccode_cpa,
                    ':capprove'  => $approval_date,
                    ':h_acc'     => $h_acc,
                    ':h_eth'     => $h_eth,
                    ':h_oth'     => $h_oth,
                    ':score'     => $score,
                    ':total'     => $total_exam > 0 ? $total_exam : null,
                    ':percent'   => $percent,
                    ':issued_at' => date('Y-m-d H:i:s', $ts),
                    ':issued_by' => $user_id,
                ]);
                $ins->closeCursor();
            } catch (\PDOException $ex) {
                if ($ex->getCode() !== '23000') {
                    error_log('AutoApproveBackground insert Error: ' . $ex->getMessage());
                }
            }
        }
    }

    $db->commit();
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('AutoApproveBackground error: ' . $e->getMessage());
}
