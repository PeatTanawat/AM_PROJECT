<?php

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

/* ---------- helpers ---------- */
$str = function (string $key): ?string {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return $v === '' ? null : $v;
};

/* ---------- validate ---------- */
$code   = $str('coupon_code');
$detail = $str('coupon_detail');
$type   = $str('coupon_type');     // percent | fixed
$no     = $str('coupon_no');       // จำนวนส่วนลด
$status = $str('coupon_status');   // 1 = เปิดใช้งาน, 0 = ฉบับร่าง/ปิด

if ($code === null) {
    Response::json(0, 'กรุณากรอกรหัสคูปอง (Code)', null);
}
if ($detail === null) {
    Response::json(0, 'กรุณากรอกรายละเอียดคูปอง', null);
}
if ($type === null || !in_array($type, ['percent', 'fixed'], true)) {
    Response::json(0, 'กรุณาเลือกประเภทส่วนลด', null);
}
if ($no === null) {
    Response::json(0, 'กรุณากรอกจำนวนส่วนลด', null);
}
$status = ($status === '1') ? '1' : '0';

// รหัสคูปองห้ามซ้ำ
$dup = $pdo_connect->prepare("SELECT coupon_id FROM tbl_coupon WHERE coupon_code = :code AND delete_at IS NULL LIMIT 1");
$dup->execute([':code' => $code]);
$exists = $dup->fetchColumn();
$dup->closeCursor();
if ($exists) {
    Response::json(0, 'รหัสคูปองนี้ถูกใช้งานแล้ว', null);
}

try {
    $sql = "INSERT INTO tbl_coupon
                (coupon_code, coupon_detail, coupon_type, coupon_no, coupon_status,
                 coupon_limit, coupon_limit_person, coupon_min, coupon_max,
                 coupon_start, coupon_end)
            VALUES
                (:code, :detail, :type, :no, :status,
                 :limit, :limit_person, :min, :max,
                 :start, :end)";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute([
        ':code'         => $code,
        ':detail'       => $detail,
        ':type'         => $type,
        ':no'           => $no,
        ':status'       => $status,
        ':limit'        => $str('coupon_limit'),
        ':limit_person' => $str('coupon_limit_person'),
        ':min'          => $str('coupon_min'),
        ':max'          => $str('coupon_max'),
        ':start'        => $str('coupon_start'),
        ':end'          => $str('coupon_end'),
    ]);
    $new_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มคูปองส่วนลดสำเร็จ', ['coupon_id' => $new_id]);
} catch (Exception $e) {
    error_log('Add Coupon Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
