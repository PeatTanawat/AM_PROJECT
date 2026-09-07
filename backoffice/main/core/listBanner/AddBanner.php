<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;
use App\Utility\ImageOptimizer;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

/* ---------- helpers ---------- */
$str = function (string $key): ?string {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? null : $v;
};

/* ---------- validate ---------- */
$banner_order  = isset($_POST['banner_order']) ? (int) $_POST['banner_order'] : 0;
$banner_url    = $str('banner_url');
$banner_status = isset($_POST['banner_status']) ? trim($_POST['banner_status']) : '1';
$banner_status = ($banner_status === '1') ? '1' : '0';

if ($banner_order <= 0) {
    Response::json(0, 'กรุณากรอกลำดับการแสดง (ต้องมากกว่า 0)', null);
}

// ลำดับห้ามซ้ำกับแบนเนอร์อื่น
$dup = $pdo_connect->prepare("SELECT banner_id FROM tbl_banner WHERE banner_order = :order AND delete_at IS NULL LIMIT 1");
$dup->execute([':order' => $banner_order]);
$exists = $dup->fetchColumn();
$dup->closeCursor();
if ($exists) {
    Response::json(0, 'ลำดับการแสดงนี้ถูกใช้งานแล้ว กรุณาเลือกลำดับอื่น', null);
}

/* ---------- อัปโหลดรูปแบนเนอร์ ---------- */
if (empty($_FILES['banner_image']['name']) || ($_FILES['banner_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(0, 'กรุณาอัปโหลดรูปแบนเนอร์', null);
}

$allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
$ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));

if (!isset($allowed[$ext])) {
    Response::json(0, 'รองรับเฉพาะไฟล์รูปภาพ (jpg, png, webp, gif)', null);
}
if ($_FILES['banner_image']['size'] > 5 * 1024 * 1024) {
    Response::json(0, 'ขนาดรูปต้องไม่เกิน 5 MB', null);
}

// อัปโหลดขึ้น S3 แล้วเก็บ URL เต็ม
$filename = bin2hex(random_bytes(8));
ImageOptimizer::toWebp('banner_image'); // แปลงรูปเป็น WebP ก่อนอัปขึ้น S3
$s3Result = AwsS3::uploadFileDirectly($_FILES['banner_image'], true, 'banner', $filename);
if (isset($s3Result['error'])) {
    Response::json(0, 'อัปโหลดรูปขึ้น S3 ไม่สำเร็จ: ' . $s3Result['error'], null);
}
$banner_image = $s3Result['url'];

/* ---------- insert ---------- */
try {
    $sql = "INSERT INTO tbl_banner (banner_order, banner_url, banner_image, banner_status)
            VALUES (:order, :url, :image, :status)";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute([
        ':order'  => $banner_order,
        ':url'    => $banner_url,
        ':image'  => $banner_image,
        ':status' => $banner_status,
    ]);
    $new_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มแบนเนอร์สำเร็จ', ['banner_id' => $new_id]);
} catch (Exception $e) {
    error_log('Add Banner Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
