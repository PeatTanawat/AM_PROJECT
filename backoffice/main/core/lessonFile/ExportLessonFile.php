<?php
// ส่งเอกสารประกอบการสอนเป็น base64 ผ่าน JSON (เลียนแบบ ExportCertificate)
// เพื่อให้หน้าพรีวิวประกอบ blob ฝั่ง client -> ไม่มี request ที่หน้าตาเป็นไฟล์ให้ download manager ดัก

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

// เปิดผ่าน POST (ไม่มี header Authorization) -> รับ token จาก POST แทน
if (empty($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_POST['access_token'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_POST['access_token'];
}

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$id = isset($_POST['lesson_file_id']) ? (int) $_POST['lesson_file_id'] : 0;
if ($id <= 0) {
    Response::json(0, 'ไม่พบเอกสาร', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT lesson_file_name, lesson_file_type, lesson_file_path FROM tbl_lesson_file
     WHERE lesson_file_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$row) {
    Response::json(0, 'ไม่พบเอกสารนี้ หรือถูกลบไปแล้ว', null);
}

$storedPath = trim((string)($row['lesson_file_path'] ?? ''));
$path = null;
if ($storedPath !== '') {
    $cleanPath = preg_replace('~^(am/)?(backoffice/)?~i', '', ltrim($storedPath, './'));
    $fullPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    if (is_file($fullPath)) {
        $path = $fullPath;
    }
}

if (!$path) {
    $matches = glob(dirname(__DIR__, 3) . '/upload/lesson_file/' . $id . '.*');
    if ($matches && is_file($matches[0])) {
        $path = $matches[0];
    }
}

if (!$path || !is_file($path)) {
    Response::json(0, 'ไม่พบไฟล์บนเซิร์ฟเวอร์', null);
}
$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// mime สำหรับพรีวิว (อิงนามสกุลก่อน แล้ว fallback เป็นค่าที่บันทึกไว้)
$mimeMap = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
    'txt' => 'text/plain; charset=utf-8', 'csv' => 'text/csv; charset=utf-8',
];
$mime = $mimeMap[$ext] ?? ($row['lesson_file_type'] ?: 'application/octet-stream');

$name = trim((string) $row['lesson_file_name']);
if ($name === '') { $name = 'document'; }

$data = @file_get_contents($path);
if ($data === false) {
    Response::json(0, 'อ่านไฟล์ไม่สำเร็จ', null);
}

Response::json(1, 'ok', [
    'filename' => $name . '.' . $ext,
    'mime'     => $mime,
    'file'     => base64_encode($data),
]);
