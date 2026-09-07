<?php
// โหลดคลาส Pdf จาก backoffice (เนื่องจาก cpdth/src ไม่มี Pdf.php)
$backoffice_path = dirname(__DIR__, 3) . '/backoffice';
if (!class_exists('App\Utility\Pdf') && file_exists($backoffice_path . '/src/Utility/Pdf.php')) {
    require_once $backoffice_path . '/src/Utility/Pdf.php';
}

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Pdf;
use App\Utility\Response;

// เปิดผ่าน form POST (ไม่มี header Authorization) -> รับ token จาก POST แทน
if (empty($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && ! empty($_POST['access_token'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_POST['access_token'];
}

$access_token = Auth::requireUserToken();
$admin_id     = $access_token->user_id ?? null;
if (! $admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$enroll_id = isset($_POST['enroll_id']) ? (int) $_POST['enroll_id'] : 0;
if ($enroll_id <= 0) {
    Response::json(0, 'ไม่พบรายการ', null);
}

// ประเภทใบ: cpd=ผู้ทำบัญชี (ค่าเริ่มต้น), cpa=ผู้สอบบัญชี
$cert_type = strtolower(trim((string) ($_POST['cert_type'] ?? 'cpd')));
if (! in_array($cert_type, ['cpd', 'cpa'], true)) {
    $cert_type = 'cpd';
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (! $pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ---- 1) มี snapshot ของ enroll นี้แล้วหรือยัง (ไม่แยกประเภท cpd/cpa ในการค้นหาเพื่อให้ขึ้นรวมใบเดียว) ----
$sel = $pdo_connect->prepare(
    "SELECT * FROM tbl_certificate_snapshot
      WHERE enroll_id = :e
      ORDER BY cert_id ASC LIMIT 1"
);
$sel->execute([':e' => $enroll_id]);
$snap = $sel->fetch(PDO::FETCH_ASSOC);
$sel->closeCursor();

// ---- 2) ยังไม่มี -> ดึงข้อมูลสด แล้ว freeze ลง snapshot ----
if (! $snap) {
    $stmt = $pdo_connect->prepare(
        "SELECT e.enroll_id,
                e.enroll_user_id, 
                e.enroll_course_id, 
                e.create_at, 
                e.enroll_is_completed,
                u.user_firstname, 
                u.user_lastname, 
                u.user_citizen_id, 
                u.user_cpd_no, 
                u.user_cpa_no, 
                u.id_card_image,
                c.course_name, 
                c.course_instructor, 
                c.course_code_cpd_1, 
                c.course_code_cpd_2, 
                c.course_code_cpd_3, 
                c.course_code_cpd_4,
                c.course_code_cpa_1, 
                c.course_code_cpa_2, 
                c.course_code_cpa_3, 
                c.course_code_cpa_4,
                c.course_approval_date_1, 
                c.course_approval_date_2, 
                c.course_approval_date_3, 
                c.course_approval_date_4,
                c.course_cpd_hour, 
                c.course_cpd_ethics, 
                c.course_cpd_other,
                c.course_cpa_hour, 
                c.course_cpa_ethics, 
                c.course_cpa_other,
                c.course_number_exam,
                (SELECT a.attempt_score FROM tbl_exam_attempt a
                  WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
                  ORDER BY a.attempt_id DESC LIMIT 1) AS score,
                (SELECT a.attempt_pass FROM tbl_exam_attempt a
                  WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
                  ORDER BY a.attempt_pass DESC, a.attempt_id DESC LIMIT 1) AS pass,
                (SELECT a.create_at FROM tbl_exam_attempt a
                  WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
                  ORDER BY a.attempt_id DESC LIMIT 1) AS exam_completed_at
         FROM tbl_course_enrollment e
         LEFT JOIN tbl_user u   ON e.enroll_user_id = u.user_id
         LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
         WHERE e.enroll_id = :id AND e.delete_at IS NULL LIMIT 1"
    );
    $stmt->execute([':id' => $enroll_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (! $row) {
        Response::json(0, 'ไม่พบรายการนี้ หรือถูกยกเลิกไปแล้ว', null);
    }

    $is_passed   = ((string) ($row['pass'] ?? '0')) === '1';
    $is_approved = ((string) ($row['enroll_is_completed'] ?? '0')) === '1';

    if (! $is_passed && ! $is_approved) {
        Response::json(0, 'ไม่สามารถออกใบรับรองได้ เนื่องจากผู้เรียนยังสอบไม่ผ่านและยังไม่อนุมัติ', null);
    }

    // วันที่ออกใบ = วันที่สอบเสร็จ (attempt ล่าสุด) ; fallback วันที่สมัคร/ปัจจุบัน
    $exam_dt = ! empty($row['exam_completed_at']) ? $row['exam_completed_at']
        : (! empty($row['create_at']) ? $row['create_at'] : date('Y-m-d H:i:s'));
    $ts = strtotime($exam_dt);

    $ym       = date('ym', $ts);
    $cnt_stmt = $pdo_connect->prepare("SELECT COUNT(DISTINCT enroll_id) FROM tbl_certificate_snapshot WHERE cert_no LIKE :ym");
    $cnt_stmt->execute([':ym' => $ym . '%']);
    $seq = ((int) $cnt_stmt->fetchColumn()) + 1;
    $cnt_stmt->closeCursor();

    $cert_no = $ym . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

    // กำหนดค่าตั้งต้นในการบันทึกลงตาราง snapshot
    $has_cpd = ! empty($row['user_cpd_no']);
    $has_cpa = ! empty($row['user_cpa_no']);
    $cert_type_to_save = $has_cpd ? 'cpd' : ($has_cpa ? 'cpa' : 'cpd');

    // เลขทะเบียนตามสิทธิ์ที่มี
    $lic_cpd = trim((string) ($row['user_cpd_no'] ?? ''));
    $lic_cpa = trim((string) ($row['user_cpa_no'] ?? ''));

    // หาไตรมาสเพื่อใช้เลือกคอลัมน์ของรหัสหลักสูตรและวันที่อนุมัติ (1-4)
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

    $total   = (int) ($row['course_number_exam'] ?? 0);
    $score   = $row['score'] !== null ? (int) $row['score'] : null;
    $percent = ($score !== null && $total > 0) ? round($score / $total * 100, 2) : null;

    $approval_date = (! empty($row['course_approval_date_' . $q_idx]) && $row['course_approval_date_' . $q_idx] !== '0000-00-00')
        ? $row['course_approval_date_' . $q_idx] : null;

    // ชั่วโมงสำหรับบันทึก (เลือก CPD เป็นหลัก หากไม่มีให้ใช้ CPA)
    $h_acc = $has_cpd ? (float) ($row['course_cpd_hour'] ?? 0) : (float) ($row['course_cpa_hour'] ?? 0);
    $h_eth = $has_cpd ? (float) ($row['course_cpd_ethics'] ?? 0) : (float) ($row['course_cpa_ethics'] ?? 0);
    $h_oth = $has_cpd ? (float) ($row['course_cpd_other'] ?? 0) : (float) ($row['course_cpa_other'] ?? 0);

    // INSERT (รองรับฟิลด์ user_licensecpa_no ใน DB และกันชนด้วย UNIQUE(enroll_id,cert_type))
    try {
        $ins = $pdo_connect->prepare(
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
            ':user_id'   => (int) $row['enroll_user_id'],
            ':course_id' => (int) $row['enroll_course_id'],
            ':cert_no'   => $cert_no,
            ':cert_type' => $cert_type_to_save,
            ':fn'        => (string) ($row['user_firstname'] ?? ''),
            ':ln'        => (string) ($row['user_lastname'] ?? ''),
            ':cid'       => (string) ($row['user_citizen_id'] ?? ''),
            ':lic'       => $lic_cpd,
            ':lic_cpa'   => $lic_cpa,
            ':idimg'     => (string) ($row['id_card_image'] ?? ''),
            ':cname'     => (string) ($row['course_name'] ?? ''),
            ':cinstr'    => (string) ($row['course_instructor'] ?? ''),
            ':ccode_cpd' => $ccode_cpd,
            ':ccode_cpa' => $ccode_cpa,
            ':capprove'  => $approval_date,
            ':h_acc'     => $h_acc,
            ':h_eth'     => $h_eth,
            ':h_oth'     => $h_oth,
            ':score'     => $score,
            ':total'     => $total > 0 ? $total : null,
            ':percent'   => $percent,
            ':issued_at' => date('Y-m-d H:i:s', $ts),
            ':issued_by' => (int) $admin_id,
        ]);
        $ins->closeCursor();
    } catch (\PDOException $ex) {
        if ($ex->getCode() !== '23000') {
            error_log('CertSnapshot insert Error: ' . $ex->getMessage());
        }
    }

    // อ่าน snapshot ที่เพิ่งสร้าง มาใช้ render
    $sel2 = $pdo_connect->prepare(
        "SELECT * FROM tbl_certificate_snapshot
          WHERE enroll_id = :e
          ORDER BY cert_id ASC LIMIT 1"
    );
    $sel2->execute([':e' => $enroll_id]);
    $snap = $sel2->fetch(PDO::FETCH_ASSOC);
    $sel2->closeCursor();

    if (! $snap) {
        Response::json(0, 'สร้างใบรับรองไม่สำเร็จ', null);
    }
}

// ---- 3) เตรียมค่าจาก snapshot (ทั้งหมด freeze แล้ว) ----
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$cert_no  = (string) $snap['cert_no'];
$ts       = ! empty($snap['issued_at']) ? strtotime($snap['issued_at']) : time();
$fullname = trim(($snap['user_firstname'] ?? '') . ' ' . ($snap['user_lastname'] ?? ''));

$user_cpd_no = trim((string) ($snap['user_license_no'] ?? ''));
$user_cpa_no = trim((string) ($snap['user_licensecpa_no'] ?? ''));

// Fallback ถ้าใน snapshot ยังว่างอยู่ ให้ลองดึงจาก tbl_user
$user_id_check = ! empty($snap['user_id']) ? (int) $snap['user_id'] : 0;
if ($user_cpd_no === '' && $user_cpa_no === '' && $user_id_check > 0) {
    $u_stmt = $pdo_connect->prepare("SELECT user_cpd_no, user_cpa_no FROM tbl_user WHERE user_id = :uid LIMIT 1");
    $u_stmt->execute([':uid' => $user_id_check]);
    $u_data = $u_stmt->fetch(PDO::FETCH_ASSOC);
    $u_stmt->closeCursor();
    if ($u_data) {
        $user_cpd_no = trim((string) ($u_data['user_cpd_no'] ?? ''));
        $user_cpa_no = trim((string) ($u_data['user_cpa_no'] ?? ''));
    }
}

// ดึงข้อมูลรหัสหลักสูตรและชั่วโมงของทั้ง CPD และ CPA จาก tbl_course เพื่อแสดงผลแยกตามทะเบียนที่มี
$course_cpd_code = '';
$course_cpa_code = '';
$cpd_hours_text = '-';
$cpa_hours_text = '-';

$c_stmt = $pdo_connect->prepare(
    "SELECT course_code_cpd_1, course_code_cpd_2, course_code_cpd_3, course_code_cpd_4,
            course_code_cpa_1, course_code_cpa_2, course_code_cpa_3, course_code_cpa_4,
            course_cpd_hour, course_cpd_ethics, course_cpd_other, 
            course_cpa_hour, course_cpa_ethics, course_cpa_other 
     FROM tbl_course WHERE course_id = :cid LIMIT 1"
);
$c_stmt->execute([':cid' => (int) $snap['course_id']]);
$course_row = $c_stmt->fetch(PDO::FETCH_ASSOC);
$c_stmt->closeCursor();

$q_idx = (int) ceil((int) date('n', $ts) / 3);
$quarter = str_pad((string) $q_idx, 2, '0', STR_PAD_LEFT);

    // รหัสหลักสูตร CPD
    if (!empty($snap['course_code_cpd'])) {
        $course_cpd_code = (string) $snap['course_code_cpd'];
    } else if ($course_row) {
        $cpd_raw = (string) ($course_row['course_code_cpd_' . $q_idx] ?? '');
        if ($cpd_raw !== '') {
            $code_q = preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $cpd_raw, 1);
            $course_cpd_code = str_replace(['[', ']'], '', $code_q);
        }
    }
    // รหัสหลักสูตร CPA
    if (!empty($snap['course_code_cpa'])) {
        $course_cpa_code = (string) $snap['course_code_cpa'];
    } else if ($course_row) {
        $cpa_raw = (string) ($course_row['course_code_cpa_' . $q_idx] ?? '');
        if ($cpa_raw !== '') {
            $code_q2 = preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $cpa_raw, 1);
            $course_cpa_code = str_replace(['[', ']'], '', $code_q2);
        }
    }

    if ($course_row) {
        // ชั่วโมง CPD
        $cpd_h = number_format((float) ($course_row['course_cpd_hour'] ?? 0), 2);
        $cpd_e = number_format((float) ($course_row['course_cpd_ethics'] ?? 0), 2);
        $cpd_o = (float) ($course_row['course_cpd_other'] ?? 0);
        $cpd_hours_text = 'บัญชี ' . $cpd_h . ' ชม. จรรยาบรรณ ' . $cpd_e . ' ชม.';
        if ($cpd_o > 0) {
            $cpd_hours_text .= ' อื่น ๆ ' . number_format($cpd_o, 2) . ' ชม.';
        }

        // ชั่วโมง CPA
        $cpa_h = number_format((float) ($course_row['course_cpa_hour'] ?? 0), 2);
        $cpa_e = number_format((float) ($course_row['course_cpa_ethics'] ?? 0), 2);
        $cpa_o = (float) ($course_row['course_cpa_other'] ?? 0);
        $cpa_hours_text = 'บัญชี ' . $cpa_h . ' ชม. จรรยาบรรณ ' . $cpa_e . ' ชม.';
        if ($cpa_o > 0) {
            $cpa_hours_text .= ' อื่น ๆ ' . number_format($cpa_o, 2) . ' ชม.';
        }
    }

// ตรวจสอบและสร้างแถวแสดงผลตามเงื่อนไข (มีอย่างใดอย่างหนึ่ง หรือ มีทั้งคู่ต่อกันลงมา)
$license_rows_html = '';
if ($user_cpd_no !== '' && $user_cpa_no !== '') {
    $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้ทำบัญชี</td><td style="font-size:18px;">:&nbsp;' . $esc($user_cpd_no) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้สอบบัญชี</td><td style="font-size:18px;">:&nbsp;' . $esc($user_cpa_no) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">ชื่อหลักสูตร</td><td style="font-size:18px;">:&nbsp;' . $esc($snap['course_name'] ?? '-') . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl" style="padding-top: 15px;">รหัสหลักสูตรผู้ทำบัญชี</td><td style="font-size:17px; padding-top: 15px;">:&nbsp;' . $esc($course_cpd_code !== '' ? $course_cpd_code : '-') . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">รหัสหลักสูตรผู้สอบบัญชี</td><td style="font-size:17px;">:&nbsp;' . $esc($course_cpa_code !== '' ? $course_cpa_code : '-') . '</td></tr>';
    
    $approve_date = (! empty($snap['course_approval_date']) && $snap['course_approval_date'] !== '0000-00-00')
        ? date('d/m/Y', strtotime($snap['course_approval_date'])) : '-';
    
    $license_rows_html .= '<tr><td align="right" class="lbl">วันที่อนุมัติหลักสูตร</td><td style="font-size:17px;">:&nbsp;' . $esc($approve_date) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">นับชั่วโมงผู้ทำบัญชี (CPD)</td><td style="font-size:17px;">:&nbsp;' . $esc($cpd_hours_text) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">นับชั่วโมงผู้สอบบัญชี (CPA)</td><td style="font-size:17px;">:&nbsp;' . $esc($cpa_hours_text) . '</td></tr>';
} elseif ($user_cpd_no !== '') {
    $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้ทำบัญชี</td><td style="font-size:18px;">:&nbsp;' . $esc($user_cpd_no) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">ชื่อหลักสูตร</td><td style="font-size:18px;">:&nbsp;' . $esc($snap['course_name'] ?? '-') . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl" style="padding-top: 15px;">รหัสหลักสูตรผู้ทำบัญชี</td><td style="font-size:17px; padding-top: 15px;">:&nbsp;' . $esc($course_cpd_code !== '' ? $course_cpd_code : '-') . '</td></tr>';
    
    $approve_date = (! empty($snap['course_approval_date']) && $snap['course_approval_date'] !== '0000-00-00')
        ? date('d/m/Y', strtotime($snap['course_approval_date'])) : '-';
        
    $license_rows_html .= '<tr><td align="right" class="lbl">วันที่อนุมัติหลักสูตร</td><td style="font-size:17px;">:&nbsp;' . $esc($approve_date) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">นับชั่วโมงผู้ทำบัญชี (CPD)</td><td style="font-size:17px;">:&nbsp;' . $esc($cpd_hours_text) . '</td></tr>';
} elseif ($user_cpa_no !== '') {
    $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้สอบบัญชี</td><td style="font-size:18px;">:&nbsp;' . $esc($user_cpa_no) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">ชื่อหลักสูตร</td><td style="font-size:18px;">:&nbsp;' . $esc($snap['course_name'] ?? '-') . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl" style="padding-top: 15px;">รหัสหลักสูตรผู้สอบบัญชี</td><td style="font-size:17px; padding-top: 15px;">:&nbsp;' . $esc($course_cpa_code !== '' ? $course_cpa_code : '-') . '</td></tr>';
    
    $approve_date = (! empty($snap['course_approval_date']) && $snap['course_approval_date'] !== '0000-00-00')
        ? date('d/m/Y', strtotime($snap['course_approval_date'])) : '-';
        
    $license_rows_html .= '<tr><td align="right" class="lbl">วันที่อนุมัติหลักสูตร</td><td style="font-size:17px;">:&nbsp;' . $esc($approve_date) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">นับชั่วโมงผู้สอบบัญชี (CPA)</td><td style="font-size:17px;">:&nbsp;' . $esc($cpa_hours_text) . '</td></tr>';
} else {
    $fallback_lic       = trim((string) ($snap['user_citizen_id'] ?? ''));
    $license_rows_html .= '<tr><td align="right" class="lbl">เลขประจำตัวประชาชน</td><td style="font-size:18px;">:&nbsp;' . $esc($fallback_lic !== '' ? $fallback_lic : '-') . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">ชื่อหลักสูตร</td><td style="font-size:18px;">:&nbsp;' . $esc($snap['course_name'] ?? '-') . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl" style="padding-top: 15px;">รหัสหลักสูตรผู้ทำบัญชี</td><td style="font-size:17px; padding-top: 15px;">:&nbsp;' . $esc($course_cpd_code !== '' ? $course_cpd_code : ($course_cpa_code !== '' ? $course_cpa_code : '-')) . '</td></tr>';
    
    $approve_date = (! empty($snap['course_approval_date']) && $snap['course_approval_date'] !== '0000-00-00')
        ? date('d/m/Y', strtotime($snap['course_approval_date'])) : '-';
        
    $license_rows_html .= '<tr><td align="right" class="lbl">วันที่อนุมัติหลักสูตร</td><td style="font-size:17px;">:&nbsp;' . $esc($approve_date) . '</td></tr>';
    $license_rows_html .= '<tr><td align="right" class="lbl">นับชั่วโมงผู้ทำบัญชี (CPD)</td><td style="font-size:17px;">:&nbsp;' . $esc($cpd_hours_text) . '</td></tr>';
}

$course    = (string) ($snap['course_name'] ?? '');
$instructor = trim((string) ($snap['course_instructor'] ?? '')) ?: '-';
$train_date = date('d/m/Y', $ts);

$score_percent = $snap['score_percent'];
$score_txt     = ($score_percent !== null && $score_percent !== '') ? number_format((float) $score_percent, 2) . ' %' : '-';

// รูปบัตรประชาชน (KYC)
$id_img_uri = '';
$id_card    = trim((string) ($snap['id_card_image_snapshot'] ?? ''));
if ($id_card !== '') {
    $s3_key = $id_card;
    if (strpos($id_card, 'http://') === 0 || strpos($id_card, 'https://') === 0) {
        $s3_key = ltrim((string) parse_url($id_card, PHP_URL_PATH), '/');
    } else {
        $s3_key = ltrim($id_card, '/');
    }

    // เรียกใช้ AwsS3::getFileUrl เพื่อดึงภาพจาก AWS S3
    $url = \App\Utility\AwsS3::getFileUrl($s3_key);

    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        $bin = @file_get_contents($url);
        if ($bin !== false && strlen($bin) > 0) {
            $mime = 'image/jpeg';
            // mPDF ไม่รองรับ CSS object-fit: cover จึงใช้ PHP GD ในการครอปรูปสัดส่วน 220x135 ก่อนสร้าง PDF
            if (function_exists('imagecreatefromstring')) {
                $src_img = @imagecreatefromstring($bin);
                if ($src_img !== false) {
                    $orig_w = imagesx($src_img);
                    $orig_h = imagesy($src_img);

                    $target_w     = 440; // 220px * 2 (HD)
                    $target_h     = 270; // 135px * 2 (HD)
                    $target_ratio = $target_w / $target_h;
                    $orig_ratio   = $orig_w / $orig_h;

                    if ($orig_ratio > $target_ratio) {
                        $crop_h = $orig_h;
                        $crop_w = (int) ($orig_h * $target_ratio);
                        $crop_x = (int) (($orig_w - $crop_w) / 2);
                        $crop_y = 0;
                    } else {
                        $crop_w = $orig_w;
                        $crop_h = (int) ($orig_w / $target_ratio);
                        $crop_x = 0;
                        $crop_y = (int) (($orig_h - $crop_h) / 2);
                    }

                    $dst_img = imagecreatetruecolor($target_w, $target_h);
                    imagecopyresampled($dst_img, $src_img, 0, 0, $crop_x, $crop_y, $target_w, $target_h, $crop_w, $crop_h);

                    ob_start();
                    imagejpeg($dst_img, null, 92);
                    $cropped_bin = ob_get_clean();

                    imagedestroy($src_img);
                    imagedestroy($dst_img);

                    if ($cropped_bin !== false && strlen($cropped_bin) > 0) {
                        $bin  = $cropped_bin;
                        $mime = 'image/jpeg';
                    }
                }
            } else {
                $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                if ($ext === 'png') {
                    $mime = 'image/png';
                } elseif ($ext === 'gif') {
                    $mime = 'image/gif';
                } elseif ($ext === 'webp') {
                    $mime = 'image/webp';
                }

            }
            $id_img_uri = 'data:' . $mime . ';base64,' . base64_encode($bin);
        } else {
            $id_img_uri = $url;
        }
    } else {
        // รูปเก่า (cpdth local fallback)
        $sibling    = dirname(dirname(__DIR__, 3)) . '/cpdth/';
        $id_img_uri = Pdf::fileToDataUri($sibling . ltrim($id_card, '/'));
    }
}

// ---- HTML สำหรับ PDF (mPDF: ใช้ table แทน flex) ----
$company = 'บริษัท เอ เอ็ม ซีพีดี จำกัด';
$tax_id  = '0105565002221';
$agency  = '06-330';

$id_img_html = $id_img_uri !== ''
    ? '<img src="' . $id_img_uri . '" style="width:240px;height:145px;border:1px solid #bbb;object-fit:cover;">'
    : '';

// โลโก้ AM GROUP
$logo_uri = Pdf::fileToDataUri(dirname(__DIR__, 3) . '/backoffice/assets/images/am-group-logo.png');
if ($logo_uri === '') {
    $logo_uri = Pdf::fileToDataUri(dirname(__DIR__, 2) . '/assets/images/logo/am-group-logo.png');
}
$logo_html = $logo_uri !== '' ? '<img src="' . $logo_uri . '" style="width:115px;">' : 'AM GROUP';

// ลายเซ็นผู้มีอำนาจ (เช็ค .jpg ก่อนให้ตรงกับฝั่งหลังบ้าน)
$sign_uri = Pdf::fileToDataUri(dirname(__DIR__, 3) . '/backoffice/assets/images/signature-amgroup.jpg');
if ($sign_uri === '') {
    $sign_uri = Pdf::fileToDataUri(dirname(__DIR__, 3) . '/backoffice/assets/images/signature-amgroup.png');
}
if ($sign_uri === '') {
    $sign_uri = Pdf::fileToDataUri(dirname(__DIR__, 2) . '/assets/images/signature-amgroup.png');
}
$sign_html = $sign_uri !== '' ? '<img src="' . $sign_uri . '" style="width:100px;height:80px;">' : '';

$html = '
<style>
    body {
        font-family: thsarabun;
        color: #222;
        font-size: 18pt;
        line-height: 1.35;
    }

    .hd { font-size: 16pt; }
    .hd-sub { font-size: 17px; }
    .logo { font-size: 20pt; font-weight: bold; color: #345585; padding-left: 20px; padding-top: 5px; }
    .company { font-size: 25px; color: #3b5998; font-weight: bold; }
    .cert-no { font-size: 17px; font-weight: normal; color: #555; }
    .title { text-align: center; font-weight: bold; font-size: 21px; margin: 16px 0 10px 0; color: #111; }
    .lead { text-indent: 100px; text-align: left; margin: 8px 0; line-height: 1.2; font-size: 17px; color: #222; }
    .detail { font-size: 18pt; margin: 0 auto; }
    .detail td { vertical-align: top; line-height: 1.0; padding: 0px 3px; }
    .lbl { text-align: right; white-space: nowrap; font-size: 17px; }
    .val { text-align: left; font-size: 18px; color: #222; }
    .sign { text-align: center; line-height: 1.4; font-size: 18px; color: #222; }
</style>

<table width="100%" class="hd"><tr>
    <td width="30%" valign="middle" class="logo">' . $logo_html . '</td>
    <td width="40%" align="center" valign="middle" class="company">' . $esc($company) . '</td>
    <td width="30%" align="right" valign="top" class="cert-no">' . $esc($cert_no) . '</td>
</tr></table>
<table width="100%" class="hd-sub" style="margin-top:50px;"><tr>
    <td width="50%" align="left" style="padding-left: 20px;">CPD e-Learning</td>
    <td width="50%" align="right">วันที่ ' . $esc($train_date) . '</td>
</tr></table>

<div class="title">หนังสือรับรอง</div>

<p class="lead" style="padding-left: 20px;">ตามที่' . $esc($company) . ' เลขประจำตัวผู้เสียภาษี ' . $esc($tax_id) . ' รหัสหน่วยงาน ' . $esc($agency) . '<br>
ได้จัดฝึกอบรมและสัมมนาหลักสูตร ที่ได้รับความเห็นชอบจากสภาวิชาชีพบัญชี ในพระบรมราชูปถัมภ์ตามข้อบังคับกับสภาวิชาชีพ<br>
เพื่อพัฒนาความรู้ต่อเนื่องทางวิชาชีพของผู้ทำบัญชี มีรายละเอียดดังนี้</p>

<table align="center" cellpadding="3" class="detail">
    <tr><td align="right" class="lbl" style="padding-top: 15px;">ผู้เข้าสัมมนา</td><td style="font-size:18px; padding-top: 15px;">:&nbsp;' . $esc($fullname !== '' ? $fullname : '-') . '</td></tr>
    ' . $license_rows_html . '
    <tr><td align="right" class="lbl">วิทยากรนำเสนอ</td><td style="font-size:17px;">:&nbsp;' . $esc($instructor) . '</td></tr>
    <tr><td align="right" class="lbl">วัน เดือน ปี ที่อบรม</td><td style="font-size:17px;">:&nbsp;' . $esc($train_date) . '</td></tr>
    <tr><td align="right" class="lbl">สถานที่</td><td style="font-size:17px;">:&nbsp;การพัฒนาความรู้ต่อเนื่องผ่านระบบเครือข่ายอินเตอร์เน็ต (e-Learning)</td></tr>
    <tr><td align="right" class="lbl">คะแนนสอบ</td><td style="font-size:17px;">:&nbsp;' . $esc($score_txt) . '</td></tr>
</table>

<table width="100%" style="margin-top: 20px;"><tr>
    <td width="50%" valign="bottom" style="padding-left: 20px;">' . $id_img_html . '</td>
    <td width="50%" valign="bottom" class="sign">
        บริษัทฯ ขอรับรองว่าท่านได้ผ่านการสัมมนาหลักสูตรดังกล่าวจริง<br>
        ' . ($sign_html !== '' ? $sign_html . '<br>' : '<br><br><br>') . '
        ขอแสดงความนับถือ<br>
        ในนาม ' . $esc($company) . '
    </td>
</tr></table>
';

try {
    $pdf = Pdf::make($html, [
        'title'         => 'CourseCertificate',
        'font'          => 'thsarabun',
        'font_size'     => 18,
        'margin_left'   => 16,
        'margin_right'  => 16,
        'margin_top'    => 13,
        'margin_bottom' => 12,
    ]);
    $filename = 'certificate_' . $cert_no . '.pdf';

    // โหมดพรีวิว: ส่ง base64 ผ่าน JSON (เลี่ยง download manager จับไฟล์ PDF)
    if (($_POST['mode'] ?? '') === 'base64') {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        Response::json(1, 'ok', ['filename' => $filename, 'pdf' => base64_encode($pdf)]);
    }

    // โหมด stream ปกติ (เปิด/ดาวน์โหลดตรง)
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $pdf;
    exit;
} catch (\Throwable $e) {
    error_log('ExportCertificate Error: ' . $e->getMessage());
    Response::json(0, 'สร้าง PDF ไม่สำเร็จ: ' . $e->getMessage(), null);
}
