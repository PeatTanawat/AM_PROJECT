<?php
// รายการบัญชีธนาคารบริษัท (tbl_company_banks + tbl_bank_mapping)

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

$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$search = trim((string) ($_POST['search'] ?? ''));

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(bm.bank_code LIKE :search OR bm.bank_abbreviation LIKE :search OR bm.bank_name LIKE :search OR cb.account_name LIKE :search OR cb.account_no LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // จำนวนทั้งหมดหลังกรอง
    $sql_cnt = "SELECT COUNT(*) 
                FROM tbl_company_banks cb 
                LEFT JOIN tbl_bank_mapping bm ON cb.bank_id = bm.bank_id 
                $where_sql";
    $stmt_cnt = $pdo_connect->prepare($sql_cnt);
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน
    $sql = "SELECT cb.id, cb.bank_id, cb.account_name, cb.account_no, cb.active_status,
                   bm.bank_code, bm.bank_abbreviation, bm.bank_name, bm.logo_image
            FROM tbl_company_banks cb
            LEFT JOIN tbl_bank_mapping bm ON cb.bank_id = bm.bank_id
            $where_sql
            ORDER BY cb.id DESC
            LIMIT :offset, :per_page";
            
    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) { 
        $stmt->bindValue($k, $v); 
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $list = [];
    foreach ($rows as $row) {
        $logo_image = $row['logo_image'] ?? null;
        if (!empty($logo_image)) {
            // ดึงภาพจาก AWS S3 และแปลงเป็น Base64 (หรือ Presigned URL ถ้าล้มเหลว)
            $logo_image = AwsS3::getFileUrl($logo_image, '+30 minutes', true);
        }

        $list[] = [
            'id'                => (int) ($row['id'] ?? 0),
            'bank_id'           => (int) ($row['bank_id'] ?? 0),
            'bank_code'         => $row['bank_code'] ?? '',
            'bank_abbreviation' => $row['bank_abbreviation'] ?? '',
            'bank_name'         => $row['bank_name'] ?? '',
            'logo_image'        => $logo_image,
            'account_name'      => $row['account_name'] ?? '',
            'account_no'        => $row['account_no'] ?? '',
            'active_status'     => (string) ($row['active_status'] ?? '1'),
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListBankSetting Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูลบัญชีธนาคาร: ' . $e->getMessage(), null);
} finally {
    $pdo_connect = null;
}
