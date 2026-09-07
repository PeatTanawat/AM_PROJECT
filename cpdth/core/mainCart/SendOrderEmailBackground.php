<?php
// Prevent script from aborting when client disconnects
ignore_user_abort(true);
set_time_limit(0);

// Flush buffers and close connection immediately if possible
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    while (ob_get_level() > 0) ob_end_clean();
    header("Connection: close");
    ob_start();
    echo "Processing";
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
}

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database\Connection;
use App\Utility\Email;

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$base_url = isset($_POST['base_url']) ? $_POST['base_url'] : '';

if ($order_id <= 0) {
    exit;
}

try {
    $db_instance = new Connection();
    $pdo = $db_instance->getPdo();

    // Fetch user and order details
    $stmt = $pdo->prepare("
        SELECT o.transaction_ref, u.user_firstname, u.user_lastname, u.user_email
        FROM tbl_orders o
        JOIN tbl_user u ON o.user_id = u.user_id
        WHERE o.order_id = :order_id
        LIMIT 1
    ");
    $stmt->execute([':order_id' => $order_id]);
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($orderData && !empty($orderData['user_email'])) {
        $transaction_ref = $orderData['transaction_ref'];
        $user_firstname = $orderData['user_firstname'];
        $user_lastname = $orderData['user_lastname'];
        $user_email = $orderData['user_email'];

        $view_order_url = $base_url . "/slip.php?id=" . $order_id;
        $email_subject = "ชำระเงินสำเร็จ - #" . $transaction_ref;
        
        $email_body = '
        <div style="font-family: \'Prompt\', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <div style="background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
                </div>
                <div style="padding: 30px 40px;">
                    <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">สวัสดี คุณ ' . htmlspecialchars($user_firstname . ' ' . $user_lastname) . '</h3>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                        เราได้รับการชำระเงินของคำสั่งซื้อหมายเลข <strong>#' . $transaction_ref . '</strong> ขณะนี้ระบบกำลังประมวลผลคำสั่งซื้อ เมื่อคอร์สเรียนของคุณพร้อมใช้งานจะได้รับอีเมลแจ้งเตือนอีกครั้ง
                    </p>
                    <div style="text-align: center; margin-bottom: 30px;">
                        <a href="' . $view_order_url . '" style="background-color: #1e293b; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 500; font-size: 0.95rem; display: inline-block;">ดูคำสั่งซื้อ</a>
                    </div>
                    <p style="color: #475569; font-size: 0.95rem; margin-bottom: 20px;">
                        ขอบคุณที่ใช้บริการ CPDTH
                    </p>
                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">
                    <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.5; margin: 0;">
                        หากไม่สามารถกดปุ่ม "ดูคำสั่งซื้อ" ได้ คุณสามารถคัดลอกลิงก์ไปวางในเว็บเบราว์เซอร์ของคุณได้: <br>
                        <a href="' . $view_order_url . '" style="color: #3b82f6; text-decoration: none;">' . $view_order_url . '</a>
                    </p>
                </div>
            </div>
        </div>';

        Email::send($user_email, $email_subject, $email_body);
    }
} catch (Exception $e) {
    // Log error if needed, but do not interrupt
    error_log("SendOrderEmailBackground Error: " . $e->getMessage());
}
