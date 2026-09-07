<?php
// แก้ไขข้อมูลจังหวัด (tbl_province)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$id           = (int) ($_POST['id'] ?? 0);
$name_th      = trim((string) ($_POST['name_th'] ?? ''));
$name_en      = trim((string) ($_POST['name_en'] ?? ''));

if ($id <= 0) {
    Response::json(0, 'ไม่พบรหัสจังหวัดที่ต้องการแก้ไข', null);
}
if ($name_th === '') {
    Response::json(0, 'กรุณาระบุชื่อจังหวัด (ภาษาไทย)', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // เช็คว่าชื่อซ้ำกับจังหวัดอื่นหรือไม่
    $stmt_check = $pdo_connect->prepare("SELECT id FROM tbl_province WHERE name_th = :name_th AND id != :id AND deleted_at IS NULL LIMIT 1");
    $stmt_check->bindValue(':name_th', $name_th);
    $stmt_check->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        $stmt_check->closeCursor();
        Response::json(0, 'มีชื่อจังหวัดนี้ในระบบแล้ว', null);
    }
    $stmt_check->closeCursor();

    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE tbl_province 
            SET name_th = :name_th, name_en = :name_en, updated_at = :updated_at 
            WHERE id = :id AND deleted_at IS NULL";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->bindValue(':name_th', $name_th);
    $stmt->bindValue(':name_en', $name_en !== '' ? $name_en : null);
    $stmt->bindValue(':updated_at', $now);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'แก้ไขจังหวัดสำเร็จ', null);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการแก้ไขจังหวัด: ' . $e->getMessage(), null);
}
