<?php

use App\Database\Connection;
use App\Utility\Response;
use Firebase\JWT\JWT;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$userId = isset($_POST['userId']) ? trim($_POST['userId']) : '';

if (empty($userId)) {
    Response::json(0, 'ไม่พบ LINE User ID', null);
}

// ตรวจสอบจาก tbl_user ด้วย line_token
$sql_check = "SELECT * FROM tbl_user 
              WHERE line_token = :userId 
              AND user_status = 1 
              AND delete_at IS NULL 
              LIMIT 1";

$stmt = $pdo_connect->prepare($sql_check);
$stmt->execute([':userId' => $userId]);
$row_login = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if ($row_login) {
    // พบ line_token ใน tbl_user -> ทำการสร้าง JWT Token สำหรับ Login สำเร็จ
    $now = time();
    $createdAt = date('Y-m-d H:i:s', $now);
    $expiresAt = date('Y-m-d H:i:s', $now + 25200);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $jti = bin2hex(random_bytes(16));

    // ปิด Token เก่าที่ยังไม่หมดอายุ
    $sql_edit = "UPDATE tbl_login_token SET
                 end_datetime = :end_datetime
                 WHERE user_id = :user_id AND end_datetime IS NULL";
    $stmt_edit = $pdo_connect->prepare($sql_edit);
    $stmt_edit->bindValue(":user_id", $row_login['user_id'], PDO::PARAM_STR);
    $stmt_edit->bindValue(":end_datetime", $createdAt, PDO::PARAM_STR);
    $stmt_edit->execute();
    $stmt_edit->closeCursor();

    // บันทึก Token ใหม่
    $sql_insert = "INSERT INTO tbl_login_token SET
                   token_code = :token_code,
                   user_id = :user_id,
                   create_datetime = :create_datetime,
                   expire_datetime = :expire_datetime,
                   ip_address = :ip_address,
                   user_agent = :user_agent";
    $stmt_insert = $pdo_connect->prepare($sql_insert);
    $stmt_insert->bindValue(":token_code", $jti, PDO::PARAM_STR);
    $stmt_insert->bindValue(":user_id", $row_login['user_id'], PDO::PARAM_STR);
    $stmt_insert->bindValue(":create_datetime", $createdAt, PDO::PARAM_STR);
    $stmt_insert->bindValue(":expire_datetime", $expiresAt, PDO::PARAM_STR);
    $stmt_insert->bindValue(":ip_address", $ip, PDO::PARAM_STR);
    $stmt_insert->bindValue(":user_agent", $agent, PDO::PARAM_STR);
    $stmt_insert->execute();
    $stmt_insert->closeCursor();

    // สร้าง JWT
    $payload = [
        'jti' => $jti,
        'iat' => $now,
        'exp' => $now + 25200,
    ];

    $secret = $_ENV['JWT_SECRET'] ?? '';
    if (empty($secret)) {
        Response::json(0, 'ไม่สามารถสร้างโทเค็นได้', null);
    }

    $jwt_token = JWT::encode($payload, $secret, 'HS256');

    Response::json(1, 'เข้าสู่ระบบสำเร็จ', [
        'access_token' => $jwt_token,
        'user_firstname' => $row_login['user_firstname']
    ]);
} else {
    // ส่งกลับรหัส 2 เพื่อระบุว่าไม่พบบัญชีที่เชื่อมโยงกับ line_token นี้
    Response::json(2, 'ไม่พบรหัส LINE ของคุณในระบบ กรุณาเข้าสู่ระบบด้วยอีเมลแล้วทำการเชื่อมต่อ LINE ในเมนูโปรไฟล์ของคุณ', [
        'line_token' => $userId
    ]);
}
