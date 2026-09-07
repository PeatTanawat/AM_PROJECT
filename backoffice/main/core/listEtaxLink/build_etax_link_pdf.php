<?php
// ฟังก์ชันกลางสร้าง PDF ใบกำกับภาษีจากลิ้งค์ standalone (tbl_etax_link + tbl_etax_link_item)
// ใช้ template + รูปแบบเดียวกับ listEtax/build_etax_pdf.php (mPDF, ฟอนต์ garuda, A4)
// reuse helper pdf_baht_text() จาก build_etax_pdf.php (มี function_exists guard อยู่แล้ว)

use App\Utility\Pdf;

require_once dirname(__DIR__) . '/listEtax/build_etax_pdf.php';

if (!function_exists('build_etax_link_pdf')) {
    /**
     * สร้าง PDF ใบกำกับภาษีจาก link_id (ลิ้งค์ standalone)
     * @return array ['ok'=>bool, 'msg'=>string, 'pdf'=>string(binary), 'doc_no'=>string, 'password'=>string]
     */
    function build_etax_link_pdf(\PDO $pdo, int $link_id): array
    {
        $stmt = $pdo->prepare(
            "SELECT id, etax_no, customer_name, customer_type, customer_tax_id, customer_address,
                    doc_date, subtotal, vat_amount, grand_total
             FROM tbl_etax_link
             WHERE id = :id AND delete_at IS NULL LIMIT 1"
        );
        $stmt->execute([':id' => $link_id]);
        $link = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$link) {
            return ['ok' => false, 'msg' => 'ไม่พบลิ้งค์ใบกำกับภาษีนี้', 'pdf' => '', 'doc_no' => '', 'password' => ''];
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

        $cust_name = $link['customer_name'] !== '' ? $link['customer_name'] : '-';
        $cust_tax  = $link['customer_tax_id'] !== '' ? $link['customer_tax_id'] : '-';
        $cust_addr = trim((string) ($link['customer_address'] ?? ''));
        $cust_addr = $cust_addr !== '' ? $cust_addr : '-';

        // รหัสผ่านไฟล์ = เลข 4 ตัวท้ายของเลขประจำตัวผู้เสียภาษี (เหมือนใบกำกับภาษีของออเดอร์)
        $tax_digits   = preg_replace('/\D/', '', (string) $cust_tax);
        $pdf_password = strlen($tax_digits) >= 4 ? substr($tax_digits, -4) : '';

        $subtotal = (float) ($link['subtotal'] ?? 0);
        $vat      = (float) ($link['vat_amount'] ?? 0);
        $total    = (float) ($link['grand_total'] ?? 0);

        $doc_no   = (string) $link['etax_no'];
        $doc_date = $link['doc_date'] ? date('d/m/Y', strtotime($link['doc_date'])) : date('d/m/Y');

        $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $num = fn($v) => number_format((float) $v, 2);

        $CO = [
            'name'    => 'บริษัท เอ เอ็ม ซีพีดี จำกัด',
            'address' => 'เลขที่ 16 ซอยลาดกระบัง 14/1 ถนนลาดกระบัง<br>แขวงลาดกระบัง เขตลาดกระบัง กรุงเทพมหานคร 10520',
            'phone'   => '083-4296854',
            'email'   => 'cpdth@am-amaudit.com',
            'tax_id'  => '0105565002221 (สำนักงานใหญ่)',
        ];

        // โลโก้ AM GROUP (ไฟล์อยู่ในโปรเจกต์เรา; fallback ไป cpdth ถ้าไม่เจอ)
        $logo_uri = Pdf::fileToDataUri(dirname(__DIR__, 3) . '/assets/images/am-group-logo.png');
        if ($logo_uri === '') {
            $logo_uri = Pdf::fileToDataUri(dirname(dirname(__DIR__, 3)) . '/cpdth/assets/images/logo/am-group-logo.png');
        }
        $logo_html = $logo_uri !== ''
            ? '<img src="' . $logo_uri . '" style="width:150px;">'
            : '<div style="font-size:14pt;font-weight:bold;color:#1d3557;">AM GROUP</div>';

        $vat_label = function (string $t): string {
            if ($t === 'inc')  { return 'รวมภาษี'; }
            if ($t === 'exc')  { return 'แยกภาษี'; }
            return 'ไม่มีภาษี';
        };

        $rows = '';
        if ($items) {
            $i = 1;
            foreach ($items as $x) {
                $rows .= '<tr>
                    <td align="center">' . ($i++) . '</td>
                    <td>' . $esc($x['product_name'] ?? '-') . '</td>
                    <td align="center">' . $num($x['qty'] ?? 0) . '</td>
                    <td align="right">' . $num($x['price'] ?? 0) . '</td>
                    <td align="right">' . $num($x['amount_before'] ?? 0) . '</td>
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
                เลขประจำตัวผู้เสียภาษี: ' . $esc($cust_tax) . '
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
            <td width="55%" valign="top" style="line-height:1.8;">&nbsp;</td>
            <td width="45%" valign="top">
                <table class="sum" width="100%">
                    <tr><td class="l" width="58%">รวม</td><td align="right">' . $num($subtotal) . '</td></tr>
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
