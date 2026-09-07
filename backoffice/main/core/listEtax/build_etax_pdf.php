<?php
// ฟังก์ชันกลางสร้าง PDF ใบเสร็จรับเงิน/ใบกำกับภาษี จาก order_id
// ใช้ร่วมกันระหว่าง ExportEtax.php (เปิด/ดาวน์โหลด) และ SendEtaxEmail.php (แนบไฟล์)

use App\Utility\Pdf;

if (!function_exists('pdf_baht_text')) {
    // แปลงจำนวนเงินเป็นข้อความภาษาไทย
    function pdf_baht_text(float $amount): string
    {
        $amount = round($amount, 2);
        $thai = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $unit = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        $convert = function (string $s) use ($thai, $unit) {
            $r = ''; $len = strlen($s);
            for ($i = 0; $i < $len; $i++) {
                $d = (int) $s[$i];
                $pos = $len - $i;
                $u = ($pos - 1) % 6;
                if ($d !== 0) {
                    if ($u === 1 && $d === 1)        { $r .= 'สิบ'; }
                    elseif ($u === 1 && $d === 2)    { $r .= 'ยี่สิบ'; }
                    elseif ($u === 0 && $d === 1 && $len > 1) { $r .= 'เอ็ด'; }
                    else                             { $r .= $thai[$d] . $unit[$u]; }
                }
                if ($pos > 1 && ($pos - 1) % 6 === 0) { $r .= 'ล้าน'; }
            }
            return $r;
        };
        $t = explode('.', number_format($amount, 2, '.', ''));
        $baht = $t[0]; $satang = $t[1];
        $txt = ($baht === '0') ? 'ศูนย์บาท' : $convert($baht) . 'บาท';
        $txt .= ($satang === '00') ? 'ถ้วน' : $convert($satang) . 'สตางค์';
        return '(' . $txt . ')';
    }
}

