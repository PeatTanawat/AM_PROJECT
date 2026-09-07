<?php
// นับจำนวนสำหรับ badge ข้างเมนู sidebar (ให้ check.js เรียกเป็นระยะเพื่ออัปเดตแบบสด)
//  - order_pending  = คำสั่งซื้อรอยืนยันการโอนเงิน
//  - verify_pending = คำขอยืนยันตัวตนที่รอตรวจ
//  - chat_unread    = ข้อความจากผู้เรียนที่ยังไม่อ่าน
// (คิวรีตรงกับ sidebar.php เพื่อให้ค่าเริ่มต้นตอนโหลดหน้ากับตอน poll ตรงกัน)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
if (!($access_token->user_id ?? null)) {
    Response::json(0, 'Unauthorized', null);
}

$pdo = (new Connection())->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// นับแบบกันพัง: ตารางไม่มี/คิวรีพลาด -> คืน 0 (ไม่ทำให้ทั้ง response ล้ม)
$count = function (string $sql) use ($pdo): int {
    try {
        $st = $pdo->query($sql);
        $n = (int) $st->fetchColumn();
        $st->closeCursor();
        return $n;
    } catch (\Throwable $e) {
        return 0;
    }
};

Response::json(1, 'Success', [
    'order_pending'  => $count("SELECT COUNT(*) FROM tbl_orders WHERE payment_status = '0' AND payment_method = '3'"),
    'verify_pending' => $count("SELECT COUNT(*) FROM tbl_user WHERE delete_at IS NULL AND identity_verified = '1'"),
    'chat_unread'    => $count("SELECT COUNT(*) FROM tbl_chat_messages WHERE delete_at IS NULL AND sender_type = '1' AND is_read = '0'"),
    'exam_result'    => $count("SELECT COUNT(*) 
                                FROM tbl_exam_attempt a 
                                LEFT JOIN tbl_course_enrollment b ON a.attempt_enroll_id = b.enroll_id 
                                WHERE a.attempt_pass = '1'
                                AND b.enroll_is_completed = '0'                               
                                "),
]);
