<?php
// รีวิวจากลูกค้า (หน้าเว็บ) — ดึงรายการแบบ custom table (แบ่งหน้าฝั่ง server)
// คืน JSON { list, total, page, per_page } -> reviews.php นำไป render ผ่าน view/listReview/GetTable.php

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

$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$search = trim((string) ($_POST['search'] ?? ''));
$status = trim((string) ($_POST['status'] ?? '')); // '' = ทั้งหมด, '1' = แสดงผล, '0' = ซ่อน

$where  = ["u.delete_at IS NULL"];
$params = [];
if ($search !== '') {
    // ใช้ placeholder แยกชื่อกันแต่ละจุด (PDO::ATTR_EMULATE_PREPARES=false ของโปรเจกต์นี้
    // ไม่รองรับ named placeholder ซ้ำหลายจุดในคิวรีเดียว -> SQLSTATE[HY093])
    $where[] = "(r.comment LIKE :search1 OR u.user_firstname LIKE :search2 OR u.user_lastname LIKE :search3 OR u.user_email LIKE :search4 OR r.reviewer_name LIKE :search5)";
    $like = '%' . $search . '%';
    $params[':search1'] = $like;
    $params[':search2'] = $like;
    $params[':search3'] = $like;
    $params[':search4'] = $like;
    $params[':search5'] = $like;
}
if ($status === '0' || $status === '1') {
    $where[] = "r.is_approved = :status";
    $params[':status'] = $status;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // ภาพรวมคะแนน (ทุกรีวิว ไม่ขึ้นกับตัวกรองค้นหา/สถานะ — สรุปภาพรวมทั้งหมดเสมอ)
    $stmt_stats = $pdo_connect->query(
        "SELECT r.rating, COUNT(*) AS c
         FROM tbl_reviews r
         LEFT JOIN tbl_user u ON u.user_id = r.user_id
         WHERE u.delete_at IS NULL
         GROUP BY r.rating"
    );
    $rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $stats_total = 0;
    $rating_sum = 0;
    foreach ($stmt_stats->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $star = (int) $r['rating'];
        $cnt  = (int) $r['c'];
        if ($star >= 1 && $star <= 5) {
            $rating_counts[$star] = $cnt;
            $stats_total += $cnt;
            $rating_sum  += $star * $cnt;
        }
    }
    $stmt_stats->closeCursor();

    $breakdown = [];
    foreach ([5, 4, 3, 2, 1] as $star) {
        $cnt = $rating_counts[$star];
        $breakdown[] = [
            'star'    => $star,
            'count'   => $cnt,
            'percent' => $stats_total > 0 ? round($cnt / $stats_total * 100, 1) : 0,
        ];
    }
    $stats = [
        'total'     => $stats_total,
        'average'   => $stats_total > 0 ? round($rating_sum / $stats_total, 2) : 0,
        'breakdown' => $breakdown,
    ];

    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) FROM tbl_reviews r LEFT JOIN tbl_user u ON u.user_id = r.user_id $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    // ข้อมูลหน้าปัจจุบัน
    $sql = "SELECT r.review_id, r.user_id, r.reviewer_name, r.rating, r.comment, r.created_at, r.is_approved,
                   u.user_firstname, u.user_lastname, u.user_email
            FROM tbl_reviews r
            LEFT JOIN tbl_user u ON u.user_id = r.user_id
            $where_sql
            ORDER BY r.created_at DESC, r.review_id DESC
            LIMIT :offset, :per_page";
    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $list = [];
    foreach ($rows as $row) {
        $full_name = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        $list[] = [
            'review_id'   => $row['review_id'],
            'reviewer'    => $full_name !== '' ? $full_name : (trim((string) ($row['reviewer_name'] ?? '')) !== '' ? $row['reviewer_name'] : '-'),
            'user_email'  => $row['user_email'],
            'rating'      => $row['rating'],
            'comment'     => $row['comment'],
            'created_at'  => $row['created_at'],
            'is_approved' => $row['is_approved'],
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'stats' => $stats]);

} catch (\Throwable $e) {
    error_log('GetListReview Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
