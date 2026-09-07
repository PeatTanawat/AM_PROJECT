<?php
// รายการข้อมูลหลักธนาคาร (Master list จาก tbl_bank_mapping)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo_connect->prepare("SELECT bank_id, bank_code, bank_abbreviation, bank_name, logo_image FROM tbl_bank_mapping ORDER BY bank_id ASC");
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($banks as &$b) {
        if (!empty($b['logo_image'])) {
            $b['logo_image'] = AwsS3::getFileUrl($b['logo_image'], '+30 minutes', true);
        }
    }
    unset($b);

    Response::json(1, 'สำเร็จ', $banks);

} catch (\Throwable $e) {
    error_log('GetBankMapping Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล master ธนาคาร: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
