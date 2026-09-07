<?php
// ดึงข้อมูลใบรับรองผลการสอบ 1 ใบ (สำหรับหน้าพิมพ์ + โมดัลดูรูปยืนยันตัวตน)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$enroll_id = isset($_POST['enroll_id']) ? (int) $_POST['enroll_id'] : 0;
if ($enroll_id <= 0) {
    Response::json(0, 'ไม่พบรายการ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT e.enroll_id, e.enroll_user_id, e.enroll_course_id, e.enroll_is_completed, e.create_at,
            u.user_firstname, u.user_lastname, u.user_citizen_id, u.user_cpd_no, u.user_cpa_no,
            u.user_phone, u.user_email,
            u.id_card_image, u.current_photo, u.identity_verified,
            c.course_name, c.course_instructor, 
            c.course_code_cpd_1, c.course_code_cpd_2, c.course_code_cpd_3, c.course_code_cpd_4,
            c.course_code_cpa_1, c.course_code_cpa_2, c.course_code_cpa_3, c.course_code_cpa_4,
            c.course_approval_date_1, c.course_approval_date_2, c.course_approval_date_3, c.course_approval_date_4,
            c.course_cpd_hour, c.course_cpd_ethics, c.course_cpd_other,
            c.course_number_exam, c.course_minimum_score,
            snap.cert_id, snap.cert_no AS snap_cert_no, snap.cert_type AS snap_cert_type,
            snap.user_firstname AS snap_fn, snap.user_lastname AS snap_ln, snap.user_citizen_id AS snap_cid,
            snap.user_license_no AS snap_cpd, snap.user_licensecpa_no AS snap_cpa,
            snap.id_card_image_snapshot, snap.course_name AS snap_cname, snap.course_code_cpd AS snap_ccode_cpd, snap.course_code_cpa AS snap_ccode_cpa,
            snap.course_approval_date AS snap_capprove, snap.course_instructor AS snap_cinstr,
            snap.hours_account AS snap_h_acc, snap.hours_ethics AS snap_h_eth, snap.hours_other AS snap_h_oth,
            snap.exam_score AS snap_score, snap.exam_total AS snap_total, snap.score_percent AS snap_percent,
            snap.issued_at AS snap_issued_at,
            (SELECT a.attempt_score FROM tbl_exam_attempt a
              WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
              ORDER BY a.attempt_id DESC LIMIT 1) AS score,
            (SELECT a.attempt_pass FROM tbl_exam_attempt a
              WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
              ORDER BY a.attempt_id DESC LIMIT 1) AS pass
     FROM tbl_course_enrollment e
     LEFT JOIN tbl_user u   ON e.enroll_user_id = u.user_id
     LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
     LEFT JOIN tbl_certificate_snapshot snap ON e.enroll_id = snap.enroll_id
     WHERE e.enroll_id = :id AND e.delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $enroll_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$row) {
    Response::json(0, 'ไม่พบรายการนี้ หรือถูกยกเลิกไปแล้ว', null);
}

// รูป KYC ดึงจาก S3
$img = function ($p) {
    $p = trim((string) ($p ?? ''));
    if ($p === '') return '';
    
    // หากฐานข้อมูลจำ URL เดิมเอาไว้ (เช่น https://bigsara-demo.com/am/cpdth/identity/...)
    // ให้ตัดโดเมนทิ้ง เพื่อเอาเฉพาะ S3 Key ไปเจนลิงก์ใหม่
    $p = preg_replace('#^https?://[^/]+/am/cpdth/#i', '', $p);
    $p = preg_replace('#^https?://[^/]+/cpdth/#i', '', $p);
    
    return AwsS3::getFileUrl($p);
};

$ts = !empty($row['snap_issued_at']) ? strtotime($row['snap_issued_at']) : ($row['create_at'] ? strtotime($row['create_at']) : time());

$q_idx = (int) ceil((int) date('n', $ts) / 3);
$quarter_text = 'ไตรมาส ' . $q_idx;

$fmt_code = function ($code_raw, $q) {
    if (empty($code_raw)) return '';
    $quarter = str_pad((string) $q, 2, '0', STR_PAD_LEFT);
    $code_q  = preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $code_raw, 1);
    return str_replace(['[', ']'], '', $code_q);
};

