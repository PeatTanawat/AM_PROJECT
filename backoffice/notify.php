<?php
// ไฟล์ am/backoffice/notify.php
// ทำหน้าที่รับ Webhook จากระบบ cpdth (หน้าบ้าน) เพื่อสั่งยิงแจ้งเตือน WebPush

// 1. ตรวจสอบการอนุญาตข้ามโดเมน (CORS) เผื่อกรณีจำเป็น
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 2. เรียกใช้ Autoload และคลาส Notification ของฝั่ง am เอง
require_once __DIR__ . '/vendor/autoload.php'; 
require_once __DIR__ . '/src/Utility/Notification.php';

// Debug Log:
file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Webhook received\n", FILE_APPEND);
file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Headers: " . json_encode(getallheaders()) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Input: " . file_get_contents('php://input') . "\n", FILE_APPEND);

// 3. ตรวจสอบสิทธิ์ (ต้องใช้ my_secret_key_1234 ให้ตรงกับฝั่ง cpdth ที่ส่งมา)
$headers = getallheaders();
$auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if ($auth !== 'Bearer my_secret_key_1234') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: รหัสผ่าน API ไม่ถูกต้อง']);
    exit;
}

// 4. รับข้อมูล JSON ที่ฝั่งหน้าบ้านส่งมา
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง (ไม่ใช่ JSON)']);
    exit;
}

$title = $data['title'] ?? 'การแจ้งเตือนใหม่';
$message = $data['message'] ?? '';
$type = $data['type'] ?? 'general';
$link_url = $data['link_url'] ?? '#';
$reference_id = $data['reference_id'] ?? null;

// เข้ารหัส reference_id (ถ้ามี) เพื่อสร้าง link_url ที่ถูกต้อง
if ($reference_id) {
    $ciphered_key = urlencode(\App\Utility\Cipher::encrypt($reference_id));
    if ($type === 'user_verify') {
        $link_url = 'user_edit.php?key=' . $ciphered_key;
    } elseif ($type === 'order_slip') {
        $link_url = 'order_detail2.php?key=' . $ciphered_key;
    }
}

try {
    // 5. โยนข้อมูลให้คลาส Notification ของ am จัดการต่อ
    $result = \App\Utility\Notification::send($title, $message, $type, $link_url, $reference_id);
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'ส่งแจ้งเตือน WebPush สำเร็จ']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดภายใน Notification::send()']);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}
