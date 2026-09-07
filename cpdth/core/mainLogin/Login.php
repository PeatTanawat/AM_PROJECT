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

$sql_login = "SELECT 
    u.*,
    e.enroll_id,
    e.enroll_date,
    e.enroll_expiry_date,
    c.course_name,
    DATEDIFF(DATE(e.enroll_expiry_date), CURRENT_DATE()) AS days_remaining
FROM tbl_user u
LEFT JOIN tbl_course_enrollment e 
       ON e.enroll_user_id = u.user_id 
      AND e.delete_at IS NULL 
      AND (e.enroll_access = '1' OR e.enroll_payment_status = 'paid')
      AND e.enroll_expiry_date IS NOT NULL
      AND e.send_email IS NULL
      AND DATEDIFF(DATE(e.enroll_expiry_date), CURRENT_DATE()) BETWEEN 0 AND 10
LEFT JOIN tbl_course c 
       ON e.enroll_course_id = c.course_id 
      AND c.delete_at IS NULL
WHERE u.user_email = :username 
  AND u.delete_at IS NULL
ORDER BY days_remaining ASC";

$stmt_login = $pdo_connect->prepare($sql_login);
$stmt_login->execute([':username' => $username]);
$login_rows = $stmt_login->fetchAll(PDO::FETCH_ASSOC);
$stmt_login->closeCursor();

$row_login = $login_rows[0] ?? null;
$prefetched_expiring = [];

if ($row_login && !empty($login_rows)) {
    foreach ($login_rows as $lr) {
        if (!empty($lr['enroll_id'])) {
            $prefetched_expiring[] = [
                'enroll_id'      => (int) $lr['enroll_id'],
                'course_name'    => $lr['course_name'],
                'enroll_date'    => !empty($lr['enroll_date']) ? date('d/m/Y', strtotime($lr['enroll_date'])) : '-',
                'expiry_date'    => !empty($lr['enroll_expiry_date']) ? date('d/m/Y', strtotime($lr['enroll_expiry_date'])) : '-',
                'days_remaining' => (int) $lr['days_remaining']
            ];
        }
    }
}

if ($row_login) {

    if (password_verify($password, $row_login['user_password'])) {

        if ($row_login['user_status'] == 1) {

            $now = time();

            $createdAt = date('Y-m-d H:i:s', $now);

            $expiresAt = date('Y-m-d H:i:s', $now + 25200);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $jti = bin2hex(random_bytes(16));

            $sql_edit = "UPDATE tbl_login_token SET

                    end_datetime = :end_datetime

                    WHERE user_id = :user_id AND end_datetime IS NULL;";

            $stmt_edit = $pdo_connect->prepare($sql_edit);

            $stmt_edit->bindValue(":user_id", $row_login['user_id'], PDO::PARAM_STR);

            $stmt_edit->bindValue(":end_datetime", $createdAt, PDO::PARAM_STR);

            $edit = $stmt_edit->execute();

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

                'exp' => $now + 25200,

            ];

            $secret = $_ENV['JWT_SECRET'] ?? '';

            if (empty($secret)) {

                Response::json(0, 'ไม่สามารถสร้างโทเค็นได้', null);

            }

            $jwt_token = JWT::encode($payload, $secret, 'HS256');

            $is_expired = false;
            if (!empty($row_login['id_card_expiry_date'])) {
                $today = date('Y-m-d');
                if ($row_login['id_card_expiry_date'] < $today) {
                    $is_expired = true;
                    \App\Utility\ExpiredIdCardNotifier::notifyUser($pdo_connect, $row_login);
                }
            }

            $expiring_courses = $prefetched_expiring;

            if (!empty($expiring_courses)) {
                require_once __DIR__ . '/CheckCourseExpiry.php';
                sendCourseExpiryNotification(
                    $pdo_connect,
                    $row_login['user_email'] ?? '',
                    $row_login['user_firstname'] ?? '',
                    $row_login['user_lastname'] ?? '',
                    $expiring_courses
                );
            }

            Response::json(1, 'เข้าสู่ระบบสำเร็จ', [
                'access_token' => $jwt_token,
                'id_card_expired' => $is_expired,
                'expiring_courses' => $expiring_courses
            ]);

        } else {
            if (isset($row_login['email_status']) && $row_login['email_status'] == 0) {
                Response::json(2, 'กรุณายืนยันอีเมลของคุณก่อนเข้าสู่ระบบ', ['email' => $row_login['user_email']]);
            } else {
                Response::json(0, 'บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ', null);
            }
        }

    } else {

        Response::json(0, 'ตรวจสอบ Username และ Password อีกครั้ง', null);

    }

} else {

    Response::json(0, 'ตรวจสอบ Username และ Password อีกครั้ง', null);

}
