<?php
// Script สำหรับจำลองการทำงาน ExportCertificate.php ในโหมดพรีวิว (base64) เพื่อดู Error Output จริงๆ
$_POST['access_token'] = 'dummy'; // เพื่อให้ผ่าน Auth requireUserToken แบบจำลองใน test (หรือผ่าน mock)
$_POST['enroll_id'] = 1; // dummy ID
$_POST['mode'] = 'base64';

// ดึงไฟล์หลักมาทดสอบเพื่อดู PHP Fatal Error
try {
    ob_start();
    // เราสร้าง mock function/class เพื่อจำลอง environment
    class MockAuth {
        public static function requireUserToken() {
            $obj = new stdClass();
            $obj->user_id = 999;
            return $obj;
        }
    }
    class MockResponse {
        public static function json($status, $message, $data) {
            echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
            exit;
        }
    }
    
    // ทำการ override class ชั่วคราว (หรือใช้วิธี mock Database Connection)
    // เพื่อรันโค้ด ExportCertificate.php ส่วนล่างสุดที่เป็นของ tFPDF โดยเฉพาะ
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
