<?php
namespace App\Core\mainChat;

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

$currentUser = Auth::getUser();
if (!$currentUser) {
    echo Response::json(0, 'Unauthorized');
    exit;
}

$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message)) {
    echo Response::json(0, 'Message cannot be empty');
    exit;
}

try {
    $db = (new Connection())->getPdo();

    $user_id = $currentUser->user_id;
    $receiver_id = 1; // Default admin id
    $sender_type = '1'; // 2 for User, 1 for Admin
    $room_id = $user_id;

    $stmt = $db->prepare("
        INSERT INTO tbl_chat_messages 
        (user_id, sender_id, receiver_id, sender_type, room_id, message, is_read) 
        VALUES 
        (:user_id, :sender_id, :receiver_id, :sender_type, :room_id, :message, '0')
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':sender_id' => $user_id,
        ':receiver_id' => $receiver_id,
        ':sender_type' => $sender_type,
        ':room_id' => $room_id,
        ':message' => $message
    ]);

    echo Response::json(1, 'Message sent successfully');

} catch (\Exception $e) {
    error_log("Error in SendMessage: " . $e->getMessage());
    echo Response::json(0, 'Database error');
}
