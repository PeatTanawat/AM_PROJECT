<?php
use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\SMS;

try {
    // 1. Check Authentication
    $currentUser = Auth::requireUserToken();
    $user_id = (int)$currentUser->user_id;

    $db_instance = new Connection();
    $db = $db_instance->getPdo();

    if (!$db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 2. Fetch User's phone number
    $sql_user = "SELECT user_phone FROM tbl_user WHERE user_id = :uid LIMIT 1";
    $stmt = $db->prepare($sql_user);
    $stmt->execute([':uid' => $user_id]);
    $phone = $stmt->fetchColumn();
    $stmt->closeCursor();

    if (empty($phone)) {
        Response::json(0, 'ไม่พบเบอร์โทรศัพท์ที่ลงทะเบียนไว้ในระบบ กรุณาอัปเดตเบอร์โทรศัพท์ในประวัติส่วนตัว', null);
    }

    // 3. Request OTP via ThaiBulkSMS
    $res = SMS::requestOTP($phone, $user_id);

    if ($res['result'] == 1) {
        $formatted_phone = $phone;
        if (!empty($phone)) {
            $cleaned = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleaned) == 10) {
                $formatted_phone = substr($cleaned, 0, 3) . '-' . substr($cleaned, 3, 3) . '-' . substr($cleaned, 6);
            }
        }
        Response::json(1, 'ส่ง OTP เรียบร้อยแล้ว', [
            'token'    => $res['token'],
            'refno'    => $res['refno'],
            'phone'    => $formatted_phone,
            'otp_code' => $res['otp_code'] ?? null
        ]);
    } else {
        Response::json(0, $res['msg'] ?? 'ไม่สามารถส่ง OTP ได้', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
