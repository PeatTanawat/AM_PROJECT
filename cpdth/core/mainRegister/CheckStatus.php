<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Response;
use Firebase\JWT\JWT;

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (! $pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$user_email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($user_email)) {
    Response::json(0, 'กรุณาระบุอีเมล', null);
}

try {
    $sql_check  = "SELECT * FROM tbl_user WHERE user_email = :email AND delete_at IS NULL LIMIT 1";
    $stmt_check = $pdo_connect->prepare($sql_check);
    $stmt_check->execute([':email' => $user_email]);
    $row_user = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $stmt_check->closeCursor();

    if ($row_user && $row_user['email_status'] == 1) {
        // ทำการเข้าสู่ระบบแบบอัตโนมัติ (Auto Login) เพื่อให้แท็บเดิมนี้เข้าสู่ระบบไปด้วย
        $now       = time();
        $createdAt = date('Y-m-d H:i:s', $now);
        $expiresAt = date('Y-m-d H:i:s', $now + 86400);
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;
        $agent     = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $jti       = bin2hex(random_bytes(16));

        // ปิด Token เก่าที่ยังไม่หมดอายุ
        $sql_edit = "UPDATE tbl_login_token SET
            end_datetime = :end_datetime
            WHERE user_id = :user_id AND end_datetime IS NULL";
        $stmt_edit = $pdo_connect->prepare($sql_edit);
        $stmt_edit->bindValue(":user_id", $row_user['user_id'], PDO::PARAM_STR);
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
        $stmt_insert->bindValue(":user_id", $row_user['user_id'], PDO::PARAM_STR);
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
            'exp' => $now + 86400,
        ];
        $secret = $_ENV['JWT_SECRET'] ?? '';

        if (! empty($secret)) {
            $jwt_token                = JWT::encode($payload, $secret, 'HS256');
            $_SESSION['access_token'] = $jwt_token;
            Response::json(1, 'ยืนยันตัวตนสำเร็จแล้ว', ['access_token' => $jwt_token]);
        } else {
            Response::json(0, 'ระบบล้มเหลวในการสร้างเซสชัน', null);
        }
    } else {
        Response::json(0, 'ยังไม่ได้ยืนยันตัวตน', null);
    }
} catch (PDOException $e) {
    Response::json(0, 'ข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage(), null);
}
