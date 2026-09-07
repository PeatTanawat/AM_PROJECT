<?php

use App\Database\Connection;
use App\Utility\Response;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    Response::json(0, 'กรุณากรอกอีเมลของคุณ', null);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::json(0, 'รูปแบบอีเมลไม่ถูกต้อง', null);
}

try {
    // Self-healing check for columns in tbl_user
    $stmt_desc = $pdo_connect->query("DESCRIBE tbl_user");
    $columns = [];
    while ($row = $stmt_desc->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = strtolower($row['Field']);
    }
    $stmt_desc->closeCursor();

    if (!in_array('reset_token', $columns)) {
        $pdo_connect->exec("ALTER TABLE tbl_user ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL AFTER user_status");
    }
    if (!in_array('reset_token_expire', $columns)) {
        $pdo_connect->exec("ALTER TABLE tbl_user ADD COLUMN reset_token_expire DATETIME NULL DEFAULT NULL AFTER reset_token");
    }

    // Check if email exists
    $sql_check = "SELECT user_id, user_firstname, user_lastname FROM tbl_user WHERE user_email = :email AND delete_at IS NULL LIMIT 1";
    $stmt_check = $pdo_connect->prepare($sql_check);
    $stmt_check->execute([':email' => $email]);
    $user = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $stmt_check->closeCursor();

    if (!$user) {
        // Safe check
        Response::json(0, 'ไม่พบที่อยู่อีเมลนี้ในระบบ', null);
    }

    // Generate token and expiry
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

    // Save to user
    $sql_update = "UPDATE tbl_user SET reset_token = :token, reset_token_expire = :expiry WHERE user_id = :user_id";
    $stmt_update = $pdo_connect->prepare($sql_update);
    $stmt_update->execute([
        ':token' => $token,
        ':expiry' => $expiry,
        ':user_id' => $user['user_id']
    ]);
    $stmt_update->closeCursor();

    // Prepare link
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base_dir = preg_replace('#/core(/[^/]+)*$#i', '', $script_dir);
    if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
        $base_dir = '';
    }
    $base_url = $protocol . "://" . $host . $base_dir;
    $reset_url = $base_url . "/reset-password.php?token=" . $token;
    $logo_url = rtrim($base_url, '/') . '/assets/images/logo/G_AM_logo-01.jpg';

    $subject = "รีเซ็ตรหัสผ่านบัญชีผู้ใช้ - CPDTH";
    $body = '
    <div style="font-family: \'Prompt\', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <div style="background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
            </div>
            <div style="padding: 30px 40px;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">สวัสดี คุณ ' . htmlspecialchars($user['user_firstname'] . ' ' . $user['user_lastname']) . '</h3>
                <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                    เราได้รับคำขอในการรีเซ็ตรหัสผ่านสำหรับบัญชีผู้ใช้นี้ กรุณากดปุ่มด้านล่างเพื่อตั้งค่ารหัสผ่านใหม่ของคุณ (ลิงก์นี้จะมีอายุการใช้งาน 1 ชั่วโมง):
                </p>
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="' . $reset_url . '" style="background-color: #1e293b; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 500; font-size: 0.95rem; display: inline-block;">รีเซ็ตรหัสผ่านใหม่</a>
                </div>
                <p style="color: #475569; font-size: 0.95rem; margin-bottom: 20px;">
                    ขอบคุณที่ใช้บริการ CPDTH
                </p>
                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">
                <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.5; margin: 0;">
                    หากไม่สามารถกดปุ่ม "รีเซ็ตรหัสผ่านใหม่" ได้ คุณสามารถคัดลอกลิงก์ไปวางในเว็บเบราว์เซอร์ของคุณได้: <br>
                    <a href="' . $reset_url . '" style="color: #3b82f6; text-decoration: none;">' . $reset_url . '</a>
                </p>
            </div>
        </div>
    </div>';

    $mail_sent = \App\Utility\Email::send($email, $subject, $body);

    if ($mail_sent) {
        Response::json(1, 'ระบบได้ส่งลิงก์เพื่อรีเซ็ตรหัสผ่านไปยังอีเมลของคุณเรียบร้อยแล้ว กรุณาตรวจสอบอีเมลของคุณ', null);
    } else {
        Response::json(0, 'ไม่สามารถส่งอีเมลได้ในขณะนี้ กรุณาลองใหม่อีกครั้งภายหลัง', null);
    }

} catch (\Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการประมวลผลคำขอ: ' . $e->getMessage(), null);
}
