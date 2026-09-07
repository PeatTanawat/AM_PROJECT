<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database\Connection;
use App\Utility\Email;
use Dotenv\Dotenv;

// โหลด Environment Variables
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();
}

if (!function_exists('sendExamPassNotification')) {
    /**
     * ฟังก์ชันสำหรับส่งอีเมลแบบสอบถามหลังการอบรมเมื่อสอบผ่าน
     */
    function sendExamPassNotification(PDO $pdo, int $user_id, int $course_id): bool
    {
        if ($user_id <= 0 || $course_id <= 0) {
            return false;
        }

        try {
            // 1. ดึงข้อมูลผู้ใช้งาน
            $stmt_u = $pdo->prepare("SELECT user_firstname, user_lastname, user_email FROM tbl_user WHERE user_id = :uid LIMIT 1");
            $stmt_u->execute([':uid' => $user_id]);
            $user_info = $stmt_u->fetch(PDO::FETCH_ASSOC);
            $stmt_u->closeCursor();

            if (!$user_info || empty($user_info['user_email'])) {
                error_log("sendExamPassNotification: No user email found for user_id = $user_id");
                return false;
            }

            $fullName  = trim(($user_info['user_firstname'] ?? '') . ' ' . ($user_info['user_lastname'] ?? ''));
            $userEmail = $user_info['user_email'];

            // 2. ดึง question_link จาก tbl_website_setting
            $questionLink = '';
            try {
                $stmt_s = $pdo->query("SELECT question_link FROM tbl_website_setting LIMIT 1");
                if ($stmt_s) {
                    $questionLink = (string)$stmt_s->fetchColumn();
                    $stmt_s->closeCursor();
                }
            } catch (\Throwable $t) {
                // Fallback
            }

            if (empty($questionLink)) {
                $questionLink = 'https://forms.gle/CPDTH_Survey';
            }

            // 3. Render HTML Template สำหรับแบบสอบถามอย่างเดียว
            $templateFile = __DIR__ . '/../../view/email/question_template.php';
            $emailBody = '';
            if (file_exists($templateFile)) {
                ob_start();
                include $templateFile;
                $emailBody = ob_get_clean();
            }

            if (!empty($emailBody)) {
                $subject = "แบบสอบถามหลังการอบรม - CPDTH";
                $sent = Email::send($userEmail, $subject, $emailBody);
                if (!$sent) {
                    error_log("sendExamPassNotification: Email::send failed for $userEmail");
                    return false;
                }
                return true;
            }
        } catch (\Throwable $e) {
            error_log("sendExamPassNotification Error: " . $e->getMessage());
        }

        return false;
    }
}

// หากเรียกตรงผ่าน HTTP Request เป็น standalone script
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'SendEvaluationEmailBackground.php' || (isset($_POST['user_id']) && isset($_POST['course_id']))) {
    ignore_user_abort(true);
    set_time_limit(0);

    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header("Connection: close");
        header("Content-Type: text/plain");
        ob_start();
        echo "Processing";
        $size = ob_get_length();
        header("Content-Length: $size");
        @ob_end_flush();
        @flush();
    }

    $user_id   = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

    if ($user_id > 0 && $course_id > 0) {
        try {
            $db_instance = new Connection();
            $pdo = $db_instance->getPdo();
            if ($pdo) {
                file_put_contents(__DIR__ . '/../../debug_email.log', date('Y-m-d H:i:s') . " - Calling sendExamPassNotification for user $user_id, course $course_id\n", FILE_APPEND);
                $res = sendExamPassNotification($pdo, $user_id, $course_id);
                file_put_contents(__DIR__ . '/../../debug_email.log', date('Y-m-d H:i:s') . " - Result: " . ($res ? 'Success' : 'Fail') . "\n", FILE_APPEND);
            }
        } catch (\Throwable $e) {
            error_log("SendEvaluationEmailBackground execution error: " . $e->getMessage());
        }
    }
}
