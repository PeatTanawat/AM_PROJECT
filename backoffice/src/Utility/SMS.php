<?php

namespace App\Utility;

class SMS
{
    /**
     * ดึงข้อมูล Base64 Token หรือ Credentials ของ ThaiBulkSMS จาก .env
     */
    public static function getAuthHeader(): string
    {
        // 1) ดึงคีย์หลักสำหรับเช็คเครดิตจาก OTP_TOKEN และ OTP_SECRET ใน .env
        $key    = trim((string) ($_ENV['OTP_TOKEN'] ?? getenv('OTP_TOKEN') ?: 'PweVx3EQ2bXkS2dhn58y9mLUGK-BTg'));
        $secret = trim((string) ($_ENV['OTP_SECRET'] ?? getenv('OTP_SECRET') ?: 'KnenmDd31Ofi3GHZPWDComas0nltcD'));

        // 2) ถ้าไม่มี ให้ fallback ไปใช้ THAIBULK_KEY / THAIBULK_SECRET เดิม
        if ($key === '' && $secret === '') {
            $key    = trim((string) ($_ENV['THAIBULK_KEY'] ?? getenv('THAIBULK_KEY') ?: '17848730433214'));
            $secret = trim((string) ($_ENV['THAIBULK_SECRET'] ?? getenv('THAIBULK_SECRET') ?: '56cd080ba71bb6880e8a2181434a8d92'));
        }

        // 3) ถ้ามี THAIBULK_BASE64 ตั้งใน .env
        $b64 = $_ENV['THAIBULK_BASE64'] ?? getenv('THAIBULK_BASE64') ?: '';
        $b64 = trim((string) $b64);
        if ($b64 !== '') {
            return preg_replace('/^Basic\s+/i', '', $b64);
        }

        // ถ้า KEY ถูกแปลงเป็น Base64 มาแล้วล่วงหน้า (ไม่มี secret)
        if (!empty($key) && empty($secret)) {
            return preg_replace('/^Basic\s+/i', '', $key);
        }

        // ถ้ามีทั้ง KEY และ SECRET ให้นำมาต่อด้วย : แล้วแปลงเป็น Base64 อัตโนมัติ
        if (!empty($key) && !empty($secret)) {
            return base64_encode($key . ':' . $secret);
        }

        return '';
    }

    /**
     * ดึงข้อมูลเครดิตคงเหลือจาก ThaiBulkSMS (โดยเฉพาะประเภท STANDARD SMS / OTP)
     * @return array [result => 1/0, standard_sms => int, formatted => string, msg => string, raw => array]
     */
    public static function getCredit(): array
    {
        $auth_token = self::getAuthHeader();
        if (empty($auth_token)) {
            return [
                'result'       => 0,
                'standard_sms' => 0,
                'formatted'    => '0',
                'msg'          => 'ไม่ได้ตั้งค่า OTP_TOKEN / OTP_SECRET หรือ THAIBULK_KEY / THAIBULK_SECRET ในไฟล์ .env'
            ];
        }

        $ch = curl_init('https://api-v2.thaibulksms.com/credit');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Basic ' . $auth_token
            ],
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err  = curl_error($ch);
        curl_close($ch);

        if (!$response) {
            return [
                'result'       => 0,
                'standard_sms' => 0,
                'formatted'    => '0',
                'msg'          => 'ไม่สามารถเชื่อมต่อ ThaiBulkSMS API ได้: ' . ($curl_err ?: 'Timeout/Network error')
            ];
        }

        $json = json_decode($response, true);
        if ($http_code !== 200 || !is_array($json)) {
            $err_msg = $json['error']['description'] ?? $json['message'] ?? 'รหัสตอบกลับ HTTP ' . $http_code;
            return [
                'result'       => 0,
                'standard_sms' => 0,
                'formatted'    => '0',
                'msg'          => 'ThaiBulkSMS ตอบกลับผิดพลาด: ' . $err_msg,
                'raw'          => $json
            ];
        }

        $standard_credit = 0;
        $corporate_credit = 0;
        $otp_credit = 0;

        if (!empty($json['remaining_credit']) && is_array($json['remaining_credit'])) {
            $standard_credit  = (int) ($json['remaining_credit']['standard'] ?? 0);
            $corporate_credit = (int) ($json['remaining_credit']['corporate'] ?? 0);
            $otp_credit       = (int) ($json['remaining_credit']['otp'] ?? 0);
        } elseif (!empty($json['credit']) && is_array($json['credit'])) {
            foreach ($json['credit'] as $item) {
                $type = strtoupper(trim((string) ($item['type'] ?? '')));
                $amount = (int) ($item['amount'] ?? 0);
                if (strpos($type, 'OTP') !== false) {
                    $otp_credit = $amount;
                } elseif (strpos($type, 'CORPORATE') !== false) {
                    $corporate_credit = $amount;
                } elseif (strpos($type, 'STANDARD') !== false || strpos($type, 'SMS') !== false) {
                    $standard_credit = $amount;
                }
            }
        } elseif (isset($json['amount'])) {
            $standard_credit = (int) $json['amount'];
        }

        // กำหนดเครดิตหลักที่จะแสดงผล (ลำดับความสำคัญ: OTP > CORPORATE > STANDARD)
        $main_credit = 0;
        $credit_type = 'STANDARD SMS';

        if ($otp_credit > 0) {
            $main_credit = $otp_credit;
            $credit_type = 'OTP';
        } elseif ($corporate_credit > 0) {
            $main_credit = $corporate_credit;
            $credit_type = 'CORPORATE SMS';
        } else {
            $main_credit = $standard_credit;
            $credit_type = 'STANDARD SMS';
        }

        // กรณีที่ยอดทั้งหมดเป็น 0 แต่มีข้อมูลในอาเรย์ ให้ยึดข้อมูลตัวแรกสุด
        if ($main_credit === 0 && !empty($json['credit']) && is_array($json['credit'])) {
            $main_credit = (int) ($json['credit'][0]['amount'] ?? 0);
            $credit_type = strtoupper(trim((string) ($json['credit'][0]['type'] ?? 'STANDARD SMS')));
        }

        return [
            'result'           => 1,
            'standard_sms'     => $main_credit, // ใช้ตัวแปรชื่อเดิมเพื่อไม่ให้กระทบจุดอื่นที่เรียกใช้
            'otp_credit'       => $otp_credit,
            'corporate_credit' => $corporate_credit,
            'standard_credit'  => $standard_credit,
            'credit_type'      => $credit_type,
            'formatted'        => number_format($main_credit),
            'msg'              => 'ดึงข้อมูลเครดิต SMS สำเร็จ',
            'raw'              => $json
        ];
    }
}
