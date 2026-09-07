<?php
use App\Database\Connection;
use App\Utility\Response;

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$user_email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($user_email)) {
    Response::json(0, 'กรุณาระบุอีเมล', null);
}

try {
    // 1. ตรวจสอบว่าผู้ใช้มีตัวตนในระบบและยังไม่ได้ยืนยันอีเมลหรือไม่
    $sql_check = "SELECT * FROM tbl_user WHERE user_email = :email AND delete_at IS NULL LIMIT 1";
    $stmt_check = $pdo_connect->prepare($sql_check);
    $stmt_check->execute([':email' => $user_email]);
    $row_user = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $stmt_check->closeCursor();

    if (!$row_user) {
        Response::json(0, 'ไม่พบอีเมลนี้ในระบบสมัครสมาชิก', null);
    }

    if ($row_user['email_status'] == 1) {
        Response::json(0, 'อีเมลนี้ได้รับการยืนยันตัวตนเรียบร้อยแล้ว', null);
    }

    // 2. สร้าง Token ใหม่และบันทึกข้อมูล
    $verification_token = bin2hex(random_bytes(16));
    
    $sql_update = "UPDATE tbl_user SET 
        verification_token = :token 
        WHERE user_id = :user_id";
    $stmt_update = $pdo_connect->prepare($sql_update);
    $update_result = $stmt_update->execute([
        ':token'   => $verification_token,
        ':user_id' => $row_user['user_id']
    ]);
    $stmt_update->closeCursor();

    if ($update_result) {
        // 3. ส่งอีเมลยืนยันตัวตนอีกครั้ง
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_dir = preg_replace('#/core(/[^/]+)*$#i', '', $script_dir);
        if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
            $base_dir = '';
        }
        $base_url = $protocol . "://" . $host . $base_dir;
        $verify_url = $base_url . "/verify.php?token=" . $verification_token;
        $logo_url = rtrim($base_url, '/') . '/assets/images/logo/G_AM_logo-01.jpg';

        $subject = "ยืนยันการลงทะเบียนสมาชิก (ส่งซ้ำ) - CPDTH";
        $body = '
        <div style="font-family: \'Prompt\', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <div style="background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
                </div>
                <div style="padding: 30px 40px;">
                    <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">สวัสดี คุณ ' . htmlspecialchars($row_user['user_firstname'] . ' ' . $row_user['user_lastname']) . '</h3>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                        เราได้รับคำขอให้ส่งอีเมลยืนยันการสมัครสมาชิกของคุณใหม่อีกครั้ง กรุณากดปุ่มด้านล่างเพื่อยืนยันการใช้งานบัญชี:
                    </p>
                    <div style="text-align: center; margin-bottom: 30px;">
                        <a href="' . $verify_url . '" style="background-color: #1e293b; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 500; font-size: 0.95rem; display: inline-block;">ยืนยันที่อยู่อีเมล</a>
                    </div>
                    <p style="color: #475569; font-size: 0.95rem; margin-bottom: 20px;">
                        ขอบคุณที่ใช้บริการ CPDTH
                    </p>
                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">
                    <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.5; margin: 0;">
                        หากไม่สามารถกดปุ่ม "ยืนยันที่อยู่อีเมล" ได้ คุณสามารถคัดลอกลิงก์ไปวางในเว็บเบราว์เซอร์ของคุณได้: <br>
                        <a href="' . $verify_url . '" style="color: #3b82f6; text-decoration: none;">' . $verify_url . '</a>
                    </p>
                </div>
            </div>
        </div>';

        if (\App\Utility\Email::send($user_email, $subject, $body)) {
            Response::json(1, 'ส่งอีเมลยืนยันตัวตนใหม่สำเร็จแล้ว กรุณาตรวจสอบกล่องจดหมายของคุณ', null);
        } else {
            Response::json(0, 'ระบบส่งเมลขัดข้อง กรุณาลองใหม่อีกครั้งในภายหลัง', null);
        }
    } else {
        Response::json(0, 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลระบบ', null);
    }
} catch (PDOException $e) {
    Response::json(0, 'ข้อผิดพลาดระบบฐานข้อมูล: ' . $e->getMessage(), null);
}
