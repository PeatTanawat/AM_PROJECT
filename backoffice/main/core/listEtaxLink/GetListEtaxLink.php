<?php
// รายการลิ้งค์ออกใบกำกับภาษี (สำหรับหน้า etax_link.php) — custom table แบ่งหน้าฝั่ง server
// คืน JSON { list, total, page, per_page } -> หน้า etax_link นำไป render ผ่าน view/listEtaxLink/ViewData.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo = $db_instance->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$f_doc_status  = trim((string) ($_POST['f_doc_status'] ?? ''));   // 1=ออกใบกำกับแล้ว 2=ยกเลิก
$f_link_status = trim((string) ($_POST['f_link_status'] ?? ''));  // 1=ใช้งานได้ 0=ปิดใช้งาน
$search        = trim((string) ($_POST['search'] ?? ''));         // เลขใบกำกับ / ชื่อลูกค้า

$where  = ["l.delete_at IS NULL"];
$params = [];
if ($f_doc_status === '1' || $f_doc_status === '2')  { $where[] = "l.doc_status = :f_doc_status"; $params[':f_doc_status'] = $f_doc_status; }
if ($f_link_status === '1' || $f_link_status === '0') { $where[] = "l.link_status = :f_link_status"; $params[':f_link_status'] = $f_link_status; }
if ($search !== '') {
    $where[] = "(CONCAT_WS(' ', l.etax_no, l.customer_name) LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    // จำนวนทั้งหมดหลังกรอง
    $stmt_cnt = $pdo->prepare("SELECT COUNT(*) FROM tbl_etax_link l $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    $sql = "SELECT l.id, l.etax_no, l.customer_name, l.doc_date, l.doc_status, l.link_status,
                   l.public_token, l.grand_total,
                   (SELECT product_name FROM tbl_etax_link_item
                     WHERE link_id = l.id AND delete_at IS NULL
                     ORDER BY list_order ASC, id ASC LIMIT 1) AS first_item,
                   (SELECT COUNT(*) FROM tbl_etax_link_item
                     WHERE link_id = l.id AND delete_at IS NULL) AS item_count
            FROM tbl_etax_link l
            $where_sql
            ORDER BY l.id DESC
            LIMIT :offset, :per_page";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $list = [];
    foreach ($rows as $r) {
        $first = trim((string) ($r['first_item'] ?? ''));
        $cnt   = (int) ($r['item_count'] ?? 0);
        $items_label = $first !== '' ? $first : '-';
        if ($cnt > 1) { $items_label .= ' (+' . ($cnt - 1) . ' รายการ)'; }

        $ts = $r['doc_date'] ? strtotime($r['doc_date']) : time();
        $date = date('d/m/', $ts) . (date('Y', $ts) + 543);

        $list[] = [
            'id'          => (int) $r['id'],
            'etax_no'     => (string) $r['etax_no'],
            'customer'    => (string) ($r['customer_name'] !== '' ? $r['customer_name'] : '-'),
            'items'       => $items_label,
            'date'        => $date,
            'doc_status'  => (string) ($r['doc_status'] ?? '1'),
            'link_status' => (string) ($r['link_status'] ?? '1'),
            'token'       => (string) $r['public_token'],
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);
} catch (\Throwable $e) {
    error_log('GetListEtaxLink Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo = null;
}
