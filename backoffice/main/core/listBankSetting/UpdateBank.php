<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

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

$id            = isset($_POST['id']) ? (int) $_POST['id'] : (int) ($_POST['bank_id'] ?? 0);
$bank_id       = isset($_POST['bank_id']) && isset($_POST['id']) ? (int) $_POST['bank_id'] : (int) ($_POST['bank_id'] ?? 0);
$account_name  = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
$account_no    = isset($_POST['account_no']) ? trim($_POST['account_no']) : '';
$active_status = isset($_POST['active_status']) ? trim($_POST['active_status']) : '1';

if ($id <= 0) {
    Response::json(0, 'ไม่พบรายการบัญชีธนาคารที่ต้องการแก้ไข', null);
}

if ($bank_id <= 0) {
    Response::json(0, 'กรุณาเลือกธนาคาร', null);
}

if ($account_name === '') {
    Response::json(0, 'กรุณากรอกชื่อ account', null);
}

if ($account_no === '') {
    Response::json(0, 'กรุณากรอกเลขที่บัญชี', null);
}

try {
    // ดึงข้อมูลเดิมใน tbl_company_banks
    $stmt_old = $pdo_connect->prepare("SELECT id FROM tbl_company_banks WHERE id = :id LIMIT 1");
    $stmt_old->execute([':id' => $id]);
    if (!$stmt_old->fetchColumn()) {
        $stmt_old->closeCursor();
        Response::json(0, 'ไม่พบข้อมูลบัญชีธนาคารที่ต้องการแก้ไข', null);
    }
    $stmt_old->closeCursor();

    $sql_update = "UPDATE tbl_company_banks SET
                    bank_id       = :bank_id,
                    account_name  = :account_name,
                    account_no    = :account_no,
                    active_status = :active_status
                   WHERE id       = :id";

    $stmt_update = $pdo_connect->prepare($sql_update);
    $update = $stmt_update->execute([
        ':bank_id'       => $bank_id,
        ':account_name'  => $account_name,
        ':account_no'    => $account_no,
        ':active_status' => $active_status,
        ':id'            => $id,
    ]);
    $stmt_update->closeCursor();

    if (!$update) {
        throw new Exception('เกิดข้อผิดพลาดในการปรับปรุงข้อมูล');
    }

    Response::json(1, 'แก้ไขข้อมูลบัญชีธนาคารสำเร็จ', null);

} catch (\Throwable $e) {
    error_log('Update Bank Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
