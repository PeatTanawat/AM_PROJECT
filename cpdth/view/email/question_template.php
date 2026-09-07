<?php
/**
 * Email Template: แบบสอบถามหลังการอบรม
 * 
 * ตัวแปรที่ใช้:
 * - $questionLink : ลิงก์ทำแบบสอบถาม (ดึงจาก tbl_website_setting.question_link)
 * - $fullName     : ชื่อผู้ใช้งาน (เผื่อใช้งานเพิ่มเติม)
 */

if (empty($logo_url)) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (!empty($host)) {
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_dir = preg_replace('#/(core|scripts|src|main|backoffice)(/[^/]+)*$#i', '', $script_dir);
        if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
            $base_dir = '';
        }
        $base_url = $protocol . '://' . $host . $base_dir;
        if (strpos($base_url, '/cpdth') === false && strpos($host, 'localhost') !== false) {
            $base_url = rtrim($base_url, '/') . '/cpdth';
        }
    } else {
        $base_url = 'https://bigsara-demo.com/am/cpdth';
    }

    $logo_url = rtrim($base_url, '/') . '/assets/images/logo/G_AM_logo-01.jpg';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แบบสอบถามหลังการอบรม - CPDTH</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Prompt', sans-serif;">

    <div style="font-family: 'Prompt', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">

            <!-- ================= Header / ส่วนหัวอีเมล ================= -->
            <div style="background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
            </div>

            <!-- ================= Content / เนื้อหาหลัก ================= -->
            <div style="padding: 30px 40px;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">
                    สวัสดี <?php echo !empty($fullName) ? 'คุณ ' . htmlspecialchars($fullName) : 'ท่านผู้ใช้บริการอบรมกับ CPDTH'; ?>
                </h3>

                <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                    เราขอขอบคุณที่เลือกอบรมกับ CPDTH เราขอให้ท่านช่วยทำแบบสอบถามเพื่อพัฒนาหลักสูตร และการบริการให้ดีขึ้นไป โดยท่านสามารถทำแบบสอบถามโดยการคลิกที่ปุ่มด้านล่าง
                </p>

                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="<?php echo htmlspecialchars($questionLink); ?>" target="_blank" style="background-color: #1e293b; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 500; font-size: 0.95rem; display: inline-block;">
                        ทำแบบสอบถาม
                    </a>
                </div>

                <p style="color: #475569; font-size: 0.95rem; margin-bottom: 20px;">
                    ขอขอบคุณที่สละเวลาในการทำแบบสอบถาม CPDTH
                </p>

                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">

                <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.5; margin: 0;">
                    หากไม่สามารถกดปุ่ม "ทำแบบสอบถาม" ได้ คุณสามารถคัดลอกลิงก์ไปวางในเว็บเบราว์เซอร์ของคุณได้: <br>
                    <a href="<?php echo htmlspecialchars($questionLink); ?>" target="_blank" style="color: #3b82f6; text-decoration: none; word-break: break-all;">
                        <?php echo htmlspecialchars($questionLink); ?>
                    </a>
                </p>
            </div>

        </div>
    </div>

</body>

</html>
