<?php
namespace App\Core\MainHistory;

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;
use Exception;
use PDO;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำสั่งซื้อ', null);
}

if (!isset($_FILES['slip_file'])) {
    Response::json(0, 'กรุณาอัปโหลดรูปภาพสลิปชำระเงิน', null);
}

$file = $_FILES['slip_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    Response::json(0, 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์สลิป', null);
}

$db = (new Connection())->getPdo();

// Fetch order
$stmt = $db->prepare("SELECT * FROM tbl_orders WHERE order_id = :id AND user_id = :uid LIMIT 1");
$stmt->execute([':id' => $order_id, ':uid' => $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$order) {
    Response::json(0, 'ไม่พบคำสั่งซื้อ', null);
}

// Can only reupload if status is not '1' (paid)
if ($order['payment_status'] === '1') {
    Response::json(0, 'คำสั่งซื้อนี้ชำระเงินเรียบร้อยแล้ว ไม่สามารถอัปโหลดสลิปใหม่ได้', null);
}

$s3_url = '';
try {
    $s3_result = AwsS3::uploadFileDirectly($file, true, 'slips');
    $s3_url = $s3_result['url'] ?? '';
} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการอัปโหลดสลิป: ' . $e->getMessage(), null);
}

$db->beginTransaction();

try {
    $new_status = '0'; // รอเจ้าหน้าที่ตรวจสอบ
    $order_internal_note = $order['order_internal_note'] ?? '';
    
    // ลบรูปสลิปเก่าออกจาก S3 (ถ้ามี)
    if (!empty($order['slip_image'])) {
        try {
            AwsS3::deleteFileByURL($order['slip_image']);
        } catch (Exception $e) {}
    }

    if (!empty($s3_url)) {
        if (empty($order_internal_note)) {
            $order_internal_note = "S3_URL_REUPLOAD: " . $s3_url;
        } else {
            $order_internal_note .= " | S3_URL_REUPLOAD: " . $s3_url;
        }
    }
    
    $upd = $db->prepare("UPDATE tbl_orders SET payment_status = :status, remark_reject = NULL, slip_image = :slip_image, order_internal_note = :note WHERE order_id = :oid AND user_id = :uid");
    $upd->execute([
        ':status' => $new_status,
        ':slip_image' => $s3_url,
        ':note' => $order_internal_note,
        ':oid' => $order_id,
        ':uid' => $user_id
    ]);

    // Update enrollment to pending
    $enroll_payment_status = 'pending';
    $enroll_access = 0;
    
    // Fetch courses in this order to update enrollment
    $stmt = $db->prepare("SELECT course_id FROM tbl_order_detail WHERE order_id = :oid");
    $stmt->execute([':oid' => $order_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();
    
    if (count($courses) > 0) {
        $upd_enroll = $db->prepare("UPDATE tbl_course_enrollment SET enroll_payment_status = :pstatus, enroll_access = :acc, update_at = :now WHERE enroll_user_id = :uid AND enroll_course_id = :cid AND delete_at IS NULL");
        $now = date('Y-m-d H:i:s');
        foreach ($courses as $cid) {
            $upd_enroll->execute([
                ':pstatus' => $enroll_payment_status,
                ':acc' => $enroll_access,
                ':now' => $now,
                ':uid' => $user_id,
                ':cid' => $cid
            ]);
        }
    }

    $db->commit();
    
    // Send Webhook Notification for manual slip verification
    $name = trim(($access_token->user_firstname ?? '') . ' ' . ($access_token->user_lastname ?? ''));
    if (empty($name)) $name = 'ลูกค้าไม่ทราบชื่อ';

    $webhook_url = 'https://bigsara-demo.com/am/backoffice/notify.php'; 
    
    $webhook_data = [
        'title' => 'แจ้งเตือนตรวจสอบสลิป',
        'message' => 'คุณ ' . $name . ' แก้ไขแนบสลิปชำระเงินใหม่ (รอเจ้าหน้าที่ตรวจสอบ)',
        'type' => 'order_slip',
        'link_url' => 'order_detail.php?id=' . $order_id,
        'reference_id' => $order_id
    ];

    $ch_web = curl_init($webhook_url);
    curl_setopt($ch_web, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_web, CURLOPT_POST, true);
    curl_setopt($ch_web, CURLOPT_POSTFIELDS, json_encode($webhook_data));
    curl_setopt($ch_web, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer my_secret_key_1234'
    ]);
    curl_setopt($ch_web, CURLOPT_TIMEOUT, 5);
    
    $response_web = curl_exec($ch_web);
    curl_close($ch_web);
    
    Response::json(1, 'ส่งสลิปชำระเงินใหม่เรียบร้อยแล้ว กรุณารอเจ้าหน้าที่ตรวจสอบ', ['verified' => false]);

} catch (Exception $e) {
    $db->rollBack();
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage(), null);
}
