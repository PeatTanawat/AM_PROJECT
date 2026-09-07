<?php
// ส่งออกรายงานคำสั่งซื้อเป็น Excel (.xlsx)
// เฉพาะคำสั่งซื้อที่ "ชำระเงินแล้ว + สำเร็จ" (payment_status = '1') ตามช่วงวันที่ที่เลือก

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

// แปลงวันที่ d/m/Y -> Y-m-d
$norm = function (string $s): string {
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $s, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return $s;
};
$from = $norm(trim((string) ($_POST['from'] ?? '')));
$to   = $norm(trim((string) ($_POST['to'] ?? '')));

$where  = ["o.payment_status = '1'"]; // ชำระแล้ว + สำเร็จ
$params = [];
if ($from !== '') { $where[] = "DATE(o.created_at) >= :from"; $params[':from'] = $from; }
if ($to !== '')   { $where[] = "DATE(o.created_at) <= :to";   $params[':to']   = $to; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT o.transaction_ref, o.total_price, o.created_at, o.payment_method,
               u.user_firstname, u.user_lastname,
               (SELECT GROUP_CONCAT(c.course_name SEPARATOR ', ')
                  FROM tbl_order_detail od
                  LEFT JOIN tbl_course c ON c.course_id = od.course_id
                 WHERE od.order_id = o.order_id) AS course_names
        FROM tbl_orders o
        LEFT JOIN tbl_user u ON o.user_id = u.user_id
        $where_sql
        ORDER BY o.created_at ASC";
$stmt = $pdo_connect->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$method_label = function (string $m): string {
    if ($m === '1') { return 'PromptPay'; }
    if ($m === '2') { return 'โอนเงินผ่านธนาคาร'; }
    if ($m === '3') { return 'บัตรเครดิต'; }
    return '-';
};

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('คำสั่งซื้อ');

$headers = ['ลำดับ', 'หมายเลขคำสั่งซื้อ', 'ชื่อลูกค้า', 'คอร์สเรียน', 'ยอดรวม (บาท)', 'วิธีชำระเงิน', 'วันที่สั่งซื้อ'];
$sheet->fromArray($headers, null, 'A1');

$r = 2; $i = 1; $sum = 0.0;
foreach ($rows as $row) {
    $full  = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
    $total = (float) ($row['total_price'] ?? 0);
    $sum  += $total;

    $sheet->setCellValue('A' . $r, $i++);
    $sheet->setCellValueExplicit('B' . $r, (string) ($row['transaction_ref'] ?? ''), DataType::TYPE_STRING);
    $sheet->setCellValue('C' . $r, $full !== '' ? $full : '-');
    $sheet->setCellValue('D' . $r, (string) ($row['course_names'] ?? ''));
    $sheet->setCellValue('E' . $r, $total);
    $sheet->setCellValue('F' . $r, $method_label((string) ($row['payment_method'] ?? '')));
    $sheet->setCellValue('G' . $r, $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '');
    $r++;
}

// แถวสรุปยอดรวม
$sheet->setCellValue('D' . $r, 'รวมทั้งหมด');
$sheet->setCellValue('E' . $r, $sum);
$sheet->getStyle('D' . $r . ':E' . $r)->getFont()->setBold(true);

// จัดรูปแบบ
$sheet->getStyle('A1:G1')->getFont()->setBold(true);
$sheet->getStyle('E2:E' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'order_report_' . date('Ymd_His') . '.xlsx';

// ล้าง output ที่อาจค้าง (กันไฟล์เสีย) แล้ว stream xlsx
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
