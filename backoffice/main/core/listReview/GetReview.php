<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$review_id = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
if ($review_id <= 0) {
    Response::json(0, 'ไม่พบรหัสรีวิว', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$stmt = $pdo_connect->prepare(
    "SELECT r.review_id, r.user_id, r.reviewer_name, r.reviewer_image, r.rating, r.comment, r.created_at, r.is_approved,
            u.user_firstname, u.user_lastname, u.user_email
     FROM tbl_reviews r
     LEFT JOIN tbl_user u ON u.user_id = r.user_id
     WHERE r.review_id = :id
     LIMIT 1"
);
$stmt->execute([':id' => $review_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$review) {
    Response::json(0, 'ไม่พบรีวิวนี้', null);
}

Response::json(1, 'Success', ['review' => $review]);
