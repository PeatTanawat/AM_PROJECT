<?php
// core/mainProfile/SaveChangePhone.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

try {
    // 1. ตรวจสอบสิทธิ์การเข้าสู่ระบบและดึง user_id
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    // 2. ดึงข้อมูลจาก $_POST
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    if (empty($phone)) {
        Response::json(0, 'กรุณากรอกหมายเลขโทรศัพท์', null);
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        Response::json(0, 'กรุณากรอกหมายเลขโทรศัพท์ 10 หลักให้ถูกต้อง', null);
    }

    // 3. เชื่อมต่อฐานข้อมูล
    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 4. บันทึกข้อมูลเข้าฐานข้อมูล
    $sql_update = "UPDATE tbl_user SET 
        user_phone = :phone
        WHERE user_id = :user_id AND delete_at IS NULL";
    
    $stmt_update = $pdo_connect->prepare($sql_update);
    $result = $stmt_update->execute([
        ':phone'   => $phone,
        ':user_id' => $user_id
    ]);
    $stmt_update->closeCursor();

    if ($result) {
        Response::json(1, 'เปลี่ยนหมายเลขโทรศัพท์เรียบร้อยแล้ว', null);
    } else {
        Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดของระบบ: ' . $e->getMessage(), null);
}
