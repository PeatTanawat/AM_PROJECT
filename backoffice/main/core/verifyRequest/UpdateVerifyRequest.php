<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\AwsS3;
use App\Utility\Email;
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

// ผลการตรวจสอบเอกสาร: 2 = อนุมัติ, 1 = ปฏิเสธ
$result = isset($_POST['approver_citizen']) ? trim((string) $_POST['approver_citizen']) : '';
if (!in_array($result, ['1', '2'], true)) {
    Response::json(0, 'กรุณาเลือกผลการตรวจสอบเอกสาร', null);
}

$remark = isset($_POST['remark']) ? trim((string) $_POST['remark']) : '';
$id_card_expiry_date = isset($_POST['id_card_expiry_date']) && trim($_POST['id_card_expiry_date']) !== '' ? trim($_POST['id_card_expiry_date']) : null;

// ปฏิเสธ (1) ต้องมีหมายเหตุเสมอ
if ($result === '1' && $remark === '') {
    Response::json(0, 'กรุณาระบุหมายเหตุการไม่อนุมัติ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ดึงข้อมูลรูปเดิมเพื่อเตรียมลบใน S3 หากมีการอัปโหลดรูปใหม่
$stmt_check = $pdo_connect->prepare(
    "SELECT user_id, user_email, user_firstname, user_lastname, id_card_image, current_photo, identity_verified FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1"
);
$stmt_check->execute([':id' => $target_id]);
$exists = $stmt_check->fetch(PDO::FETCH_ASSOC);
$stmt_check->closeCursor();

if (!$exists) {
    Response::json(0, 'ไม่พบคำขอยืนยันตัวตนนี้', null);
}

// จัดการอัปโหลดรูปเอกสารใหม่ไปยัง AWS S3 (ถ้ามีการเลือกไฟล์)
$new_id_card_path = null;
if (!empty($_FILES['file_id_card']['tmp_name']) && $_FILES['file_id_card']['error'] === UPLOAD_ERR_OK) {
    $filename_citizen = 'citizen_' . $target_id . '_' . time();
    $citizen_upload = AwsS3::uploadFileDirectly($_FILES['file_id_card'], true, 'identity', $filename_citizen);
    if (isset($citizen_upload['error'])) {
        Response::json(0, 'ไม่สามารถอัปโหลดรูปเอกสารได้: ' . $citizen_upload['error'], null);
    }
    $new_id_card_path = $citizen_upload['path'];
}

// จัดการอัปโหลดรูปหน้ายืนยันใหม่ไปยัง AWS S3 (ถ้ามีการเลือกไฟล์)
$new_current_photo_path = null;
if (!empty($_FILES['file_current_photo']['tmp_name']) && $_FILES['file_current_photo']['error'] === UPLOAD_ERR_OK) {
    $filename_avatar = 'avatar_' . $target_id . '_' . time();
    $avatar_upload = AwsS3::uploadFileDirectly($_FILES['file_current_photo'], true, 'identity', $filename_avatar);
    if (isset($avatar_upload['error'])) {
        if ($new_id_card_path) {
            AwsS3::deleteFile($new_id_card_path);
        }
        Response::json(0, 'ไม่สามารถอัปโหลดรูปหน้ายืนยันได้: ' . $avatar_upload['error'], null);
    }
    $new_current_photo_path = $avatar_upload['path'];
}

// อนุมัติ -> ยืนยันตัวตนสำเร็จ (identity_verified = 2)
// ปฏิเสธ -> กลับไปสถานะยังไม่ยืนยัน (identity_verified = 0) ให้ผู้ใช้ส่งใหม่ได้
$identity_verified = ($result === '2') ? '2' : '0';

// log ประวัติการยืนยันตัวตน (action_type: 1 = ผ่าน/อนุมัติ, 2 = ยกเลิก/ปฏิเสธ)
$action_type = ($result === '2') ? '1' : '2';
$log_remark = ($result === '2')
    ? ($remark !== '' ? $remark : 'อนุมัติยืนยันตัวตนโดยตรวจสอบเอกสาร')
    : $remark;   // ปฏิเสธ มีหมายเหตุเสมอ (ตรวจไว้ด้านบนแล้ว)

try {
    $pdo_connect->beginTransaction();

    $update_fields = [
        "identity_verified = :iv",
        "approver_citizen  = :ac",
        "remark            = :rm",
        "id_card_expiry_date = :expiry"
    ];

    $update_params = [
        ':iv' => $identity_verified,      
        ':ac' => $result,
        ':rm' => $remark !== '' ? $remark : null,
        ':expiry' => $id_card_expiry_date,
        ':id' => $target_id,
    ];

    if ($new_id_card_path !== null) {
        $update_fields[] = "id_card_image = :id_card_img";
        $update_params[':id_card_img'] = $new_id_card_path;
    }
    if ($new_current_photo_path !== null) {
        $update_fields[] = "current_photo = :curr_photo";
        $update_params[':curr_photo'] = $new_current_photo_path;
    }

    $sql_update = "UPDATE tbl_user SET " . implode(", ", $update_fields) . " WHERE user_id = :id AND delete_at IS NULL";
    $stmt = $pdo_connect->prepare($sql_update);
    $stmt->execute($update_params);
    $stmt->closeCursor();

    // ลบไฟล์เดิมใน AWS S3 เมื่อมีการอัปโหลดไฟล์ใหม่แทนที่สำเร็จ
    if ($new_id_card_path !== null && !empty($exists['id_card_image'])) {
        AwsS3::deleteFile($exists['id_card_image']);
    }
    if ($new_current_photo_path !== null && !empty($exists['current_photo'])) {
        AwsS3::deleteFile($exists['current_photo']);
    }

    // บันทึกประวัติ -> ให้แท็บ "ประวัติการยืนยันตัวตน" มีข้อมูล
    $stmt_log = $pdo_connect->prepare(
        "INSERT INTO tbl_identity_verification_log (user_id, create_user_id, action_type, remark)
         VALUES (:uid, :admin, :act, :rm)"
    );
    $stmt_log->execute([
        ':uid' => $target_id,
        ':admin' => (int) $admin_id,
        ':act' => $action_type,
        ':rm' => $log_remark !== '' ? $log_remark : null,
    ]);
    $stmt_log->closeCursor();

    $pdo_connect->commit();

    // =============== ส่ง Email แจ้งเตือน ===============
    $send_email = isset($_POST['send_email']) ? $_POST['send_email'] : '0';
    $user_email = $exists['user_email'] ?? '';
    
    // ส่งเมลเฉพาะเมื่อสถานะ identity_verified เปลี่ยนจริง (ไม่ส่งซ้ำถ้าแค่แก้ข้อมูลอื่น เช่น วันหมดอายุ)
    $status_changed = ((string)$identity_verified !== (string)($exists['identity_verified'] ?? ''));

    if ($send_email === '1' && !empty($user_email) && $status_changed) {
        $display_name = trim(($exists['user_firstname'] ?? '') . ' ' . ($exists['user_lastname'] ?? ''));
        if ($display_name === '') {
            $display_name = 'ผู้ใช้งาน';
        }
        $isApproved = ($result === '2');
        $login_url = 'https://bigsara-demo.com/am/cpdth/';
        
        ob_start();
        include __DIR__ . '/../../view/email/identity_verify_template.php';
        $email_body = ob_get_clean();

        try {
            $subject = $isApproved ? 'ผลการตรวจสอบเอกสารยืนยันตัวตน - อนุมัติสำเร็จ' : 'ผลการตรวจสอบเอกสารยืนยันตัวตน - ไม่ผ่านการอนุมัติ';
            Email::send($user_email, $subject, $email_body, true);
        } catch (\Throwable $eMail) {
            error_log('IdentityVerify mail error: ' . $eMail->getMessage());
        }
    }
    // ===============================================

} catch (\Throwable $e) {
    if ($pdo_connect->inTransaction()) {
        $pdo_connect->rollBack();
    }
    error_log('UpdateVerifyRequest Error: ' . $e->getMessage());
    Response::json(0, 'บันทึกผลการตรวจสอบไม่สำเร็จ', null);
}

$msg = ($result === '2') ? 'อนุมัติการยืนยันตัวตนเรียบร้อย' : 'ปฏิเสธการยืนยันตัวตนเรียบร้อย';
Response::json(1, $msg, null);
