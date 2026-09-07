<?php
// อัปโหลดไฟล์วิดีโอบทเรียนลงโฟลเดอร์เครื่อง Local (backoffice/upload/lesson/)
// และบันทึก relative path (upload/lesson/...) ลง tbl_lesson.lesson_video

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
if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}

if (empty($_FILES['video_file']['name']) || ($_FILES['video_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['video_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errMsg = 'กรุณาเลือกไฟล์วิดีโอ';
    if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
        $errMsg = 'ขนาดไฟล์วิดีโอใหญ่เกินกว่าที่เซิร์ฟเวอร์กำหนด (ตรวจสอบ upload_max_filesize / post_max_size ใน php.ini)';
    }
    Response::json(0, $errMsg, null);
}

$ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
$allowExt = ['mp4', 'mov', 'avi', 'wmv', 'mkv', 'webm', 'm4v'];
if (!in_array($ext, $allowExt, true)) {
    Response::json(0, 'รองรับเฉพาะไฟล์วิดีโอ (mp4, mov, avi, wmv, mkv, webm, m4v)', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ตรวจสอบว่ามีบทเรียนนี้จริงหรือไม่
$check = $pdo_connect->prepare("SELECT lesson_id, lesson_video FROM tbl_lesson WHERE lesson_id = :id AND delete_at IS NULL LIMIT 1");
$check->execute([':id' => $lesson_id]);
$currentLesson = $check->fetch(PDO::FETCH_ASSOC);
$check->closeCursor();

if (!$currentLesson) {
    Response::json(0, 'ไม่พบบทเรียนนี้', null);
}

try {
    // โฟลเดอร์เป้าหมาย: backoffice/upload/lesson
    $uploadDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'lesson';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new Exception("ไม่สามารถสร้างโฟลเดอร์เก็บวิดีโอได้: " . $uploadDir);
        }
    }

    // สร้างชื่อไฟล์ที่ปลอดภัยและไม่ซ้ำกัน
    $randomHash = bin2hex(random_bytes(8));
    $newFileName = "video_{$lesson_id}_{$randomHash}.{$ext}";
    $targetFilePath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

    // ย้ายไฟล์ที่อัปโหลด
    if (is_uploaded_file($_FILES['video_file']['tmp_name'])) {
        if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $targetFilePath)) {
            throw new Exception("ไม่สามารถย้ายไฟล์วิดีโอไปยังโฟลเดอร์เป้าหมายได้");
        }
    } else {
        if (!copy($_FILES['video_file']['tmp_name'], $targetFilePath)) {
            throw new Exception("ไม่สามารถบันทึกไฟล์วิดีโอได้");
        }
    }

    $relativeDbPath = "upload/lesson/{$newFileName}";

    // ลบไฟล์วิดีโอเก่าในเครื่องหากมีอยู่เดิม
    $oldVideo = trim((string)($currentLesson['lesson_video'] ?? ''));
    if ($oldVideo !== '' && !preg_match('#player\.vimeo\.com#i', $oldVideo) && !preg_match('#vimeo\.com#i', $oldVideo)) {
        $oldFileClean = preg_replace('~^(am/)?(backoffice/)?~i', '', ltrim($oldVideo, './'));
        $oldFullPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldFileClean);
        if (is_file($oldFullPath) && $oldFullPath !== $targetFilePath) {
            @unlink($oldFullPath);
        }
    }

    // บันทึก Path ลงฐานข้อมูล
    $upd = $pdo_connect->prepare("UPDATE tbl_lesson SET lesson_video = :v WHERE lesson_id = :id AND delete_at IS NULL");
    $upd->execute([':v' => $relativeDbPath, ':id' => $lesson_id]);
    $upd->closeCursor();

    Response::json(1, 'อัปโหลดวิดีโอและบันทึกข้อมูลเรียบร้อย', [
        'lesson_id'    => $lesson_id,
        'lesson_video' => $relativeDbPath,
        'file_name'    => $newFileName
    ]);
} catch (Exception $e) {
    error_log("UploadVideoLocal Error: " . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกวิดีโอ: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
