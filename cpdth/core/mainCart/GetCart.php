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
    $user_id = (int)$currentUser->user_id;

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    $sql = "SELECT ci.item_id, ci.course_id, ci.course_price, c.course_name, c.course_cover_image
            FROM tbl_cart_item ci
            JOIN tbl_cart cart ON ci.cart_id = cart.cart_id
            JOIN tbl_course c ON ci.course_id = c.course_id
            WHERE cart.user_id = :user_id AND c.delete_at IS NULL
            ORDER BY ci.list_order ASC";

    $stmt = $pdo_connect->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $total = 0;
    foreach ($items as &$item) {
        $total += (float)$item['course_price'];
        $item['course_price_fmt'] = number_format($item['course_price']);
        if ($item['course_cover_image']) {
            $item['course_cover_image'] = \App\Utility\AwsS3::getFileUrl($item['course_cover_image'], '+30 minutes', false);
        }
    }

    // Fetch active company banks
    $company_banks = [];
    try {
        $stmt_bank = $pdo_connect->prepare("
            SELECT cb.*, bm.bank_name, bm.bank_abbreviation, bm.logo_image
            FROM tbl_company_banks cb
            LEFT JOIN tbl_bank_mapping bm ON cb.bank_id = bm.bank_id
            WHERE cb.active_status = '1'
        ");
        $stmt_bank->execute();
        $company_banks = $stmt_bank->fetchAll(PDO::FETCH_ASSOC);
        $stmt_bank->closeCursor();

        // เรียงลำดับตาม ID จากน้อยไปมาก (เอา ID น้อยสุดขึ้นเป็นรายการแรก)
        usort($company_banks, function($a, $b) {
            $key = isset($a['company_bank_id']) ? 'company_bank_id' : (isset($a['id']) ? 'id' : (isset($a['bank_id']) ? 'bank_id' : ''));
            if ($key) {
                return (int)$a[$key] <=> (int)$b[$key];
            }
            return 0;
        });

        foreach ($company_banks as &$bank) {
            if (!empty($bank['logo_image'])) {
                $bank['logo_image_url'] = \App\Utility\AwsS3::getFileUrl($bank['logo_image'], '+30 minutes', false);
            } else {
                $bank['logo_image_url'] = '';
            }
        }
    } catch (Exception $bank_ex) {
        error_log('GetCart Bank Error: ' . $bank_ex->getMessage());
    }

    Response::json(1, 'Success', [
        'items' => $items,
        'total' => $total,
        'total_fmt' => number_format($total),
        'count' => count($items),
        'bank' => $company_banks
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
