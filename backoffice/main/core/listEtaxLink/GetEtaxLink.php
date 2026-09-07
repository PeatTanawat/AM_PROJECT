<?php
// ดึงข้อมูลลิ้งค์ใบกำกับภาษี 1 รายการ + รายการสินค้า (สำหรับหน้า etax_link_view.php)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$link_id = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
if ($link_id <= 0) {
    Response::json(0, 'ไม่พบรหัสลิ้งค์', null);
}

$db_instance = new Connection();
$pdo = $db_instance->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, etax_no, customer_name, customer_type, customer_tax_id, customer_address,
                customer_email, addr_branch, addr_branch_name, doc_date, subtotal, vat_amount, grand_total,
                doc_status, link_status, public_token
         FROM tbl_etax_link WHERE id = :id AND delete_at IS NULL LIMIT 1"
    );
    $stmt->execute([':id' => $link_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    if (!$link) {
        Response::json(0, 'ไม่พบลิ้งค์ใบกำกับภาษีนี้', null);
    }

    $it = $pdo->prepare(
        "SELECT product_name, qty, price, discount, vat_type, amount_before, vat_amount, line_total
         FROM tbl_etax_link_item
         WHERE link_id = :id AND delete_at IS NULL
         ORDER BY list_order ASC, id ASC"
    );
    $it->execute([':id' => $link_id]);
    $items = $it->fetchAll(PDO::FETCH_ASSOC);
    $it->closeCursor();

    $type_label = $link['customer_type'] === '2' ? 'นิติบุคคล' : 'บุคคลธรรมดา';
    $ts = $link['doc_date'] ? strtotime($link['doc_date']) : time();

    Response::json(1, 'Success', [
        'link' => [
            'id'          => (int) $link['id'],
            'etax_no'     => (string) $link['etax_no'],
            'name'        => (string) $link['customer_name'],
            'type'        => $type_label,
            'tax_id'      => (string) $link['customer_tax_id'],
            'address'     => (string) ($link['customer_address'] ?? ''),
            'email'       => (string) ($link['customer_email'] ?? ''),
            'branch'      => (string) ($link['addr_branch'] ?? ''),
            'branch_name' => (string) ($link['addr_branch_name'] ?? ''),
            'doc_date'    => date('d/m/Y', $ts),
            'subtotal'    => (float) $link['subtotal'],
            'vat'         => (float) $link['vat_amount'],
            'total'       => (float) $link['grand_total'],
            'doc_status'  => (string) $link['doc_status'],
            'link_status' => (string) $link['link_status'],
            'token'       => (string) $link['public_token'],
        ],
        'items' => array_map(fn($x) => [
            'product_name' => (string) $x['product_name'],
            'qty'          => (float) $x['qty'],
            'price'        => (float) $x['price'],
            'discount'     => (float) $x['discount'],
            'vat_type'     => (string) $x['vat_type'],
            'amount_before' => (float) $x['amount_before'],
            'vat_amount'   => (float) $x['vat_amount'],
            'line_total'   => (float) $x['line_total'],
        ], $items),
    ]);
} catch (\Throwable $e) {
    error_log('GetEtaxLink Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo = null;
}
