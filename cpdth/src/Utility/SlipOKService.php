<?php
namespace App\Utility;

use Exception;

class SlipOKService
{
    /**
     * ดึงรหัส API Key จากตัวแปรสภาพแวดล้อม (.env)
     */
    private static function getApiKey(): string
    {
        return $_ENV['SLIPOK_API_KEY'] ?? '';
    }

    /**
     * ดึงรหัสสาขา (Branch ID) จากตัวแปรสภาพแวดล้อม (.env)
     */
    private static function getBranchId(): string
    {
        return $_ENV['SLIPOK_BRANCH_ID'] ?? '';
    }

    /**
     * ตรวจสอบสลิปโอนเงินผ่านรูปภาพสลิปที่ผู้ใช้อัปโหลด (ส่งแบบไฟล์ภาพโดยตรง)
     *
     * @param string $filePath เส้นทางไฟล์รูปภาพในเครื่องเซิร์ฟเวอร์ (เช่น $_FILES['slip']['tmp_name'])
     * @param float|null $amount ยอดเงินโอนที่ต้องการตรวจ (ไม่ระบุก็ได้ แต่ถ้าระบุระบบจะตรวจว่าตรงกับสลิปไหม)
     * @param bool $log ต้องการบันทึกประวัติการสแกนลง Dashboard ของ SlipOK หรือไม่
     * @return array ผลลัพธ์ในรูปแบบ Array ที่ได้จากการตอบกลับของ SlipOK
     * @throws Exception
     */
    public static function verifySlipByImage(string $filePath, ?float $amount = null, bool $log = true): array
    {
        $apiKey = self::getApiKey();
        $branchId = self::getBranchId();

        if (empty($apiKey) || empty($branchId)) {
            throw new Exception("ยังไม่ได้ตั้งค่า SLIPOK_API_KEY หรือ SLIPOK_BRANCH_ID ในไฟล์สภาพแวดล้อม (.env)");
        }

        if (!file_exists($filePath)) {
            throw new Exception("ไม่พบไฟล์รูปภาพสลิปตามตำแหน่งที่ระบุ: " . $filePath);
        }

        $url = "https://api.slipok.com/api/line/apikey/" . $branchId;

        // จัดเตรียมข้อมูลสำหรับส่งโพสต์แบบ multipart/form-data เพื่ออัปโหลดไฟล์
        $postData = [
            'files' => new \CURLFile($filePath, mime_content_type($filePath) ?: 'image/jpeg', basename($filePath)),
            'log'   => $log ? 'true' : 'false'
        ];

        if ($amount !== null) {
            $postData['amount'] = (string)$amount;
        }

        return self::executePost($url, $postData, true);
    }

    /**
     * ตรวจสอบสลิปโอนเงินโดยใช้ข้อมูลข้อความ QR Code ดิบ (Mini QR)
     *
     * @param string $qrData ข้อความสติงของ QR Code ที่สแกนได้จากตัวสลิป
     * @param float|null $amount ยอดเงินโอนที่ต้องการตรวจ
     * @param bool $log ต้องการบันทึกประวัติการสแกนลง Dashboard ของ SlipOK หรือไม่
     * @return array ผลลัพธ์ในรูปแบบ Array
     * @throws Exception
     */
    public static function verifySlipByQrData(string $qrData, ?float $amount = null, bool $log = true): array
    {
        $apiKey = self::getApiKey();
        $branchId = self::getBranchId();

        if (empty($apiKey) || empty($branchId)) {
            throw new Exception("ยังไม่ได้ตั้งค่า SLIPOK_API_KEY หรือ SLIPOK_BRANCH_ID ในไฟล์สภาพแวดล้อม (.env)");
        }

        $url = "https://api.slipok.com/api/line/apikey/" . $branchId;

        $postData = [
            'data' => $qrData,
            'log'  => $log
        ];

        if ($amount !== null) {
            $postData['amount'] = $amount;
        }

        return self::executePost($url, $postData, false);
    }

    /**
     * ตรวจสอบสลิปโอนเงินโดยใช้ลิงก์ URL ของรูปภาพ
     *
     * @param string $imageUrl URL ของรูปภาพสลิป (รูปภาพต้องสามารถเข้าถึงได้จากภายนอก)
     * @param float|null $amount ยอดเงินโอนที่ต้องการตรวจ
     * @param bool $log ต้องการบันทึกประวัติการสแกนลง Dashboard ของ SlipOK หรือไม่
     * @return array ผลลัพธ์ในรูปแบบ Array
     * @throws Exception
     */
    public static function verifySlipByUrl(string $imageUrl, ?float $amount = null, bool $log = true): array
    {
        $apiKey = self::getApiKey();
        $branchId = self::getBranchId();

        if (empty($apiKey) || empty($branchId)) {
            throw new Exception("ยังไม่ได้ตั้งค่า SLIPOK_API_KEY หรือ SLIPOK_BRANCH_ID ในไฟล์สภาพแวดล้อม (.env)");
        }

        $url = "https://api.slipok.com/api/line/apikey/" . $branchId;

        $postData = [
            'url' => $imageUrl,
            'log' => $log
        ];

        if ($amount !== null) {
            $postData['amount'] = $amount;
        }

        return self::executePost($url, $postData, false);
    }

    /**
     * ตรวจสอบโควตาคงเหลือของบัญชีผู้ใช้ SlipOK
     *
     * @param string|null $branchId รหัสสาขา (ถ้าไม่ระบุจะดึงจาก .env)
     * @return array ข้อมูลจำนวนครั้งที่ใช้งานไปและคงเหลือในบัญชี
     * @throws Exception
     */
    public static function getQuota(?string $branchId = null): array
    {
        $apiKey = self::getApiKey();
        $branchId = $branchId ?: self::getBranchId();

        if (empty($apiKey) || empty($branchId)) {
            throw new Exception("ยังไม่ได้ตั้งค่า SLIPOK_API_KEY หรือ SLIPOK_BRANCH_ID ในไฟล์สภาพแวดล้อม (.env)");
        }

        $url = "https://api.slipok.com/api/line/apikey/" . $branchId . "/quota";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-authorization: " . $apiKey]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อเช็กโควตา (CURL Error): " . $curlError);
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("ไม่สามารถถอดรหัส JSON จากผลลัพธ์ของ SlipOK ได้ HTTP Code: " . $httpCode . ", Response: " . $response);
        }

        return $result;
    }

    /**
     * ฟังก์ชันภายใน (Helper) สำหรับทำหน้าที่ยิง Request แบบ POST ไปยังระบบ API ของ SlipOK
     */
    private static function executePost(string $url, array $data, bool $isMultipart): array
    {
        $apiKey = self::getApiKey();
        $headers = [
            "x-authorization: " . $apiKey
        ];

        if (!$isMultipart) {
            $headers[] = "Content-Type: application/json";
            $postFields = json_encode($data);
        } else {
            // สำหรับ Multipart (อัปโหลดรูปภาพ) cURL จะกำหนด Content-Type และ Boundary ให้อัตโนมัติ
            $postFields = $data;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("เกิดข้อผิดพลาดในการเชื่อมต่อ SlipOK API (CURL Error): " . $curlError);
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("ไม่สามารถถอดรหัส JSON จากผลลัพธ์ของ SlipOK ได้ HTTP Code: " . $httpCode . ", Response: " . $response);
        }

        return $result;
    }
}
