<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    // 1. Check Auth
    $currentUser = Auth::requireUserToken();
    $user_id = (int)$currentUser->user_id;

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    $course_id = isset($_POST['course_id']) ? (int)trim($_POST['course_id']) : 0;
    if ($course_id <= 0) {
        Response::json(0, 'รหัสคอร์สไม่ถูกต้อง', null);
    }


    // 4. Fetch Course to get price & verify status
    $sql_course = "SELECT course_id, course_price, course_promotion, course_status 
                   FROM tbl_course 
                   WHERE course_id = :course_id AND delete_at IS NULL LIMIT 1";
    $stmt = $pdo_connect->prepare($sql_course);
    $stmt->execute([':course_id' => $course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$course) {
        Response::json(0, 'ไม่พบคอร์สเรียนนี้', null);
    }
    if ($course['course_status'] != '1') {
        Response::json(0, 'คอร์สเรียนนี้ไม่เปิดให้บริการ', null);
    }

    $force_add = isset($_POST['force_add']) ? (int)$_POST['force_add'] : 0;
    
    if ($force_add !== 1) {
        $sql_check_owned = "
            SELECT d.course_id 
            FROM tbl_order_detail d
            JOIN tbl_orders o ON d.order_id = o.order_id
            WHERE o.user_id = :user_id 
              AND d.course_id = :course_id 
              AND YEAR(o.created_at) = YEAR(NOW())
            LIMIT 1
        ";
        $stmt = $pdo_connect->prepare($sql_check_owned);
        $stmt->execute([
            ':user_id' => $user_id,
            ':course_id' => $course_id
        ]);
        $owned = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($owned) {
            Response::json(2, 'ต้องการเพิ่มลงตะกร้าอีกครั้งหรือไม่?', [
                'course_id' => $course_id,
                'owned' => true
            ]);
        }
    }

    //  Get or Create Cart for the user
    $sql_cart = "SELECT cart_id FROM tbl_cart WHERE user_id = :user_id LIMIT 1";
    $stmt = $pdo_connect->prepare($sql_cart);
    $stmt->execute([':user_id' => $user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($cart) {
        $cart_id = (int)$cart['cart_id'];
    } else {
        $sql_insert_cart = "INSERT INTO tbl_cart (user_id, created_at, updated_at) VALUES (:user_id, NOW(), NOW())";
        $stmt = $pdo_connect->prepare($sql_insert_cart);
        $stmt->execute([':user_id' => $user_id]);
        $cart_id = (int)$pdo_connect->lastInsertId();
        $stmt->closeCursor();
    }

    //  เช็คว่ามี cart หรือยัง
    $sql_check_item = "SELECT item_id FROM tbl_cart_item WHERE cart_id = :cart_id AND course_id = :course_id LIMIT 1";
    $stmt = $pdo_connect->prepare($sql_check_item);
    $stmt->execute([
        ':cart_id' => $cart_id,
        ':course_id' => $course_id
    ]);
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($existing_item) {
        // เช็คซ้ำว่ามีดอร์สนี้หรือยัง
        $sql_count = "SELECT COUNT(item_id) FROM tbl_cart_item WHERE cart_id = :cart_id";
        $stmt = $pdo_connect->prepare($sql_count);
        $stmt->execute([':cart_id' => $cart_id]);
        $total_items = (int)$stmt->fetchColumn();
        $stmt->closeCursor();

        Response::json(0, 'คุณมีคอร์สเรียนนี้ในตะกร้าแล้ว ไม่สามารถเพิ่มซ้ำได้', [
            'cart_count' => $total_items
        ]);
    }

    //  + listorder
    $sql_order = "SELECT IFNULL(MAX(list_order), 0) + 1 FROM tbl_cart_item WHERE cart_id = :cart_id";
    $stmt = $pdo_connect->prepare($sql_order);
    $stmt->execute([':cart_id' => $cart_id]);
    $next_order = (int)$stmt->fetchColumn();
    $stmt->closeCursor();

    // Calculate final price (use promotion price if available and cheaper)
    $final_price = (float)$course['course_price'];
    $promo_price = isset($course['course_promotion']) ? (float)$course['course_promotion'] : 0;
    
    if ($promo_price > 0 && $promo_price < $final_price) {
        $final_price = $promo_price;
    }

    // เพิ่มสินค้าลงตะกร้า
    $sql_insert_item = "INSERT INTO tbl_cart_item (cart_id, course_id, added_at, list_order, course_price) 
                        VALUES (:cart_id, :course_id, NOW(), :list_order, :course_price)";
    $stmt = $pdo_connect->prepare($sql_insert_item);
    $stmt->execute([
        ':cart_id' => $cart_id,
        ':course_id' => $course_id,
        ':list_order' => $next_order,
        ':course_price' => $final_price
    ]);
    $stmt->closeCursor();

    // update เวลา
    $sql_update_cart = "UPDATE tbl_cart SET updated_at = NOW(), created_at = created_at WHERE cart_id = :cart_id";
    $stmt = $pdo_connect->prepare($sql_update_cart);
    $stmt->execute([':cart_id' => $cart_id]);
    $stmt->closeCursor();

    // นับจำนวนสินค้า
    $sql_count = "SELECT COUNT(item_id) FROM tbl_cart_item WHERE cart_id = :cart_id";
    $stmt = $pdo_connect->prepare($sql_count);
    $stmt->execute([':cart_id' => $cart_id]);
    $total_items = (int)$stmt->fetchColumn();
    $stmt->closeCursor();

    Response::json(1, 'เพิ่มคอร์สลงตะกร้าเรียบร้อยแล้ว', [
        'cart_count' => $total_items
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
