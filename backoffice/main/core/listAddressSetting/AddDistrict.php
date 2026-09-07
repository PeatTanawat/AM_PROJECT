<?php
// เพิ่มอำเภอใหม่ (tbl_district)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$province_id = (int) ($_POST['province_id'] ?? 0);
$name_th     = trim((string) ($_POST['name_th'] ?? ''));
$name_en     = trim((string) ($_POST['name_en'] ?? ''));

if ($province_id <= 0) {
    Response::json(0, 'กรุณาระบุจังหวัดที่สังกัด', null);
}
if ($name_th === '') {
    Response::json(0, 'กรุณาระบุชื่ออำเภอ/เขต (ภาษาไทย)', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // เช็คชื่อซ้ำในจังหวัดเดียวกัน
    $stmt_check = $pdo_connect->prepare("SELECT id FROM tbl_district WHERE province_id = :province_id AND name_th = :name_th AND deleted_at IS NULL LIMIT 1");
    $stmt_check->bindValue(':province_id', $province_id, PDO::PARAM_INT);
    $stmt_check->bindValue(':name_th', $name_th);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        $stmt_check->closeCursor();
        Response::json(0, 'มีชื่ออำเภอนี้ในจังหวัดดังกล่าวแล้ว', null);
    }
    $stmt_check->closeCursor();

    // คำนวณรหัส ID ถัดไป
    $stmt_max = $pdo_connect->query("SELECT IFNULL(MAX(id), 0) + 1 FROM tbl_district");
    $new_id = (int) $stmt_max->fetchColumn();

    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO tbl_district (id, name_th, name_en, province_id, created_at, updated_at) 
            VALUES (:id, :name_th, :name_en, :province_id, :created_at, :updated_at)";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->bindValue(':id', $new_id, PDO::PARAM_INT);
    $stmt->bindValue(':name_th', $name_th);
    $stmt->bindValue(':name_en', $name_en !== '' ? $name_en : null);
    $stmt->bindValue(':province_id', $province_id, PDO::PARAM_INT);
    $stmt->bindValue(':created_at', $now);
    $stmt->bindValue(':updated_at', $now);
    $stmt->execute();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มอำเภอสำเร็จ', null);
} catch (\Throwable $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการเพิ่มอำเภอ: ' . $e->getMessage(), null);
}
