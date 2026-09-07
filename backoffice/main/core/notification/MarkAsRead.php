<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Database\Connection;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $db = (new Connection())->getPdo();

    // ดึงค่า noti_id ที่ส่งมา (ถ้าเป็น 'all' คือให้อ่านทั้งหมด)
    $notiId = $_POST['noti_id'] ?? 'all';

    if ($notiId === 'all') {
        $stmt = $db->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE is_read = 0");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE noti_id = :noti_id");
        $stmt->execute([':noti_id' => $notiId]);
    }

    echo json_encode(['status' => 'success']);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