$cpd_options = [];
$cpa_options = [];
$approval_dates_map = [];

for ($q = 1; $q <= 4; $q++) {
    $cpd_code = $fmt_code($row['course_code_cpd_' . $q] ?? '', $q);
    if ($cpd_code !== '') {
        $cpd_options[] = [
            'q' => $q,
            'code' => $cpd_code,
            'label' => $cpd_code . ' (ไตรมาส ' . $q . ')'
        ];
    }
    $cpa_code = $fmt_code($row['course_code_cpa_' . $q] ?? '', $q);
    if ($cpa_code !== '') {
        $cpa_options[] = [
            'q' => $q,
            'code' => $cpa_code,
            'label' => $cpa_code . ' (ไตรมาส ' . $q . ')'
        ];
    }
    $appr_date = $row['course_approval_date_' . $q] ?? '';
    if (!empty($appr_date) && $appr_date !== '0000-00-00') {
        $approval_dates_map[$q] = $appr_date;
    }
}

// คะแนน + เปอร์เซ็นต์
$total   = $row['snap_total'] !== null ? (int) $row['snap_total'] : (int) ($row['course_number_exam'] ?? 0);
$score   = $row['snap_score'] !== null ? (int) $row['snap_score'] : ($row['score'] !== null ? (int) $row['score'] : null);
$percent = ($row['snap_percent'] !== null && $row['snap_percent'] !== '') 
    ? number_format((float) $row['snap_percent'], 2) 
    : (($score !== null && $total > 0) ? number_format($score / $total * 100, 2) : null);

$fmt_date = function ($d) {
    if (!$d || $d === '0000-00-00') { return ''; }
    $t = is_numeric($d) ? (int) $d : strtotime($d);
    return $t ? date('d/m/Y', $t) : '';
};

