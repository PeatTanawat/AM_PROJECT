<?php
// เพิ่มจังหวัดใหม่ (tbl_province)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$name_th      = trim((string) ($_POST['name_th'] ?? ''));
$name_en      = trim((string) ($_POST['name_en'] ?? ''));

if ($name_th === '') {
    Response::json(0, 'กรุณาระบุชื่อจังหวัด (ภาษาไทย)', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // เช็คว่ามีชื่อจังหวัดนี้อยู่แล้วหรือไม่
    $stmt_check = $pdo_connect->prepare("SELECT id FROM tbl_province WHERE name_th = :name_th AND deleted_at IS NULL LIMIT 1");
    $stmt_check->bindValue(':name_th', $name_th);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        $stmt_check->closeCursor();
        Response::json(0, 'มีชื่อจังหวัดนี้ในระบบแล้ว', null);
    }
    $stmt_check->closeCursor();

    // คำนวณรหัส ID ถัดไป
    $stmt_max = $pdo_connect->query("SELECT IFNULL(MAX(id), 0) + 1 FROM tbl_province");
    $new_id = (int) $stmt_max->fetchColumn();

    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO tbl_province (id, name_th, name_en, created_at, updated_at) 
            VALUES (:id, :name_th, :name_en, :created_at, :updated_at)";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->bindValue(':id', $new_id, PDO::PARAM_INT);
    $stmt->bindValue(':name_th', $name_th);
    $stmt->bindValue(':name_en', $name_en !== '' ? $name_en : null);
    $stmt->bindValue(':created_at', $now);
    $stmt->bindValue(':updated_at', $now);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มจังหวัดสำเร็จ', null);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการเพิ่มจังหวัด: ' . $e->getMessage(), null);
}
