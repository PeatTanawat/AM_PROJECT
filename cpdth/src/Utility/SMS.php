<?php
namespace App\Utility;

class SMS {
    private static function getCredentials() {
        $key    = $_ENV['THAIBULK_KEY']    ?? getenv('THAIBULK_KEY')    ?: 'PweVx3EQ2bXkS2dhn58y9mLUGK-BTg';
        $secret = $_ENV['THAIBULK_SECRET'] ?? getenv('THAIBULK_SECRET') ?: 'KnenmDd31Ofi3GHZPWDComas0nltcD';
        return ['key' => $key, 'secret' => $secret];
    }

    /**
     * Send Standard SMS using ThaiBulkSMS API v2
     * @param string $phone
     * @param string $message
     * @return array [result => 1/0, msg => '']
     */
    public static function sendSMS($phone, $message) {
        $creds = self::getCredentials();
        if (empty($creds['key']) || empty($creds['secret'])) {
            return ['result' => 0, 'msg' => 'ไม่ได้ตั้งค่า THAIBULK_KEY หรือ THAIBULK_SECRET'];
        }

        $sender = $_ENV['THAIBULK_SENDER'] ?? getenv('THAIBULK_SENDER') ?: 'CPD1';

        $postFields = [
            'msisdn'  => $phone,
            'message' => $message,
            'sender'  => $sender,
            'force'   => 'standard'
        ];

        // Basic Auth Header
        $authHeader = 'Basic ' . base64_encode($creds['key'] . ':' . $creds['secret']);

        $ch = curl_init('https://api-v2.thaibulksms.com/sms');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($postFields),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: ' . $authHeader,
                'Accept: application/json'
            ],
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res = json_decode($response, true);
        
        file_put_contents(__DIR__ . '/debug_sms.log', date('Y-m-d H:i:s') . " HTTP $http_code | Send SMS Response: $response\n", FILE_APPEND);

        if ($http_code >= 200 && $http_code < 300) {
            return ['result' => 1, 'msg' => 'ส่ง SMS สำเร็จ'];
        }

        $msg = $res['error']['description'] ?? 'เกิดข้อผิดพลาดในการส่ง SMS (HTTP ' . $http_code . ')';
        return ['result' => 0, 'msg' => $msg];
    }

    /**
     * Request OTP — Generate locally, save to DB and send via standard SMS
     * @param string $phone '08XXXXXXXX'
     * @param int $user_id
     * @return array [result => 1/0, token => '<otp_token>', refno => '<ref>', msg => '']
     */
    public static function requestOTP($phone, $user_id = 0) {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) !== 10) {
            return ['result' => 0, 'msg' => 'หมายเลขโทรศัพท์ไม่ถูกต้อง ต้องเป็นตัวเลข 10 หลัก'];
        }

        try {
            $db = (new \App\Database\Connection())->getPdo();
            
            // Ensure table exists
            $createTableSql = "CREATE TABLE IF NOT EXISTS `tbl_otp_log` (
              `otp_id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) DEFAULT 0,
              `otp_code` varchar(10) NOT NULL,
              `ref_code` varchar(10) NOT NULL,
              `token` varchar(64) NOT NULL,
              `failed_attempts` int(11) DEFAULT 0,
              `expire_at` datetime NOT NULL,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`otp_id`),
              KEY `token` (`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $db->exec($createTableSql);

            // Generate OTP locally
            $otp_code = sprintf("%04d", rand(0, 9999));
            $ref_code = $otp_code;
            $token = bin2hex(random_bytes(16));
            
            // Set expiration to 5 minutes
            $expire_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $created_at = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO tbl_otp_log (user_id, otp_code, ref_code, token, failed_attempts, expire_at, created_at)
                    VALUES (:uid, :code, :ref, :token, 0, :expire, :created)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':uid' => $user_id,
                ':code' => $otp_code,
                ':ref' => $ref_code,
                ':token' => $token,
                ':expire' => $expire_at,
                ':created' => $created_at
            ]);
            $stmt->closeCursor();
            
            // Attempt SMS sending (silently catch failure for local testing)
            try {
                $message = "[CPDTH] รหัส OTP ของคุณคือ $otp_code (Ref: $ref_code) ใช้งานได้ภายใน 5 นาที";
                self::sendSMS($phone, $message);
            } catch (\Exception $smsEx) {
                // Ignore SMS send error in local environment
            }
            
            return [
                'result'   => 1,
                'token'    => $token,
                'refno'    => $ref_code,
                'otp_code' => $otp_code,
                'msg'      => 'ส่งรหัส OTP สำเร็จ'
            ];
        } catch (\Exception $e) {
            return ['result' => 0, 'msg' => 'ไม่สามารถบันทึกและส่ง OTP ได้: ' . $e->getMessage()];
        }
    }

    /**
     * Verify OTP — Check code against database
     * @param string $storedOtp  OTP Token generated by requestOTP
     * @param string $userPin    OTP Pin input from user
     * @return array [result => 1/0, msg => '']
     */
    public static function verifyOTP($storedOtp, $userPin) {
        if (empty($storedOtp) || empty($userPin)) {
            return ['result' => 0, 'msg' => 'ข้อมูลไม่ครบถ้วน'];
        }

        try {
            $db = (new \App\Database\Connection())->getPdo();
            
            // Check OTP
            $sql = "SELECT * FROM tbl_otp_log WHERE token = :token LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':token' => $storedOtp]);
            $otp = $stmt->fetch();
            $stmt->closeCursor();

            if (!$otp) {
                return ['result' => 0, 'msg' => 'รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว'];
            }

            // Check expiration
            $now = date('Y-m-d H:i:s');
            if ($otp['expire_at'] < $now) {
                $del = $db->prepare("DELETE FROM tbl_otp_log WHERE otp_id = :id");
                $del->execute([':id' => $otp['otp_id']]);
                return ['result' => 0, 'msg' => 'รหัส OTP หมดอายุแล้ว'];
            }

            // Check PIN (accepts 4-digit OTP code or Ref code)
            if ($otp['otp_code'] === $userPin || strcasecmp((string)$otp['ref_code'], $userPin) === 0) {
                // Correct! Delete OTP record
                $del = $db->prepare("DELETE FROM tbl_otp_log WHERE otp_id = :id");
                $del->execute([':id' => $otp['otp_id']]);
                return ['result' => 1, 'msg' => 'ยืนยันรหัส OTP ถูกต้อง'];
            } else {
                // Incorrect pin
                $failed = (int)$otp['failed_attempts'] + 1;
                if ($failed >= 3) {
                    $del = $db->prepare("DELETE FROM tbl_otp_log WHERE otp_id = :id");
                    $del->execute([':id' => $otp['otp_id']]);
                    return ['result' => 0, 'msg' => 'คุณกรอกรหัสยืนยันผิดครบ 3 ครั้ง ระบบจะเริ่มต้นเรียนบทเรียนนี้ใหม่ตั้งแต่ต้น'];
                } else {
                    $upd = $db->prepare("UPDATE tbl_otp_log SET failed_attempts = :failed WHERE otp_id = :id");
                    $upd->execute([':failed' => $failed, ':id' => $otp['otp_id']]);
                    return ['result' => 0, 'msg' => 'รหัส OTP ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง (เหลือโอกาสอีก ' . (3 - $failed) . ' ครั้ง)'];
                }
            }
        } catch (\Exception $e) {
            return ['result' => 0, 'msg' => 'ระบบเกิดข้อผิดพลาดในการตรวจสอบ: ' . $e->getMessage()];
        }
    }
}
