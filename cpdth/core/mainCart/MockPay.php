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
    $user_id = (int) $currentUser->user_id;

    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    if ($order_id <= 0) {
        Response::json(0, 'รหัสสั่งซื้อไม่ถูกต้อง', null);
    }

    $db_instance = new Connection();
    $pdo = $db_instance->getPdo();

    if (!$pdo) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // Start Database Transaction
    $pdo->beginTransaction();

    // Update order status to paid ('1')
    $sql_update = "UPDATE tbl_orders 
                   SET payment_status = '1' 
                   WHERE order_id = :order_id AND user_id = :user_id AND payment_status = '0'";
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);

    $affected = $stmt->rowCount();
    $stmt->closeCursor();

    if ($affected > 0) {
        // Fetch course details inside the order to insert enrollments
        $sql_items = "SELECT od.course_id, c.course_period 
                      FROM tbl_order_detail od
                      LEFT JOIN tbl_course c ON od.course_id = c.course_id
                      WHERE od.order_id = :order_id";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([':order_id' => $order_id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        $stmt_items->closeCursor();

        $now = date('Y-m-d H:i:s');

        // Insert into tbl_course_enrollment
        $sql_enroll = "INSERT INTO tbl_course_enrollment (
                           enroll_user_id, enroll_course_id, enroll_payment_status, 
                           enroll_date, enroll_expiry_date, enroll_is_completed
                       ) VALUES (
                           :user_id, :course_id, 'paid', 
                           :enroll_date, :expiry_date, '0'
                       )";
        $stmt_enroll = $pdo->prepare($sql_enroll);

        foreach ($items as $item) {
            $course_id = (int) $item['course_id'];
            $period = isset($item['course_period']) ? (int) $item['course_period'] : 0;

            $expiry_date = null;
            if ($period > 0) {
                $expiry_date = date('Y-m-d 23:59:59', strtotime("+$period days"));
            }

            $stmt_enroll->execute([
                ':user_id' => $user_id,
                ':course_id' => $course_id,
                ':enroll_date' => $now,
                ':expiry_date' => $expiry_date
            ]);
        }
        $stmt_enroll->closeCursor();

        // 4. Delete cart and cart items now that payment is successful
        $sql_cart = "SELECT cart_id FROM tbl_cart WHERE user_id = :user_id LIMIT 1";
        $stmt_cart = $pdo->prepare($sql_cart);
        $stmt_cart->execute([':user_id' => $user_id]);
        $cart = $stmt_cart->fetch(PDO::FETCH_ASSOC);
        $stmt_cart->closeCursor();

        if ($cart) {
            $cart_id = (int) $cart['cart_id'];

            $sql_clear_items = "DELETE FROM tbl_cart_item WHERE cart_id = :cart_id";
            $stmt_clear = $pdo->prepare($sql_clear_items);
            $stmt_clear->execute([':cart_id' => $cart_id]);
            $stmt_clear->closeCursor();

            $sql_clear_cart = "DELETE FROM tbl_cart WHERE cart_id = :cart_id";
            $stmt_clear_c = $pdo->prepare($sql_clear_cart);
            $stmt_clear_c->execute([':cart_id' => $cart_id]);
            $stmt_clear_c->closeCursor();
        }

        $pdo->commit();
        Response::json(1, 'ชำระเงินสำเร็จ (จำลอง)', null);
    } else {
        $pdo->rollBack();
        Response::json(0, 'ไม่พบออเดอร์ หรือออเดอร์นี้ชำระเงินเรียบร้อยแล้ว', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการจำลองการชำระเงิน: ' . $e->getMessage(), null);
}
