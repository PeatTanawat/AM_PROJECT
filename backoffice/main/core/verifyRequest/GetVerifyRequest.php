<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\AwsS3;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;

if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($target_id <= 0) {
    Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ข้อมูลเอกสารยืนยันตัวตนของผู้ใช้ (เฉพาะที่ยังไม่ถูกลบ)
$stmt = $pdo_connect->prepare(
    "SELECT user_id,
            user_prefix,
            user_firstname, 
            user_lastname,
            user_citizen_id,
            identity_verified, 
            id_card_expiry_date,
            id_card_image, 
            current_photo,
    approver_citizen, remark
     FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt->execute([':id' => $target_id]);
$verify = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$verify) {
    Response::json(0, 'ไม่พบคำขอยืนยันตัวตนนี้', null);
}

// แปลง S3 key → Presigned URL (30 นาที) ก่อนส่งกลับ
$verify['id_card_image'] = $verify['id_card_image']
    ? AwsS3::getFileUrl($verify['id_card_image'])
    : null;
$verify['current_photo'] = $verify['current_photo']
    ? AwsS3::getFileUrl($verify['current_photo'])
    : null;

Response::json(1, 'Success', [
    'verify' => $verify,
]);
