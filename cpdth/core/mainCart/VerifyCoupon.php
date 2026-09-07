<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    // 1. Check Authentication
    $currentUser = Auth::requireUserToken();
    $user_id = (int)$currentUser->user_id;

    $coupon_code = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
    if (empty($coupon_code)) {
        Response::json(0, 'กรุณากรอกรหัสคูปอง', null);
    }

    $db_instance = new Connection();
    $pdo = $db_instance->getPdo();

    if (!$pdo) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 2. Query Coupon
    $sql_coupon = "SELECT * FROM tbl_coupon WHERE coupon_code = :code AND delete_at IS NULL LIMIT 1";
    $stmt = $pdo->prepare($sql_coupon);
    $stmt->execute([':code' => $coupon_code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$coupon) {
        Response::json(0, 'ไม่พบรหัสคูปองนี้ในระบบ', null);
    }

    if ((string)($coupon['coupon_status'] ?? '') !== '1') {
        Response::json(0, 'คูปองนี้ถูกปิดใช้งานชั่วคราว', null);
    }

    // 3. Check Start/End Dates
    if (!empty($coupon['coupon_start']) && $coupon['coupon_start'] !== '0000-00-00' && $coupon['coupon_start'] !== '0000-00-00 00:00:00') {
        $start_ts = strtotime(strlen($coupon['coupon_start']) <= 10 ? ($coupon['coupon_start'] . ' 00:00:00') : $coupon['coupon_start']);
        if ($start_ts && time() < $start_ts) {
            Response::json(0, 'คูปองนี้ยังไม่ถึงระยะเวลาเริ่มต้นใช้งาน', null);
        }
    }

    if (!empty($coupon['coupon_end']) && $coupon['coupon_end'] !== '0000-00-00' && $coupon['coupon_end'] !== '0000-00-00 00:00:00') {
        $end_ts = strtotime(strlen($coupon['coupon_end']) <= 10 ? ($coupon['coupon_end'] . ' 23:59:59') : $coupon['coupon_end']);
        if ($end_ts && time() > $end_ts) {
            Response::json(0, 'คูปองนี้หมดอายุการใช้งานแล้ว', null);
        }
    }

    // 4. Calculate Cart Total and verify against coupon_min
    $sql_cart = "SELECT ci.course_price 
                 FROM tbl_cart_item ci
                 JOIN tbl_cart c ON ci.cart_id = c.cart_id
                 WHERE c.user_id = :user_id";
    $stmt_cart = $pdo->prepare($sql_cart);
    $stmt_cart->execute([':user_id' => $user_id]);
    $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);
    $stmt_cart->closeCursor();

    if (empty($cart_items)) {
        Response::json(0, 'ไม่พบรายการสินค้าในตะกร้า', null);
    }

    $cart_total = 0.0;
    foreach ($cart_items as $item) {
        $cart_total += (float)$item['course_price'];
    }

    $coupon_min = isset($coupon['coupon_min']) && (float)$coupon['coupon_min'] > 0 ? (float)$coupon['coupon_min'] : 0.0;
    if ($coupon_min > 0 && $cart_total < $coupon_min) {
        Response::json(0, 'ยอดสั่งซื้อขั้นต่ำต้องไม่น้อยกว่า ' . number_format($coupon_min, 2) . ' ฿', null);
    }

    // 5. Check Global Usage Limit
    $coupon_limit = isset($coupon['coupon_limit']) && (int)$coupon['coupon_limit'] > 0 ? (int)$coupon['coupon_limit'] : 0;
    if ($coupon_limit > 0) {
        $sql_log_count = "SELECT COUNT(log_id) FROM tbl_coupon_logs WHERE coupon_id = :cid";
        $stmt_log = $pdo->prepare($sql_log_count);
        $stmt_log->execute([':cid' => $coupon['coupon_id']]);
        $used_count = (int)$stmt_log->fetchColumn();
        $stmt_log->closeCursor();

        if ($used_count >= $coupon_limit) {
            Response::json(0, 'คูปองนี้ถูกใช้งานครบตามจำนวนสิทธิ์ทั้งหมดแล้ว', null);
        }
    }

    // 6. Check Per-Person Usage Limit
    $coupon_limit_person = isset($coupon['coupon_limit_person']) && (int)$coupon['coupon_limit_person'] > 0 ? (int)$coupon['coupon_limit_person'] : 0;
    if ($coupon_limit_person > 0) {
        $sql_user_used = "SELECT COUNT(cl.log_id) 
                          FROM tbl_coupon_logs cl 
                          JOIN tbl_orders o ON o.order_id = cl.order_id 
                          WHERE cl.coupon_id = :cid 
                            AND o.user_id = :uid";
        $stmt_user = $pdo->prepare($sql_user_used);
        $stmt_user->execute([':cid' => $coupon['coupon_id'], ':uid' => $user_id]);
        $user_used_count = (int)$stmt_user->fetchColumn();
        $stmt_user->closeCursor();

        if ($user_used_count >= $coupon_limit_person) {
            Response::json(0, 'คุณใช้สิทธิ์คูปองนี้ครบตามจำนวนที่กำหนดแล้ว (จำกัด ' . $coupon_limit_person . ' ครั้ง/ท่าน)', null);
        }
    }

    // 7. Calculate Discount (Support percent & fixed, with coupon_max cap for percent)
    $coupon_type = strtolower(trim($coupon['coupon_type'] ?? 'fixed'));
    $coupon_no   = (float)($coupon['coupon_no'] ?? 0);
    $coupon_max  = isset($coupon['coupon_max']) && (float)$coupon['coupon_max'] > 0 ? (float)$coupon['coupon_max'] : 0.0;

    $discount_amount = 0.00;
    if ($coupon_type === 'percent') {
        $discount_amount = ($cart_total * $coupon_no) / 100.0;
        if ($coupon_max > 0 && $discount_amount > $coupon_max) {
            $discount_amount = $coupon_max;
        }
    } else {
        // fixed
        $discount_amount = $coupon_no;
    }

    // Discount cannot exceed cart total
    $discount_amount = min($discount_amount, $cart_total);
    $discount_amount = round($discount_amount, 2);

    $discount_display = ($coupon_type === 'percent') 
                        ? (rtrim(rtrim(number_format($coupon_no, 2), '0'), '.') . '%') 
                        : (number_format($discount_amount, 2) . ' ฿');

    // All checks passed
    Response::json(1, 'คูปองสามารถใช้งานได้', [
        'coupon_id'        => (int)$coupon['coupon_id'],
        'coupon_code'      => $coupon['coupon_code'],
        'coupon_type'      => $coupon_type,
        'coupon_no'        => $coupon_no,
        'coupon_max'       => $coupon_max,
        'discount_amount'  => $discount_amount,
        'discount_display' => $discount_display
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการตรวจสอบคูปอง: ' . $e->getMessage(), null);
}
