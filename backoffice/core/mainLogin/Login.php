<?php

use App\Database\Connection;
use App\Utility\Response;
use Dotenv\Dotenv;
use Firebase\JWT\JWT;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));

$dotenv->load();

$db_instance = new Connection();

$pdo_connect = $db_instance->getPdo();

if (! $pdo_connect) {

    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);

}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';

$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {

    Response::json(0, 'กรุณากรอก Username และ Password', null);

}

$datetime = date('Y-m-d H:i:s');

$sql_login = "SELECT u.* FROM tbl_user u WHERE u.user_email = :username AND u.delete_at IS NULL";

$stmt_login = $pdo_connect->prepare($sql_login);

$stmt_login->execute([

    ':username' => $username,

]);

$row_login = $stmt_login->fetch(PDO::FETCH_ASSOC);

$stmt_login->closeCursor();

if ($row_login) {

    if (password_verify($password, $row_login['user_password'])) {

        // เฉพาะผู้ดูแลระบบ (admin_status=1) เท่านั้นที่เข้าใช้งานระบบหลังบ้านได้
        if ((string)($row_login['admin_status'] ?? '0') !== '1') {
            Response::json(0, 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบผู้ดูแล', null);
        }

        if ($row_login['user_status'] == 1) {

            $now = time();

            $createdAt = date('Y-m-d H:i:s', $now);

            $expiresAt = date('Y-m-d H:i:s', $now + 86400);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $jti = bin2hex(random_bytes(16));

            $sql_edit = "UPDATE tbl_login_token SET

                    end_datetime = :end_datetime

                    WHERE user_id = :user_id AND end_datetime IS NULL;";

            $stmt_edit = $pdo_connect->prepare($sql_edit);

            $stmt_edit->bindValue(":user_id", $row_login['user_id'], PDO::PARAM_STR);

            $stmt_edit->bindValue(":end_datetime", $createdAt, PDO::PARAM_STR);

            // ปิด single-session: ไม่ revoke session เดิม เพื่อกันการเด้งออกเวลา login ซ้ำ/เปิดหลายแท็บ
            // $edit = $stmt_edit->execute();

            $stmt_edit->closeCursor();

            $sql_insert = "INSERT INTO tbl_login_token SET

                    token_code = :token_code,

                    user_id = :user_id,

                    create_datetime = :create_datetime,

                    expire_datetime = :expire_datetime,

                    ip_address = :ip_address,

                    user_agent = :user_agent;";

            $stmt_insert = $pdo_connect->prepare($sql_insert);

            $stmt_insert->bindValue(":token_code", $jti, PDO::PARAM_STR);

            $stmt_insert->bindValue(":user_id", $row_login['user_id'], PDO::PARAM_STR);

            $stmt_insert->bindValue(":create_datetime", $createdAt, PDO::PARAM_STR);

            $stmt_insert->bindValue(":expire_datetime", $expiresAt, PDO::PARAM_STR);

            $stmt_insert->bindValue(":ip_address", $ip, PDO::PARAM_STR);

            $stmt_insert->bindValue(":user_agent", $agent, PDO::PARAM_STR);

            $insert = $stmt_insert->execute();

            $error_insert = $stmt_insert->errorInfo();

            $stmt_insert->closeCursor();

            if (! $insert) {

                Response::json(0, 'เกิดข้อผิดพลาดในการสร้างโทเค็น', null);

            }

            $payload = [

                'jti' => $jti,

                'iat' => $now,

                'exp' => $now + 86400,

            ];

            $secret = $_ENV['JWT_SECRET'] ?? '';

            if (empty($secret)) {

                Response::json(0, 'ไม่สามารถสร้างโทเค็นได้', null);

            }

            $jwt_token = JWT::encode($payload, $secret, 'HS256');

            setcookie('bo_access_token', $jwt_token, time() + 86400, '/');

            Response::json(1, 'เข้าสู่ระบบสำเร็จ', ['access_token' => $jwt_token]);

        } else {

            Response::json(0, 'บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ', null);

        }

    } else {

        Response::json(0, 'ตรวจสอบ Username และ Password อีกครั้ง', null);

    }

} else {

    Response::json(0, 'ตรวจสอบ Username และ Password อีกครั้ง', null);

}
