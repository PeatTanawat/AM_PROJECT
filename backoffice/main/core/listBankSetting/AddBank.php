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

$bank_id       = isset($_POST['bank_id']) ? (int) $_POST['bank_id'] : 0;
$account_name  = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
$account_no    = isset($_POST['account_no']) ? trim($_POST['account_no']) : '';
$active_status = isset($_POST['active_status']) ? trim($_POST['active_status']) : '1';

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
    // ตรวจสอบว่า bank_id มีอยู่จริงใน tbl_bank_mapping
    $stmt_check = $pdo_connect->prepare("SELECT bank_id FROM tbl_bank_mapping WHERE bank_id = :bank_id LIMIT 1");
    $stmt_check->execute([':bank_id' => $bank_id]);
    if (!$stmt_check->fetchColumn()) {
        $stmt_check->closeCursor();
        Response::json(0, 'ไม่พบธนาคารที่ระบุในระบบ', null);
    }
    $stmt_check->closeCursor();

    $sql_insert = "INSERT INTO tbl_company_banks 
                    (bank_id, account_name, account_no, active_status)
                   VALUES 
                    (:bank_id, :account_name, :account_no, :active_status)";
    
    $stmt_insert = $pdo_connect->prepare($sql_insert);
    $insert = $stmt_insert->execute([
        ':bank_id'       => $bank_id,
        ':account_name'  => $account_name,
        ':account_no'    => $account_no,
        ':active_status' => $active_status,
    ]);
    $stmt_insert->closeCursor();

    if (!$insert) {
        throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูลบัญชีธนาคาร');
    }

    Response::json(1, 'เพิ่มข้อมูลบัญชีธนาคารสำเร็จ', null);

} catch (\Throwable $e) {
    error_log('Add Bank Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
