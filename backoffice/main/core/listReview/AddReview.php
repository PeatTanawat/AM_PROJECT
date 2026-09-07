<?php
// เพิ่มรีวิวจากฝั่งแอดมิน -> INSERT tbl_reviews
// ผู้รีวิวเลือกได้ 2 แบบ:
//   reviewer_type = 'user'   -> ผูกกับลูกค้าที่มีอยู่ (user_id), reviewer_name = NULL
//   reviewer_type = 'custom' -> พิมพ์ชื่อเอง (reviewer_name), user_id = NULL
// วันที่รีวิว: ปล่อยว่าง = ตอนนี้ / กรอกเป็น d/m/Y ได้ (แอดมินเลือกย้อนหลังได้)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;
use App\Utility\ImageOptimizer;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$reviewer_type = isset($_POST['reviewer_type']) ? trim((string) $_POST['reviewer_type']) : 'user';
$user_id       = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$reviewer_name = isset($_POST['reviewer_name']) ? trim((string) $_POST['reviewer_name']) : '';
$rating        = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$comment       = isset($_POST['comment']) ? trim((string) $_POST['comment']) : '';
$is_approved   = isset($_POST['is_approved']) && (string) $_POST['is_approved'] === '0' ? '0' : '1';
$review_date   = isset($_POST['review_date']) ? trim((string) $_POST['review_date']) : '';

if ($reviewer_type !== 'user' && $reviewer_type !== 'custom') {
    Response::json(0, 'ประเภทผู้รีวิวไม่ถูกต้อง', null);
}
if ($rating < 1 || $rating > 5) {
    Response::json(0, 'กรุณาระบุคะแนนระหว่าง 1-5', null);
}
if ($comment === '') {
    Response::json(0, 'กรุณากรอกข้อความรีวิว', null);
}
if ($reviewer_type === 'user' && $user_id <= 0) {
    Response::json(0, 'กรุณาเลือกลูกค้า', null);
}
if ($reviewer_type === 'custom' && $reviewer_name === '') {
    Response::json(0, 'กรุณากรอกชื่อผู้รีวิว', null);
}

// วันที่รีวิว: ว่าง = ตอนนี้ / มีค่า d/m/Y -> Y-m-d (คงเวลาปัจจุบันไว้เพื่อให้ลำดับในวันเดียวกันสมเหตุผล)
$created_at = date('Y-m-d H:i:s');
if ($review_date !== '') {
    $dt = DateTime::createFromFormat('d/m/Y', $review_date);
    $errs = DateTime::getLastErrors();
    if ($dt === false || ($errs && (($errs['warning_count'] ?? 0) > 0 || ($errs['error_count'] ?? 0) > 0))) {
        Response::json(0, 'รูปแบบวันที่รีวิวไม่ถูกต้อง (วว/ดด/ปปปป)', null);
    }
    $created_at = $dt->format('Y-m-d') . ' ' . date('H:i:s');
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// เลือกลูกค้าที่มีอยู่ -> ตรวจว่ามีสมาชิกจริง (ยังไม่ถูกลบ) แล้วไม่เก็บ reviewer_name
if ($reviewer_type === 'user') {
    $cu = $pdo_connect->prepare("SELECT user_id, user_firstname, user_lastname FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1");
    $cu->execute([':id' => $user_id]);
    $u_row = $cu->fetch(PDO::FETCH_ASSOC);
    if (!$u_row) {
        $cu->closeCursor();
        Response::json(0, 'ไม่พบสมาชิกนี้', null);
    }
    $cu->closeCursor();
    $insert_user_id = $user_id;
    $insert_name    = trim(($u_row['user_firstname'] ?? '') . ' ' . ($u_row['user_lastname'] ?? ''));

} else {
    // พิมพ์ชื่อเอง -> ไม่ผูกกับสมาชิก
    $insert_user_id = null;
    $insert_name    = $reviewer_name;
}

/* ---------- อัปโหลดรูปผู้รีวิวขึ้น Amazon S3 (ถ้ามี) ---------- */
$reviewer_image = null;
if (!empty($_FILES['reviewer_image']['name']) && ($_FILES['reviewer_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext = strtolower(pathinfo($_FILES['reviewer_image']['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        Response::json(0, 'รองรับเฉพาะไฟล์รูปภาพ (jpg, png, webp, gif)', null);
    }
    if ($_FILES['reviewer_image']['size'] > 5 * 1024 * 1024) {
        Response::json(0, 'ขนาดรูปต้องไม่เกิน 5 MB', null);
    }

    // อัปโหลดขึ้น S3 โดยระบุโฟลเดอร์ปลายทางคือ 'reviews/reviewer' และสุ่มชื่อไฟล์ใหม่
    $filename = bin2hex(random_bytes(8));
    ImageOptimizer::toWebp('reviewer_image'); // แปลงรูปเป็น WebP ก่อนอัปขึ้น S3
    $s3Result = AwsS3::uploadFileDirectly($_FILES['reviewer_image'], true, 'reviews/reviewer', $filename);

    if (isset($s3Result['error'])) {
        Response::json(0, 'อัปโหลดรูปขึ้น S3 ไม่สำเร็จ: ' . $s3Result['error'], null);
    }

    // เก็บ URL สาธารณะของ S3 ลงฐานข้อมูล
    $reviewer_image = $s3Result['url'];
}

try {
    $stmt = $pdo_connect->prepare(
        "INSERT INTO tbl_reviews (user_id, reviewer_name, reviewer_image, rating, comment, is_approved, created_at)
                          VALUES (:user_id, :reviewer_name, :reviewer_image, :rating, :comment, :is_approved, :created_at)"
    );
    $stmt->bindValue(':user_id', $insert_user_id, $insert_user_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':reviewer_name', $insert_name, $insert_name === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':reviewer_image', $reviewer_image, $reviewer_image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':rating', (string) $rating, PDO::PARAM_STR);
    $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
    $stmt->bindValue(':is_approved', $is_approved, PDO::PARAM_STR);
    $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
    $stmt->execute();
    $new_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มรีวิวสำเร็จ', ['review_id' => $new_id]);
} catch (\Throwable $e) {
    error_log('AddReview Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
