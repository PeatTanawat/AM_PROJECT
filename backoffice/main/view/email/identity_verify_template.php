<?php
/**
 * Email Template: แจ้งผลการยืนยันตัวตน (อนุมัติ / ไม่อนุมัติ)
 * 
 * ตัวแปรที่ใช้ใน Template:
 * - $isApproved   : boolean (true = อนุมัติ, false = ไม่อนุมัติ)
 * - $display_name : ชื่อผู้ใช้งาน (รวมคำนำหน้า ชื่อ นามสกุล)
 * - $remark       : เหตุผลการไม่อนุมัติ (กรณี $isApproved เป็น false)
 * - $login_url    : ลิงก์เข้าสู่ระบบ
 * - $logo_url     : ลิงก์โลโก้เว็บไซต์
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?php echo $isApproved ? 'แจ้งผลการยืนยันตัวตน - อนุมัติสำเร็จ' : 'แจ้งผลการยืนยันตัวตน - ไม่ผ่านการอนุมัติ'; ?></title>
</head>
<body style="margin: 0; padding: 30px 0; background-color: #f4f6f9; font-family: 'Prompt', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <!-- ================= Header / Logo & Title ================= -->
        <div style="background-color: #ffffff; padding: 25px 20px 20px 20px; text-align: center; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
            
            <h2 style="margin: 15px 0 0 0; font-size: 18px; font-weight: 700; color: #1e3a8a;">
                แจ้งผลการยืนยันตัวตน
            </h2>
        </div>

        <!-- ================= Status Icon & Title ================= -->
        <div style="padding: 30px 25px 15px 25px; text-align: center;">
            <?php if ($isApproved): ?>
                <!-- Green Circle Icon (Approved) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 15px 0;">
                    <tr>
                        <td align="center" valign="middle">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="64" height="64" style="width: 64px; height: 64px; border-collapse: collapse; margin: 0 auto;">
                                <tr>
                                    <td align="center" valign="middle" style="width: 64px; height: 64px; background-color: #d1fae5; border-radius: 50%; text-align: center; vertical-align: middle;">
                                        <span style="color: #10b981; font-size: 32px; font-weight: bold; line-height: 64px; display: block; text-align: center; font-family: Arial, sans-serif;">✓</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <h3 style="margin: 0 0 10px 0; color: #047857; font-size: 20px; font-weight: 700;">
                    ยืนยันตัวตนสำเร็จ
                </h3>
            <?php else: ?>
                <!-- Red Circle Icon (Rejected) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 15px 0;">
                    <tr>
                        <td align="center" valign="middle">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="64" height="64" style="width: 64px; height: 64px; border-collapse: collapse; margin: 0 auto;">
                                <tr>
                                    <td align="center" valign="middle" style="width: 64px; height: 64px; background-color: #fee2e2; border-radius: 50%; text-align: center; vertical-align: middle;">
                                        <span style="color: #ef4444; font-size: 32px; font-weight: bold; line-height: 64px; display: block; text-align: center; font-family: Arial, sans-serif;">✕</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <h3 style="margin: 0 0 10px 0; color: #b91c1c; font-size: 20px; font-weight: 700;">
                    ไม่ผ่านการอนุมัติการยืนยันตัวตน
                </h3>
            <?php endif; ?>
        </div>

        <!-- ================= Main Body Content ================= -->
        <div style="padding: 10px 30px 30px 30px; color: #334155; line-height: 1.7; font-size: 15px;">
            <p style="margin-top: 0; font-size: 16px;">
                เรียน <strong><?php echo htmlspecialchars($display_name); ?></strong>
            </p>

            <?php if ($isApproved): ?>
                <p style="margin-bottom: 12px;">
                    ระบบได้ทำการตรวจสอบเอกสารยืนยันตัวตนของคุณเป็นที่เรียบร้อยแล้ว <span style="color: #059669; font-weight: 600;">ผลการตรวจสอบคือ อนุมัติสำเร็จ</span>
                </p>
                <p style="color: #475569; margin-bottom: 25px;">
                    ขณะนี้ บัญชีผู้ใช้งานของคุณได้รับการยืนยันตัวตนเรียบร้อยแล้ว ท่านสามารถเข้าสู่ระบบเพื่อลงทะเบียนเรียนคอร์สต่างๆ และใช้งานบริการทั้งหมดของเว็บไซต์ได้ตามปกติ
                </p>

                <!-- Login Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo htmlspecialchars($login_url); ?>" target="_blank" style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 13px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; display: inline-block; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                        เข้าสู่ระบบ
                    </a>
                </div>
            <?php else: ?>
                <p style="margin-bottom: 12px;">
                    ระบบได้ทำการตรวจสอบเอกสารยืนยันตัวตนของคุณแล้ว พบว่า<span style="color: #dc2626; font-weight: 600;">ไม่ผ่านการอนุมัติ</span> โดยมีรายละเอียดดังนี้:
                </p>

                <!-- Remark / Rejection Reason Box -->
                <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; padding: 14px 16px; margin: 20px 0; color: #991b1b;">
                    <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px; color: #7f1d1d;">
                        สาเหตุ / หมายเหตุจากเจ้าหน้าที่:
                    </div>
                    <div style="font-size: 15px; color: #991b1b; word-break: break-word;">
                        <?php echo nl2br(htmlspecialchars($remark)); ?>
                    </div>
                </div>

                <p style="color: #475569; margin-bottom: 25px;">
                    กรุณาเข้าสู่ระบบเพื่อทำการแก้ไข ถ่ายภาพเอกสารหรือรูปถ่ายใบหน้าให้ชัดเจน และอัปโหลดเอกสารยืนยันตัวตนใหม่อีกครั้ง
                </p>

                <!-- Action Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo htmlspecialchars($login_url); ?>" target="_blank" style="background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 13px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; display: inline-block; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);">
                        เข้าสู่ระบบ
                    </a>
                </div>
            <?php endif; ?>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;">

            <p style="font-size: 13px; color: #64748b; margin: 0; line-height: 1.6; text-align: center;">
                หากปุ่มด้านบนไม่สามารถกดได้ คุณสามารถคัดลอกลิงก์ด้านล่างไปเปิดบนเว็บเบราว์เซอร์ได้:<br>
                <a href="<?php echo htmlspecialchars($login_url); ?>" target="_blank" style="color: #2563eb; text-decoration: underline; word-break: break-all;">
                    <?php echo htmlspecialchars($login_url); ?>
                </a>
            </p>
        </div>

        <!-- ================= Footer ================= -->
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 20px; text-align: center;">
            <p style="font-size: 12px; color: #94a3b8; margin: 0;">
                อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติจากระบบ CPDTH กรุณาอย่าตอบกลับอีเมลนี้
            </p>
        </div>

    </div>

</body>
</html>
