<?php
namespace App\Utility;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    /**
     * ส่งอีเมลผ่าน SMTP (Gmail)
     *
     * @param string $to      อีเมลผู้รับ
     * @param string $subject หัวข้ออีเมล
     * @param string $body    เนื้อหาอีเมล (รองรับ HTML)
     * @param bool   $isHtml  เป็น HTML หรือไม่ (เริ่มต้น true)
     * @param array  $attachments รายการไฟล์แนบ [['path'=>..., 'name'=>...], ...] (ไม่ใส่ก็ได้)
     * @return bool true หากสำเร็จ
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true, array $attachments = []): bool
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
            $mail->Port       = (int) ($_ENV['SMTP_PORT'] ?? 587);
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'System'
            );
            $mail->addAddress($to);

            // Attachments
            foreach ($attachments as $att) {
                if (!empty($att['path']) && is_file($att['path'])) {
                    $mail->addAttachment($att['path'], $att['name'] ?? '');
                }
            }

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            return $mail->send();
        } catch (\Throwable $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }
}
