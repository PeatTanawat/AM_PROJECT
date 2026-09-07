<?php
// เปิดดูเอกสารประกอบ "แบบ inline" (ให้ browser แสดงในแท็บใหม่ ไม่บังคับดาวน์โหลด)
// public เหมือนไฟล์ใน upload/ ที่เสิร์ฟตรงอยู่แล้ว (รูปหน้าปกฯลฯ)
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\Connection;

$key = isset($_GET['key']) ? trim($_GET['key']) : '';
$id = \App\Utility\Cipher::decrypt($key);
if ($id <= 0) { http_response_code(404); exit('ไม่พบไฟล์'); }

$pdo = (new Connection())->getPdo();
$stmt = $pdo->prepare("SELECT lesson_file_name, lesson_file_type, lesson_file_path FROM tbl_lesson_file WHERE lesson_file_id = :id AND delete_at IS NULL LIMIT 1");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit('ไม่พบไฟล์'); }

$storedPath = trim((string)($row['lesson_file_path'] ?? ''));

// หากเป็น Full Remote URL (เช่น S3 URL)
if (preg_match('~^https?://~i', $storedPath)) {
    header('Location: ' . $storedPath);
    exit;
}

// ตรวจสอบไฟล์ในเครื่อง Local จาก path ในฐานข้อมูล
$path = null;
if ($storedPath !== '') {
    $cleanPath = preg_replace('~^(am/)?(backoffice/)?~i', '', ltrim($storedPath, './'));
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    if (is_file($fullPath)) {
        $path = $fullPath;
    }
}

// Fallback: ไฟล์เดิมที่ใช้ชื่อเป็น {id}.{ext}
if (!$path) {
    $matches = glob(dirname(__DIR__) . '/upload/lesson_file/' . $id . '.*');
    if ($matches && is_file($matches[0])) {
        $path = $matches[0];
    }
}

if (!$path || !is_file($path)) {
    http_response_code(404);
    exit('ไม่พบไฟล์บนเซิร์ฟเวอร์');
}
$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// เสิร์ฟด้วย mime ตามนามสกุล (ให้ browser แสดง inline ได้ถูกต้อง)
$mimeMap = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
    'txt' => 'text/plain; charset=utf-8', 'csv' => 'text/csv; charset=utf-8',
];
$serveMime = $mimeMap[$ext] ?? ($row['lesson_file_type'] ?: 'application/octet-stream');

$name = trim((string)$row['lesson_file_name']);
if ($name === '') { $name = 'document'; }

header('Content-Type: ' . $serveMime);
// inline = เปิดดูในแท็บ (ไม่ใช่ attachment ที่บังคับโหลด); filename* รองรับชื่อไทย
header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($name) . '.' . $ext);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
