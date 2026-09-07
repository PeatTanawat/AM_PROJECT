<?php
// รายชื่อคอร์ส (สำหรับ dropdown ในหน้ารายงาน/เอกสาร)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

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

try {
    $rows = $pdo->query(
        "SELECT course_id, course_name
         FROM tbl_course
         WHERE delete_at IS NULL
         ORDER BY course_name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(fn($r) => [
        'id'   => (int) $r['course_id'],
        'name' => (string) $r['course_name'],
    ], $rows);

    Response::json(1, 'Success', ['courses' => $data]);
} catch (\Throwable $e) {
    error_log('Report GetCourses Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo = null;
}
