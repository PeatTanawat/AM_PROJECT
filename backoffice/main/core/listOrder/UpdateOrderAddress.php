<?php
// บันทึกที่อยู่ใบเสร็จ/ใบกำกับภาษีของลูกค้า (จากหน้ารายละเอียดคำสั่งซื้อ)
// - แก้ไขแถวเดิม (addr_id) หรือสร้างใหม่ให้ลูกค้าของออเดอร์ถ้ายังไม่มี

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$addr_id  = isset($_POST['addr_id']) ? (int) $_POST['addr_id'] : 0;
$type     = (isset($_POST['type']) && $_POST['type'] === '2') ? '2' : '1';
$name     = trim((string) ($_POST['name'] ?? ''));
$tax_id   = trim((string) ($_POST['tax_id'] ?? ''));
$branch   = trim((string) ($_POST['branch'] ?? ''));
$branch_name = trim((string) ($_POST['branch_name'] ?? ''));
$phone    = trim((string) ($_POST['phone'] ?? ''));
$detail   = trim((string) ($_POST['detail'] ?? ''));
$subdist  = trim((string) ($_POST['subdistrict'] ?? ''));
$district = trim((string) ($_POST['district'] ?? ''));
$province = trim((string) ($_POST['province'] ?? ''));
$zipcode  = trim((string) ($_POST['zipcode'] ?? ''));

if ($order_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำสั่งซื้อ', null);
}
if ($zipcode === '' || $subdist === '' || $district === '' || $province === '') {
    Response::json(0, 'กรุณากรอก รหัสไปรษณีย์ / ตำบล / อำเภอ / จังหวัด', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // หา user ของออเดอร์
    $q = $pdo_connect->prepare("SELECT user_id FROM tbl_orders WHERE order_id = :id LIMIT 1");
    $q->execute([':id' => $order_id]);
    $user_id = (int) $q->fetchColumn();
    $q->closeCursor();
    if (!$user_id) {
        Response::json(0, 'ไม่พบคำสั่งซื้อนี้', null);
    }

    $fields = [
        ':type' => $type, ':name' => $name, ':tax' => $tax_id, ':branch' => $branch, ':branch_name' => $branch_name, ':phone' => $phone,
        ':detail' => $detail, ':sub' => $subdist, ':dist' => $district, ':prov' => $province, ':zip' => $zipcode,
        ':oid' => $order_id
    ];

    $sql = "UPDATE tbl_orders SET
                order_addr_type = :type, 
                order_addr_name = :name, 
                order_addr_tax_id = :tax, 
                order_addr_branch = :branch, 
                order_addr_branch_name = :branch_name, 
                order_addr_phone = :phone,
                order_addr_detail = :detail, 
                order_addr_subdistrict = :sub, 
                order_addr_district = :dist, 
                order_addr_province = :prov, 
                order_addr_zipcode = :zip
            WHERE order_id = :oid";
    
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute($fields);
    $stmt->closeCursor();

    Response::json(1, 'บันทึกที่อยู่สำเร็จ', null);
} catch (Throwable $e) {
    error_log('UpdateOrderAddress Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
