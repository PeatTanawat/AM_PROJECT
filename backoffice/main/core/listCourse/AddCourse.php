<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;
use App\Utility\ImageOptimizer;

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

/* ---------- helpers: แปลงค่าจากฟอร์มให้พร้อมบันทึก ---------- */
$str = function (string $key): ?string {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? null : $v;
};
$int = function (string $key): ?int {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? null : (int) $v;
};
$dec = function (string $key): float {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? 0.0 : (float) $v;
};
$flag = function (string $key, string $default): string {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '1' ? '1' : ($v === '0' ? '0' : $default);
};

/* ---------- validate ---------- */
$course_name = $str('course_name');
if ($course_name === null) {
    Response::json(0, 'กรุณากรอกชื่อคอร์สเรียน', null);
}

$course_group = $int('course_group');
if ($course_group === null) {
    Response::json(0, 'กรุณาเลือกหมวดหมู่', null);
}

// ---------- ตรวจฟิลด์บังคับให้ครบตามช่องที่ติดดาว * ในฟอร์ม ----------
$course_type = $int('course_type');
if ($course_type === null || $course_type <= 0) {
    Response::json(0, 'กรุณาเลือกประเภท', null);
}
if ($str('course_instructor') === null) {
    Response::json(0, 'กรุณากรอกผู้บรรยาย/ผู้สอน', null);
}
if ($str('course_overview') === null) {
    Response::json(0, 'กรุณากรอกรายละเอียดโดยย่อ', null);
}
if ($str('course_detail') === null) {
    Response::json(0, 'กรุณากรอกรายละเอียดแบบเต็ม', null);
}

// ตัวเลขบังคับ: ต้องกรอก + เป็นตัวเลข + ไม่ติดลบ
$numeric_required = [
    'course_exam_time'     => 'ระยะเวลาทำข้อสอบ',
    'course_minimum_score' => 'คะแนนขั้นต่ำในการผ่านข้อสอบ',
    'course_number_exam'   => 'จำนวนข้อสอบที่ต้องทำ',
    'course_number_time'   => 'จำนวนครั้งที่ทำข้อสอบได้',
    'course_cpd_hour'      => 'ชั่วโมง CPD บัญชี',
    'course_cpd_ethics'    => 'ชั่วโมง CPD บัญชี (จรรยาบรรณ)',
    'course_cpd_other'     => 'ชั่วโมง CPD (อื่น ๆ)',
    'course_cpa_hour'      => 'ชั่วโมง CPA บัญชี',
    'course_cpa_ethics'    => 'ชั่วโมง CPA บัญชี (จรรยาบรรณ)',
    'course_cpa_other'     => 'ชั่วโมง CPA (อื่น ๆ)',
    'course_price'         => 'ราคาปกติ',
    'course_period'        => 'ระยะเวลาอบรม (วัน)',
];
foreach ($numeric_required as $nk => $nlabel) {
    $nraw = isset($_POST[$nk]) ? trim((string) $_POST[$nk]) : '';
    if ($nraw === '') {
        Response::json(0, 'กรุณากรอก' . $nlabel, null);
    }
    if (!is_numeric($nraw) || (float) $nraw < 0) {
        Response::json(0, $nlabel . ' ต้องเป็นตัวเลขไม่ติดลบ', null);
    }
}

// ราคาโปรโมชั่น (ไม่บังคับ) — ถ้ากรอกต้องไม่ติดลบและไม่เกินราคาปกติ
$promo_raw = isset($_POST['course_promotion']) ? trim((string) $_POST['course_promotion']) : '';
if ($promo_raw !== '') {
    if (!is_numeric($promo_raw) || (float) $promo_raw < 0) {
        Response::json(0, 'ราคาโปรโมชั่นต้องเป็นตัวเลขไม่ติดลบ', null);
    }
    if ((float) $promo_raw > (float) ($_POST['course_price'] ?? 0)) {
        Response::json(0, 'ราคาโปรโมชั่นต้องไม่เกินราคาปกติ', null);
    }
}

