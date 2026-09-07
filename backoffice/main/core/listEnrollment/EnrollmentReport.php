<?php
// ส่งออกรายงานคอร์สเรียนคงเหลือเป็น Excel (.xlsx) ตามฟิลเตอร์ที่เลือก

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$f_course = trim((string) ($_POST['f_course'] ?? ''));
$f_member = trim((string) ($_POST['f_member'] ?? ''));

$where  = ['e.delete_at IS NULL'];
$params = [];
if ($f_course !== '' && ctype_digit($f_course)) {
    $where[] = "e.enroll_course_id = :f_course";
    $params[':f_course'] = (int) $f_course;
}
if ($f_member !== '') {
    $where[] = "CONCAT_WS(' ', u.user_firstname, u.user_lastname) LIKE :f_member";
    $params[':f_member'] = '%' . $f_member . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT e.enroll_date, e.enroll_expiry_date, e.enroll_is_completed, e.create_at,
               u.user_firstname, u.user_lastname, u.user_phone, c.course_name
        FROM tbl_course_enrollment e
        LEFT JOIN tbl_user u   ON e.enroll_user_id = u.user_id
        LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
        $where_sql
        ORDER BY e.enroll_id DESC";
$stmt = $pdo_connect->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$fmt = fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '-';
$now = time();

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('คอร์สเรียนคงเหลือ');
$headers = ['ลำดับ', 'ชื่อสมาชิก', 'เบอร์โทรติดต่อ', 'ชื่อคอร์ส', 'วันที่ซื้อ', 'วันที่เปิดใช้', 'วันหมดอายุ', 'อายุคงเหลือ (วัน)', 'สถานะ'];
$sheet->fromArray($headers, null, 'A1');

$r = 2; $i = 1;
foreach ($rows as $row) {
    $full = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
    $exp  = $row['enroll_expiry_date'] ?? null;
    if (!$exp) {
        $expiry_txt = 'ไม่มีกำหนด';
        $remain_txt = '-';
    } else {
        $expiry_txt = $fmt($exp);
        $exp_dt   = new DateTime(date('Y-m-d', strtotime($exp)));
        $today_dt = new DateTime(date('Y-m-d'));
        $days     = (int) $today_dt->diff($exp_dt)->format("%r%a");
        $remain_txt = $days > 0 ? ($days . ' วัน') : 'หมดอายุ';
    }
    $status = ((string) ($row['enroll_is_completed'] ?? '0') === '1') ? 'เรียนจบแล้ว' : 'กำลังเรียน';

    $sheet->setCellValue('A' . $r, $i++);
    $sheet->setCellValue('B' . $r, $full !== '' ? $full : '-');
    $sheet->setCellValueExplicit('C' . $r, (string) ($row['user_phone'] ?? '-'), DataType::TYPE_STRING);
    $sheet->setCellValue('D' . $r, (string) ($row['course_name'] ?? '-'));
    $sheet->setCellValue('E' . $r, $fmt($row['create_at'] ?: $row['enroll_date']));
    $sheet->setCellValue('F' . $r, $fmt($row['enroll_date'] ?: $row['create_at']));
    $sheet->setCellValue('G' . $r, $expiry_txt);
    $sheet->setCellValue('H' . $r, $remain_txt);
    $sheet->setCellValue('I' . $r, $status);
    $r++;
}

$sheet->getStyle('A1:I1')->getFont()->setBold(true);
foreach (range('A', 'I') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

$filename = 'course_remaining_' . date('Ymd_His') . '.xlsx';
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
