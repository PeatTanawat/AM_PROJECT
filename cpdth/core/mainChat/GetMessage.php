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

try {
    $db = (new Connection())->getPdo();

    $user_id = $currentUser->user_id;

    $stmt = $db->prepare("
        SELECT * FROM tbl_chat_messages 
        WHERE room_id = :room_id AND delete_at IS NULL
        ORDER BY created_at ASC
    ");

    $stmt->execute([':room_id' => $user_id]);
    $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        // Based on user's SendMessage logic, they used sender_type '1' or '2'.
        // Or we can just check if sender_id == current user id.
        $msg['is_mine'] = ($msg['sender_id'] == $user_id);
    }

    echo Response::json(1, 'Success', $messages);

} catch (\Exception $e) {
    error_log("Error in GetMessage: " . $e->getMessage());
    echo Response::json(0, 'Database error');
}
