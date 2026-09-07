<?php
/**
 * สคริปต์รันเบื้องหลัง (Background Watcher) สำหรับตรวจจับข้อมูลใหม่
 * แนะนำให้ตั้ง Cron Job ให้รันไฟล์นี้ทุกๆ 1 นาที: * * * * * php /path/to/watcher.php
 */
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;
use App\Utility\Notification;
use Dotenv\Dotenv;

// โหลด ENV
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = (new Connection())->getPdo();

try {
    // 1. ดึง Last ID ของแต่ละตารางจาก Tracker
    $stmt = $db->query("SELECT table_name, last_id FROM tbl_notification_tracker");
    $trackers = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $trackers[$row['table_name']] = (int) $row['last_id'];
    }

    // ฟังก์ชันช่วยเพื่ออัปเดต Last ID กลับไปที่ตาราง Tracker
    $updateLastId = function($tableName, $newLastId) use ($db) {
        $stmt = $db->prepare("UPDATE tbl_notification_tracker SET last_id = :last_id, updated_at = NOW() WHERE table_name = :table_name");
        $stmt->execute([':last_id' => $newLastId, ':table_name' => $tableName]);
    };

    // ==========================================
    // ตรวจสอบ 1: ยืนยันตัวตน (tbl_user)
    // เงื่อนไข: ส่งข้อมูลยืนยันตัวตนใหม่ (สมมติ identity_verified = '1' คือรอยืนยัน)
    // ==========================================
    $lastUserId = $trackers['tbl_user'] ?? 0;
    $stmtUser = $db->prepare("SELECT user_id, user_firstname, user_lastname, identity_verified FROM tbl_user WHERE user_id > :last_id AND identity_verified = '1' ORDER BY user_id ASC");
    $stmtUser->execute([':last_id' => $lastUserId]);
    $users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

    $maxUserId = $lastUserId;
    foreach ($users as $user) {
        $maxUserId = max($maxUserId, $user['user_id']);
        Notification::send(
            'คำขอยืนยันตัวตนใหม่',
            'คุณ ' . $user['user_firstname'] . ' ' . $user['user_lastname'] . ' ได้ส่งคำขอยืนยันตัวตนเข้าสู่ระบบ',
            'user_verify',
            'verify_request.php'
        );
    }
    if ($maxUserId > $lastUserId) {
        $updateLastId('tbl_user', $maxUserId);
    }


    // ==========================================
    // ตรวจสอบ 2: อนุมัติคอร์ส (tbl_course_enrollment)
    // เงื่อนไข: มีการสมัครคอร์สใหม่เข้ามา
    // ==========================================
    // $lastEnrollId = $trackers['tbl_course_enrollment'] ?? 0;
    // $stmtEnroll = $db->prepare("
    //     SELECT e.enroll_id, u.user_firstname, u.user_lastname 
    //     FROM tbl_course_enrollment e 
    //     LEFT JOIN tbl_user u ON e.user_id = u.user_id 
    //     WHERE e.enroll_id > :last_id 
    //     ORDER BY e.enroll_id ASC
    // ");
    // $stmtEnroll->execute([':last_id' => $lastEnrollId]);
    // $enrolls = $stmtEnroll->fetchAll(PDO::FETCH_ASSOC);

    // $maxEnrollId = $lastEnrollId;
    // foreach ($enrolls as $enroll) {
    //     $maxEnrollId = max($maxEnrollId, $enroll['enroll_id']);
    //     $name = trim(($enroll['user_firstname'] ?? '') . ' ' . ($enroll['user_lastname'] ?? ''));
    //     if (empty($name)) $name = 'ลูกค้าไม่ทราบชื่อ';
        
    //     Notification::send(
    //         'คำขออนุมัติคอร์สเรียน',
    //         'คุณ ' . $name . ' ขออนุมัติเข้าเรียนคอร์สใหม่',
    //         'course_approval',
    //         'course_remaining.php' // สามารถปรับเปลี่ยนลิงก์ไปยังหน้าอนุมัติคอร์สได้
    //     );
    // }
    // if ($maxEnrollId > $lastEnrollId) {
    //     $updateLastId('tbl_course_enrollment', $maxEnrollId);
    // }


    // // ==========================================
    // // ตรวจสอบ 3: คำสั่งซื้อคอร์สเรียน (tbl_orders)
    // // เงื่อนไข: มีคำสั่งซื้อใหม่เกิดขึ้น
    // // ==========================================
    // $lastOrderId = $trackers['tbl_orders'] ?? 0;
    // $stmtOrder = $db->prepare("
    //     SELECT o.order_id, o.order_number, u.user_firstname, u.user_lastname 
    //     FROM tbl_orders o 
    //     LEFT JOIN tbl_user u ON o.user_id = u.user_id 
    //     WHERE o.order_id > :last_id 
    //     ORDER BY o.order_id ASC
    // ");
    // $stmtOrder->execute([':last_id' => $lastOrderId]);
    // $orders = $stmtOrder->fetchAll(PDO::FETCH_ASSOC);

    // $maxOrderId = $lastOrderId;
    // foreach ($orders as $order) {
    //     $maxOrderId = max($maxOrderId, $order['order_id']);
    //     $name = trim(($order['user_firstname'] ?? '') . ' ' . ($order['user_lastname'] ?? ''));
    //     if (empty($name)) $name = 'ลูกค้าไม่ทราบชื่อ';
        
    //     Notification::send(
    //         'คำสั่งซื้อใหม่',
    //         'คุณ ' . $name . ' สั่งซื้อคอร์ส (Order: ' . $order['order_number'] . ')',
    //         'new_order',
    //         'order_pending.php'
    //     );
    // }
    // if ($maxOrderId > $lastOrderId) {
    //     $updateLastId('tbl_orders', $maxOrderId);
    // }


    // ==========================================
    // [ฟีเจอร์กันตารางบวม] ลบประวัติการแจ้งเตือนที่เก่ากว่า 30 วัน
    // ==========================================
    $db->exec("DELETE FROM tbl_notifications WHERE created_at < NOW() - INTERVAL 30 DAY");

    echo "Watcher executed successfully at " . date('Y-m-d H:i:s') . "\n";

} catch (\Exception $e) {
    error_log("Watcher Error: " . $e->getMessage());
    echo "Watcher failed: " . $e->getMessage() . "\n";
}
