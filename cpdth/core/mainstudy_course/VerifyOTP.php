<?php
use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\SMS;

try {
    // 1. Check Authentication
    $currentUser = Auth::requireUserToken();

    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $pin = isset($_POST['pin']) ? trim($_POST['pin']) : '';

    if (empty($token) || empty($pin)) {
        Response::json(0, 'กรุณากรอกรหัส OTP ให้ครบถ้วน', null);
    }

    // 2. Verify OTP code via ThaiBulkSMS
    $res = SMS::verifyOTP($token, $pin);

    if ($res['result'] == 1) {
        Response::json(1, 'ยืนยันรหัสสำเร็จ', null);
    } else {
        Response::json(0, $res['msg'] ?? 'รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
