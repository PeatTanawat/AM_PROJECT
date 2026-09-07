<?php
// core/mainAddress/SetDefaultAddress.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

try {
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    $addr_id = isset($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;
    if ($addr_id <= 0) {
        Response::json(0, 'ไม่พบรหัสที่อยู่', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 1. ตรวจสอบว่าที่อยู่นี้มีจริงและเป็นของผู้ใช้รายนี้
    $stmt_check = $pdo_connect->prepare("SELECT addr_id FROM tbl_user_address WHERE addr_id = :addr_id AND addr_user_id = :user_id AND delete_at IS NULL LIMIT 1");
    $stmt_check->execute([
        ':addr_id' => $addr_id,
        ':user_id' => $user_id
    ]);
    $address = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $stmt_check->closeCursor();

    if (!$address) {
        Response::json(0, 'ไม่พบที่อยู่ดังกล่าว', null);
    }

    // 2. เคลียร์ที่อยู่เริ่มต้นเดิมทั้งหมดของผู้ใช้รายนี้
    $stmt_clear = $pdo_connect->prepare("UPDATE tbl_user_address SET addr_is_default = 0 WHERE addr_user_id = :user_id AND delete_at IS NULL");
    $stmt_clear->execute([':user_id' => $user_id]);
    $stmt_clear->closeCursor();

    // 3. ตั้งค่าที่อยู่นี้ให้เป็นค่าเริ่มต้น
    $stmt_set = $pdo_connect->prepare("UPDATE tbl_user_address SET addr_is_default = 1 WHERE addr_id = :addr_id AND addr_user_id = :user_id");
    $result = $stmt_set->execute([
        ':addr_id' => $addr_id,
        ':user_id' => $user_id
    ]);
    $stmt_set->closeCursor();

    if ($result) {
        Response::json(1, 'ตั้งค่าที่อยู่เริ่มต้นเรียบร้อยแล้ว', null);
    } else {
        Response::json(0, 'ไม่สามารถตั้งค่าที่อยู่เริ่มต้นได้', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
