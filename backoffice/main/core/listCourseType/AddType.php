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

$type_name = isset($_POST['type_name']) ? trim($_POST['type_name']) : '';

if ($type_name === '') {
    Response::json(0, 'กรุณากรอกชื่อประเภท', null);
}

try {
    $sql_insert = "INSERT INTO tbl_course_type SET type_name = :type_name";
    $stmt_insert = $pdo_connect->prepare($sql_insert);
    $insert = $stmt_insert->execute([':type_name' => $type_name]);
    $stmt_insert->closeCursor();

    if (!$insert) {
        throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
    }

    Response::json(1, 'บันทึกข้อมูลสำเร็จ', null);
} catch (Exception $e) {
    error_log('Add Type Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
