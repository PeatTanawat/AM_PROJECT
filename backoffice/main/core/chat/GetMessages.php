<?php
// ข้อความทั้งหมดในห้องแชท 1 ห้อง (เรียงเก่า -> ใหม่) + ทำเครื่องหมายว่าอ่านข้อความของผู้เรียนแล้ว

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$room_id  = isset($_POST['room_id']) ? (int) $_POST['room_id'] : 0;
$after_id = isset($_POST['after_id']) ? (int) $_POST['after_id'] : 0;  // >0 = ดึงเฉพาะข้อความใหม่ (poll)
if ($room_id <= 0) {
    Response::json(0, 'ไม่พบห้องแชท', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // ทำเครื่องหมายว่าอ่านข้อความของผู้เรียน (sender_type='1') ในห้องนี้แล้ว
    $mark = $pdo_connect->prepare(
        "UPDATE tbl_chat_messages SET is_read = '1'
         WHERE room_id = :room AND sender_type = '1' AND is_read = '0' AND delete_at IS NULL"
    );
    $mark->bindValue(':room', $room_id, PDO::PARAM_INT);
    $mark->execute();
    $mark->closeCursor();

    // ข้อมูลผู้เรียนเจ้าของห้อง — ส่งเฉพาะตอนโหลดเต็ม (after_id=0) เพื่อลดภาระตอน poll
    $learner = null;
    if ($after_id <= 0) {
        $stmt_u = $pdo_connect->prepare(
            "SELECT c.user_id, u.user_firstname, u.user_lastname, u.user_email
             FROM tbl_chat_messages c
             LEFT JOIN tbl_user u ON u.user_id = c.user_id
             WHERE c.room_id = :room AND c.delete_at IS NULL
             ORDER BY c.messages_id ASC LIMIT 1"
        );
        $stmt_u->bindValue(':room', $room_id, PDO::PARAM_INT);
        $stmt_u->execute();
        $owner = $stmt_u->fetch(PDO::FETCH_ASSOC);
        $stmt_u->closeCursor();

        if (!$owner) {
            Response::json(0, 'ไม่พบข้อมูลห้องแชทนี้', null);
        }
        $full_name = trim(($owner['user_firstname'] ?? '') . ' ' . ($owner['user_lastname'] ?? ''));
        $learner = [
            'user_id'    => (int) $owner['user_id'],
            'name'       => $full_name !== '' ? $full_name : ('ผู้เรียน #' . $owner['user_id']),
            'user_email' => $owner['user_email'],
        ];
    }

    // ข้อความ — โหลดเต็ม หรือเฉพาะที่ใหม่กว่า after_id
    $sql = "SELECT messages_id, sender_type, message, created_at
            FROM tbl_chat_messages
            WHERE room_id = :room AND delete_at IS NULL";
    if ($after_id > 0) { $sql .= " AND messages_id > :after"; }
    $sql .= " ORDER BY messages_id ASC";
    $stmt = $pdo_connect->prepare($sql);
    $stmt->bindValue(':room', $room_id, PDO::PARAM_INT);
    if ($after_id > 0) { $stmt->bindValue(':after', $after_id, PDO::PARAM_INT); }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $messages = [];
    foreach ($rows as $r) {
        $messages[] = [
            'messages_id' => (int) $r['messages_id'],
            'is_admin'    => (string) $r['sender_type'] === '2',
            'message'     => $r['message'],
            'created_at'  => $r['created_at'],
        ];
    }

    Response::json(1, 'สำเร็จ', [
        'room_id'  => $room_id,
        'learner'  => $learner,     // null ตอน poll (after_id>0)
        'messages' => $messages,    // เฉพาะที่ใหม่กว่า after_id ตอน poll
    ]);

} catch (\Throwable $e) {
    error_log('GetMessages Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
