<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Database\Connection;
use App\Utility\Auth;

try {
    // 1. ตรวจสอบการเข้าสู่ระบบ
    $currentUser = Auth::getUser();
    if (!$currentUser) {
        http_response_code(401);
        die("Unauthorized access.");
    }

    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $file_id = \App\Utility\Cipher::decrypt($key);
    if ($file_id <= 0) {
        http_response_code(400);
        die("Invalid file ID.");
    }

    $db = (new Connection())->getPdo();
    $sql = "SELECT lesson_file_name, lesson_file_type, lesson_file_path FROM tbl_lesson_file WHERE lesson_file_id = :id AND delete_at IS NULL LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$file || empty($file['lesson_file_name'])) {
        http_response_code(404);
        die("File not found.");
    }

    $filename = $file['lesson_file_name'];
    $fileType = $file['lesson_file_type'];

    $storedPath = trim((string)($file['lesson_file_path'] ?? ''));
    $filePath = null;

    if ($storedPath !== '') {
        if (preg_match('~^https?://~i', $storedPath)) {
            header("Location: " . $storedPath);
            exit;
        }
        $cleanPath = preg_replace('~^(am/)?(backoffice/)?~i', '', ltrim($storedPath, './'));
        $target = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backoffice' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, preg_replace('~^upload/~i', '', $cleanPath));
        if (is_file($target)) {
            $filePath = $target;
        }
    }

    if (!$filePath) {
        $possible_paths = [
            __DIR__ . '/lesson_file/' . $filename,
            dirname(__DIR__) . '/backoffice/upload/lesson_file/' . $filename
        ];
        foreach ($possible_paths as $path) {
            if (is_file($path)) {
                $filePath = $path;
                break;
            }
        }
    }

    if ($filePath && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $downloadName = $filename;
        if (!preg_match('/\.[a-z0-9]+$/i', $downloadName) && $ext !== '') {
            $downloadName .= '.' . $ext;
        }

        // ส่งไฟล์ให้ดาวน์โหลดโดยตรง
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($fileType ?: 'application/octet-stream'));
        header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($downloadName));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        die("ไม่พบไฟล์บนเซิร์ฟเวอร์");
    }

} catch (Exception $e) {
    http_response_code(500);
    die("Error: " . $e->getMessage());
}
