<?php
// อัปเดตข้อมูลใบรับรองผลการสอบ (แก้ไขลง tbl_certificate_snapshot โดยตรง)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$enroll_id = isset($_POST['enroll_id']) ? (int) $_POST['enroll_id'] : 0;
if ($enroll_id <= 0 && !empty($_POST['key'])) {
    $enroll_id = (int) \App\Utility\Cipher::decrypt(trim($_POST['key']));
}
if ($enroll_id <= 0) {
    Response::json(0, 'ไม่พบรายการ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ตรวจสอบว่ามีรายการ enrollment นี้อยู่จริง
$chk = $pdo_connect->prepare(
    "SELECT enroll_id, enroll_user_id, enroll_course_id, create_at 
       FROM tbl_course_enrollment 
      WHERE enroll_id = :id AND delete_at IS NULL LIMIT 1"
);
$chk->execute([':id' => $enroll_id]);
$enroll = $chk->fetch(PDO::FETCH_ASSOC);
$chk->closeCursor();

if (!$enroll) {
    Response::json(0, 'ไม่พบรายการนี้ หรือถูกยกเลิกไปแล้ว', null);
}

// รับค่าจาก POST
$cert_no              = trim((string) ($_POST['cert_no'] ?? ''));
$user_firstname       = trim((string) ($_POST['user_firstname'] ?? ''));
$user_lastname        = trim((string) ($_POST['user_lastname'] ?? ''));
$user_citizen_id      = trim((string) ($_POST['user_citizen_id'] ?? ''));
$user_license_no      = trim((string) ($_POST['user_license_no'] ?? ''));
$user_licensecpa_no   = trim((string) ($_POST['user_licensecpa_no'] ?? ''));
$course_name          = trim((string) ($_POST['course_name'] ?? ''));

$course_code_cpd      = trim((string) ($_POST['course_code_cpd'] ?? ''));
if ($course_code_cpd === 'manual') {
    $course_code_cpd  = trim((string) ($_POST['course_code_cpd_manual'] ?? ''));
}
$course_code_cpa      = trim((string) ($_POST['course_code_cpa'] ?? ''));
if ($course_code_cpa === 'manual') {
    $course_code_cpa  = trim((string) ($_POST['course_code_cpa_manual'] ?? ''));
}

$course_code          = trim((string) ($_POST['course_code'] ?? ''));
if (empty($course_code)) {
    $course_code      = $course_code_cpd !== '' ? $course_code_cpd : $course_code_cpa;
}

$course_approval_date = trim((string) ($_POST['course_approval_date'] ?? ''));
$course_instructor    = trim((string) ($_POST['course_instructor'] ?? ''));

$hours_account = isset($_POST['hours_account']) && $_POST['hours_account'] !== '' ? (float) $_POST['hours_account'] : 0;
$hours_ethics  = isset($_POST['hours_ethics']) && $_POST['hours_ethics'] !== '' ? (float) $_POST['hours_ethics'] : 0;
$hours_other   = isset($_POST['hours_other']) && $_POST['hours_other'] !== '' ? (float) $_POST['hours_other'] : 0;

$exam_score    = isset($_POST['exam_score']) && $_POST['exam_score'] !== '' ? (int) $_POST['exam_score'] : null;
$exam_total    = isset($_POST['exam_total']) && $_POST['exam_total'] !== '' ? (int) $_POST['exam_total'] : null;

if (isset($_POST['score_percent']) && $_POST['score_percent'] !== '') {
    $score_percent = (float) $_POST['score_percent'];
} else if ($exam_score !== null && $exam_total !== null && $exam_total > 0) {
    $score_percent = round($exam_score / $exam_total * 100, 2);
} else {
    $score_percent = null;
}

$issued_at_raw = trim((string) ($_POST['issued_at'] ?? ''));
$issued_at     = !empty($issued_at_raw) ? date('Y-m-d H:i:s', strtotime($issued_at_raw)) : date('Y-m-d H:i:s');
$capprove      = (!empty($course_approval_date) && $course_approval_date !== '0000-00-00') ? $course_approval_date : null;

// เช็คว่ามี snapshot ของ enroll_id นี้แล้วหรือไม่
$sel = $pdo_connect->prepare("SELECT cert_id FROM tbl_certificate_snapshot WHERE enroll_id = :id LIMIT 1");
$sel->execute([':id' => $enroll_id]);
$has_snap = (bool) $sel->fetchColumn();
$sel->closeCursor();

if ($has_snap) {
    // UPDATE snapshot ที่มีอยู่แล้ว
    try {
        $upd = $pdo_connect->prepare(
            "UPDATE tbl_certificate_snapshot
                SET cert_no              = :cert_no,
                    user_firstname       = :fn,
                    user_lastname        = :ln,
                    user_citizen_id      = :cid,
                    user_license_no      = :lic_cpd,
                    user_licensecpa_no   = :lic_cpa,
                    course_name          = :cname,
                    course_code_cpd      = :ccode_cpd,
                    course_code_cpa      = :ccode_cpa,
                    course_approval_date = :capprove,
                    course_instructor    = :cinstr,
                    hours_account        = :h_acc,
                    hours_ethics         = :h_eth,
                    hours_other          = :h_oth,
                    exam_score           = :score,
                    exam_total           = :total,
                    score_percent        = :percent,
                    issued_at            = :issued_at,
                    issued_by            = :admin_id
              WHERE enroll_id = :id"
        );
        $upd->execute([
            ':cert_no'   => $cert_no,
            ':fn'        => $user_firstname,
            ':ln'        => $user_lastname,
            ':cid'       => $user_citizen_id,
            ':lic_cpd'   => $user_license_no,
            ':lic_cpa'   => $user_licensecpa_no,
            ':cname'     => $course_name,
            ':ccode_cpd' => $course_code_cpd,
            ':ccode_cpa' => $course_code_cpa,
            ':capprove'  => $capprove,
            ':cinstr'    => $course_instructor,
            ':h_acc'     => $hours_account,
            ':h_eth'     => $hours_ethics,
            ':h_oth'     => $hours_other,
            ':score'     => $exam_score,
            ':total'     => $exam_total,
            ':percent'   => $score_percent,
            ':issued_at' => $issued_at,
            ':admin_id'  => (int) $admin_id,
            ':id'        => $enroll_id,
        ]);
        $upd->closeCursor();
    } catch (\PDOException $ex) {
        // Fallback กรณีตารางอาจจะยังมีคอลัมน์ course_code เดิมอยู่
        $upd2 = $pdo_connect->prepare(
            "UPDATE tbl_certificate_snapshot
                SET cert_no              = :cert_no,
                    user_firstname       = :fn,
                    user_lastname        = :ln,
                    user_citizen_id      = :cid,
                    user_license_no      = :lic_cpd,
                    user_licensecpa_no   = :lic_cpa,
                    course_name          = :cname,
                    course_code_cpd      = :ccode_cpd,
                    course_code_cpa      = :ccode_cpa,
                    course_code          = :ccode,
                    course_approval_date = :capprove,
                    course_instructor    = :cinstr,
                    hours_account        = :h_acc,
                    hours_ethics         = :h_eth,
                    hours_other          = :h_oth,
                    exam_score           = :score,
                    exam_total           = :total,
                    score_percent        = :percent,
                    issued_at            = :issued_at,
                    issued_by            = :admin_id
              WHERE enroll_id = :id"
        );
        $upd2->execute([
            ':cert_no'   => $cert_no,
            ':fn'        => $user_firstname,
            ':ln'        => $user_lastname,
            ':cid'       => $user_citizen_id,
            ':lic_cpd'   => $user_license_no,
            ':lic_cpa'   => $user_licensecpa_no,
            ':cname'     => $course_name,
            ':ccode_cpd' => $course_code_cpd,
            ':ccode_cpa' => $course_code_cpa,
            ':ccode'     => $course_code,
            ':capprove'  => $capprove,
            ':cinstr'    => $course_instructor,
            ':h_acc'     => $hours_account,
            ':h_eth'     => $hours_ethics,
            ':h_oth'     => $hours_other,
            ':score'     => $exam_score,
            ':total'     => $exam_total,
            ':percent'   => $score_percent,
            ':issued_at' => $issued_at,
            ':admin_id'  => (int) $admin_id,
            ':id'        => $enroll_id,
        ]);
        $upd2->closeCursor();
    }
} else {
    // INSERT snapshot ใหม่ถ้ายังไม่มี
    $cert_type = !empty($user_license_no) ? 'cpd' : (!empty($user_licensecpa_no) ? 'cpa' : 'cpd');
    try {
        $ins = $pdo_connect->prepare(
            "INSERT INTO tbl_certificate_snapshot
               (enroll_id, user_id, course_id, cert_no, cert_type,
                user_firstname, user_lastname, user_citizen_id, user_license_no, user_licensecpa_no,
                course_name, course_instructor, course_code_cpd, course_code_cpa, course_approval_date,
                hours_account, hours_ethics, hours_other,
                exam_score, exam_total, score_percent, issued_at, issued_by)
             VALUES
               (:enroll_id, :user_id, :course_id, :cert_no, :cert_type,
                :fn, :ln, :cid, :lic_cpd, :lic_cpa,
                :cname, :cinstr, :ccode_cpd, :ccode_cpa, :capprove,
                :h_acc, :h_eth, :h_oth,
                :score, :total, :percent, :issued_at, :issued_by)"
        );
        $ins->execute([
            ':enroll_id' => $enroll_id,
            ':user_id'   => (int) $enroll['enroll_user_id'],
            ':course_id' => (int) $enroll['enroll_course_id'],
            ':cert_no'   => $cert_no,
            ':cert_type' => $cert_type,
            ':fn'        => $user_firstname,
            ':ln'        => $user_lastname,
            ':cid'       => $user_citizen_id,
            ':lic_cpd'   => $user_license_no,
            ':lic_cpa'   => $user_licensecpa_no,
            ':cname'     => $course_name,
            ':cinstr'    => $course_instructor,
            ':ccode_cpd' => $course_code_cpd,
            ':ccode_cpa' => $course_code_cpa,
            ':capprove'  => $capprove,
            ':h_acc'     => $hours_account,
            ':h_eth'     => $hours_ethics,
            ':h_oth'     => $hours_other,
            ':score'     => $exam_score,
            ':total'     => $exam_total,
            ':percent'   => $score_percent,
            ':issued_at' => $issued_at,
            ':issued_by' => (int) $admin_id,
        ]);
        $ins->closeCursor();
    } catch (\PDOException $ex) {
        $ins2 = $pdo_connect->prepare(
            "INSERT INTO tbl_certificate_snapshot
               (enroll_id, user_id, course_id, cert_no, cert_type,
                user_firstname, user_lastname, user_citizen_id, user_license_no, user_licensecpa_no,
                course_name, course_instructor, course_code_cpd, course_code_cpa, course_code, course_approval_date,
                hours_account, hours_ethics, hours_other,
                exam_score, exam_total, score_percent, issued_at, issued_by)
             VALUES
               (:enroll_id, :user_id, :course_id, :cert_no, :cert_type,
                :fn, :ln, :cid, :lic_cpd, :lic_cpa,
                :cname, :cinstr, :ccode_cpd, :ccode_cpa, :ccode, :capprove,
                :h_acc, :h_eth, :h_oth,
                :score, :total, :percent, :issued_at, :issued_by)"
        );
        $ins2->execute([
            ':enroll_id' => $enroll_id,
            ':user_id'   => (int) $enroll['enroll_user_id'],
            ':course_id' => (int) $enroll['enroll_course_id'],
            ':cert_no'   => $cert_no,
            ':cert_type' => $cert_type,
            ':fn'        => $user_firstname,
            ':ln'        => $user_lastname,
            ':cid'       => $user_citizen_id,
            ':lic_cpd'   => $user_license_no,
            ':lic_cpa'   => $user_licensecpa_no,
            ':cname'     => $course_name,
            ':cinstr'    => $course_instructor,
            ':ccode_cpd' => $course_code_cpd,
            ':ccode_cpa' => $course_code_cpa,
            ':ccode'     => $course_code,
            ':capprove'  => $capprove,
            ':h_acc'     => $hours_account,
            ':h_eth'     => $hours_ethics,
            ':h_oth'     => $hours_other,
            ':score'     => $exam_score,
            ':total'     => $exam_total,
            ':percent'   => $score_percent,
            ':issued_at' => $issued_at,
            ':issued_by' => (int) $admin_id,
        ]);
        $ins2->closeCursor();
    }
}

Response::json(1, 'ปรับปรุงข้อมูลใบรับรองเรียบร้อยแล้ว', ['enroll_id' => $enroll_id]);
