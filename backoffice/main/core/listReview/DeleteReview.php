<?php
// ลบรีวิว — hard delete (tbl_reviews ไม่มีคอลัมน์ delete_at เหมือนตารางอื่น ๆ ในระบบ)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$review_id = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
if ($review_id <= 0) {
    Response::json(0, 'ไม่พบรหัสรีวิวที่ต้องการลบ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare("DELETE FROM tbl_reviews WHERE review_id = :id");
    $stmt->execute([':id' => $review_id]);
    $deleted = $stmt->rowCount();
    $stmt->closeCursor();

    if ($deleted <= 0) {
        Response::json(0, 'ไม่พบรีวิวนี้ หรือถูกลบไปแล้ว', null);
    }

    Response::json(1, 'ลบรีวิวสำเร็จ', null);
} catch (Exception $e) {
    error_log('Delete Review Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการลบข้อมูล', null);
} finally {
    $pdo_connect = null;
}
