<?php
// ส่งออกรายการผู้ใช้/ลูกค้าทั้งหมดเป็น Excel (.xlsx) ตามฟิลเตอร์ที่เลือก

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// เผื่อเรียกแบบแนบ access_token ใน POST body
if (empty($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_POST['access_token'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_POST['access_token'];
}

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$search = trim((string) ($_POST['search'] ?? ''));
$filter_status = trim((string) ($_POST['filter_status'] ?? ''));
$filter_verify = trim((string) ($_POST['filter_verify'] ?? ''));

$where = ["u.delete_at IS NULL"];
$params = [];

if ($filter_status !== '') {
    $where[] = "u.user_status = :filter_status";
    $params[':filter_status'] = $filter_status;
}

if ($filter_verify !== '') {
    $where[] = "u.identity_verified = :filter_verify";
    $params[':filter_verify'] = $filter_verify;
}

if ($search !== '') {
    $where[] = "(CONCAT_WS(' ',
                     u.user_firstname, u.user_lastname,
                     u.user_email, u.user_citizen_id, u.user_cpd_no, u.user_cpa_no
                 ) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    $sql_data = "SELECT
                    u.user_id,
                    u.user_firstname,
                    u.user_lastname,
                    u.user_email,
                    u.user_phone,
                    u.user_citizen_id,
                    u.user_cpd_no,
                    u.user_cpa_no,
                    u.user_status,
                    u.identity_verified,
                    u.id_card_expiry_date,
                    (SELECT COALESCE(SUM(total_price), 0) FROM tbl_orders WHERE user_id = u.user_id) AS total_spent
                FROM tbl_user u
                $where_sql
                ORDER BY u.user_id DESC";

    $stmt_data = $pdo_connect->prepare($sql_data);
    foreach ($params as $k => $v) {
        $stmt_data->bindValue($k, $v);
    }
    $stmt_data->execute();
    $rows = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
    $stmt_data->closeCursor();

    $ss = new Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('ผู้ใช้และลูกค้าทั้งหมด');
    $sheet->setShowGridLines(true);

    // 1) หัวรายงาน (Report Title Header)
    $sheet->mergeCells('A1:J1');
    $sheet->setCellValue('A1', 'รายงานข้อมูลผู้ใช้ / ลูกค้าทั้งหมด ส่งออก ณ วันที่ ' . date('d/m/Y'));
    $styleReportTitle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 13,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1F3BB3'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    $sheet->getStyle('A1:J1')->applyFromArray($styleReportTitle);
    $sheet->getRowDimension(1)->setRowHeight(36);

    // 2) หัวตาราง (Table Header)
    $headers = [
        'ลำดับ',
        'ชื่อ-นามสกุล',
        'อีเมล',
        'เบอร์โทรศัพท์',
        'เลขบัตรประชาชน',
        'เลขที่ผู้ทำบัญชี',
        'เลขที่ผู้สอบบัญชี',
        'สถานะการยืนยันตัวตน',
        'สถานะการใช้งาน',
        'ยอดที่ใช้จ่าย'
    ];

    $sheet->fromArray($headers, null, 'A2');

    $styleHeader = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '0F172A'],
            'size' => 11,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'DCE6F1'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
        ],
    ];
    $sheet->getStyle('A2:J2')->applyFromArray($styleHeader);
    $sheet->getRowDimension(2)->setRowHeight(28);

    // 3) สไตล์ตารางข้อมูล (Data Rows)
    $styleBorder = [
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    $rowCell = 3;
    $no = 1;

    foreach ($rows as $row) {
        $full_name = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        if ($full_name === '') {
            $full_name = '-';
        }

        $verify_val = (string) ($row['identity_verified'] ?? '0');
        if ($verify_val === '2') {
            $verify_text = 'ยืนยัน';
        } elseif ($verify_val === '1') {
            $verify_text = 'ระหว่างดำเนินการ';
        } else {
            $verify_text = 'ยังไม่ยืนยัน';
        }

        $status_val = (string) ($row['user_status'] ?? '1');
        $status_text = ($status_val === '1') ? 'ใช้งาน' : 'ระงับ';

        $sheet->setCellValue('A' . $rowCell, $no++);
        $sheet->setCellValue('B' . $rowCell, $full_name);
        $sheet->setCellValue('C' . $rowCell, (string) ($row['user_email'] ?? '-'));
        $sheet->setCellValueExplicit('D' . $rowCell, (string) ($row['user_phone'] ?? '-'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowCell, (string) ($row['user_citizen_id'] ?? '-'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowCell, (string) ($row['user_cpd_no'] ?? '-'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G' . $rowCell, (string) ($row['user_cpa_no'] ?? '-'), DataType::TYPE_STRING);
        $sheet->setCellValue('H' . $rowCell, $verify_text);
        $sheet->setCellValue('I' . $rowCell, $status_text);
        $sheet->setCellValue('J' . $rowCell, (float) ($row['total_spent'] ?? 0));

        // Format cell alignment, borders and background
        $sheet->getStyle('A' . $rowCell . ':J' . $rowCell)->applyFromArray($styleBorder);

        // สีพื้นหลังสลับบรรทัด (Zebra striping)
        if ($rowCell % 2 === 0) {
            $sheet->getStyle('A' . $rowCell . ':J' . $rowCell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8FAFC');
        }

        $sheet->getStyle('A' . $rowCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $rowCell . ':I' . $rowCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J' . $rowCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J' . $rowCell)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getRowDimension($rowCell)->setRowHeight(22);
        $rowCell++;
    }

    // Auto Column Width
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'User_List_' . date('Ymd_His') . '.xlsx';
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($ss);
    $writer->save('php://output');
    exit;

} catch (\Throwable $e) {
    error_log('Export User Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการสร้างไฟล์ Excel', null);
}
