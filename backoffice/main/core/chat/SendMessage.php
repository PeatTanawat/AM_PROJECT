<?php
// แอดมินตอบข้อความในห้องแชท (บันทึกเป็น sender_type = '2')

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$room_id = isset($_POST['room_id']) ? (int) $_POST['room_id'] : 0;
$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';

if ($room_id <= 0) {
    Response::json(0, 'ไม่พบห้องแชท', null);
}
if ($message === '') {
    Response::json(0, 'กรุณากรอกข้อความ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // หาผู้เรียนเจ้าของห้อง (receiver ของข้อความแอดมิน)
    $stmt_u = $pdo_connect->prepare(
        "SELECT user_id FROM tbl_chat_messages
         WHERE room_id = :room AND delete_at IS NULL
         ORDER BY messages_id ASC LIMIT 1"
    );
    $stmt_u->bindValue(':room', $room_id, PDO::PARAM_INT);
    $stmt_u->execute();
    $learner_id = (int) $stmt_u->fetchColumn();
    $stmt_u->closeCursor();

    if ($learner_id <= 0) {
        Response::json(0, 'ไม่พบข้อมูลห้องแชทนี้', null);
    }

    // แอดมินตอบ: sender_type='2', อ่านแล้ว (is_read='1' — เป็นข้อความของแอดมินเอง)
    $stmt = $pdo_connect->prepare(
        "INSERT INTO tbl_chat_messages
            (user_id, sender_id, receiver_id, sender_type, room_id, message, is_read)
         VALUES (:user_id, :sender, :receiver, '2', :room, :message, '1')"
    );
    $stmt->bindValue(':user_id', $learner_id, PDO::PARAM_INT);
    $stmt->bindValue(':sender', (int) $admin_id, PDO::PARAM_INT);
    $stmt->bindValue(':receiver', $learner_id, PDO::PARAM_INT);
    $stmt->bindValue(':room', $room_id, PDO::PARAM_INT);
    $stmt->bindValue(':message', $message);
    $stmt->execute();
    $new_id = (int) $pdo_connect->lastInsertId();
    $stmt->closeCursor();

    Response::json(1, 'ส่งข้อความสำเร็จ', ['messages_id' => $new_id]);

} catch (\Throwable $e) {
    error_log('SendMessage Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
