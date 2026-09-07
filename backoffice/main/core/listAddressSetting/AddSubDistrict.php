<?php
// เพิ่มตำบลใหม่ (tbl_sub_district)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$amphure_id = (int) ($_POST['amphure_id'] ?? 0);
$name_th    = trim((string) ($_POST['name_th'] ?? ''));
$name_en    = trim((string) ($_POST['name_en'] ?? ''));
$zip_code   = trim((string) ($_POST['zip_code'] ?? ''));

if ($amphure_id <= 0) {
    Response::json(0, 'กรุณาระบุอำเภอที่สังกัด', null);
}
if ($name_th === '') {
    Response::json(0, 'กรุณาระบุชื่อตำบล/แขวง (ภาษาไทย)', null);
}
if ($zip_code === '') {
    Response::json(0, 'กรุณาระบุรหัสไปรษณีย์', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // เช็คชื่อซ้ำในอำเภอเดียวกัน
    $stmt_check = $pdo_connect->prepare("SELECT id FROM tbl_sub_district WHERE amphure_id = :amphure_id AND name_th = :name_th AND deleted_at IS NULL LIMIT 1");
    $stmt_check->bindValue(':amphure_id', $amphure_id, PDO::PARAM_INT);
    $stmt_check->bindValue(':name_th', $name_th);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        $stmt_check->closeCursor();
        Response::json(0, 'มีชื่อตำบลนี้ในอำเภอดังกล่าวแล้ว', null);
    }
    $stmt_check->closeCursor();

    // คำนวณรหัส ID ถัดไป
    $stmt_max = $pdo_connect->query("SELECT IFNULL(MAX(id), 0) + 1 FROM tbl_sub_district");
    $new_id = (int) $stmt_max->fetchColumn();

    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO tbl_sub_district (id, name_th, name_en, zip_code, amphure_id, created_at, updated_at) 
            VALUES (:id, :name_th, :name_en, :zip_code, :amphure_id, :created_at, :updated_at)";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->bindValue(':id', $new_id, PDO::PARAM_INT);
    $stmt->bindValue(':name_th', $name_th);
    $stmt->bindValue(':name_en', $name_en !== '' ? $name_en : null);
    $stmt->bindValue(':zip_code', $zip_code);
    $stmt->bindValue(':amphure_id', $amphure_id, PDO::PARAM_INT);
    $stmt->bindValue(':created_at', $now);
    $stmt->bindValue(':updated_at', $now);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มตำบลสำเร็จ', null);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการเพิ่มตำบล: ' . $e->getMessage(), null);
}
