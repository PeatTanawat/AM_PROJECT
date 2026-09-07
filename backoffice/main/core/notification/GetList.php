<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Utility\Response;
use App\Database\Connection;

try {
    $db = (new Connection())->getPdo();

    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $stmtTotal = $db->query("SELECT COUNT(noti_id) FROM tbl_notifications");
    $total = $stmtTotal->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM tbl_notifications ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::json(1, 'Success', [
        'list' => $list,
        'total' => $total,
        'page' => $page,
        'per_page' => $limit
    ]);

} catch (\Exception $e) {
    Response::json(0, 'Error: ' . $e->getMessage(), null);
}
