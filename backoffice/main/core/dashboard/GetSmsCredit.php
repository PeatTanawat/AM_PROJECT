<?php
// คืนค่า JSON จำนวนเครดิต OTP / STANDARD SMS คงเหลือจาก ThaiBulkSMS API
// สำหรับหน้า Dashboard (home.php)

use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\SMS;

$access_token = Auth::requireUserToken();
if (!($access_token->user_id ?? null)) {
    Response::json(0, 'Unauthorized', null);
}

$res = SMS::getCredit();
if ($res['result'] === 1) {
    Response::json(1, 'Success', [
        'standard_sms' => $res['standard_sms'],
        'formatted'    => $res['formatted'],
        'credit_type'  => $res['credit_type'] ?? 'STANDARD SMS',
        'raw'          => $res['raw'] ?? null
    ]);
} else {
    Response::json(0, $res['msg'] ?? 'ไม่สามารถดึงข้อมูลเครดิต SMS ได้', [
        'standard_sms' => 0,
        'formatted'    => '0',
        'credit_type'  => 'STANDARD SMS'
    ]);
}