// รูปหน้าปก — บังคับตอนเพิ่มคอร์สใหม่
if (empty($_FILES['course_cover_image']['name']) || ($_FILES['course_cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(0, 'กรุณาเลือกรูปหน้าปก', null);
}

/* ---------- อัปโหลดรูปหน้าปกขึ้น S3 (ถ้ามี) ---------- */
$course_cover_image = null;
if (!empty($_FILES['course_cover_image']['name']) && ($_FILES['course_cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext = strtolower(pathinfo($_FILES['course_cover_image']['name'], PATHINFO_EXTENSION));

    if (!isset($allowed[$ext])) {
        Response::json(0, 'รองรับเฉพาะไฟล์รูปภาพ (jpg, png, webp, gif)', null);
    }
    if ($_FILES['course_cover_image']['size'] > 5 * 1024 * 1024) {
        Response::json(0, 'ขนาดรูปต้องไม่เกิน 5 MB', null);
    }

    // อัปโหลดขึ้น S3 แล้วเก็บ URL เต็ม
    $filename = bin2hex(random_bytes(8));
    ImageOptimizer::toWebp('course_cover_image'); // แปลงรูปเป็น WebP ก่อนอัปขึ้น S3
    $s3Result = AwsS3::uploadFileDirectly($_FILES['course_cover_image'], true, 'course', $filename);
    if (isset($s3Result['error'])) {
        Response::json(0, 'อัปโหลดรูปขึ้น S3 ไม่สำเร็จ: ' . $s3Result['error'], null);
    }
    $course_cover_image = $s3Result['url'];
}

/* ---------- insert ---------- */
try {
    $fields = [
        'course_cover_image'     => $course_cover_image,
        'course_name'            => $course_name,
        'course_type'            => $int('course_type'),
        'course_group'           => $course_group,
        'course_instructor'      => $str('course_instructor'),
        'course_overview'        => $str('course_overview'),
        'course_detail'          => $str('course_detail'),
        'course_demo_link'       => $str('course_demo_link'),
        'course_period'          => $int('course_period'),
        'course_approval_date_1' => $str('course_approval_date_1'),
        'course_approval_date_2' => $str('course_approval_date_2'),
        'course_approval_date_3' => $str('course_approval_date_3'),
        'course_approval_date_4' => $str('course_approval_date_4'),
        'course_code_cpd_1'      => $str('course_code_cpd_1'),
        'course_code_cpd_2'      => $str('course_code_cpd_2'),
        'course_code_cpd_3'      => $str('course_code_cpd_3'),
        'course_code_cpd_4'      => $str('course_code_cpd_4'),
        'course_code_cpa_1'      => $str('course_code_cpa_1'),
        'course_code_cpa_2'      => $str('course_code_cpa_2'),
        'course_code_cpa_3'      => $str('course_code_cpa_3'),
        'course_code_cpa_4'      => $str('course_code_cpa_4'),
        'course_cpd_hour'        => $dec('course_cpd_hour'),
        'course_cpd_ethics'      => $dec('course_cpd_ethics'),
        'course_cpd_other'       => $dec('course_cpd_other'),
        'course_cpa_hour'        => $dec('course_cpa_hour'),
        'course_cpa_ethics'      => $dec('course_cpa_ethics'),
        'course_cpa_other'       => $dec('course_cpa_other'),
        'course_exam_time'       => $int('course_exam_time'),
        'course_minimum_score'   => $int('course_minimum_score'),
        'course_number_exam'     => $int('course_number_exam'),
        'course_number_time'     => $int('course_number_time'),
        'course_price'           => $dec('course_price'),
        'course_promotion'       => $dec('course_promotion'),
        'course_display'         => $flag('course_display', '1'),
        'course_status'          => $flag('course_status', '1'),
        'course_skip'            => $flag('course_skip', '0'),
        'course_otp'             => $flag('course_otp', '0'),
        'course_question'        => $flag('course_question', '0'),
        'question_limit'  => $int('course_question_limit'),
        'question_time'   => $int('course_question_time'),
        'approve_certificate_auto'   => $flag('course_auto_approve', '0'),
        'approver_certificate_type'  => $flag('course_approve_type', '0'),
    ];

    $columns      = array_keys($fields);
    $placeholders = array_map(fn($c) => ':' . $c, $columns);
    $sql = "INSERT INTO tbl_course (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = $pdo_connect->prepare($sql);
    foreach ($fields as $col => $val) {
        $stmt->bindValue(':' . $col, $val);
    }
    $ok = $stmt->execute();
    $stmt->closeCursor();

    if (!$ok) {
        throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
    }

    $course_id = (int) $pdo_connect->lastInsertId();
    Response::json(1, 'บันทึกคอร์สเรียนสำเร็จ', ['course_id' => $course_id]);
} catch (Exception $e) {
    error_log('Add Course Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
