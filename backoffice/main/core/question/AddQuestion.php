<?php
// เพิ่มคำถามระหว่างรับชม (tbl_question) + ตัวเลือก (tbl_question_choice)

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

$lesson_id     = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
$question_text = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
$choices       = isset($_POST['choice_text']) && is_array($_POST['choice_text']) ? $_POST['choice_text'] : [];
$correct       = isset($_POST['correct']) ? (int) $_POST['correct'] : 0;   // 1-based

if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}
if ($question_text === '') {
    Response::json(0, 'กรุณากรอกคำถาม', null);
}

// ตัวเลือกที่ไม่ว่าง (เก็บ index เดิมไว้เทียบกับ correct)
$valid = [];
foreach ($choices as $i => $c) {
    $c = trim((string)$c);
    if ($c !== '') { $valid[$i + 1] = $c; }   // index 1-based ตรงกับ correct
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

/* ---------- อัปโหลดไฟล์ (ภาพ/ไฟล์ประกอบ) ---------- */
$saveFile = function (string $field, array $allowExt): ?string {
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowExt, true)) {
        Response::json(0, 'ชนิดไฟล์ไม่รองรับ (' . $field . ')', null);
    }
    // อัปโหลดขึ้น S3 แล้วเก็บ URL เต็ม
    $filename = bin2hex(random_bytes(8));
    $s3Result = AwsS3::uploadFileDirectly($_FILES[$field], true, 'question', $filename);
    if (isset($s3Result['error'])) {
        Response::json(0, 'อัปโหลดไฟล์ขึ้น S3 ไม่สำเร็จ (' . $field . '): ' . $s3Result['error'], null);
    }
    return $s3Result['url'];
};
ImageOptimizer::toWebp('question_image'); // แปลงรูปเป็น WebP ก่อนอัปขึ้น S3
$image_path = $saveFile('question_image', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
$file_path  = $saveFile('question_file', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png']);

try {
    $pdo_connect->beginTransaction();

    $stmt = $pdo_connect->prepare(
        "INSERT INTO tbl_question (lesson_id, question_text, question_image, question_file)
         VALUES (:lid, :text, :img, :file)"
    );
    $stmt->execute([
        ':lid'  => $lesson_id,
        ':text' => $question_text,
        ':img'  => $image_path,
        ':file' => $file_path,
    ]);
    $question_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    $cstmt = $pdo_connect->prepare(
        "INSERT INTO tbl_question_choice (question_id, question_choice_text, question_choice_correct)
         VALUES (:qid, :text, :correct)"
    );
    foreach ($valid as $idx => $text) {
        $cstmt->execute([
            ':qid'     => $question_id,
            ':text'    => $text,
            ':correct' => ($idx === $correct) ? '1' : '0',
        ]);
    }
    $cstmt->closeCursor();

    $pdo_connect->commit();
    Response::json(1, 'เพิ่มคำถามสำเร็จ', ['question_id' => $question_id]);
} catch (Exception $e) {
    if ($pdo_connect->inTransaction()) { $pdo_connect->rollBack(); }
    error_log('Add Question Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
