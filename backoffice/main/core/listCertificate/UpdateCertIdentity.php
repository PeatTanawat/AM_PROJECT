<?php
// ปรับปรุงไฟล์ยืนยันตัวตนในเอกสาร -> ตั้งรูปในใบรับรอง (tbl_user.id_card_image)
// ให้เป็นรูปเอกสารปัจจุบันของผู้ใช้ (tbl_user.current_photo)
// ใช้เมื่อรูปในใบรับรองไม่ตรงกับรูปปัจจุบัน (ผู้ใช้อัปเดตเอกสารใหม่)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;
use App\Utility\ImageOptimizer;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$enroll_id = isset($_POST['enroll_id']) ? (int) $_POST['enroll_id'] : 0;
if ($enroll_id <= 0) {
    Response::json(0, 'ไม่พบรายการ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// หา user ของ enrollment นี้ + รูปเอกสารปัจจุบัน
$stmt = $pdo_connect->prepare(
    "SELECT u.user_id, u.current_photo, u.id_card_image
       FROM tbl_course_enrollment e
       LEFT JOIN tbl_user u ON e.enroll_user_id = u.user_id
      WHERE e.enroll_id = :id AND e.delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $enroll_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$row || empty($row['user_id'])) {
    Response::json(0, 'ไม่พบผู้ใช้ของรายการนี้', null);
}
$current = trim((string) ($row['current_photo'] ?? ''));

$target_image = '';

if (isset($_FILES['new_identity']) && $_FILES['new_identity']['error'] === UPLOAD_ERR_OK) {
    ImageOptimizer::toWebp('new_identity');
    $upload = AwsS3::uploadFileDirectly($_FILES['new_identity'], true, 'certificate_identity');
    if (!empty($upload['path'])) {
        $target_image = $upload['path'];
    } else {
        Response::json(0, 'ไม่สามารถอัปโหลดไฟล์ใหม่ได้: ' . ($upload['error'] ?? 'Unknown Error'), null);
    }
} else {
    Response::json(0, 'กรุณาอัปโหลดรูปภาพใหม่', null);
}

$upd = $pdo_connect->prepare(
    "UPDATE tbl_certificate_snapshot 
     SET id_card_image_snapshot = :img 
     WHERE enroll_id = :enroll_id"
);
$upd->execute([':img' => $target_image, ':enroll_id' => $enroll_id]);

Response::json(1, 'ปรับปรุงไฟล์ยืนยันตัวตนในเอกสารเรียบร้อยแล้ว', ['enroll_id' => $enroll_id]);
