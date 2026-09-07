<?php
// อนุมัติออกใบรับรองการสอบ -> ตั้ง enroll_is_completed='1' (ผ่าน/จบหลักสูตร) + enroll_completed_at=NOW()
// เงื่อนไข: ต้องสอบผ่านจริง (attempt_pass=1) และยังไม่เคยอนุมัติมาก่อน

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

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

// ตรวจว่ามีรายการจริง + สอบผ่าน (attempt_pass ครั้งล่าสุด = 1) + ยังไม่อนุมัติ
$chk = $pdo_connect->prepare(
    "SELECT e.enroll_is_completed,
            (SELECT a.attempt_pass FROM tbl_exam_attempt a
              WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_course_id = e.enroll_course_id
              ORDER BY a.attempt_pass DESC, a.attempt_id DESC LIMIT 1) AS pass
       FROM tbl_course_enrollment e
      WHERE e.enroll_id = :id AND e.delete_at IS NULL LIMIT 1"
);
$chk->execute([':id' => $enroll_id]);
$row = $chk->fetch(PDO::FETCH_ASSOC);
$chk->closeCursor();

if (!$row) {
    Response::json(0, 'ไม่พบรายการนี้ หรือถูกยกเลิกไปแล้ว', null);
}
if ((string) ($row['pass'] ?? '0') !== '1') {
    Response::json(0, 'รายการนี้ยังสอบไม่ผ่าน ไม่สามารถอนุมัติออกใบรับรองได้', null);
}
if ((string) ($row['enroll_is_completed'] ?? '0') === '1') {
    Response::json(0, 'รายการนี้อนุมัติออกใบรับรองไปแล้ว', null);
}

$upd = $pdo_connect->prepare(
    "UPDATE tbl_course_enrollment
        SET enroll_is_completed = '1', enroll_completed_at = NOW()
      WHERE enroll_id = :id AND delete_at IS NULL"
);
$upd->execute([':id' => $enroll_id]);

