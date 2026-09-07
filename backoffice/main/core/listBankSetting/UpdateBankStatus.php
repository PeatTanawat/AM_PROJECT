<?php
// สลับสถานะเปิด/ปิดใช้งานบัญชีธนาคารบริษัท (active_status: 1 = เปิดใช้งาน, 0 = ปิดใช้งาน)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$id            = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$active_status = isset($_POST['active_status']) && (string) $_POST['active_status'] === '1' ? '1' : '0';

if ($id <= 0) {
    Response::json(0, 'ไม่พบรายการที่ต้องการสลับสถานะ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare(
        "UPDATE tbl_company_banks SET active_status = :status WHERE id = :id"
    );
    $stmt->bindValue(':status', $active_status, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $affected = $stmt->rowCount();
    $stmt->closeCursor();

    if ($affected === 0) {
        // เช็คว่ามี id นี้หรือไม่
        $check_stmt = $pdo_connect->prepare("SELECT active_status FROM tbl_company_banks WHERE id = :id LIMIT 1");
        $check_stmt->execute([':id' => $id]);
        $curr_status = $check_stmt->fetchColumn();
        $check_stmt->closeCursor();
        if ($curr_status === false) {
            Response::json(0, 'ไม่พบรายการบัญชีธนาคารนี้ในระบบ', null);
        }
    }

    Response::json(1, $active_status === '1' ? 'เปิดใช้งานบัญชีธนาคารแล้ว' : 'ปิดใช้งานบัญชีธนาคารแล้ว', [
        'id'            => $id,
        'active_status' => $active_status,
    ]);
} catch (\Throwable $e) {
    error_log('Update Bank Status Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการปรับเปลี่ยนสถานะ: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
