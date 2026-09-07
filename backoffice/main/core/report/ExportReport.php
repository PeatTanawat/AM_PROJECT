<?php
// ตัวจ่ายงานออกรายงาน/เอกสาร (Excel .xlsx) — เลือก builder ตาม report_type
// รูปแบบ stream เหมือน OrderReport/EnrollmentReport (fetch + Bearer -> blob)
// builder แต่ละไฟล์อยู่ใน core/report/builders/ และต้องเซ็ตตัวแปร $ss (Spreadsheet) + $filename

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// เผื่อเปิดผ่าน form POST (ไม่มี header Authorization) -> รับ token จาก POST แทน
if (empty($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_POST['access_token'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_POST['access_token'];
}

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo = $db_instance->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$report_type = trim((string) ($_POST['report_type'] ?? ''));

// ===== helper ที่ทุก builder ใช้ร่วมกัน (อยู่ใน scope ของ include) =====

// คำนำหน้าชื่อ: 1=นาย 2=นาง 3=นางสาว
$prefix_label = function ($code): string {
    $map = [1 => 'นาย', 2 => 'นาง', 3 => 'นางสาว'];
    $k = (int) $code;
    return $map[$k] ?? '';
};

// แปลงวันที่ d/m/Y (จากฟอร์ม) -> Y-m-d (สำหรับ query)
$norm_date = function (string $s): string {
    $s = trim($s);
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $s, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return $s;
};

// format วันที่จาก DB -> d/m/Y (เว้นว่างถ้าไม่มี)
$fmt_date = function ($d): string {
    if (empty($d) || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') { return ''; }
    return date('d/m/Y', strtotime((string) $d));
};

// format วันที่+เวลา -> d/m/Y H:i
$fmt_datetime = function ($d): string {
    if (empty($d) || $d === '0000-00-00 00:00:00') { return ''; }
    return date('d/m/Y H:i', strtotime((string) $d));
};

// แยกชั่วโมงทศนิยม -> [ชั่วโมง, นาที]  เช่น 1.50 -> [1, 30]
$split_hours = function ($decimal): array {
    $total_min = (int) round(((float) $decimal) * 60);
    return [intdiv($total_min, 60), $total_min % 60];
};

// เลขที่ใบรับรอง (running) ตามรูปแบบเดิม: ym ของ create_at + enroll_id เติม 0 เป็น 4 หลัก
$cert_no = function ($create_at, $enroll_id): string {
    $ts = $create_at ? strtotime((string) $create_at) : time();
    return date('ym', $ts) . str_pad((string) $enroll_id, 4, '0', STR_PAD_LEFT);
};

// ตัดวงเล็บ [] ออกจากรหัสหลักสูตร (ให้เหมือนหน้าคอร์ส/ใบรับรอง)
$clean_code = function ($code): string {
    $code = trim((string) $code);
    return $code !== '' ? str_replace(['[', ']'], '', $code) : '';
};

// ===== เลือก builder =====
$builders = [
    'cpa_attendance'                 => __DIR__ . '/builders/build_attendance.php',
    'cpd_attendance'                 => __DIR__ . '/builders/build_attendance.php',
    'course_attendance_registration' => __DIR__ . '/builders/build_registration.php',
    'exam_result'                    => __DIR__ . '/builders/build_exam_result.php',
    'user_list'                      => __DIR__ . '/builders/build_user_list.php',
];

if (!isset($builders[$report_type])) {
    Response::json(0, 'ประเภทเอกสารไม่ถูกต้อง', null);
}

// builder จะตั้งค่า $ss (Spreadsheet) และ $filename ให้
$ss = null;
$filename = 'report_' . date('Ymd_His') . '.xlsx';

try {
    require $builders[$report_type];

    if (!$ss) {
        Response::json(0, 'ไม่สามารถสร้างรายงานได้', null);
    }

    while (ob_get_level() > 0) { @ob_end_clean(); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($ss);
    $writer->save('php://output');
    exit;
} catch (\Throwable $e) {
    error_log('ExportReport(' . $report_type . ') Error: ' . $e->getMessage());
    Response::json(0, 'สร้างรายงานไม่สำเร็จ', null);
}
