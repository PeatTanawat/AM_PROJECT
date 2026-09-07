<?php
// รายละเอียดคำสั่งซื้อ 1 รายการ: ข้อมูลทั่วไป + ใบกำกับภาษี + รายการ + สรุป VAT

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\AwsS3;
use App\Utility\Response;

$access_token = Auth::requireUserToken();
$admin_id     = $access_token->user_id ?? null;
if (! $admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
if ($order_id <= 0) {
    Response::json(0, 'ไม่พบรหัสคำสั่งซื้อ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (! $pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// order + ลูกค้า
$stmt = $pdo_connect->prepare(
    "SELECT o.order_id,
            o.user_id,
            o.transaction_ref,
            o.total_price,
            o.payment_status,
            o.payment_method,
            o.created_at,
            o.order_internal_note,
            o.order_addr_type,
            o.order_addr_name,
            o.order_addr_tax_id,
            o.order_addr_branch,
            o.order_addr_branch_name,
            o.order_addr_phone,
            o.order_addr_detail,
            o.order_addr_subdistrict,
            o.order_addr_district,
            o.order_addr_province,
            o.order_addr_zipcode,
            o.slip_image,
            u.user_firstname,
            u.user_lastname,
            u.user_phone,
            u.user_email,
            u.user_citizen_id
     FROM tbl_orders o
     LEFT JOIN tbl_user u ON o.user_id = u.user_id
     WHERE o.order_id = :id
     LIMIT 1"
);
$stmt->execute([':id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
if (! $order) {
    Response::json(0, 'ไม่พบคำสั่งซื้อนี้', null);
}

// รายการคอร์สในคำสั่งซื้อ
$it = $pdo_connect->prepare(
    "SELECT od.price_at_purchase, c.course_name
     FROM tbl_order_detail od
     LEFT JOIN tbl_course c ON c.course_id = od.course_id
     WHERE od.order_id = :id
     ORDER BY od.list_order ASC, od.detail_id ASC"
);
$it->execute([':id' => $order_id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);
$it->closeCursor();

// คูปองส่วนลดที่ใช้ในออเดอร์นี้ (tbl_coupon_logs JOIN tbl_coupon)
$cl = $pdo_connect->prepare(
    "SELECT cl.coupon_id,
            cl.discount_amount,
            c.coupon_code,
            c.coupon_detail,
            c.coupon_type,
            c.coupon_no
     FROM tbl_coupon_logs cl
     LEFT JOIN tbl_coupon c ON c.coupon_id = cl.coupon_id
     WHERE cl.order_id = :id
     LIMIT 1"
);
$cl->execute([':id' => $order_id]);
$coupon_log = $cl->fetch(PDO::FETCH_ASSOC) ?: null;
$cl->closeCursor();

// ที่อยู่ใบกำกับภาษี (ค่าเริ่มต้นของลูกค้า)
$ad = $pdo_connect->prepare(
    "SELECT addr_id, addr_type, addr_name, addr_tax_id, addr_branch, addr_branch_name, addr_phone, addr_detail,
            addr_subdistrict, addr_district, addr_province, addr_zipcode
     FROM tbl_user_address
     WHERE addr_user_id = :uid AND delete_at IS NULL
     ORDER BY addr_is_default DESC, addr_id DESC
     LIMIT 1"
);
$ad->execute([':uid' => (int) $order['user_id']]);
$addr = $ad->fetch(PDO::FETCH_ASSOC) ?: null;
$ad->closeCursor();

$full = trim(($order['user_firstname'] ?? '') . ' ' . ($order['user_lastname'] ?? ''));

$type_label = fn($t) => $t === '2' ? 'นิติบุคคล' : 'บุคคลธรรมดา';

// ใบกำกับภาษี (ค่าแสดงผล)
$receipt = [
    'type'        => '-',
    'tax_id'      => '-',
    'branch'      => '-',
    'branch_name' => '-',
    'name'        => $full !== '' ? $full : '-',
    'address'     => '-',
    'phone'       => $order['user_phone'] ?: '-',
];
// ตรวจสอบว่าออเดอร์นี้มีข้อมูลที่อยู่ถูกแช่แข็งไว้หรือยัง (ถ้ามีใช้ของออเดอร์, ถ้าไม่มีใช้ค่า default จาก tbl_user_address)
$has_order_addr = ! empty($order['order_addr_name']) || ! empty($order['order_addr_type']);

// เลือก Source ของข้อมูลที่อยู่
$src_type        = $has_order_addr ? $order['order_addr_type'] : ($addr['addr_type'] ?? '1');
$src_name        = $has_order_addr ? $order['order_addr_name'] : ($addr['addr_name'] ?? '');
$src_tax_id      = $has_order_addr ? $order['order_addr_tax_id'] : ($addr['addr_tax_id'] ?? '');
$src_branch      = $has_order_addr ? $order['order_addr_branch'] : ($addr['addr_branch'] ?? '');
$src_branch_name = $has_order_addr ? $order['order_addr_branch_name'] : ($addr['addr_branch_name'] ?? '');
$src_phone       = $has_order_addr ? $order['order_addr_phone'] : ($addr['addr_phone'] ?? '');
$src_detail      = $has_order_addr ? $order['order_addr_detail'] : ($addr['addr_detail'] ?? '');
$src_subdistrict = $has_order_addr ? $order['order_addr_subdistrict'] : ($addr['addr_subdistrict'] ?? '');
$src_district    = $has_order_addr ? $order['order_addr_district'] : ($addr['addr_district'] ?? '');
$src_province    = $has_order_addr ? $order['order_addr_province'] : ($addr['addr_province'] ?? '');
$src_zipcode     = $has_order_addr ? $order['order_addr_zipcode'] : ($addr['addr_zipcode'] ?? '');

// ค่าดิบสำหรับ prefill โมดัลแก้ไขที่อยู่
$receipt_raw = [
    'addr_id'     => $has_order_addr ? 0 : ($addr['addr_id'] ?? 0), // ถ้าใช้ออเดอร์ ให้ส่ง 0 เพื่อไม่ให้ไปผูกกับ user_address
    'type'        => $src_type,
    'email'       => $order['user_email'] ?? '',
    'name'        => $src_name,
    'tax_id'      => $src_tax_id,
    'branch'      => $src_branch,
    'branch_name' => $src_branch_name,
    'phone'       => $src_phone,
    'detail'      => $src_detail,
    'subdistrict' => $src_subdistrict,
    'district'    => $src_district,
    'province'    => $src_province,
    'zipcode'     => $src_zipcode,
];

if ($has_order_addr || $addr) {
    $addr_text = trim(implode(' ', array_filter([
        $src_detail,
        $src_subdistrict,
        $src_district,
        $src_province,
        $src_zipcode,
    ])));
    $receipt = [
        'type'        => $type_label((string) $src_type),
        'tax_id'      => $src_tax_id ?: '-',
        'branch'      => $src_branch ?: '-',
        'branch_name' => $src_branch_name ?: '-',
        'name'        => $src_name ?: ($full !== '' ? $full : '-'),
        'address'     => $addr_text !== '' ? $addr_text : '-',
        'phone'       => $src_phone ?: ($order['user_phone'] ?: '-'),
    ];
}

// สรุป VAT (ราคารวมภาษีมูลค่าเพิ่ม 7%)
$total    = (float) ($order['total_price'] ?? 0);
$discount = (float) ($coupon_log['discount_amount'] ?? 0);
$subtotal = round($total + $discount, 2); // ราคาก่อนหักส่วนลด
$before   = round($total / 1.07, 2);      // ราคาก่อน VAT (หลังหักส่วนลดแล้ว)
$vat      = round($total - $before, 2);

// จัดการ slip_image กรณีที่ฐานข้อมูลเก็บ string แปลกๆ เช่น มีคำว่า "S3_URL: " นำหน้า
$slip_image_url = null;
if (!empty($order['slip_image'])) {
    $raw_slip = trim($order['slip_image']);
    // ลบ S3_URL: ออก
    $raw_slip = preg_replace('/^S3_URL:\s*/i', '', $raw_slip);
    // ดึงเฉพาะชื่อไฟล์ (Key) ออกจาก URL
    $s3_key = AwsS3::urlToKey($raw_slip);
    // สร้าง Presigned URL ด้วย Key นั้น (แก้ปัญหา NoSuchKey และ AccessDenied)
    $slip_image_url = AwsS3::getFileUrl($s3_key);
}

Response::json(1, 'Success', [
    'order'       => [
        'order_id'       => (int) $order['order_id'],
        'ref'            => $order['transaction_ref'] ?? '',
        'customer'       => $full !== '' ? $full : '-',
        'total'          => $total,
        'payment_status' => (string) ($order['payment_status'] ?? '0'),
        'payment_method' => (string) ($order['payment_method'] ?? ''),
        'created_at'     => $order['created_at'] ? date('d/m/Y H:i:s', strtotime($order['created_at'])) : '-',
        'user_id'        => (int) $order['user_id'],
        'internal_note'  => $order['order_internal_note'] ?? '',
        'slip_image'     => $slip_image_url,
        'user_citizen_id'=> $order['user_citizen_id'] ?? '',
    ],
    'receipt'     => $receipt,
    'receipt_raw' => $receipt_raw,
    'items'       => array_map(fn($x) => [
        'course_name' => $x['course_name'] ?? '-',
        'price'       => (float) ($x['price_at_purchase'] ?? 0),
    ], $items),
    'coupon'      => $coupon_log ? [
        'coupon_id'       => (int) $coupon_log['coupon_id'],
        'coupon_code'     => $coupon_log['coupon_code'] ?? '-',
        'coupon_detail'   => $coupon_log['coupon_detail'] ?? '-',
        'coupon_type'     => $coupon_log['coupon_type'] ?? '-',
        'coupon_no'       => $coupon_log['coupon_no'] ?? '-',
        'discount_amount' => $discount,
    ] : null,
    'summary'     => [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'before'   => $before,
        'vat'      => $vat,
        'total'    => $total,
    ],
]);
