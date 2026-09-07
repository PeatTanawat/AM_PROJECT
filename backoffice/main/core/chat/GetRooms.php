<?php
// รายการห้องแชท (บทสนทนา) ของผู้เรียน — 1 แถวต่อ 1 ห้อง พร้อมข้อความล่าสุด + จำนวนที่ยังไม่อ่าน

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$search = trim((string) ($_POST['search'] ?? ''));

$where  = ["t.delete_at IS NULL"];
$params = [];
if ($search !== '') {
    $where[] = "(t.message LIKE :s1 OR u.user_firstname LIKE :s2 OR u.user_lastname LIKE :s3 OR u.user_email LIKE :s4)";
    $like = '%' . $search . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // ข้อความล่าสุดของแต่ละห้อง (join กับ subquery หา messages_id มากสุดต่อ room)
    $sql = "SELECT t.room_id, t.user_id, t.message AS last_message, t.created_at AS last_time,
                   t.sender_type AS last_sender_type,
                   u.user_firstname, u.user_lastname, u.user_email, u.delete_at AS user_delete_at, u.user_status,
                   (SELECT COUNT(*) FROM tbl_chat_messages x
                     WHERE x.room_id = t.room_id AND x.delete_at IS NULL
                       AND x.sender_type = '1' AND x.is_read = '0') AS unread
            FROM tbl_chat_messages t
            JOIN (SELECT room_id, MAX(messages_id) AS mid
                  FROM tbl_chat_messages WHERE delete_at IS NULL
                  GROUP BY room_id) g
              ON g.room_id = t.room_id AND g.mid = t.messages_id
            LEFT JOIN tbl_user u ON u.user_id = t.user_id
            $where_sql
            ORDER BY t.created_at DESC";
    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $list = [];
    $total_unread_rooms = 0;
    foreach ($rows as $row) {
        $full_name = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        
        $learner_name = $full_name !== '' ? $full_name : ('ผู้เรียน #' . $row['user_id']);
        if (!empty($row['user_delete_at']) || (isset($row['user_status']) && (string)$row['user_status'] !== '1')) {
            $learner_name .= ' (ปิดการใช้งาน)';
        }

        $unread = (int) $row['unread'];
        if ($unread > 0) { $total_unread_rooms++; }
        $list[] = [
            'room_id'      => (int) $row['room_id'],
            'user_id'      => (int) $row['user_id'],
            'learner'      => $learner_name,
            'user_email'   => $row['user_email'],
            'last_message' => $row['last_message'],
            'last_time'    => $row['last_time'],
            'last_by_admin' => (string) $row['last_sender_type'] === '2',
            'unread'       => $unread,
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'unread_rooms' => $total_unread_rooms]);

} catch (\Throwable $e) {
    error_log('GetRooms Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
