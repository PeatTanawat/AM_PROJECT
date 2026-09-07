<?php
// รายการใบกำกับภาษี (E-Tax) — 1 ออเดอร์ที่ชำระแล้ว = 1 ใบกำกับภาษี
// ข้อมูลลูกค้า/ที่อยู่จาก tbl_user_address (ที่อยู่ default ของลูกค้า)
// เลขเอกสาร/สถานะ = ระบบเราออกเอง
// แบ่งหน้าฝั่ง server (LIMIT/OFFSET) + ค้นหา -> คืน JSON { list, total, page, per_page }
// หน้า etax นำไป render ผ่าน view/listEtax/ViewData.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
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

$search = trim((string) ($_POST['search'] ?? ''));   // ช่องค้นหา

// ดึงจาก tbl_etax โดยตรง
$joins = "FROM tbl_etax e";

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT_WS(' ',
                     e.addr_name, e.addr_tax_id, e.order_id, e.etax_no
                 ) COLLATE utf8mb4_unicode_ci LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $stmt_cnt = $pdo_connect->prepare("SELECT COUNT(*) $joins $where_sql");
    $stmt_cnt->execute($params);
    $total = (int) $stmt_cnt->fetchColumn();
    $stmt_cnt->closeCursor();

    $sql = "
        SELECT e.order_id, e.created_at, e.addr_name, e.addr_tax_id, e.etax_no
        $joins
        $where_sql
        ORDER BY e.created_at DESC, e.id DESC
        LIMIT :offset, :per_page
    ";
    
    $stmt = $pdo_connect->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $list = [];
    foreach ($rows as $r) {
        $ts   = $r['created_at'] ? strtotime($r['created_at']) : time();
        $name = trim((string) ($r['addr_name'] ?? ''));
        $tax  = trim((string) ($r['addr_tax_id'] ?? ''));
        
        $doc_no = !empty($r['etax_no']) ? $r['etax_no'] : ('ET' . date('dm', $ts) . str_pad((string) $r['order_id'], 7, '0', STR_PAD_LEFT));

        $list[] = [
            'order_id' => (int) $r['order_id'],
            'doc_no'   => $doc_no,
            'name'     => $name !== '' ? $name : '-',
            'tax_id'   => $tax !== '' ? $tax : '-',
            'date'     => date('d/m/', $ts) . (date('Y', $ts) + 543),
            'status'   => $tax !== '' ? '1' : '0', // 1=ออกสำเร็จ, 0=ออกไม่สำเร็จ
            'source'   => 'etax',
        ];
    }

    Response::json(1, 'สำเร็จ', ['list' => $list, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);

} catch (\Throwable $e) {
    error_log('GetListEtax Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
