<?php
// builder: รายชื่อผู้เข้าอบรม CPA (ผู้สอบบัญชี) / CPD (ผู้ทำบัญชี)
// แยกชนิดด้วย $report_type: 'cpa_attendance' = ผู้สอบ, 'cpd_attendance' = ผู้ทำ
//
// โครงเอกสาร (ตามไฟล์ต้นฉบับ):
//   แถว 1  หัวตาราง: รหัสหน่วยงาน | ชื่อหน่วยงาน | รหัสหลักสูตร | ชื่อหลักสูตร | สถานที่อบรม/สัมมนา | จำนวนชั่วโมงเรียนรวมทั้งหมด
//   แถว 2  ค่า
//   แถว 5  หมายเหตุการนับชั่วโมง (คอลัมน์ E)
//   แถว 6  หัวตารางผู้เข้าอบรม
//   แถว 7+ รายชื่อผู้เข้าอบรม
// ใช้ตัวแปรจาก dispatcher: $pdo, $report_type, $prefix_label, $norm_date, $fmt_date, $split_hours, $clean_code, &$ss, $filename

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$is_cpa = ($report_type === 'cpa_attendance');

$course_id     = (int) ($_POST['course_id'] ?? 0);
$from          = $norm_date((string) ($_POST['from'] ?? ''));
$to            = $norm_date((string) ($_POST['to'] ?? ''));
$agency_code   = trim((string) ($_POST['agency_code'] ?? ''));
$agency_name   = trim((string) ($_POST['agency_name'] ?? ''));
$seminar_place = trim((string) ($_POST['seminar_place'] ?? ''));

// ---- ข้อมูลหลักสูตร ----
$course = null;
if ($course_id > 0) {
    $cstmt = $pdo->prepare(
        "SELECT course_name, course_code_cpa_1, course_code_cpd_1,
                course_cpa_hour, course_cpa_ethics, course_cpd_hour, course_cpd_ethics
         FROM tbl_course WHERE course_id = :id AND delete_at IS NULL LIMIT 1"
    );
    $cstmt->execute([':id' => $course_id]);
    $course = $cstmt->fetch(PDO::FETCH_ASSOC);
    $cstmt->closeCursor();
}

$course_name = (string) ($course['course_name'] ?? '');
$course_code = $clean_code($is_cpa ? ($course['course_code_cpa_1'] ?? '') : ($course['course_code_cpd_1'] ?? ''));
$hour_dec    = (float) ($is_cpa ? ($course['course_cpa_hour'] ?? 0) : ($course['course_cpd_hour'] ?? 0));
$ethics_dec  = (float) ($is_cpa ? ($course['course_cpa_ethics'] ?? 0) : ($course['course_cpd_ethics'] ?? 0));
[$hh, $mm]   = $split_hours($hour_dec);

