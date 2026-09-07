<?php
// core/mainAddress/DeleteAddress.php

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

    // 1. ตรวจสอบว่าที่อยู่ที่จะลบนั้นมีอยู่จริง เป็นของยูสเซอร์จริง และเช็คว่าเป็นค่าเริ่มต้นหรือไม่
    $stmt_check = $pdo_connect->prepare("SELECT addr_is_default FROM tbl_user_address WHERE addr_id = :addr_id AND addr_user_id = :user_id AND delete_at IS NULL LIMIT 1");
    $stmt_check->execute([
        ':addr_id' => $addr_id,
        ':user_id' => $user_id
    ]);
    $address = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $stmt_check->closeCursor();

    if (!$address) {
        Response::json(0, 'ไม่พบที่อยู่ดังกล่าว หรือถูกลบไปแล้ว', null);
    }

    // 2. ลบที่อยู่ (Soft Delete)
    $stmt_delete = $pdo_connect->prepare("UPDATE tbl_user_address SET delete_at = NOW(), addr_is_default = 0 WHERE addr_id = :addr_id AND addr_user_id = :user_id");
    $result = $stmt_delete->execute([
        ':addr_id' => $addr_id,
        ':user_id' => $user_id
    ]);
    $stmt_delete->closeCursor();

    if ($result) {
        // 3. ถ้าที่อยู่ที่ลบเป็นที่อยู่เริ่มต้น ให้สุ่มหรือเลือกที่อยู่อื่นที่เหลืออยู่เป็นค่าเริ่มต้นแทน
        if ((int)$address['addr_is_default'] === 1) {
            $stmt_next = $pdo_connect->prepare("SELECT addr_id FROM tbl_user_address WHERE addr_user_id = :user_id AND delete_at IS NULL ORDER BY addr_id DESC LIMIT 1");
            $stmt_next->execute([':user_id' => $user_id]);
            $next_address = $stmt_next->fetch(PDO::FETCH_ASSOC);
            $stmt_next->closeCursor();

            if ($next_address) {
                $stmt_set = $pdo_connect->prepare("UPDATE tbl_user_address SET addr_is_default = 1 WHERE addr_id = :addr_id");
                $stmt_set->execute([':addr_id' => $next_address['addr_id']]);
                $stmt_set->closeCursor();
            }
        }

        Response::json(1, 'ลบที่อยู่ออกใบกำกับภาษีเรียบร้อยแล้ว', null);
    } else {
        Response::json(0, 'ไม่สามารถลบที่อยู่ได้', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
