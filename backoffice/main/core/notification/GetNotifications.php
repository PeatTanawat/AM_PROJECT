<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Database\Connection;

header('Content-Type: application/json');

try {
    $db = (new Connection())->getPdo();

    // ดึงจำนวนรายการที่ยังไม่ได้อ่าน
    $stmtCount = $db->query("SELECT COUNT(*) as unread_count FROM tbl_notifications WHERE is_read = 0");
    $unreadCount = $stmtCount->fetchColumn();

    // ดึงรายการแจ้งเตือน 10 รายการล่าสุด
    $stmt = $db->query("SELECT noti_id, type, title, message, link_url, is_read, created_at FROM tbl_notifications ORDER BY noti_id DESC LIMIT 10");
    $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // จัดรูปแบบเวลาให้สวยงาม (ถ้าต้องการ) เช่น 2 ชม. ที่แล้ว (ฝั่ง Frontend อาจจะไปจัดการต่อ)
    
    echo json_encode([
        'status' => 'success',
        'unread_count' => $unreadCount,
        'notifications' => $notifications
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
