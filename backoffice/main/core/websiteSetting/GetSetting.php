<?php
// ตั้งค่าเว็บไซต์ (singleton) — ดึงค่าปัจจุบันของ tbl_website_setting + tbl_payment_methods
// ทั้งสองตารางมีได้แถวเดียว ถ้ายังไม่มีแถว -> คืน null ให้ฝั่ง client ใช้ค่าเริ่มต้น

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

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

// ค่าตั้งค่าเว็บไซต์
$stmt = $pdo_connect->query("SELECT * FROM tbl_website_setting ORDER BY id ASC LIMIT 1");
$setting = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
$stmt->closeCursor();

// ช่องทางการชำระเงิน
$stmt = $pdo_connect->query("SELECT * FROM tbl_payment_methods ORDER BY payment_id ASC LIMIT 1");
$payment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
$stmt->closeCursor();

Response::json(1, 'Success', ['setting' => $setting, 'payment' => $payment]);
