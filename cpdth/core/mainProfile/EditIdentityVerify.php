<?php
// core/mainProfile/EditIdentityVerify.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;

try {
    // 1. ตรวจสอบสิทธิ์การเข้าสู่ระบบและเอา user_id
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    // 2. ดึงข้อมูลจาก $_POST และ $_FILES
    $citizen_id = isset($_POST['citizen_id']) ? trim($_POST['citizen_id']) : '';
    $expire_date = isset($_POST['expire_date']) ? trim($_POST['expire_date']) : '';

    if (empty($citizen_id)) {
        Response::json(0, 'กรุณาระบุเลขประจำตัวประชาชน', null);
    }
    if (empty($expire_date)) {
        Response::json(0, 'กรุณาระบุวันหมดอายุบัตรประชาชน', null);
    }

    $today = date('Y-m-d');
    if ($expire_date < $today) {
        Response::json(0, 'บัตรประจำตัวประชาชนหมดอายุแล้ว กรุณาระบุวันหมดอายุใหม่ที่ยังไม่หมดอายุ', null);
    }

    // 3. เชื่อมต่อฐานข้อมูล
    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // ดึงพาธรูปภาพเดิมก่อนอัปเดตเพื่อนำไปใช้งานหรือลบไฟล์ทิ้ง
    $stmt_old = $pdo_connect->prepare("SELECT id_card_image, current_photo FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1");
    $stmt_old->execute([':id' => $user_id]);
    $old_files = $stmt_old->fetch(PDO::FETCH_ASSOC);
    $stmt_old->closeCursor();

    $has_old_citizen = !empty($old_files['id_card_image']);
    $has_old_avatar  = !empty($old_files['current_photo']);

    $has_new_citizen = isset($_FILES['file_citizen']) && $_FILES['file_citizen']['error'] === UPLOAD_ERR_OK;
    $has_new_avatar  = isset($_FILES['file_avatar'])  && $_FILES['file_avatar']['error']  === UPLOAD_ERR_OK;

    if (!$has_new_citizen && !$has_old_citizen) {
        Response::json(0, 'กรุณาอัพโหลดภาพถ่ายบัตรประชาชน', null);
    }
    if (!$has_new_avatar && !$has_old_avatar) {
        Response::json(0, 'กรุณาอัพโหลดภาพถ่ายปัจจุบัน', null);
    }

    // 4. ตรวจสอบและอัปเดตโครงสร้างตารางอัตโนมัติ (หากยังไม่มีคอลัมน์)
    $stmt_columns = $pdo_connect->query("SHOW COLUMNS FROM tbl_user");
    $columns = $stmt_columns->fetchAll(PDO::FETCH_COLUMN);
    $stmt_columns->closeCursor();

    if (!in_array('identity_verified', $columns)) {
        $pdo_connect->exec("ALTER TABLE tbl_user ADD COLUMN identity_verified tinyint(1) DEFAULT 0");
    }
    if (!in_array('id_card_expiry_date', $columns)) {
        $pdo_connect->exec("ALTER TABLE tbl_user ADD COLUMN id_card_expiry_date varchar(50) DEFAULT NULL");
    }
    if (!in_array('id_card_image', $columns)) {
        $pdo_connect->exec("ALTER TABLE tbl_user ADD COLUMN id_card_image text DEFAULT NULL");
    }
    if (!in_array('current_photo', $columns)) {
        $pdo_connect->exec("ALTER TABLE tbl_user ADD COLUMN current_photo text DEFAULT NULL");
    }

    // 5. ประมวลผลและอัปโหลดไฟล์ไปยัง S3
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $base_dir = dirname(dirname(__DIR__)) . '/';

    $db_path_citizen = $old_files['id_card_image'] ?? '';
    if ($has_new_citizen) {
        $ext_citizen = pathinfo($_FILES['file_citizen']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext_citizen), $allowed_extensions)) {
            Response::json(0, 'ภาพถ่ายบัตรประชาชนต้องเป็นไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)', null);
        }
        $filename_citizen = 'citizen_' . $user_id . '_' . time();
        $citizen_upload = AwsS3::uploadFileDirectly($_FILES['file_citizen'], true, 'identity', $filename_citizen);
        if (isset($citizen_upload['error'])) {
            Response::json(0, 'ไม่สามารถบันทึกไฟล์ภาพถ่ายบัตรประชาชนได้: ' . $citizen_upload['error'], null);
        }
        // ลบไฟล์เดิมในระบบออกเมื่อมีการอัปโหลดไฟล์ใหม่
        if (!empty($old_files['id_card_image'])) {
            $old_citizen_path = $base_dir . $old_files['id_card_image'];
            if (file_exists($old_citizen_path)) {
                @unlink($old_citizen_path);
            } else {
                AwsS3::deleteFile($old_files['id_card_image']);
            }
        }
        $db_path_citizen = $citizen_upload['path'];
    }

    $db_path_avatar = $old_files['current_photo'] ?? '';
    if ($has_new_avatar) {
        $ext_avatar = pathinfo($_FILES['file_avatar']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext_avatar), $allowed_extensions)) {
            Response::json(0, 'ภาพถ่ายปัจจุบันต้องเป็นไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)', null);
        }
        $filename_avatar = 'avatar_' . $user_id . '_' . time();
        $avatar_upload = AwsS3::uploadFileDirectly($_FILES['file_avatar'], true, 'identity', $filename_avatar);
        if (isset($avatar_upload['error'])) {
            Response::json(0, 'ไม่สามารถบันทึกไฟล์ภาพถ่ายปัจจุบันได้: ' . $avatar_upload['error'], null);
        }
        // ลบไฟล์เดิมในระบบออกเมื่อมีการอัปโหลดไฟล์ใหม่
        if (!empty($old_files['current_photo'])) {
            $old_avatar_path = $base_dir . $old_files['current_photo'];
            if (file_exists($old_avatar_path)) {
                @unlink($old_avatar_path);
            } else {
                AwsS3::deleteFile($old_files['current_photo']);
            }
        }
        $db_path_avatar = $avatar_upload['path'];
    }

    // 6. ทำการบันทึกข้อมูลเข้าฐานข้อมูล
    $sql_update = "UPDATE tbl_user SET 
        user_citizen_id = :citizen_id,
        id_card_expiry_date = :expire_date,
        id_card_image = :citizen_file,
        current_photo = :avatar_file,
        identity_verified = 1,
        approver_citizen = 0
        WHERE user_id = :user_id AND delete_at IS NULL";
    
    $stmt_update = $pdo_connect->prepare($sql_update);
    $result = $stmt_update->execute([
        ':citizen_id'  => $citizen_id,
        ':expire_date' => $expire_date,
        ':citizen_file'=> $db_path_citizen,
        ':avatar_file' => $db_path_avatar,
        ':user_id'     => $user_id
    ]);
    $stmt_update->closeCursor();

    if ($result) {
        // ดึงชื่อ-นามสกุล ของ user_id นี้
        $stmt_name = $pdo_connect->prepare("SELECT user_firstname, user_lastname FROM tbl_user WHERE user_id = :id LIMIT 1");
        $stmt_name->execute([':id' => $user_id]);
        $u = $stmt_name->fetch(PDO::FETCH_ASSOC);
        $name = trim(($u['user_firstname'] ?? '') . ' ' . ($u['user_lastname'] ?? ''));
        if (empty($name)) $name = 'ลูกค้าไม่ทราบชื่อ';

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_dir = preg_replace('#/core(/[^/]+)*$#i', '', $script_dir);
        if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
            $base_dir = '';
        }
        $backoffice_base = str_replace('/cpdth', '/backoffice', $base_dir);
        if (strpos($backoffice_base, '/backoffice') === false) {
            $backoffice_base = rtrim($backoffice_base, '/') . '/backoffice';
        }
        $webhook_url = $protocol . '://' . $host . $backoffice_base . '/notify.php';
        
        $webhook_data = [
            'title'        => 'คำขอแก้ไขยืนยันตัวตน',
            'message'      => 'คุณ ' . $name . ' ได้ส่งคำขอแก้ไขข้อมูลยืนยันตัวตน',
            'type'         => 'user_verify',
            'link_url'     => 'verify_request.php',
            'reference_id' => $user_id
        ];

        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhook_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer my_secret_key_1234'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code !== 200) {
            error_log("Webhook notify warning: HTTP $http_code | $curl_error");
        }

        Response::json(1, 'ส่งข้อมูลยืนยันตัวตนสำเร็จ ระบบกำลังส่งให้เจ้าหน้าที่ตรวจสอบ', null);
    } else {
        // ลบไฟล์ที่อัพโหลดขึ้นไปแล้วหาก DB ทำการอัปเดตไม่สำเร็จ
        AwsS3::deleteFile($db_path_citizen);
        AwsS3::deleteFile($db_path_avatar);
        Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดของระบบ: ' . $e->getMessage(), null);
}