// ---- รายชื่อผู้เข้าอบรม (เรียนจบ + มีเลขทะเบียนตามชนิด) ----
$reg_col = $is_cpa ? 'u.user_cpa_no' : 'u.user_cpd_no';
$where  = ["e.delete_at IS NULL", "e.enroll_is_completed = '1'", "$reg_col IS NOT NULL", "TRIM($reg_col) <> ''"];
$params = [];
if ($course_id > 0) { $where[] = 'e.enroll_course_id = :course_id'; $params[':course_id'] = $course_id; }
if ($from !== '') { $where[] = 'DATE(COALESCE(e.enroll_completed_at, e.create_at)) >= :from'; $params[':from'] = $from; }
if ($to !== '')   { $where[] = 'DATE(COALESCE(e.enroll_completed_at, e.create_at)) <= :to';   $params[':to']   = $to; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT u.user_prefix,
               u.user_firstname, 
               u.user_lastname, 
               u.user_cpa_no, 
               u.user_cpd_no,
               MAX(e.start_date) AS start_date,
               MAX(e.enroll_date) AS enroll_date, 
               (SELECT a.create_at 
                FROM tbl_exam_attempt a
                WHERE a.attempt_user_id = e.enroll_user_id 
                AND a.attempt_course_id = e.enroll_course_id
                AND a.attempt_pass = '1'
                ORDER BY a.attempt_id DESC LIMIT 1) AS enroll_completed_at,
               MAX(e.create_at) AS create_at,
               
               (SELECT a.attempt_score 
                FROM tbl_exam_attempt a
                WHERE a.attempt_user_id = e.enroll_user_id 
                AND a.attempt_course_id = e.enroll_course_id
                ORDER BY a.attempt_id DESC LIMIT 1) AS score,
               SUM(COALESCE(lp.progress_last_sec, 0)) AS total_seconds
        FROM tbl_course_enrollment e
        LEFT JOIN tbl_user u   ON e.enroll_user_id = u.user_id
        LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
        LEFT JOIN tbl_lesson l ON c.course_id  = l.course_id AND l.delete_at IS NULL
        LEFT JOIN tbl_lesson_progress lp ON l.lesson_id  = lp.progress_lesson_id AND lp.progress_user_id = e.enroll_user_id

        $where_sql
        GROUP BY e.enroll_user_id, e.enroll_course_id, u.user_prefix, u.user_firstname, u.user_lastname, u.user_cpa_no, u.user_cpd_no
        ORDER BY COALESCE(enroll_completed_at, MAX(e.create_at)) ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$sum_total_seconds = 0;
foreach ($rows as $row) {
    $sum_total_seconds += (int) ($row['total_seconds'] ?? 0);
}
$total_hh = (int) floor($sum_total_seconds / 3600);
$total_mm = (int) floor(($sum_total_seconds % 3600) / 60);

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Worksheet');

// ===== ส่วนหัว: ข้อมูลหน่วยงาน/หลักสูตร =====
$sheet->fromArray(
    ['รหัสหน่วยงาน', 'ชื่อหน่วยงาน', 'รหัสหลักสูตร', 'ชื่อหลักสูตร', 'สถานที่อบรม/สัมมนา', 'จำนวนชั่วโมงเรียนรวมทั้งหมด'],
    null, 'A1'
);
$sheet->setCellValueExplicit('A2', $agency_code, DataType::TYPE_STRING);
$sheet->setCellValue('B2', $agency_name);
$sheet->setCellValueExplicit('C2', $course_code, DataType::TYPE_STRING);
$sheet->setCellValue('D2', $course_name);
$sheet->setCellValue('E2', $seminar_place);
$sheet->setCellValue('F2', $total_hh . ' ชั่วโมง ' . $total_mm . ' นาที');

// แถวหมายเหตุการนับชั่วโมง

$sheet->getStyle('E5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// ===== ส่วนตารางผู้เข้าอบรม =====
$reg_header = $is_cpa ? 'เลขทะเบียนผู้สอบบัญชีรับอนุญาต' : 'เลขที่ผู้ทำบัญชี';
$sheet->fromArray(
    [$reg_header, 'คำนำหน้าชื่อ', 'ชื่อ', 'นามสกุล', 'ชั่วโมง', 'นาที', 'วันที่เริ่มเรียน', 'วันที่เรียนจบ', 'คะแนนสอบ'],
    null, 'A6'
);

$r = 7;
foreach ($rows as $row) {
    $reg_no = $is_cpa ? ($row['user_cpa_no'] ?? '') : ($row['user_cpd_no'] ?? '');
    $sheet->setCellValueExplicit('A' . $r, (string) $reg_no, DataType::TYPE_STRING);
    $sheet->setCellValue('B' . $r, $prefix_label($row['user_prefix']));
    $sheet->setCellValue('C' . $r, (string) ($row['user_firstname'] ?? ''));
    $sheet->setCellValue('D' . $r, (string) ($row['user_lastname'] ?? ''));
    $total_sec = (int) ($row['total_seconds'] ?? 0);
    $row_hh = (int) floor($total_sec / 3600);
    $row_mm = (int) floor(($total_sec % 3600) / 60);
    $sheet->setCellValue('E' . $r, $row_hh);
    $sheet->setCellValue('F' . $r, $row_mm);
    $sheet->setCellValue('G' . $r, $fmt_datetime($row['start_date']));
    $sheet->setCellValue('H' . $r, $fmt_datetime($row['enroll_completed_at']));
    $sheet->setCellValue('I' . $r, $row['score']);
    $r++;
}

$sheet->getStyle('A1:F1')->getFont()->setBold(true);
$sheet->getStyle('A6:I6')->getFont()->setBold(true);
foreach (range('A', 'I') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

$kind = $is_cpa ? 'cpa' : 'cpd';
$filename = $kind . '_attendance_' . date('Ymd_His') . '.xlsx';
