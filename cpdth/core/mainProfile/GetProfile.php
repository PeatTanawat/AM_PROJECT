<?php

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\AwsS3;
use App\Utility\Response;

try {
    $access_token = Auth::requireUserToken();
    $admin_id     = $access_token->user_id ?? null;

    if (! $admin_id) {
        Response::json(0, 'Unauthorized', null);
    }

    // หากส่ง user_id มาให้ใช้ค่าที่ส่งมา (เช่นแอดมินดู) หากไม่ได้ส่งมาให้ใช้ ID ของตัวเองที่เข้าสู่ระบบอยู่
    $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) $admin_id;
    if ($target_id <= 0) {
        Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (! $pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // ข้อมูลผู้ใช้ (เฉพาะที่ยังไม่ถูกลบ)
    $stmt = $pdo_connect->prepare(
        "SELECT user_id,
                user_prefix,
                user_firstname,
                user_lastname,
                user_email,
                user_phone,
                user_citizen_id,
                user_cpd_no,
                user_cpa_no,
                user_status,
                email_status ,
                identity_verified,
                approver_citizen,
                remark,
                id_card_expiry_date,
                id_card_image,
                current_photo,
                line_token
         FROM tbl_user
         WHERE user_id = :id AND delete_at IS NULL
         LIMIT 1"
    );
    $stmt->execute([':id' => $target_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (! $user) {
        Response::json(0, 'ไม่พบผู้ใช้นี้', null);
    }

    if (!empty($user['id_card_image'])) {
        $user['id_card_image'] = AwsS3::getFileUrl($user['id_card_image'], '+30 minutes', false);
    }

    if (!empty($user['current_photo'])) {
        $user['current_photo'] = AwsS3::getFileUrl($user['current_photo'], '+30 minutes', false);
    }

    // ส่งกลับข้อมูลผู้ใช้ทั้งหมดแบบปลอดภัย (แปลงรูปภาพส่วนตัวเป็น Base64 แล้ว)
    Response::json(1, 'Success', [
        'user' => $user,
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
