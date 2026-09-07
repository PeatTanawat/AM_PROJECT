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
$coupon_id = isset($_POST['coupon_id']) ? (int) $_POST['coupon_id'] : 0;
if ($coupon_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคูปอง', null);
}

$code   = $str('coupon_code');
$detail = $str('coupon_detail');
$type   = $str('coupon_type');
$no     = $str('coupon_no');
$status = $str('coupon_status');

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

// ตรวจว่าคูปองมีจริงและยังไม่ถูกลบ
$check = $pdo_connect->prepare("SELECT coupon_id FROM tbl_coupon WHERE coupon_id = :id AND delete_at IS NULL LIMIT 1");
$check->execute([':id' => $coupon_id]);
if (!$check->fetchColumn()) {
    Response::json(0, 'ไม่พบคูปองนี้ หรือถูกลบไปแล้ว', null);
}
$check->closeCursor();

// รหัสคูปองห้ามซ้ำกับคูปองอื่น
$dup = $pdo_connect->prepare("SELECT coupon_id FROM tbl_coupon WHERE coupon_code = :code AND coupon_id <> :id AND delete_at IS NULL LIMIT 1");
$dup->execute([':code' => $code, ':id' => $coupon_id]);
if ($dup->fetchColumn()) {
    Response::json(0, 'รหัสคูปองนี้ถูกใช้งานแล้ว', null);
}
$dup->closeCursor();

try {
    $sql = "UPDATE tbl_coupon SET
                coupon_code = :code,
                coupon_detail = :detail,
                coupon_type = :type,
                coupon_no = :no,
                coupon_status = :status,
                coupon_limit = :limit,
                coupon_limit_person = :limit_person,
                coupon_min = :min,
                coupon_max = :max,
                coupon_start = :start,
                coupon_end = :end
            WHERE coupon_id = :id AND delete_at IS NULL";
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
        ':id'           => $coupon_id,
    ]);
    $stmt->closeCursor();

    Response::json(1, 'บันทึกข้อมูลคูปองสำเร็จ', ['coupon_id' => $coupon_id]);
} catch (Exception $e) {
    error_log('Update Coupon Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', null);
} finally {
    $pdo_connect = null;
}
