<?php
namespace App\Utility;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email 
{
    /**
     * ฟังก์ชันสำหรับส่งอีเมลผ่าน SMTP
     *
     * @param string $to อีเมลผู้รับ
     * @param string $subject หัวข้ออีเมล
     * @param string $body เนื้อหาอีเมล (รองรับ HTML)
     * @param bool $isHtml กำหนดว่าเป็น HTML หรือไม่ (เริ่มต้นเป็น true)
     * @return bool คืนค่า true หากส่งสำเร็จ และ false หากเกิดข้อผิดพลาด
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool 
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? '';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USERNAME'] ?? '';
            $mail->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', 
                $_ENV['MAIL_FROM_NAME'] ?? 'System'
            );
            $mail->addAddress($to);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            return $mail->send();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
?>
