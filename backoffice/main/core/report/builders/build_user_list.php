<?php
// builder: รายงานการสมัครสมาชิก (user_list)
// คอลัมน์: ลำดับ | คำนำหน้าชื่อ | ชื่อ | นามสกุล | อีเมล | เบอร์โทร | หมายเลขบัตรประชาชน
//          | เลขทะเบียน CPA | เลขผู้ทำบัญชี | วันที่สมัครสมาชิก | ยอดใช้จ่ายทั้งหมด
// ใช้ตัวแปรที่ dispatcher เตรียมไว้: $pdo, $prefix_label, $norm_date, $fmt_datetime, $filename, &$ss

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$from  = $norm_date((string) ($_POST['from'] ?? ''));
$to    = $norm_date((string) ($_POST['to'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

$where  = ['u.delete_at IS NULL'];
$params = [];
if ($from !== '') { $where[] = 'DATE(u.create_at) >= :from'; $params[':from'] = $from; }
if ($to !== '')   { $where[] = 'DATE(u.create_at) <= :to';   $params[':to']   = $to; }
if ($email !== '') { $where[] = 'u.user_email LIKE :email';   $params[':email'] = '%' . $email . '%'; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT u.user_prefix, u.user_firstname, u.user_lastname, u.user_email, u.user_phone,
               u.user_citizen_id, u.user_cpa_no, u.user_cpd_no, u.create_at,
               (SELECT COALESCE(SUM(o.total_price), 0)
                  FROM tbl_orders o
                 WHERE o.user_id = u.user_id AND o.payment_status = '1') AS total_spent
        FROM tbl_user u
        $where_sql
        ORDER BY u.create_at DESC, u.user_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Worksheet');

$headers = ['ลำดับ', 'คำนำหน้าชื่อ', 'ชื่อ', 'นามสกุล', 'อีเมล', 'เบอร์โทร', 'หมายเลขบัตรประชาชน',
            'เลขทะเบียน CPA', 'เลขผู้ทำบัญชี', 'วันที่สมัครสมาชิก', 'ยอดใช้จ่ายทั้งหมด'];
$sheet->fromArray($headers, null, 'A1');

$r = 2; $i = 1;
foreach ($rows as $row) {
    $sheet->setCellValue('A' . $r, $i++);
    $sheet->setCellValue('B' . $r, $prefix_label($row['user_prefix']));
    $sheet->setCellValue('C' . $r, (string) ($row['user_firstname'] ?? ''));
    $sheet->setCellValue('D' . $r, (string) ($row['user_lastname'] ?? ''));
    $sheet->setCellValue('E' . $r, (string) ($row['user_email'] ?? ''));
    $sheet->setCellValueExplicit('F' . $r, (string) ($row['user_phone'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('G' . $r, (string) ($row['user_citizen_id'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('H' . $r, (string) ($row['user_cpa_no'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('I' . $r, (string) ($row['user_cpd_no'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValue('J' . $r, $fmt_datetime($row['create_at']));
    // ยอดเงินเป็นข้อความ "X.00" (ให้ตรงรูปแบบไฟล์ต้นฉบับเป๊ะ)
    $sheet->setCellValueExplicit('K' . $r, number_format((float) ($row['total_spent'] ?? 0), 2, '.', ''), DataType::TYPE_STRING);
    $r++;
}

$sheet->getStyle('A1:K1')->getFont()->setBold(true);
foreach (range('A', 'K') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

$filename = 'user_list_' . date('Ymd_His') . '.xlsx';
