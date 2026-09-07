<?php
use App\Database\Connection;
use App\Utility\Response;


$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// รับค่าจาก Client
$prefix          = isset($_POST['prefix']) ? trim($_POST['prefix']) : '';
$user_firstname  = isset($_POST['user_firstname']) ? trim($_POST['user_firstname']) : '';
$user_lastname   = isset($_POST['user_lastname']) ? trim($_POST['user_lastname']) : '';
$user_citizen_id = isset($_POST['user_citizen_id']) ? trim($_POST['user_citizen_id']) : '';
$user_cpd_no     = !empty(trim($_POST['user_cpd_no'] ?? '')) ? trim($_POST['user_cpd_no']) : null;
$user_cpa_no     = !empty(trim($_POST['user_cpa_no'] ?? '')) ? trim($_POST['user_cpa_no']) : null;
$user_phone      = isset($_POST['user_phone']) ? trim($_POST['user_phone']) : '';
$user_email      = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
$user_password   = isset($_POST['user_password']) ? trim($_POST['user_password']) : '';

// 1. ตรวจสอบข้อมูลที่จำเป็นว่าถูกส่งมาครบถ้วนหรือไม่
if (empty($user_firstname) || empty($user_lastname) || empty($user_citizen_id) || empty($user_phone) || empty($user_email) || empty($user_password)) {
    Response::json(0, 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน', null);
}

// 2. ตรวจสอบรูปแบบอีเมล
if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    Response::json(0, 'รูปแบบอีเมลไม่ถูกต้อง', null);
}

// 3. แปลงค่าคำนำหน้าเพื่อบันทึกลงคอลัมน์ user_prefix (1=นาย, 2=นาง, 3=นางสาว)
$user_prefix = null;
if ($prefix === '1' || $prefix === 'นาย') {
    $user_prefix = 1;
} else if ($prefix === '2' || $prefix === 'นาง') {
    $user_prefix = 2;
} else if ($prefix === '3' || $prefix === 'นางสาว') {
    $user_prefix = 3;
}

// $prefix_label_map = [1 => 'นาย', 2 => 'นาง', 3 => 'นางสาว'];
// $prefix_text = $prefix_label_map[$user_prefix] ?? '';
// $full_firstname_display = trim($prefix_text . ' ' . $user_firstname);

try {
    // 4. ตรวจสอบว่า Email ซ้ำในระบบหรือไม่
    // อนุญาตให้สมัครใหม่ได้กรณีที่ผู้ใช้เดิมถูกลบแล้ว (delete_at IS NOT NULL) และมี user_status เป็น 0
    $sql_check_email = "SELECT user_id FROM tbl_user WHERE user_email = :email AND (delete_at IS NULL OR user_status != 0) LIMIT 1";
    $stmt_check = $pdo_connect->prepare($sql_check_email);
    $stmt_check->execute([':email' => $user_email]);
    if ($stmt_check->fetch()) {
        Response::json(0, 'อีเมลนี้ถูกใช้งานไปแล้ว', null);
    }

    // 5. ตรวจสอบว่า เลขบัตรประจำตัวประชาชน ซ้ำในระบบหรือไม่
    // อนุญาตให้สมัครใหม่ได้กรณีที่ผู้ใช้เดิมถูกลบ/ระงับแล้ว (delete_at IS NOT NULL และ user_status = 0)
    $sql_check_citizen = "SELECT user_id 
    FROM tbl_user 
    WHERE user_citizen_id = :citizen_id AND (delete_at IS NULL OR user_status = '1')";
    $stmt_check_citizen = $pdo_connect->prepare($sql_check_citizen);
    $stmt_check_citizen->execute([':citizen_id' => $user_citizen_id]);
    if ($stmt_check_citizen->fetch()) {
        Response::json(0, 'เลขบัตรประจำตัวประชาชนนี้มีอยู่ในระบบแล้ว', null);
    }

    // 6. เข้ารหัส Password
    $hashed_password = password_hash($user_password, PASSWORD_BCRYPT);

    // สร้าง Verification Token
    $verification_token = bin2hex(random_bytes(16));

    // 7. บันทึกข้อมูลใหม่ลงฐานข้อมูล (INSERT)
    $sql_insert = "INSERT INTO tbl_user SET 
        user_email = :user_email,
        user_password = :user_password,
        user_prefix = :user_prefix,
        user_firstname = :user_firstname,
        user_lastname = :user_lastname,
        user_phone = :user_phone,
        user_citizen_id = :user_citizen_id,
        user_cpa_no = :user_cpa_no,
        user_cpd_no = :user_cpd_no,
        user_status = 0,
        email_status = 0,
        verification_token = :verification_token"; 
        
    $stmt_insert = $pdo_connect->prepare($sql_insert);
    $insert_result = $stmt_insert->execute([
        ':user_email'          => $user_email,
        ':user_password'       => $hashed_password,
        ':user_prefix'         => $user_prefix,
        ':user_firstname'      => $user_firstname,
        ':user_lastname'       => $user_lastname,
        ':user_phone'          => $user_phone,
        ':user_citizen_id'     => $user_citizen_id,
        ':user_cpa_no'         => $user_cpa_no,
        ':user_cpd_no'         => $user_cpd_no,
        ':verification_token'  => $verification_token
    ]);

    if ($insert_result) {
        // ส่งอีเมลยืนยันตัวตน
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

        $subject = "ยืนยันการลงทะเบียนสมาชิก - CPDTH";
        $body = '
        <div style="font-family: \'Prompt\', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <div style="background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
                </div>
                <div style="padding: 30px 40px;">
                    <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">สวัสดี คุณ ' . htmlspecialchars($user_firstname . ' ' . $user_lastname) . '</h3>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                        ขอบคุณสำหรับการลงทะเบียนสมาชิกใหม่กับระบบของเรา เพื่อความปลอดภัยของบัญชีและเปิดใช้งานบัญชีของคุณ กรุณากดปุ่มยืนยันตัวตนด้านล่างนี้:
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

        // นำเข้า Utility\Email
        \App\Utility\Email::send($user_email, $subject, $body);

        Response::json(1, 'สมัครสมาชิกสำเร็จเรียบร้อยแล้ว กรุณาตรวจสอบอีเมลของคุณเพื่อยืนยันการใช้งาน', ['email' => $user_email]);
    } else {
        Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
    }

} catch (PDOException $e) {
    Response::json(0, 'ข้อผิดพลาดระบบฐานข้อมูล: ' . $e->getMessage(), null);
}
