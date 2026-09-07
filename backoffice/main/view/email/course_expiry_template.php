<?php
/**
 * Email Template: แจ้งเตือนคอร์สเรียนใกล้หมดอายุ
 * 
 * ตัวแปรที่สามารถนำไปใช้งานใน Template นี้:
 * - $fullName         : ชื่อ-นามสกุล ของผู้ใช้งาน
 * - $expiringCourses  : รายการคอร์สที่ใกล้หมดอายุ (Array)
 *                       แต่ละคอร์สประกอบด้วย:
 *                       - course_name    (ชื่อคอร์ส)
 *                       - enroll_date    (วันที่ซื้อมา)
 *                       - expiry_date    (วันที่หมดอายุ)
 *                       - days_remaining (จำนวนวันคงเหลือ)
 */

if (empty($logo_url)) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (!empty($host)) {
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_dir = preg_replace('#/(core|scripts|src|main)(/[^/]+)*$#i', '', $script_dir);
        if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
            $base_dir = '';
        }
        $base_url = $protocol . '://' . $host . $base_dir;
        if (strpos($base_url, '/backoffice') === false && strpos($host, 'localhost') !== false) {
            $base_url = rtrim($base_url, '/') . '/backoffice';
        }
    } else {
        $base_url = 'https://bigsara-demo.com/am/backoffice';
    }

    $logo_url = rtrim($base_url, '/') . '/assets/images/G_AM_logo-01.jpg';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แจ้งเตือนคอร์สเรียนใกล้หมดอายุ</title>
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
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">สวัสดี คุณ <?php echo htmlspecialchars($fullName); ?></h3>
                <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                    ระบบตรวจพบว่าคอร์สเรียนของท่านใกล้หมดอายุ กรุณาตรวจสอบรายละเอียดคอร์สเรียนดังต่อไปนี้:
                </p>

                <!-- Loop รายการคอร์สเรียนใกล้หมดอายุ -->
                <?php foreach ($expiringCourses as $course): ?>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 12px 0; color: #1e293b; font-size: 16px;">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </h4>
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #475569;">
                            <tr>
                                <td style="padding: 4px 0; width: 130px; font-weight: 600;">ชื่อคอร์สเรียน :</td>
                                <td style="padding: 4px 0;"><?php echo htmlspecialchars($course['course_name']); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 4px 0; font-weight: 600;">วันที่หมดอายุ :</td>
                                <td style="padding: 4px 0; font-weight: 600;">
                                    <?php echo htmlspecialchars($course['expiry_date']); ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px 0; font-weight: 600;">คงเหลืออีกกี่วัน :</td>
                                <td style="padding: 4px 0;">
                                    <span style="background-color: #fef2f2; color: #ef4444; padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block;">
                                        <?php echo htmlspecialchars($course['days_remaining']); ?> วัน
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>

                <p style="color: #475569; font-size: 0.95rem; margin-bottom: 20px;">
                    กรุณาเข้าเรียนและทำแบบทดสอบให้เสร็จสิ้นก่อนวันหมดอายุ
                </p>

                <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.5; margin: 0;">
                    อีเมลฉบับนี้ส่งจากระบบ CPDTH โดยอัตโนมัติ กรุณาอย่าตอบกลับ
                </p>
            </div>

        </div>
    </div>

</body>

</html>
