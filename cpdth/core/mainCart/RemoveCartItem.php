<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    $currentUser = Auth::getUser();
    if (!$currentUser) {
        Response::json(0, 'Unauthorized', null);
    }
    $user_id = (int) $currentUser->user_id;

    $item_id = isset($_POST['item_id']) ? (int) trim($_POST['item_id']) : 0;
    if ($item_id <= 0) {
        Response::json(0, 'รหัสรายการไม่ถูกต้อง', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // Verify item belongs to the user's cart
    $sql_check = "SELECT ci.item_id, ci.cart_id 
                  FROM tbl_cart_item ci
                  JOIN tbl_cart cart ON ci.cart_id = cart.cart_id
                  WHERE ci.item_id = :item_id AND cart.user_id = :user_id LIMIT 1";
    $stmt = $pdo_connect->prepare($sql_check);
    $stmt->execute([
        ':item_id' => $item_id,
        ':user_id' => $user_id
    ]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$item) {
        Response::json(0, 'ลบหลักสูตรสำเร็จแล้ว', null);
    }

    $cart_id = (int) $item['cart_id'];

    // Delete item
    $sql_delete = "DELETE FROM tbl_cart_item WHERE item_id = :item_id";
    $stmt = $pdo_connect->prepare($sql_delete);
    $stmt->execute([':item_id' => $item_id]);
    $stmt->closeCursor();

    // Update updated_at of tbl_cart (explicitly prevent created_at from changing if ON UPDATE is active)
    $sql_update_cart = "UPDATE tbl_cart SET updated_at = NOW(), created_at = created_at WHERE cart_id = :cart_id";
    $stmt = $pdo_connect->prepare($sql_update_cart);
    $stmt->execute([':cart_id' => $cart_id]);
    $stmt->closeCursor();

    // Fetch updated cart stats
    $sql_stats = "SELECT ci.item_id, ci.course_price, c.course_name, c.course_cover_image
                  FROM tbl_cart_item ci
                  JOIN tbl_course c ON ci.course_id = c.course_id
                  WHERE ci.cart_id = :cart_id AND c.delete_at IS NULL
                  ORDER BY ci.list_order ASC";
    $stmt = $pdo_connect->prepare($sql_stats);
    $stmt->execute([':cart_id' => $cart_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $total = 0;
    foreach ($items as &$item) {
        $total += (float) $item['course_price'];
        $item['course_price_fmt'] = number_format($item['course_price']);
        if ($item['course_cover_image']) {
            $item['course_cover_image'] = \App\Utility\AwsS3::getFileUrl($item['course_cover_image'], '+30 minutes', false);
        }
    }

    Response::json(1, 'ลบหลักสูตรสำเร็จแล้ว', [
        'items' => $items,
        'total' => $total,
        'total_fmt' => number_format($total),
        'count' => count($items)
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
