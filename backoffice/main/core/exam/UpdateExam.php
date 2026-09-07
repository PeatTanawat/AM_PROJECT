<?php
// แก้ไขข้อสอบจบคอร์ส + ตัวเลือก (แทนที่ตัวเลือกเดิมทั้งชุด)

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

$exam_id   = isset($_POST['exam_id']) ? (int) $_POST['exam_id'] : 0;
$exam_text = isset($_POST['exam_text']) ? trim($_POST['exam_text']) : '';
$choices   = isset($_POST['choice_text']) && is_array($_POST['choice_text']) ? $_POST['choice_text'] : [];
$correct   = isset($_POST['correct']) ? (int) $_POST['correct'] : 0;

if ($exam_id <= 0) {
    Response::json(0, 'ไม่พบรหัสข้อสอบ', null);
}
if ($exam_text === '') {
    Response::json(0, 'กรุณากรอกคำถาม', null);
}

$valid = [];
foreach ($choices as $i => $c) {
    $c = trim((string)$c);
    if ($c !== '') { $valid[$i + 1] = $c; }
}
if (count($valid) < 2) {
    Response::json(0, 'กรุณากรอกตัวเลือกอย่างน้อย 2 ข้อ', null);
}
if (!isset($valid[$correct])) {
    Response::json(0, 'กรุณาเลือกคำตอบที่ถูกต้อง', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$check = $pdo_connect->prepare("SELECT exam_id, exam_image, exam_file FROM tbl_exam WHERE exam_id = :id AND delete_at IS NULL LIMIT 1");
$check->execute([':id' => $exam_id]);
$existing_e = $check->fetch(PDO::FETCH_ASSOC);
$check->closeCursor();
if (!$existing_e) {
    Response::json(0, 'ไม่พบข้อสอบนี้', null);
}
$old_image = (string) ($existing_e['exam_image'] ?? '');
$old_file  = (string) ($existing_e['exam_file'] ?? '');

$saveFile = function (string $field, array $allowExt): ?string {
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowExt, true)) {
        Response::json(0, 'ชนิดไฟล์ไม่รองรับ (' . $field . ')', null);
    }
    // อัปโหลดขึ้น S3 แล้วเก็บ URL เต็ม; ไม่ลบไฟล์เก่าใน S3
    $filename = bin2hex(random_bytes(8));
    $s3Result = AwsS3::uploadFileDirectly($_FILES[$field], true, 'exam', $filename);
    if (isset($s3Result['error'])) {
        Response::json(0, 'อัปโหลดไฟล์ขึ้น S3 ไม่สำเร็จ (' . $field . '): ' . $s3Result['error'], null);
    }
    return $s3Result['url'];
};
ImageOptimizer::toWebp('exam_image'); // แปลงรูปเป็น WebP ก่อนอัปขึ้น S3
$image_path = $saveFile('exam_image', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
$file_path  = $saveFile('exam_file', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png']);

try {
    $pdo_connect->beginTransaction();

    $sql = "UPDATE tbl_exam SET exam_text = :text";
    $params = [':text' => $exam_text, ':id' => $exam_id];
    if ($image_path !== null) { $sql .= ", exam_image = :img"; $params[':img'] = $image_path; }
    if ($file_path  !== null) { $sql .= ", exam_file = :file"; $params[':file'] = $file_path; }
    $sql .= " WHERE exam_id = :id AND delete_at IS NULL";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $del = $pdo_connect->prepare("UPDATE tbl_exam_choice SET delete_at = NOW() WHERE exam_id = :eid AND delete_at IS NULL");
    $del->execute([':eid' => $exam_id]);
    $del->closeCursor();

    $cstmt = $pdo_connect->prepare(
        "INSERT INTO tbl_exam_choice (exam_id, exam_choice_text, exam_choice_correct)
         VALUES (:eid, :text, :correct)"
    );
    foreach ($valid as $idx => $text) {
        $cstmt->execute([':eid' => $exam_id, ':text' => $text, ':correct' => ($idx === $correct) ? '1' : '0']);
    }
    $cstmt->closeCursor();

    $pdo_connect->commit();

    // ลบไฟล์เก่าใน S3 หลังบันทึกสำเร็จ (เฉพาะช่องที่อัปไฟล์ใหม่และเป็นคนละไฟล์)
    if ($image_path !== null && $old_image !== '' && $old_image !== $image_path && stripos($old_image, 'http') === 0) {
        AwsS3::deleteFileByURL($old_image);
    }
    if ($file_path !== null && $old_file !== '' && $old_file !== $file_path && stripos($old_file, 'http') === 0) {
        AwsS3::deleteFileByURL($old_file);
    }

    Response::json(1, 'บันทึกการแก้ไขข้อสอบสำเร็จ', ['exam_id' => $exam_id]);
} catch (Exception $e) {
    if ($pdo_connect->inTransaction()) { $pdo_connect->rollBack(); }
    error_log('Update Exam Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