$fmt_datetime = function ($d) {
    if (!$d || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') { return ''; }
    $t = is_numeric($d) ? (int) $d : strtotime($d);
    return $t ? date('d/m/Y H:i', $t) : '';
};

$fmt_date_input = function ($d) {
    if (!$d || $d === '0000-00-00') { return ''; }
    $t = is_numeric($d) ? (int) $d : strtotime($d);
    return $t ? date('Y-m-d', $t) : '';
};

$user_fn = !empty($row['snap_fn']) ? $row['snap_fn'] : ($row['user_firstname'] ?? '');
$user_ln = !empty($row['snap_ln']) ? $row['snap_ln'] : ($row['user_lastname'] ?? '');
$cid     = !empty($row['snap_cid']) ? $row['snap_cid'] : ($row['user_citizen_id'] ?? '');
$cpd     = !empty($row['snap_cpd']) ? $row['snap_cpd'] : ($row['user_cpd_no'] ?? '');
$cpa     = !empty($row['snap_cpa']) ? $row['snap_cpa'] : ($row['user_cpa_no'] ?? '');

$cname   = !empty($row['snap_cname']) ? $row['snap_cname'] : ($row['course_name'] ?? '');

// รหัสหลักสูตร CPD และ CPA ตั้งต้นของไตรมาสปัจจุบัน
$curr_cpd_code = $fmt_code($row['course_code_cpd_' . $q_idx] ?? ($row['course_code_cpd_1'] ?? ''), $q_idx);
$curr_cpa_code = $fmt_code($row['course_code_cpa_' . $q_idx] ?? ($row['course_code_cpa_1'] ?? ''), $q_idx);

$ccode_cpd = isset($row['snap_ccode_cpd']) ? $row['snap_ccode_cpd'] : $curr_cpd_code;
$ccode_cpa = isset($row['snap_ccode_cpa']) ? $row['snap_ccode_cpa'] : $curr_cpa_code;
$ccode     = ($ccode_cpd !== '' ? $ccode_cpd : $ccode_cpa);

$cappr   = !empty($row['snap_capprove']) ? $row['snap_capprove'] : ($row['course_approval_date_' . $q_idx] ?? ($row['course_approval_date_1'] ?? ''));
$cinstr  = !empty($row['snap_cinstr']) ? $row['snap_cinstr'] : ($row['course_instructor'] ?? '');

$h_acc   = $row['snap_h_acc'] !== null ? (float) $row['snap_h_acc'] : (float) ($row['course_cpd_hour'] ?? 0);
$h_eth   = $row['snap_h_eth'] !== null ? (float) $row['snap_h_eth'] : (float) ($row['course_cpd_ethics'] ?? 0);
$h_oth   = $row['snap_h_oth'] !== null ? (float) $row['snap_h_oth'] : (float) ($row['course_cpd_other'] ?? 0);

$cert_no = !empty($row['snap_cert_no']) 
    ? $row['snap_cert_no'] 
    : (((string) ($row['pass'] ?? '0') === '1') ? (date('ym', $ts) . str_pad((string) $row['enroll_id'], 4, '0', STR_PAD_LEFT)) : '-');

Response::json(1, 'Success', [
    'enroll_id'            => (int) $row['enroll_id'],
    'enroll_key'           => \App\Utility\Cipher::encrypt((string) $row['enroll_id']),
    'has_snapshot'         => !empty($row['cert_id']) ? 1 : 0,
    'cert_no'              => $cert_no,
    'user_firstname'       => (string) $user_fn,
    'user_lastname'        => (string) $user_ln,
    'fullname'             => trim($user_fn . ' ' . $user_ln),
    'accountant_no'        => (string) ($cid !== '' ? $cid : $cpd),
    'citizen_id'           => (string) $cid,
    'cpd_no'               => (string) $cpd,
    'cpa_no'               => (string) $cpa,
    'phone'                => (string) ($row['user_phone'] ?? ''),
    'email'                => (string) ($row['user_email'] ?? ''),
    'course_name'          => (string) $cname,
    'course_code'          => (string) $ccode,
    'course_code_cpd'      => (string) $ccode_cpd,
    'course_code_cpa'      => (string) $ccode_cpa,
    'course_code_cpd_curr' => $curr_cpd_code,
    'course_code_cpa_curr' => $curr_cpa_code,
    'cpd_options'          => $cpd_options,
    'cpa_options'          => $cpa_options,
    'approval_dates_map'   => $approval_dates_map,
    'quarter_text'         => $quarter_text,
    'quarter_idx'          => $q_idx,
    'approval_date'        => $fmt_date($cappr),
    'approval_date_raw'    => $fmt_date_input($cappr),
    'instructor'           => (string) $cinstr,
    'cpd_hour'             => number_format($h_acc, 2),
    'cpd_ethics'           => number_format($h_eth, 2),
    'cpd_other'            => number_format($h_oth, 2),
    'hours_account'        => $h_acc,
    'hours_ethics'         => $h_eth,
    'hours_other'          => $h_oth,
    'train_date'           => $fmt_datetime($ts),
    'train_date_fmt'       => $fmt_datetime($ts),
    'issued_at_raw'        => date('Y-m-d H:i:s', $ts),
    'num_exam'             => $total,
    'min_score'            => (int) ($row['course_minimum_score'] ?? 0),
    'score'                => $score,
    'percent'              => $percent,
    'exam_passed'          => ((string) ($row['pass'] ?? '0') === '1') ? 1 : 0,
    'cert_approved'        => ((string) ($row['enroll_is_completed'] ?? '0') === '1') ? 1 : 0,
    'id_card_image'        => $img($row['id_card_image_snapshot'] ?? $row['id_card_image'] ?? ''),
    'current_photo'        => $img($row['current_photo'] ?? ''),
]);
