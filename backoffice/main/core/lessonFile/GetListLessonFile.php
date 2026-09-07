<?php
// รายการเอกสารประกอบของคอร์ส (รวมทุกบทเรียน) — JOIN เพื่อแสดงชื่อบทเรียน

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
if ($course_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคอร์สเรียน', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT f.lesson_file_id, f.lesson_id, f.lesson_file_name, f.lesson_file_type, f.lesson_file_path, l.lesson_name
     FROM tbl_lesson_file f
     JOIN tbl_lesson l ON l.lesson_id = f.lesson_id
     WHERE l.course_id = :cid AND f.delete_at IS NULL AND l.delete_at IS NULL
     ORDER BY f.lesson_file_id ASC"
);
$stmt->execute([':cid' => $course_id]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// หา path ไฟล์: ใหม่ = URL บน S3 (lesson_file_path) / เก่า = ไฟล์ local upload/lesson_file/{id}.{ext}
$rootDir = dirname(__DIR__, 3);
foreach ($list as &$row) {
    $stored = trim((string)($row['lesson_file_path'] ?? ''));
    if ($stored !== '') {
        $row['file_path'] = $stored;   // S3 URL
    } else {
        $row['file_path'] = null;
        $matches = glob($rootDir . '/upload/lesson_file/' . (int)$row['lesson_file_id'] . '.*');
        if ($matches) {
            $row['file_path'] = 'upload/lesson_file/' . basename($matches[0]);
        }
    }
}
unset($row);

Response::json(1, 'Success', ['list_data' => $list]);
