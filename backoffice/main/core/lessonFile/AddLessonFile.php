<?php
// เพิ่มเอกสารประกอบบทเรียน — insert row ก่อนเพื่อเอา id ไปตั้งชื่อไฟล์ {id}.{ext}

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
$file_name = isset($_POST['lesson_file_name']) ? trim($_POST['lesson_file_name']) : '';

if ($lesson_id <= 0) {
    Response::json(0, 'กรุณาเลือกบทเรียน', null);
}
if ($file_name === '') {
    Response::json(0, 'กรุณากรอกชื่อเอกสาร', null);
}
if (empty($_FILES['lesson_file']['name']) || ($_FILES['lesson_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(0, 'กรุณาเลือกไฟล์', null);
}

$ext = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
$allowExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'csv'];
if (!in_array($ext, $allowExt, true)) {
    Response::json(0, 'ชนิดไฟล์ไม่รองรับ', null);
}
if ($_FILES['lesson_file']['size'] > 50 * 1024 * 1024) {
    Response::json(0, 'ขนาดไฟล์ต้องไม่เกิน 50 MB', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ตรวจว่าบทเรียนมีจริง
$check = $pdo_connect->prepare("SELECT lesson_id FROM tbl_lesson WHERE lesson_id = :id AND delete_at IS NULL LIMIT 1");
$check->execute([':id' => $lesson_id]);
if (!$check->fetchColumn()) {
    $check->closeCursor();
    Response::json(0, 'ไม่พบบทเรียนนี้', null);
}
$check->closeCursor();

// mime type จริงของไฟล์ (fallback เป็น type ที่ browser ส่งมา)
$mime = @mime_content_type($_FILES['lesson_file']['tmp_name']) ?: ($_FILES['lesson_file']['type'] ?? null);

// อัปโหลดขึ้น S3 ก่อน insert (ถ้าอัปไม่สำเร็จก็หยุด ไม่ต้องกู้คืน row) แล้วเก็บ URL เต็มลง lesson_file_path
$filename = bin2hex(random_bytes(8));
$s3Result = AwsS3::uploadFileDirectly($_FILES['lesson_file'], true, 'lesson_file', $filename);
if (isset($s3Result['error'])) {
    Response::json(0, 'อัปโหลดไฟล์ขึ้น S3 ไม่สำเร็จ: ' . $s3Result['error'], null);
}
$file_url = $s3Result['url'];

try {
    $stmt = $pdo_connect->prepare(
        "INSERT INTO tbl_lesson_file (lesson_id, lesson_file_name, lesson_file_type, lesson_file_path)
         VALUES (:lid, :name, :type, :path)"
    );
    $stmt->execute([':lid' => $lesson_id, ':name' => $file_name, ':type' => $mime, ':path' => $file_url]);
    $file_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มเอกสารสำเร็จ', ['lesson_file_id' => $file_id]);
} catch (Exception $e) {
    error_log('Add Lesson File Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
