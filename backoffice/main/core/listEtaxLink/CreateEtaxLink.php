<?php
// สร้างลิ้งค์ออกใบกำกับภาษี (standalone): บันทึก header + รายการสินค้า + gen เลขใบกำกับ + public token
// VAT 3 แบบต่อรายการ: inc=รวมภาษี (ถอด /1.07), exc=แยกภาษี (+7%), none=ไม่มีภาษี

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$customer_name = trim((string) ($_POST['customer_name'] ?? ''));
$customer_type = ($_POST['customer_type'] ?? '1') === '2' ? '2' : '1';
$customer_tax  = trim((string) ($_POST['customer_tax_id'] ?? ''));
$customer_addr = trim((string) ($_POST['customer_address'] ?? ''));
$customer_mail = trim((string) ($_POST['customer_email'] ?? ''));
$addr_branch   = trim((string) ($_POST['addr_branch'] ?? ''));
$addr_branch_name = trim((string) ($_POST['addr_branch_name'] ?? ''));
$doc_date      = trim((string) ($_POST['doc_date'] ?? ''));
$items_json    = (string) ($_POST['items'] ?? '[]');

if ($customer_name === '' || $customer_tax === '' || $doc_date === '') {
    Response::json(0, 'กรุณากรอก ชื่อลูกค้า / เลขผู้เสียภาษี / วันที่ในเอกสาร', null);
}
// วันที่จาก input type=date = Y-m-d
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc_date) || strtotime($doc_date) === false) {
    Response::json(0, 'รูปแบบวันที่ไม่ถูกต้อง', null);
}
// เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก
if (!preg_match('/^\d{13}$/', $customer_tax)) {
    Response::json(0, 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก', null);
}
// อีเมล (ถ้ากรอก) ต้องอยู่ในรูปแบบที่ถูกต้อง
if ($customer_mail !== '' && !filter_var($customer_mail, FILTER_VALIDATE_EMAIL)) {
    Response::json(0, 'รูปแบบอีเมลไม่ถูกต้อง', null);
}

$items = json_decode($items_json, true);
if (!is_array($items) || count($items) === 0) {
    Response::json(0, 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ', null);
}

// คำนวณ VAT ต่อรายการ + ยอดรวม
$rows = [];
$subtotal = 0.0; $vat_total = 0.0; $grand = 0.0; $order = 0;
foreach ($items as $it) {
    $name = trim((string) ($it['product_name'] ?? ''));
    if ($name === '') { continue; }
    $qty   = (float) ($it['qty'] ?? 0);
    $price = (float) ($it['price'] ?? 0);
    $disc  = (float) ($it['discount'] ?? 0);
    $vt    = in_array(($it['vat_type'] ?? 'inc'), ['inc', 'exc', 'none'], true) ? $it['vat_type'] : 'inc';

    $line_amount = round($qty * $price - $disc, 2);
    if ($line_amount < 0) { $line_amount = 0.0; }

    if ($vt === 'inc') {            // ราคารวม VAT แล้ว
        $before = round($line_amount / 1.07, 2);
        $v      = round($line_amount - $before, 2);
        $ltot   = $line_amount;
    } elseif ($vt === 'exc') {      // ราคายังไม่รวม VAT
        $before = $line_amount;
        $v      = round($line_amount * 0.07, 2);
        $ltot   = round($before + $v, 2);
    } else {                        // ไม่มีภาษี
        $before = $line_amount;
        $v      = 0.0;
        $ltot   = $line_amount;
    }

    $subtotal += $before; $vat_total += $v; $grand += $ltot;
    $rows[] = [
        'name' => $name, 'qty' => $qty, 'price' => $price, 'disc' => $disc, 'vt' => $vt,
        'before' => $before, 'vat' => $v, 'ltot' => $ltot, 'ord' => ++$order,
    ];
}
if (count($rows) === 0) {
    Response::json(0, 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ', null);
}
$subtotal = round($subtotal, 2); $vat_total = round($vat_total, 2); $grand = round($grand, 2);

$db_instance = new Connection();
$pdo = $db_instance->getPdo();
if (!$pdo) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $pdo->beginTransaction();

    // เลขใบกำกับภาษี: ET + dmy(วันที่สร้าง) + running 5 หลัก (unique ข้าม tbl_etax + tbl_etax_link)
    $prefix = 'ET' . date('dmy');
    $q = $pdo->prepare(
        "SELECT MAX(CAST(RIGHT(etax_no, 5) AS UNSIGNED)) AS mx FROM (
            SELECT etax_no FROM tbl_etax WHERE etax_no LIKE :p1
            UNION ALL
            SELECT etax_no FROM tbl_etax_link WHERE etax_no LIKE :p2
         ) t"
    );
    $q->execute([':p1' => $prefix . '%', ':p2' => $prefix . '%']);
    $mx = (int) $q->fetchColumn();
    $q->closeCursor();
    $etax_no = $prefix . str_pad((string) ($mx + 1), 5, '0', STR_PAD_LEFT);

    $token = bin2hex(random_bytes(32)); // 64 hex chars

    $ins = $pdo->prepare(
        "INSERT INTO tbl_etax_link
            (etax_no, customer_name, customer_type, customer_tax_id, customer_address, customer_email,
             addr_branch, addr_branch_name,
             doc_date, subtotal, vat_amount, grand_total, doc_status, link_status, public_token, created_by)
         VALUES
            (:etax_no, :name, :type, :tax, :addr, :mail,
             :branch, :branch_name,
             :doc_date, :subtotal, :vat, :grand, '1', '1', :token, :by)"
    );
    $ins->execute([
        ':etax_no'  => $etax_no,
        ':name'     => $customer_name,
        ':type'     => $customer_type,
        ':tax'      => $customer_tax,
        ':addr'     => $customer_addr !== '' ? $customer_addr : null,
        ':mail'     => $customer_mail !== '' ? $customer_mail : null,
        ':branch'   => $addr_branch !== '' ? $addr_branch : null,
        ':branch_name' => $addr_branch_name !== '' ? $addr_branch_name : null,
        ':doc_date' => $doc_date,
        ':subtotal' => $subtotal,
        ':vat'      => $vat_total,
        ':grand'    => $grand,
        ':token'    => $token,
        ':by'       => (int) $admin_id,
    ]);
    $link_id = (int) $pdo->lastInsertId();

    $insIt = $pdo->prepare(
        "INSERT INTO tbl_etax_link_item
            (link_id, product_name, qty, price, discount, vat_type, amount_before, vat_amount, line_total, list_order)
         VALUES
            (:lid, :name, :qty, :price, :disc, :vt, :before, :vat, :ltot, :ord)"
    );
    foreach ($rows as $r) {
        $insIt->execute([
            ':lid'    => $link_id,
            ':name'   => $r['name'],
            ':qty'    => $r['qty'],
            ':price'  => $r['price'],
            ':disc'   => $r['disc'],
            ':vt'     => $r['vt'],
            ':before' => $r['before'],
            ':vat'    => $r['vat'],
            ':ltot'   => $r['ltot'],
            ':ord'    => $r['ord'],
        ]);
    }
    $insIt->closeCursor();

    $pdo->commit();

    Response::json(1, 'สร้างลิ้งค์ออกใบกำกับภาษีสำเร็จ', [
        'link_id'  => $link_id,
        'link_key' => \App\Utility\Cipher::encrypt($link_id),
        'etax_no'  => $etax_no,
        'token'    => $token,
    ]);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('CreateEtaxLink Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo = null;
}