if (!function_exists('build_etax_pdf')) {
    /**
     * สร้าง PDF ใบกำกับภาษีจาก order_id
     * @return array ['ok'=>bool, 'msg'=>string, 'pdf'=>string(binary), 'doc_no'=>string]
     */
    function build_etax_pdf(\PDO $pdo, int $order_id): array
    {
        $stmt = $pdo->prepare(
            "SELECT o.order_id, o.user_id, o.total_price, o.payment_method, o.created_at,
                    o.order_addr_type, o.order_addr_name, o.order_addr_tax_id, o.order_addr_branch, o.order_addr_branch_name, 
                    o.order_addr_phone, o.order_addr_detail, o.order_addr_subdistrict, o.order_addr_district, 
                    o.order_addr_province, o.order_addr_zipcode,
                    u.user_firstname, u.user_lastname, u.user_phone, u.user_citizen_id
             FROM tbl_orders o
             LEFT JOIN tbl_user u ON o.user_id = u.user_id
             WHERE o.order_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$order) {
            return ['ok' => false, 'msg' => 'ไม่พบคำสั่งซื้อนี้', 'pdf' => '', 'doc_no' => ''];
        }

        $it = $pdo->prepare(
            "SELECT od.price_at_purchase, c.course_name
             FROM tbl_order_detail od
             LEFT JOIN tbl_course c ON c.course_id = od.course_id
             WHERE od.order_id = :id
             ORDER BY od.list_order ASC, od.detail_id ASC"
        );
        $it->execute([':id' => $order_id]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC);
        $it->closeCursor();

        // คูปองส่วนลด (ถ้ามี)
        $cl = $pdo->prepare(
            "SELECT cl.discount_amount
             FROM tbl_coupon_logs cl
             WHERE cl.order_id = :id LIMIT 1"
        );
        $cl->execute([':id' => $order_id]);
        $coupon_log = $cl->fetch(PDO::FETCH_ASSOC) ?: null;
        $cl->closeCursor();

        $ad = $pdo->prepare(
            "SELECT addr_type, addr_name, addr_tax_id, addr_branch, addr_branch_name, addr_phone, addr_detail,
                    addr_subdistrict, addr_district, addr_province, addr_zipcode
             FROM tbl_user_address
             WHERE addr_user_id = :uid AND delete_at IS NULL
             ORDER BY addr_is_default DESC, addr_id DESC LIMIT 1"
        );
        $ad->execute([':uid' => (int) $order['user_id']]);
        $addr = $ad->fetch(PDO::FETCH_ASSOC) ?: null;
        $ad->closeCursor();

        $full = trim(($order['user_firstname'] ?? '') . ' ' . ($order['user_lastname'] ?? ''));
        
        $stmt_etx = $pdo->prepare("SELECT * FROM tbl_etax WHERE order_id = :oid ORDER BY id DESC LIMIT 1");
        $stmt_etx->execute([':oid' => $order_id]);
        $etx_row = $stmt_etx->fetch(PDO::FETCH_ASSOC);
        $stmt_etx->closeCursor();

        $has_order_addr = !empty($order['order_addr_name']) || !empty($order['order_addr_type']);
        
        if ($etx_row && !empty($etx_row['addr_name'])) {
            $src_name        = $etx_row['addr_name'] ?? '';
            $src_tax_id      = $etx_row['addr_tax_id'] ?? '';
            $src_branch      = ''; // ในโครงสร้าง tbl_etax ไม่มีคอลัมน์นี้
            $src_detail      = $etx_row['etax_address'] ?? ''; // ใช้คอลัมน์ etax_address ที่มีอยู่
            $src_subdistrict = '';
            $src_district    = '';
            $src_province    = '';
            $src_zipcode     = '';
        } else {
            $src_name        = $has_order_addr ? $order['order_addr_name'] : ($addr['addr_name'] ?? '');
            $src_tax_id      = $has_order_addr ? $order['order_addr_tax_id'] : ($addr['addr_tax_id'] ?? '');
            $src_branch      = $has_order_addr ? $order['order_addr_branch'] : ($addr['addr_branch'] ?? '');
            $src_detail      = $has_order_addr ? $order['order_addr_detail'] : ($addr['addr_detail'] ?? '');
            $src_subdistrict = $has_order_addr ? $order['order_addr_subdistrict'] : ($addr['addr_subdistrict'] ?? '');
            $src_district    = $has_order_addr ? $order['order_addr_district'] : ($addr['addr_district'] ?? '');
            $src_province    = $has_order_addr ? $order['order_addr_province'] : ($addr['addr_province'] ?? '');
            $src_zipcode     = $has_order_addr ? $order['order_addr_zipcode'] : ($addr['addr_zipcode'] ?? '');
        }

        $cust_name = $src_name ?: ($full !== '' ? $full : '-');
        $cust_tax  = $src_tax_id ?: '-';
        if ($cust_tax !== '-' && trim((string)$src_branch) !== '') {
            $cust_tax_display = $cust_tax . ' (สาขา: ' . $src_branch . ')';
        } else {
            $cust_tax_display = $cust_tax;
        }

        $addr_text = trim(implode(' ', array_filter([
            $src_detail, $src_subdistrict, $src_district, $src_province, $src_zipcode
        ])));
        $cust_addr = $addr_text !== '' ? $addr_text : '-';

        // รหัสผ่านไฟล์ = เลข 4 ตัวท้ายของรหัสบัตรประชาชน (เอาเฉพาะตัวเลข)
        $citizen_digits = preg_replace('/\D/', '', (string) ($order['user_citizen_id'] ?? ''));
        $pdf_password   = strlen($citizen_digits) >= 4 ? substr($citizen_digits, -4) : $citizen_digits;

        $total    = (float) ($order['total_price'] ?? 0);
        $discount = (float) ($coupon_log['discount_amount'] ?? 0);
        $subtotal = round($total + $discount, 2);
        $before   = round($total / 1.07, 2);
        $vat      = round($total - $before, 2);



        if ($etx_row) {
            $ts = strtotime($etx_row['created_at']);
            $doc_no = $etx_row['etax_no'] ?: 'ET' . date('ym', $ts) . str_pad((string) $order_id, 7, '0', STR_PAD_LEFT);
            $doc_date = date('d/m/', $ts) . (date('Y', $ts) + 543);
        } else {
            $ts = $order['created_at'] ? strtotime($order['created_at']) : time();
            $doc_no = 'ET' . date('ym', $ts) . str_pad((string) $order_id, 7, '0', STR_PAD_LEFT);
            $doc_date = date('d/m/', $ts) . (date('Y', $ts) + 543);
        }

        $method = (string) ($order['payment_method'] ?? '');
        $method_label = $method === '1' ? 'บัตรเครดิต'
            : ($method === '2' ? 'พร้อมเพย์ (PromptPay)'
            : ($method === '3' ? 'โอนเงินเข้าบัญชีธนาคาร' : '-'));

        $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $num = fn($v) => number_format((float) $v, 2);

        $CO = [
            'name'    => 'บริษัท เอ เอ็ม ซีพีดี จำกัด',
            'address' => 'เลขที่ 16 ซอยลาดกระบัง 14/1 ถนนลาดกระบัง<br>แขวงลาดกระบัง เขตลาดกระบัง กรุงเทพมหานคร 10520',
            'phone'   => '083-4296854',
            'email'   => 'cpdth@am-amaudit.com',
            'tax_id'  => '0105565002221 (สำนักงานใหญ่)',
        ];

        // โลโก้ AM GROUP (ไฟล์อยู่ในโปรเจกต์เรา; เผื่อไว้ fallback ไป cpdth ถ้าไม่เจอ)
        $logo_uri = Pdf::fileToDataUri(dirname(__DIR__, 3) . '/assets/images/am-group-logo.png');
        if ($logo_uri === '') {
            $logo_uri = Pdf::fileToDataUri(dirname(dirname(__DIR__, 3)) . '/cpdth/assets/images/logo/am-group-logo.png');
        }
        $logo_html = $logo_uri !== ''
            ? '<img src="' . $logo_uri . '" style="width:150px;">'
            : '<div style="font-size:14pt;font-weight:bold;color:#1d3557;">AM GROUP</div>';

        $rows = '';
        if ($items) {
            $i = 1;
            foreach ($items as $x) {
                $rows .= '<tr>
                    <td align="center">' . ($i++) . '</td>
                    <td>' . $esc($x['course_name'] ?? '-') . '</td>
                    <td align="center">1</td>
                    <td align="right">' . $num($x['price_at_purchase'] ?? 0) . '</td>
                    <td align="right">' . $num($x['price_at_purchase'] ?? 0) . '</td>
                </tr>';
            }
            // แถวส่วนลด (แสดงเฉพาะเมื่อมีคูปอง)
            if ($coupon_log && $discount > 0) {
                $rows .= '<tr style="color:#c00;">
                    <td align="center">' . $i . '</td>
                    <td>ส่วนลด</td>
                    <td align="center">1</td>
                    <td align="right">-' . $num($discount) . '</td>
                    <td align="right">-' . $num($discount) . '</td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="5" align="center">ไม่มีรายการ</td></tr>';
        }

        $html = '
        <style>
            body { font-family: garuda; color:#222; font-size:11pt; }
            .title { text-align:center; font-size:16pt; font-weight:bold; }
            table.items { border-collapse:collapse; width:100%; }
            table.items th, table.items td { border:1px solid #333; padding:5px 7px; }
            table.items thead th { text-align:center; font-weight:bold; background:#f2f2f2; }
            table.sum td { border:1px solid #333; padding:5px 8px; }
            table.sum .l { background:#eaf5ea; text-align:center; }
        </style>

        <div class="title">ใบเสร็จรับเงิน/ใบกำกับภาษี</div>
        <br>
        <table width="100%"><tr>
            <td width="66%" style="line-height:1.6;">
                <b>' . $esc($CO['name']) . '</b><br>
                ที่อยู่ ' . $CO['address'] . '<br>
                โทร : ' . $esc($CO['phone']) . ' อีเมล : ' . $esc($CO['email']) . '<br>
                เลขประจำตัวผู้เสียภาษีอากร ' . $esc($CO['tax_id']) . '
            </td>
            <td width="34%" align="right" valign="top">
                ' . $logo_html . '
                <table align="right" cellspacing="0" cellpadding="0" style="margin-top:6px;"><tr>
                    <td style="border:1px solid #c1121f;color:#c1121f;padding:5px 10px;font-size:10pt;text-align:center;line-height:1.5;border-radius:6px;">ต้นฉบับ<br>ใบเสร็จรับเงิน/ใบกำกับภาษี</td>
                </tr></table>
            </td>
        </tr></table>
        <br>
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="63%" valign="top" style="border:1px solid #333;padding:8px 10px;line-height:1.6;">
                ชื่อลูกค้า / Customers: ' . $esc($cust_name) . '<br>
                ที่อยู่ / Address: ' . $esc($cust_addr) . '<br>
                เลขประจำตัวผู้เสียภาษี: ' . $esc($cust_tax_display) . '
            </td>
            <td width="4%"></td>
            <td width="33%" valign="top" style="border:1px solid #333;padding:8px 10px;line-height:1.6;">
                เลขที่ / No. ' . $esc($doc_no) . '<br>
                วันที่ / Date ' . $esc($doc_date) . '
            </td>
        </tr></table>
        <br>
        <table class="items">
            <thead><tr>
                <th width="9%">ลำดับที่<br>Item</th>
                <th>รายการ<br>Description</th>
                <th width="13%">จำนวน<br>Quantity</th>
                <th width="16%">ราคา/หน่วย<br>Unit Price</th>
                <th width="16%">จำนวนเงิน<br>Amount</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>
        <br>
        <table width="100%"><tr>
            <td width="55%" valign="top" style="line-height:1.8;">
                ชำระเงินโดย ' . $esc($method_label) . '
            </td>
            <td width="45%" valign="top">
                <table class="sum" width="100%">
                    <tr><td class="l" width="58%">รวม</td><td align="right">' . $num($before) . '</td></tr>
                    <tr><td class="l">ภาษีมูลค่าเพิ่ม 7%</td><td align="right">' . $num($vat) . '</td></tr>
                    <tr><td class="l"><b>ยอดเงินสุทธิ</b></td><td align="right"><b>' . $num($total) . '</b></td></tr>
                </table>
            </td>
        </tr></table>

        <div align="center" style="margin-top:24px;font-weight:bold;">' . $esc(pdf_baht_text($total)) . '</div>

        <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-top:30px;">
            <tr>
                <td width="35%" align="center" valign="middle" style="border:1px solid #333;padding:10px 8px;line-height:1.9;">
                    ผู้รับสินค้า<br>' . $esc($cust_name) . '<br>วันที่ ' . $esc($doc_date) . '
                </td>
                <td width="65%" valign="middle" style="border:1px solid #333;padding:10px 12px;">
                    เอกสารนี้จัดทำโดยระบบอัตโนมัติและมีการลงนามลายเซ็นทางอิเล็กทรอนิกส์เรียบร้อยแล้ว
                </td>
            </tr>
        </table>
        <div align="center" style="margin-top:12px;font-size:10pt;color:#555;">เอกสารนี้ได้จัดทำและส่งข้อมูลให้แก่กรมสรรพากรด้วยวิธีการทางอิเล็กทรอนิกส์ (e-Tax)</div>
        ';

        $pdf = Pdf::make($html, [
            'title'         => 'ใบกำกับภาษีอิเล็กทรอนิกส์',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'password'      => $pdf_password,
        ]);

        return ['ok' => true, 'msg' => '', 'pdf' => $pdf, 'doc_no' => $doc_no, 'password' => $pdf_password];
    }
}
