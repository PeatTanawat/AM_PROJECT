<?php
// builder: ฟอร์มใบลงทะเบียนอบรม (course_attendance_registration)
// *หมายเหตุ* ไม่มีไฟล์ต้นฉบับให้เทียบ -> ออกแบบเป็นใบลงทะเบียน/เซ็นชื่อเข้าอบรมที่สมเหตุผล
//   หัวกระดาษ: หลักสูตร + วัน/เวลา/สถานที่อบรม
//   ตาราง: ลำดับ | คำนำหน้า | ชื่อ | นามสกุล | เลขผู้ทำบัญชี/ผู้สอบบัญชี | เบอร์โทร | ลายมือชื่อ (เว้นว่างให้เซ็น)
//   รายชื่อ = ผู้มีสิทธิ์เข้าเรียน (ลงทะเบียน/สั่งซื้อ) ในคอร์ส ตามช่วงวันที่
// ใช้ตัวแปรจาก dispatcher: $pdo, $prefix_label, $norm_date, &$ss, $filename

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$course_id     = (int) ($_POST['course_id'] ?? 0);
$from          = $norm_date((string) ($_POST['from'] ?? ''));
$to            = $norm_date((string) ($_POST['to'] ?? ''));
$seminar_date  = trim((string) ($_POST['seminar_date'] ?? ''));
$seminar_time  = trim((string) ($_POST['seminar_time'] ?? ''));
$seminar_place = trim((string) ($_POST['seminar_place'] ?? ''));

// ชื่อหลักสูตร
$course_name = '';
if ($course_id > 0) {
    $cstmt = $pdo->prepare("SELECT course_name FROM tbl_course WHERE course_id = :id AND delete_at IS NULL LIMIT 1");
    $cstmt->execute([':id' => $course_id]);
    $course_name = (string) ($cstmt->fetchColumn() ?: '');
    $cstmt->closeCursor();
}

// ผู้มีสิทธิ์เข้าเรียนในคอร์ส (ตามช่วงวันลงทะเบียน/สั่งซื้อ)
$where  = ["e.delete_at IS NULL"];
$params = [];
if ($course_id > 0) { $where[] = 'e.enroll_course_id = :course_id'; $params[':course_id'] = $course_id; }
if ($from !== '') { $where[] = 'DATE(COALESCE(e.enroll_date, e.create_at)) >= :from'; $params[':from'] = $from; }
if ($to !== '')   { $where[] = 'DATE(COALESCE(e.enroll_date, e.create_at)) <= :to';   $params[':to']   = $to; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT u.user_prefix, u.user_firstname, u.user_lastname, u.user_phone,
               u.user_cpa_no, u.user_cpd_no
        FROM tbl_course_enrollment e
        LEFT JOIN tbl_user u ON e.enroll_user_id = u.user_id
        $where_sql
        ORDER BY u.user_firstname ASC, u.user_lastname ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Worksheet');

// ===== หัวกระดาษ =====
$sheet->setCellValue('A1', 'ใบลงทะเบียนเข้าอบรม/สัมมนา');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'หลักสูตร: ' . $course_name);
$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A3', 'วันที่อบรม/สัมมนา: ' . $seminar_date . '    เวลา: ' . $seminar_time);
$sheet->mergeCells('A3:G3');
$sheet->setCellValue('A4', 'สถานที่: ' . $seminar_place);
$sheet->mergeCells('A4:G4');

// ===== ตารางลงชื่อ =====
$head_row = 6;
$sheet->fromArray(
    ['ลำดับ', 'คำนำหน้าชื่อ', 'ชื่อ', 'นามสกุล', 'เลขผู้ทำบัญชี/ผู้สอบบัญชี', 'เบอร์โทร', 'ลายมือชื่อ'],
    null, 'A' . $head_row
);

$r = $head_row + 1; $i = 1;
foreach ($rows as $row) {
    $reg_no = trim((string) ($row['user_cpd_no'] ?? '')) !== ''
        ? (string) $row['user_cpd_no']
        : (string) ($row['user_cpa_no'] ?? '');
    $sheet->setCellValue('A' . $r, $i++);
    $sheet->setCellValue('B' . $r, $prefix_label($row['user_prefix']));
    $sheet->setCellValue('C' . $r, (string) ($row['user_firstname'] ?? ''));
    $sheet->setCellValue('D' . $r, (string) ($row['user_lastname'] ?? ''));
    $sheet->setCellValueExplicit('E' . $r, $reg_no, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('F' . $r, (string) ($row['user_phone'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValue('G' . $r, ''); // เว้นว่างให้เซ็น
    $r++;
}

// จัดรูปแบบตาราง: หัวหนา + เส้นขอบ
$last_row = max($head_row, $r - 1);
$sheet->getStyle('A' . $head_row . ':G' . $head_row)->getFont()->setBold(true);
$sheet->getStyle('A' . $head_row . ':G' . $last_row)
      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A' . $head_row . ':G' . $head_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

foreach (['A' => 8, 'B' => 12, 'C' => 22, 'D' => 22, 'E' => 24, 'F' => 16, 'G' => 24] as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

$filename = 'course_attendance_registration_' . date('Ymd_His') . '.xlsx';
