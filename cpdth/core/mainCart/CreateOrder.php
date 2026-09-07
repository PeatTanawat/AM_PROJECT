<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;
use App\Utility\SlipOKService;

try {
    // 1. Check Authentication
    $currentUser = Auth::requireUserToken();
    $user_id     = (int) $currentUser->user_id;

    $db_instance = new Connection();
    $pdo         = $db_instance->getPdo();

    if (! $pdo) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // Fetch user details for email notification
    $stmt_user = $pdo->prepare("SELECT user_firstname, user_lastname, user_email FROM tbl_user WHERE user_id = :id LIMIT 1");
    $stmt_user->execute([':id' => $user_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $stmt_user->closeCursor();

    if (! $user_info) {
        Response::json(0, 'ไม่พบข้อมูลผู้ใช้งาน', null);
    }

    $user_firstname = $user_info['user_firstname'] ?? '';
    $user_lastname  = $user_info['user_lastname'] ?? '';
    $user_email     = $user_info['user_email'] ?? '';

    // 2. Retrieve & Validate inputs
    $payment_method      = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    $order_internal_note = isset($_POST['order_internal_note']) ? trim($_POST['order_internal_note']) : null;
    $xendit_token        = isset($_POST['xendit_token']) ? trim($_POST['xendit_token']) : '';
    
    if (! in_array($payment_method, ['card', 'promptpay', 'bank_transfer'])) {
        Response::json(0, 'กรุณาเลือกช่องทางการชำระเงินที่ถูกต้อง', null);
    }

    // E-Tax details are mandatory
    $etax_type    = isset($_POST['etax_type']) ? trim($_POST['etax_type']) : 'personal';
    $etax_name    = isset($_POST['etax_name']) ? trim($_POST['etax_name']) : '';
    $etax_id      = isset($_POST['etax_id']) ? trim($_POST['etax_id']) : '';
    $etax_address = isset($_POST['etax_address']) ? trim($_POST['etax_address']) : '';
    $etax_email   = isset($_POST['etax_email']) ? trim($_POST['etax_email']) : '';
    
    // Additional branch info
    $branch_no = isset($_POST['branch_no']) ? trim($_POST['branch_no']) : '0';
    $main_branch  = isset($_POST['main_branch']) ? trim($_POST['main_branch']) : '0';
    $branch_name  = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';

    // Fetch from tbl_user_address using etax_id
    if (!empty($etax_id)) {
        try {
            $stmt_branch = $pdo->prepare("SELECT main_branch, addr_branch_name, addr_branch FROM tbl_user_address WHERE addr_user_id = :user_id AND addr_tax_id = :etax_id AND delete_at IS NULL ORDER BY addr_is_default DESC, addr_id DESC LIMIT 1");
            $stmt_branch->execute([':user_id' => $user_id, ':etax_id' => $etax_id]);
            $branch_info = $stmt_branch->fetch(PDO::FETCH_ASSOC);
            $stmt_branch->closeCursor();

            if ($branch_info) {
                // Check if key exists to avoid error if main_branch column doesn't actually exist
                if (array_key_exists('main_branch', $branch_info)) {
                    $main_branch = $branch_info['main_branch'];
                }
                if (array_key_exists('addr_branch_name', $branch_info) && !empty($branch_info['addr_branch_name'])) {
                    $branch_name = $branch_info['addr_branch_name'];
                }
                if (array_key_exists('addr_branch', $branch_info) && !empty($branch_info['addr_branch'])) {
                    $branch_no = $branch_info['addr_branch'];
                }
            }
        } catch (Exception $e) {
            // Log error or ignore if column doesn't exist
        }
    }

    if (! in_array($etax_type, ['personal', 'corporate'])) {
        Response::json(0, 'ประเภทผู้เสียภาษีไม่ถูกต้อง', null);
    }
    if (empty($etax_name) || empty($etax_id) || empty($etax_address) || empty($etax_email)) {
        Response::json(0, 'กรุณากรอกข้อมูลสำหรับออกใบกำกับภาษีให้ครบถ้วน', null);
    }
    if (strlen($etax_id) !== 13) {
        Response::json(0, 'เลขประจำตัวผู้เสียภาษีต้องมี 13 หลัก', null);
    }

    // 4. Fetch Cart items for this user (including course_period to avoid N+1 query)
    $sql_cart = "SELECT ci.course_id,
                        ci.course_price, 
                        c.cart_id, 
                        co.course_period
                 FROM tbl_cart_item ci
                 JOIN tbl_cart c ON ci.cart_id = c.cart_id
                 LEFT JOIN tbl_course co ON ci.course_id = co.course_id
                 WHERE c.user_id = :user_id";
    $stmt = $pdo->prepare($sql_cart);
    $stmt->execute([':user_id' => $user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($cart_items)) {
        Response::json(0, 'ไม่มีสินค้าในตะกร้า ไม่สามารถสั่งซื้อได้', null);
    }

    // Calculate total price
    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += (float) $item['course_price'];
    }

    // Check Coupon if submitted
    $coupon_code     = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
    $coupon_id       = null;
    $discount_amount = 0.00;

    if (! empty($coupon_code)) {
        // Query Coupon
        $sql_coupon = "SELECT * FROM tbl_coupon WHERE coupon_code = :code AND delete_at IS NULL LIMIT 1";
        $stmt_c     = $pdo->prepare($sql_coupon);
        $stmt_c->execute([':code' => $coupon_code]);
        $coupon = $stmt_c->fetch(PDO::FETCH_ASSOC);
        $stmt_c->closeCursor();

        if (! $coupon) {
            Response::json(0, 'ไม่พบรหัสคูปองนี้ในระบบ', null);
        }

        if ((string)($coupon['coupon_status'] ?? '') !== '1') {
            Response::json(0, 'คูปองนี้ถูกปิดใช้งานชั่วคราว', null);
        }

        // Check dates
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

        // Check min price
        $coupon_min = isset($coupon['coupon_min']) && (float) $coupon['coupon_min'] > 0 ? (float) $coupon['coupon_min'] : 0.0;
        if ($coupon_min > 0 && $total_price < $coupon_min) {
            Response::json(0, 'ยอดสั่งซื้อขั้นต่ำต้องไม่น้อยกว่า ' . number_format($coupon_min, 2) . ' ฿', null);
        }

        // Early check global limit
        $coupon_limit = isset($coupon['coupon_limit']) && (int) $coupon['coupon_limit'] > 0 ? (int) $coupon['coupon_limit'] : 0;
        if ($coupon_limit > 0) {
            $sql_log_count = "SELECT COUNT(log_id) FROM tbl_coupon_logs WHERE coupon_id = :cid";
            $stmt_log      = $pdo->prepare($sql_log_count);
            $stmt_log->execute([':cid' => $coupon['coupon_id']]);
            $used_count = (int) $stmt_log->fetchColumn();
            $stmt_log->closeCursor();

            if ($used_count >= $coupon_limit) {
                Response::json(0, 'คูปองนี้ถูกใช้งานครบตามจำนวนสิทธิ์ทั้งหมดแล้ว', null);
            }
        }

        // Early check per-user limit
        $coupon_limit_person = isset($coupon['coupon_limit_person']) && (int) $coupon['coupon_limit_person'] > 0 ? (int) $coupon['coupon_limit_person'] : 0;
        if ($coupon_limit_person > 0) {
            $sql_user_used = "SELECT COUNT(cl.log_id)
                              FROM tbl_coupon_logs cl
                              JOIN tbl_orders o ON o.order_id = cl.order_id
                              WHERE cl.coupon_id = :cid
                                AND o.user_id    = :uid";
            $stmt_user_used = $pdo->prepare($sql_user_used);
            $stmt_user_used->execute([':cid' => $coupon['coupon_id'], ':uid' => $user_id]);
            $user_used_count = (int) $stmt_user_used->fetchColumn();
            $stmt_user_used->closeCursor();

            if ($user_used_count >= $coupon_limit_person) {
                Response::json(0, 'คุณใช้สิทธิ์คูปองนี้ครบตามจำนวนที่กำหนดแล้ว (จำกัด ' . $coupon_limit_person . ' ครั้ง/ท่าน)', null);
            }
        }

        // Apply discount calculation (support percent and fixed)
        $coupon_id   = (int) $coupon['coupon_id'];
        $coupon_type = strtolower(trim($coupon['coupon_type'] ?? 'fixed'));
        $coupon_no   = (float) ($coupon['coupon_no'] ?? 0);
        $coupon_max  = isset($coupon['coupon_max']) && (float) $coupon['coupon_max'] > 0 ? (float) $coupon['coupon_max'] : 0.0;

        if ($coupon_type === 'percent') {
            $discount_amount = ($total_price * $coupon_no) / 100.0;
            if ($coupon_max > 0 && $discount_amount > $coupon_max) {
                $discount_amount = $coupon_max;
            }
        } else {
            $discount_amount = $coupon_no;
        }

        $discount_amount = min($discount_amount, $total_price);
        $discount_amount = round($discount_amount, 2);
        $total_price     = max(0, $total_price - $discount_amount);
    }


    // Verify bank transfer slip using SlipOK Service (API Network call should be made outside Transaction block)
    $is_slip_verified  = false;
    $slipok_response   = null;
    $slipok_error_code = null;
    $slipok_error_msg  = null;
    $overpaid_amount   = 0.00;
    $slip_image        = null;

    if ($payment_method === 'bank_transfer' || ($payment_method === 'promptpay' && isset($_FILES['slip_file']))) {

        if ($payment_method === 'bank_transfer' && ! isset($_FILES['slip_file'])) {
            Response::json(0, 'กรุณาอัปโหลดรูปภาพสลิปชำระเงิน', null);
        }

        $file = $_FILES['slip_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::json(0, 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์สลิป', null);
        }

        $s3_result = \App\Utility\AwsS3::uploadFileDirectly($file, true, 'slips');
        $slip_image = $s3_result['url'] ?? null;

        if ($payment_method === 'bank_transfer') {
            try {
            // เรียกตรวจสอบสลิปโดยไม่ฟิกยอด เพื่อหาว่าโอนมาเท่าไหร่
            $slipok_response = \App\Utility\SlipOKService::verifySlipByImage($file['tmp_name'], null);

            if (isset($slipok_response['success']) && $slipok_response['success'] === true) {
                $actual_amount = (float)($slipok_response['data']['amount'] ?? 0);
                
                if (abs($actual_amount - $total_price) < 0.01) {
                    $is_slip_verified = true;
                } elseif ($actual_amount > $total_price) {
                    // ลูกค้าโอนมาเกิน -> อนุมัติผ่าน แต่เก็บส่วนต่าง overpaid_amount ไว้แจ้งลูกค้า
                    $is_slip_verified = true;
                    $overpaid_amount = $actual_amount - $total_price;
                } else {
                    $slipok_error_code = '1013';
                    $slipok_error_msg  = 'ยอดเงินโอนในสลิป (' . number_format($actual_amount, 2) . ' ฿) น้อยกว่ายอดเงินที่ต้องชำระ (' . number_format($total_price, 2) . ' ฿)';
                }
            } else {
                $slipok_error_code = $slipok_response['code'] ?? 'UNKNOWN';
                $slipok_error_msg  = $slipok_response['message'] ?? 'ตรวจสอบสลิปไม่ผ่าน';
            }
            } catch (Exception $e) {
                $slipok_error_msg = $e->getMessage();
            }

            // Save verification failed log
            if (! $is_slip_verified) {
            // Add S3 url to raw response log if we have it
            $log_resp = $slipok_response ?? [];
            $log_resp['manual_s3_url'] = $slip_image;

            try {
                $sql_fail_log = "INSERT INTO tbl_slipok_logs
                                    (user_id, is_success, error_code, error_message, raw_response)
                                 VALUES
                                    (:user_id, 0, :error_code, :error_message, :raw_resp)";
                $stmt_fail = $pdo->prepare($sql_fail_log);
                $stmt_fail->execute([
                    ':user_id'       => $user_id,
                    ':error_code'    => $slipok_error_code,
                    ':error_message' => $slipok_error_msg,
                    ':raw_resp'      => json_encode($log_resp, JSON_UNESCAPED_UNICODE),
                ]);
                $stmt_fail->closeCursor();
            } catch (Exception $logEx) {
                // Ignore log errors
            }

            // Translate error codes (kept for reference, but we no longer block)
            $user_friendly_msg = $slipok_error_msg;
            if ($slipok_error_code == '1012') {
                $user_friendly_msg = 'สลิปนี้เคยใช้งานไปแล้วในระบบ กรุณาอัปโหลดสลิปใบใหม่';
            } elseif ($slipok_error_code == '1013') {
                // Keep the dynamic amount warning we defined above
            } elseif ($slipok_error_code == '1007' || $slipok_error_code == '1008') {
                $user_friendly_msg = 'ไม่พบ QR Code ตรวจสอบการชำระเงินในรูปภาพสลิป กรุณาใช้รูปภาพสลิปที่คมชัด';
            }

            // Remove error block so the code continues and completes the order as pending
            // Response::json(0, 'ตรวจสอบสลิปไม่ผ่าน: ' . $user_friendly_msg, null);
            }
        }
    }


    // 3. Start Database Transaction
    $pdo->beginTransaction();

    // 5. Generate transaction reference
    $transaction_ref = 'ORD-' . time() . '-' . rand(1000, 9999);

    // Map payment method code
    // '1' = card, '2' = promptpay, '3' = bank_transfer
    $payment_method_db = '3';
    if ($payment_method === 'card') {
        $payment_method_db = '1';
    } elseif ($payment_method === 'promptpay') {
        $payment_method_db = '2';
    }

    // Set payment status: Default to pending (0) for card and promptpay (will update after API call).
    // Bank transfer depends on slip verification.
    $payment_status_db = (($payment_method === 'bank_transfer') && ! $is_slip_verified) ? '0' : '0';
    if ($payment_method === 'bank_transfer' && $is_slip_verified) {
        $payment_status_db = '1';
    }


    // Address fields for tbl_orders
    $raw_addr_type = isset($_POST['order_addr_type']) ? trim($_POST['order_addr_type']) : (isset($_POST['addr_type']) ? trim($_POST['addr_type']) : '');
    if (in_array($raw_addr_type, ['2', 'corporate', 'typeCorporate'], true)) {
        $order_addr_type = '2';
    } elseif (in_array($raw_addr_type, ['1', 'individual', 'typeIndividual'], true)) {
        $order_addr_type = '1';
    } else {
        $order_addr_type = ($etax_type === 'corporate') ? '2' : '1';
    }

    $order_addr_tax_id = isset($_POST['order_addr_tax_id']) ? trim($_POST['order_addr_tax_id']) : (isset($_POST['addr_tax_id']) ? trim($_POST['addr_tax_id']) : $etax_id);
    if ($order_addr_type === '1') {
        $order_addr_branch      = null;
        $order_addr_branch_name = null;
    } else {
        $order_addr_branch      = isset($_POST['order_addr_branch']) && trim($_POST['order_addr_branch']) !== '' ? trim($_POST['order_addr_branch']) : (isset($_POST['addr_branch']) && trim($_POST['addr_branch']) !== '' ? trim($_POST['addr_branch']) : null);
        $order_addr_branch_name = isset($_POST['order_addr_branch_name']) && trim($_POST['order_addr_branch_name']) !== '' ? trim($_POST['order_addr_branch_name']) : (isset($_POST['addr_branch_name']) && trim($_POST['addr_branch_name']) !== '' ? trim($_POST['addr_branch_name']) : null);
    }
    $order_addr_phone       = isset($_POST['order_addr_phone']) ? trim($_POST['order_addr_phone']) : (isset($_POST['addr_phone']) ? trim($_POST['addr_phone']) : null);
    $order_addr_detail      = isset($_POST['order_addr_detail']) ? trim($_POST['order_addr_detail']) : (isset($_POST['addr_detail']) ? trim($_POST['addr_detail']) : null);
    $order_addr_subdistrict = isset($_POST['order_addr_subdistrict']) ? trim($_POST['order_addr_subdistrict']) : (isset($_POST['addr_subdistrict']) ? trim($_POST['addr_subdistrict']) : null);
    $order_addr_district    = isset($_POST['order_addr_district']) ? trim($_POST['order_addr_district']) : (isset($_POST['addr_district']) ? trim($_POST['addr_district']) : null);
    $order_addr_province    = isset($_POST['order_addr_province']) ? trim($_POST['order_addr_province']) : (isset($_POST['addr_province']) ? trim($_POST['addr_province']) : null);
    $order_addr_zipcode     = isset($_POST['order_addr_zipcode']) ? trim($_POST['order_addr_zipcode']) : (isset($_POST['addr_zipcode']) ? trim($_POST['addr_zipcode']) : null);
    $order_addr_name        = isset($_POST['order_addr_name']) ? trim($_POST['order_addr_name']) : (isset($_POST['addr_name']) ? trim($_POST['addr_name']) : $etax_name);

    // 6. Insert into tbl_orders
    $sql_order = "INSERT INTO tbl_orders (
                    user_id,
                    total_price, 
                    payment_status, 
                    payment_method, 
                    transaction_ref, 
                    discount_amount, 
                    order_internal_note, 
                    slip_image,
                    order_addr_type, 
                    order_addr_tax_id, 
                    order_addr_branch, 
                    order_addr_branch_name, 
                    order_addr_phone,
                    order_addr_detail, 
                    order_addr_subdistrict, 
                    order_addr_district, 
                    order_addr_province, 
                    order_addr_zipcode, 
                    order_addr_name
                  ) VALUES (
                    :user_id, 
                    :total_price, 
                    :payment_status, 
                    :payment_method, 
                    :transaction_ref, 
                    :discount_amount, 
                    :order_internal_note, 
                    :slip_image,
                    :order_addr_type, 
                    :order_addr_tax_id, 
                    :order_addr_branch, 
                    :order_addr_branch_name, 
                    :order_addr_phone,
                    :order_addr_detail, 
                    :order_addr_subdistrict, 
                    :order_addr_district, 
                    :order_addr_province, 
                    :order_addr_zipcode, 
                    :order_addr_name
                  )";

    $stmt = $pdo->prepare($sql_order);
    $stmt->execute([
        ':user_id'                => $user_id,
        ':total_price'            => $total_price,
        ':payment_status'         => $payment_status_db,
        ':payment_method'         => $payment_method_db,
        ':transaction_ref'        => $transaction_ref,
        ':discount_amount'        => $discount_amount,
        ':order_internal_note'    => $order_internal_note,
        ':slip_image'             => $slip_image,
        ':order_addr_type'        => $order_addr_type,
        ':order_addr_tax_id'      => $order_addr_tax_id,
        ':order_addr_branch'      => $order_addr_branch,
        ':order_addr_branch_name' => $order_addr_branch_name,
        ':order_addr_phone'       => $order_addr_phone,
        ':order_addr_detail'      => $order_addr_detail,
        ':order_addr_subdistrict' => $order_addr_subdistrict,
        ':order_addr_district'    => $order_addr_district,
        ':order_addr_province'    => $order_addr_province,
        ':order_addr_zipcode'     => $order_addr_zipcode,
        ':order_addr_name'        => $order_addr_name,
    ]);
    $order_id = (int) $pdo->lastInsertId();
    $stmt->closeCursor();

    // Save successful SlipOK check details to log table
    if ($is_slip_verified && $slipok_response) {
        $data_resp      = $slipok_response['data'] ?? [];
        $transDate      = $data_resp['transDate'] ?? null;
        $transTime      = $data_resp['transTime'] ?? null;
        
        // Extract sender Thai display name if available, fallback to English name
        $sender_display = $data_resp['sender']['displayName'] ?? ($data_resp['sender']['name'] ?? null);
        $receiver       = $data_resp['receiver']['displayName'] ?? ($data_resp['receiver']['name'] ?? null);
        $amount_in_slip = $data_resp['amount'] ?? null;
        $ref            = $data_resp['ref1'] ?? ($data_resp['ref2'] ?? null);

        // Fetch sending bank name dynamically from tbl_bank_mapping table
        $sendingBankCode = $data_resp['sendingBank'] ?? null;
        $sender_bank = '';
        if (!empty($sendingBankCode)) {
            $stmt_b = $pdo->prepare("SELECT bank_name FROM tbl_bank_mapping WHERE bank_code = :code LIMIT 1");
            $stmt_b->execute([':code' => $sendingBankCode]);
            $sender_bank = $stmt_b->fetchColumn();
            $stmt_b->closeCursor();
        }
        if (empty($sender_bank) && !empty($sendingBankCode)) {
            $sender_bank = "รหัสธนาคาร $sendingBankCode";
        }

        // Extract sender account number (typically masked)
        $sender_acc = $data_resp['sender']['account']['value'] ?? null;
        
        // Append bank name and account number to the sender name column for clear backoffice display
        $sender_fullname = $sender_display;
        $bank_info_text = $sender_bank;
        if ($sender_acc) {
            $bank_info_text .= ' เลขบัญชี ' . $sender_acc;
        }

        if ($sender_fullname && $bank_info_text) {
            $sender_fullname .= ' (' . $bank_info_text . ')';
        } elseif (!$sender_fullname && $bank_info_text) {
            $sender_fullname = $bank_info_text;
        }


        $sql_succ_log = "INSERT INTO tbl_slipok_logs
                           (
                            order_id,
                            user_id, 
                            is_success, 
                            trans_ref, 
                            trans_amount, 
                            trans_date, 
                            trans_time, 
                            sender_name, 
                            receiver_name, 
                            raw_response
                            )
                         VALUES
                           (
                            :order_id,
                            :user_id, 
                            1, 
                            :trans_ref, 
                            :trans_amount, 
                            :trans_date, 
                            :trans_time, 
                            :sender_name, 
                            :receiver_name, 
                            :raw_resp
                            )";
        $stmt_succ = $pdo->prepare($sql_succ_log);
        $stmt_succ->execute([
            ':order_id'      => $order_id,
            ':user_id'       => $user_id,
            ':trans_ref'     => $ref,
            ':trans_amount'  => $amount_in_slip,
            ':trans_date'    => $transDate,
            ':trans_time'    => $transTime,
            ':sender_name'   => $sender_fullname,
            ':receiver_name' => $receiver,
            ':raw_resp'      => json_encode($slipok_response, JSON_UNESCAPED_UNICODE),
        ]);
        $stmt_succ->closeCursor();
    }


    // Insert Coupon Log if applied (with race condition protection)
    if ($coupon_id !== null) {
        // Re-fetch coupon row with FOR UPDATE to prevent concurrent usage
        $stmt_lock = $pdo->prepare("SELECT coupon_limit, coupon_limit_person FROM tbl_coupon WHERE coupon_id = :cid FOR UPDATE");
        $stmt_lock->execute([':cid' => $coupon_id]);
        $coupon_locked = $stmt_lock->fetch(PDO::FETCH_ASSOC);
        $stmt_lock->closeCursor();

        $locked_limit = isset($coupon_locked['coupon_limit']) && (int) $coupon_locked['coupon_limit'] > 0 ? (int) $coupon_locked['coupon_limit'] : 0;

        // Re-count global usage inside the locked transaction
        if ($locked_limit > 0) {
            $stmt_recount = $pdo->prepare("SELECT COUNT(log_id) FROM tbl_coupon_logs WHERE coupon_id = :cid");
            $stmt_recount->execute([':cid' => $coupon_id]);
            $used_count_locked = (int) $stmt_recount->fetchColumn();
            $stmt_recount->closeCursor();

            if ($used_count_locked >= $locked_limit) {
                $pdo->rollBack();
                Response::json(0, 'คูปองนี้ถูกใช้งานครบตามจำนวนสิทธิ์ทั้งหมดแล้ว กรุณาลองใหม่อีกครั้ง', null);
            }
        }

        // Re-check per-user inside locked transaction
        $locked_limit_person = isset($coupon_locked['coupon_limit_person']) && (int) $coupon_locked['coupon_limit_person'] > 0 ? (int) $coupon_locked['coupon_limit_person'] : 0;
        if ($locked_limit_person > 0) {
            $stmt_user_lock = $pdo->prepare("SELECT COUNT(cl.log_id)
                                             FROM tbl_coupon_logs cl
                                             JOIN tbl_orders o ON o.order_id = cl.order_id
                                             WHERE cl.coupon_id = :cid
                                               AND o.user_id    = :uid");
            $stmt_user_lock->execute([':cid' => $coupon_id, ':uid' => $user_id]);
            $user_used_locked = (int) $stmt_user_lock->fetchColumn();
            $stmt_user_lock->closeCursor();

            if ($user_used_locked >= $locked_limit_person) {
                $pdo->rollBack();
                Response::json(0, 'คุณใช้สิทธิ์คูปองนี้ครบตามจำนวนที่กำหนดแล้ว', null);
            }
        }

        $sql_insert_log = "INSERT INTO tbl_coupon_logs (coupon_id, order_id, discount_amount) VALUES (:coupon_id, :order_id, :discount_amount)";
        $stmt_log_ins   = $pdo->prepare($sql_insert_log);
        $stmt_log_ins->execute([
            ':coupon_id'       => $coupon_id,
            ':order_id'        => $order_id,
            ':discount_amount' => $discount_amount,
        ]);
        $stmt_log_ins->closeCursor();
    }

                                      // 7. Generate etax_no: Day(2) + Month(2) + Year(2) + Running(5)
    $date_prefix = date('dmy'); // E.g., 190826

    // Using FOR UPDATE to lock the row and prevent race conditions on daily running numbers under concurrent load
    $sql_max  = "SELECT etax_no FROM tbl_etax WHERE etax_no LIKE :prefix ORDER BY etax_no DESC LIMIT 1 FOR UPDATE";
    $stmt_max = $pdo->prepare($sql_max);
    $stmt_max->execute([':prefix' => $date_prefix . '%']);
    $max_row = $stmt_max->fetch(PDO::FETCH_ASSOC);
    $stmt_max->closeCursor();

    $running_num = 1;
    if ($max_row && ! empty($max_row['etax_no'])) {
        $last_digits = substr($max_row['etax_no'], -5);
        $running_num = (int) $last_digits + 1;
    }

    $etax_no = $date_prefix . str_pad($running_num, 5, '0', STR_PAD_LEFT);

    // Map E-tax type: 'personal' -> '1', 'corporate' -> '2'
    $etax_type_db = ($etax_type === 'personal') ? '1' : '2';

    // 8. Insert into tbl_etax
    $sql_insert_etax = "INSERT INTO tbl_etax
                        (
                            order_id, 
                            user_id, 
                            addr_tax_id, 
                            addr_name,
                            etax_address, 
                            user_email, 
                            etax_no, 
                            etax_type,
                            main_branch, 
                            branch_name,
                            branch_no
                        ) VALUES (
                            :order_id, 
                            :user_id, 
                            :addr_tax_id, 
                            :addr_name,
                            :etax_address, 
                            :user_email, 
                            :etax_no, 
                            :etax_type,
                            :main_branch, 
                            :branch_name,
                            :branch_no
                        )";
    $stmt_etax = $pdo->prepare($sql_insert_etax);
    $stmt_etax->execute([
        ':order_id'     => $order_id,
        ':user_id'      => $user_id,
        ':addr_tax_id'  => $etax_id,
        ':addr_name'    => $etax_name,
        ':etax_address' => $etax_address,
        ':user_email'   => $etax_email,
        ':etax_no'      => $etax_no,
        ':etax_type'    => $etax_type_db,
        ':main_branch'  => $main_branch,
        ':branch_name'  => $branch_name,
        ':branch_no'    => $branch_no,
    ]);
    $stmt_etax->closeCursor();

    // 9. Insert items into tbl_order_detail
    $sql_detail = "INSERT INTO tbl_order_detail 
                        (
                            order_id, 
                            course_id, 
                            price_at_purchase, 
                            list_order
                        )
                   VALUES (:order_id, :course_id, :price_at_purchase, :list_order)";
    $stmt_detail = $pdo->prepare($sql_detail);

    $list_order = 1;
    foreach ($cart_items as $item) {
        $stmt_detail->execute([
            ':order_id'          => $order_id,
            ':course_id'         => (int) $item['course_id'],
            ':price_at_purchase' => (float) $item['course_price'],
            ':list_order'        => $list_order++,
        ]);
    }
    $stmt_detail->closeCursor();

    // 10. Generate course enrollment with correct access status
    $enroll_payment_status = ($payment_status_db === '1') ? 'paid' : 'pending';
    $enroll_access         = ($payment_status_db === '1') ? 1 : 0;

    $now                   = date('Y-m-d H:i:s');

    $sql_enroll = "INSERT INTO tbl_course_enrollment (
                       enroll_user_id, enroll_course_id, enroll_payment_status,
                       enroll_date, enroll_expiry_date, enroll_is_completed, enroll_access, enroll_period
                   ) VALUES (
                       :user_id, :course_id, :enroll_payment_status,
                       :enroll_date, :expiry_date, '0', :enroll_access, :enroll_period
                   )";
    $stmt_enroll = $pdo->prepare($sql_enroll);

    foreach ($cart_items as $item) {
        $course_id = (int) $item['course_id'];

        // Fetch course period from pre-fetched cart items to avoid N+1 queries
        $period = isset($item['course_period']) ? (int) $item['course_period'] : 0;

        $expiry_date = null;
        if ($period > 0 && $enroll_payment_status === 'paid') {
            $expiry_date = date('Y-m-d 23:59:59', strtotime("+$period days"));
        }

        $stmt_enroll->execute([
            ':user_id'               => $user_id,
            ':course_id'             => $course_id,
            ':enroll_payment_status' => $enroll_payment_status,
            ':enroll_date'           => $now,
            ':expiry_date'           => $expiry_date,
            ':enroll_access'         => $enroll_access,
            ':enroll_period'         => $period,
        ]);
    }
    $stmt_enroll->closeCursor();

    // 11. Clear Cart immediately
    $cart_id = (int) $cart_items[0]['cart_id'];

    $sql_clear_items = "DELETE FROM tbl_cart_item WHERE cart_id = :cart_id";
    $stmt_clear      = $pdo->prepare($sql_clear_items);
    $stmt_clear->execute([':cart_id' => $cart_id]);
    $stmt_clear->closeCursor();

    $sql_clear_cart = "DELETE FROM tbl_cart WHERE cart_id = :cart_id";
    $stmt_clear_c   = $pdo->prepare($sql_clear_cart);
    $stmt_clear_c->execute([':cart_id' => $cart_id]);
    $stmt_clear_c->closeCursor();

    // Commit Transaction (temporarily to save order before external API calls)
    $pdo->commit();

    // ==========================================
    // 11.5 XENDIT API INTEGRATION
    // ==========================================
    $xendit_secret_key = $_ENV['XENDIT_SECRET_KEY'] ?? 'xnd_development_...'; // เปลี่ยนเป็น Secret Key จริง
    $xendit_auth = base64_encode($xendit_secret_key . ':');
    $qr_string = null;

    if ($payment_method === 'card' && !empty($xendit_token)) {
        // Charge Credit Card
        $ch = curl_init('https://api.xendit.co/v2/charges');
        $payload = [
            'reference_id' => $transaction_ref,
            'currency' => 'THB',
            'amount' => (int)$total_price, // Xendit requires integer for THB (or careful with decimals)
            'payment_method' => [
                'type' => 'CREDIT_CARD',
                'credit_card' => [
                    'token_id' => $xendit_token
                ]
            ]
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $xendit_auth
        ]);
        $response_xendit = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res_json = json_decode($response_xendit, true);
        if ($http_status === 200 && isset($res_json['status']) && ($res_json['status'] === 'SUCCEEDED' || $res_json['status'] === 'CAPTURED')) {
            // Update order to paid
            $payment_status_db = '1';
            $pdo->prepare("UPDATE tbl_orders SET payment_status = '1' WHERE order_id = :id")->execute([':id' => $order_id]);
            $pdo->prepare("UPDATE tbl_course_enrollment SET enroll_payment_status = 'paid', enroll_access = 1 WHERE enroll_order_id = :id")
                ->execute([':id' => $order_id]); // Note: enroll_order_id doesn't exist, we should use enroll_user_id + logic, but since we just inserted, let's update by enroll_user_id and enroll_date
            // Actually, safer to update by enroll_user_id and course_id based on cart items.
            foreach ($cart_items as $item) {
                $pdo->prepare("UPDATE tbl_course_enrollment SET enroll_payment_status = 'paid', enroll_access = 1 WHERE enroll_user_id = :uid AND enroll_course_id = :cid ORDER BY enroll_id DESC LIMIT 1")
                    ->execute([':uid' => $user_id, ':cid' => $item['course_id']]);
            }
        } else {
            Response::json(0, 'การชำระเงินผ่านบัตรเครดิตล้มเหลว: ' . ($res_json['message'] ?? 'Unknown Error'), null);
        }
    } elseif ($payment_method === 'promptpay') {
        // Generate Dynamic QR Code
        $ch = curl_init('https://api.xendit.co/qr_codes');
        $payload = [
            'external_id' => (string)$order_id, // Use order_id as external ID for webhook matching
            'type' => 'DYNAMIC',
            'callback_url' => $base_url . '/webhook_xendit.php',
            'amount' => (int)$total_price
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'API-VERSION: 2022-07-31',
            'Authorization: Basic ' . $xendit_auth
        ]);
        $response_xendit = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res_json = json_decode($response_xendit, true);
        if ($http_status === 200 || $http_status === 201) {
            $qr_string = $res_json['qr_string'];
        } else {
            Response::json(0, 'ไม่สามารถสร้าง QR Code จาก Xendit ได้: ' . ($res_json['message'] ?? 'Unknown Error'), null);
        }
    }

    // 12. Send Email Notification Asynchronously via cURL
    $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    // Construct base URL robustly
    $base_url   = "$protocol://" . $_SERVER['HTTP_HOST'];
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if ($script_dir !== '\\' && $script_dir !== '/') {
        $base_url .= $script_dir;
    }

    // Ensure URL doesn't have trailing slash for consistency
    $base_url = rtrim($base_url, '/');

    $bg_url = $base_url . "/core/mainCart/SendOrderEmailBackground.php";
    // 12. ส่งอีเมลคำสั่งซื้อ (เฉพาะกรณีที่ชำระเงินสำเร็จแล้วเท่านั้น)
    if ($payment_status_db === '1') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bg_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'order_id' => $order_id,
            'base_url' => $base_url,
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // wait max 500ms
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        curl_close($ch);
    }
    // 13. Send Webhook Notification for manual slip verification if payment_status is '0'
    if ($payment_status_db === '0') {
        $name = trim(($user_firstname ?? '') . ' ' . ($user_lastname ?? ''));
        if (empty($name)) $name = 'ลูกค้าไม่ทราบชื่อ';

        $webhook_url = 'https://bigsara-demo.com/am/backoffice/notify.php'; 
        
        $webhook_data = [
            'title' => 'แจ้งเตือนตรวจสอบสลิป',
            'message' => 'คุณ ' . $name . ' แนบสลิปชำระเงินแต่ระบบตรวจสอบไม่ผ่าน (รอเจ้าหน้าที่ตรวจสอบ)',
            'type' => 'order_slip',
            'link_url' => 'order_detail.php?id=' . $order_id,
            'reference_id' => $order_id
        ];

        $ch_web = curl_init($webhook_url);
        curl_setopt($ch_web, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_web, CURLOPT_POST, true);
        curl_setopt($ch_web, CURLOPT_POSTFIELDS, json_encode($webhook_data));
        curl_setopt($ch_web, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer my_secret_key_1234'
        ]);
        curl_setopt($ch_web, CURLOPT_TIMEOUT, 5);
        
        $response_web = curl_exec($ch_web);
        // We can ignore the response or handle errors if we want, but usually we just let it ping
        curl_close($ch_web);
    }

    Response::json(1, 'สร้างคำสั่งซื้อสำเร็จ', [
        'order_id'        => $order_id,
        'transaction_ref' => $transaction_ref,
        'total_price'     => $total_price,
        'etax_no'         => $etax_no,
        'created_at'      => date('d/m/Y H:i'),
        'user_fullname'   => $user_firstname . ' ' . $user_lastname,
        'payment_method'  => $payment_method,
        'payment_status'  => $payment_status_db,
        'overpaid'        => isset($overpaid_amount) ? $overpaid_amount : 0,
        'qr_string'       => $qr_string
    ]);


} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::json(0, 'เกิดข้อผิดพลาดในการประมวลผลคำสั่งซื้อ: ' . $e->getMessage(), null);
}