// ดึงข้อมูลเพื่อสร้าง snapshot ลง tbl_certificate_snapshot ล่วงหน้า เพื่อให้รูปภาพและข้อมูลพร้อมถูกอัปเดต/แสดงผล
$stmt = $pdo_connect->prepare(
    "SELECT e.enroll_id, e.enroll_user_id, e.enroll_course_id, e.create_at, e.enroll_is_completed,
            u.user_firstname, u.user_lastname, u.user_citizen_id, u.user_cpd_no, u.user_cpa_no, u.id_card_image,
            c.course_name, c.course_instructor, c.course_code_cpd_1, c.course_code_cpa_1,
            c.course_approval_date_1, c.course_cpd_hour, c.course_cpd_ethics, c.course_cpd_other,
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

if ($row) {
    // หาวันที่สอบเสร็จ
    $exam_dt = ! empty($row['exam_completed_at']) ? $row['exam_completed_at']
        : (! empty($row['create_at']) ? $row['create_at'] : date('Y-m-d H:i:s'));
    $ts = strtotime($exam_dt);

    $ym = date('ym', $ts);

    // กำหนดประเภทที่จะสร้าง (เช็คเลขทะเบียนของสมาชิก)
    $types = [];
    if (!empty(trim((string)($row['user_cpd_no'] ?? '')))) {
        $types[] = 'cpd';
    }
    if (!empty(trim((string)($row['user_cpa_no'] ?? '')))) {
        $types[] = 'cpa';
    }

    // ถ้าหากไม่มีเลขทะเบียนเลย ให้สร้างแบบ cpd เป็นค่าเริ่มต้น
    if (empty($types)) {
        $types[] = 'cpd';
    }

    foreach ($types as $cert_type) {
        // เจนเลขที่ใบรับรอง (cert_no)
        $cnt_stmt = $pdo_connect->prepare("SELECT COUNT(DISTINCT enroll_id) FROM tbl_certificate_snapshot WHERE cert_no LIKE :ym");
        $cnt_stmt->execute([':ym' => $ym . '%']);
        $seq = ((int) $cnt_stmt->fetchColumn()) + 1;
        $cnt_stmt->closeCursor();

        $cert_no = $ym . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        // เลขทะเบียนตามประเภท
        $license_no = $cert_type === 'cpa'
            ? (string) ($row['user_cpa_no'] ?? '')
            : (string) ($row['user_cpd_no'] ?? '');

        // รหัสหลักสูตร CPD และ CPA
        $quarter = str_pad((string) (int) ceil((int) date('n', $ts) / 3), 2, '0', STR_PAD_LEFT);

        $cpd_raw = (string) ($row['course_code_cpd_1'] ?? '');
        $cpd_q   = $cpd_raw !== '' ? preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $cpd_raw, 1) : '';
        $ccode_cpd_frozen = $cpd_q !== '' ? str_replace(['[', ']'], '', $cpd_q) : '';

        $cpa_raw = (string) ($row['course_code_cpa_1'] ?? '');
        $cpa_q   = $cpa_raw !== '' ? preg_replace('/\[\d{1,2}\](?=[^\[]*$)/', $quarter, $cpa_raw, 1) : '';
        $ccode_cpa_frozen = $cpa_q !== '' ? str_replace(['[', ']'], '', $cpa_q) : '';

        $total   = (int) ($row['course_number_exam'] ?? 0);
        $score   = $row['score'] !== null ? (int) $row['score'] : null;
        $percent = ($score !== null && $total > 0) ? round($score / $total * 100, 2) : null;

        $approval_date = (! empty($row['course_approval_date_1']) && $row['course_approval_date_1'] !== '0000-00-00')
            ? $row['course_approval_date_1'] : null;

        $lic_cpd = (string) ($row['user_cpd_no'] ?? '');
        $lic_cpa = (string) ($row['user_cpa_no'] ?? '');

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
                ':cert_type' => $cert_type,
                ':fn'        => (string) ($row['user_firstname'] ?? ''),
                ':ln'        => (string) ($row['user_lastname'] ?? ''),
                ':cid'       => (string) ($row['user_citizen_id'] ?? ''),
                ':lic'       => $license_no,
                ':lic_cpa'   => $lic_cpa,
                ':idimg'     => (string) ($row['id_card_image'] ?? ''),
                ':cname'     => (string) ($row['course_name'] ?? ''),
                ':cinstr'    => (string) ($row['course_instructor'] ?? ''),
                ':ccode_cpd' => $ccode_cpd_frozen,
                ':ccode_cpa' => $ccode_cpa_frozen,
                ':capprove'  => $approval_date,
                ':h_acc'     => (float) ($row['course_cpd_hour'] ?? 0),
                ':h_eth'     => (float) ($row['course_cpd_ethics'] ?? 0),
                ':h_oth'     => (float) ($row['course_cpd_other'] ?? 0),
                ':score'     => $score,
                ':total'     => $total > 0 ? $total : null,
                ':percent'   => $percent,
                ':issued_at' => date('Y-m-d H:i:s', $ts),
                ':issued_by' => (int) $admin_id,
            ]);
            $ins->closeCursor();
        } catch (\PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                try {
                    $ins2 = $pdo_connect->prepare(
                        "INSERT INTO tbl_certificate_snapshot
                           (enroll_id, user_id, course_id, cert_no, cert_type,
                            user_firstname, user_lastname, user_citizen_id, user_license_no, user_licensecpa_no, id_card_image_snapshot,
                            course_name, course_instructor, course_code_cpd, course_code_cpa, course_code, course_approval_date,
                            hours_account, hours_ethics, hours_other,
                            exam_score, exam_total, score_percent, issued_at, issued_by)
                          VALUES
                           (:enroll_id, :user_id, :course_id, :cert_no, :cert_type,
                            :fn, :ln, :cid, :lic, :lic_cpa, :idimg,
                            :cname, :cinstr, :ccode_cpd, :ccode_cpa, :ccode, :capprove,
                            :h_acc, :h_eth, :h_oth,
                            :score, :total, :percent, :issued_at, :issued_by)"
                    );
                    $ins2->execute([
                        ':enroll_id' => $enroll_id,
                        ':user_id'   => (int) $row['enroll_user_id'],
                        ':course_id' => (int) $row['enroll_course_id'],
                        ':cert_no'   => $cert_no,
                        ':cert_type' => $cert_type,
                        ':fn'        => (string) ($row['user_firstname'] ?? ''),
                        ':ln'        => (string) ($row['user_lastname'] ?? ''),
                        ':cid'       => (string) ($row['user_citizen_id'] ?? ''),
                        ':lic'       => $license_no,
                        ':lic_cpa'   => $lic_cpa,
                        ':idimg'     => (string) ($row['id_card_image'] ?? ''),
                        ':cname'     => (string) ($row['course_name'] ?? ''),
                        ':cinstr'    => (string) ($row['course_instructor'] ?? ''),
                        ':ccode_cpd' => $ccode_cpd_frozen,
                        ':ccode_cpa' => $ccode_cpa_frozen,
                        ':ccode'     => $ccode_cpd_frozen ?: $ccode_cpa_frozen,
                        ':capprove'  => $approval_date,
                        ':h_acc'     => (float) ($row['course_cpd_hour'] ?? 0),
                        ':h_eth'     => (float) ($row['course_cpd_ethics'] ?? 0),
                        ':h_oth'     => (float) ($row['course_cpd_other'] ?? 0),
                        ':score'     => $score,
                        ':total'     => $total > 0 ? $total : null,
                        ':percent'   => $percent,
                        ':issued_at' => date('Y-m-d H:i:s', $ts),
                        ':issued_by' => (int) $admin_id,
                    ]);
                    $ins2->closeCursor();
                } catch (\PDOException $ex2) {
                    if ($ex2->getCode() !== '23000') {
                        error_log('CertSnapshot insert Error: ' . $ex2->getMessage());
                    }
                }
            }
        }
    }
}

Response::json(1, 'อนุมัติออกใบรับรองเรียบร้อยแล้ว', ['enroll_id' => $enroll_id]);
