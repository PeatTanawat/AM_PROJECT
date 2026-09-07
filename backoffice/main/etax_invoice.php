<?php
    // ใบเสร็จรับเงิน/ใบกำกับภาษี (พิมพ์ / บันทึกเป็น PDF จากเบราว์เซอร์)
    // ข้อมูลจากออเดอร์ (get_order) + ที่อยู่จาก tbl_user_address
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $order_id = \App\Utility\Cipher::decrypt($key);
    $breadcrumbs = [['label' => 'ใบกำกับภาษี']];

    // ข้อมูลผู้ขาย (บริษัทเรา)
    $COMPANY = [
        'name'    => 'บริษัท เอ เอ็ม ซีพีดี จำกัด',
        'address' => 'เลขที่ 16 ซอยลาดกระบัง 14/1 ถนนลาดกระบัง แขวงลาดกระบัง เขตลาดกระบัง กรุงเทพมหานคร 10520',
        'phone'   => '083-4296854',
        'email'   => 'cpdth@am-amaudit.com',
        'tax_id'  => '0105565002221 (สำนักงานใหญ่)',
    ];
?>
<?php include "header.php"; ?>

<style>
    .inv-paper { max-width: 820px; margin: 0 auto; background: #fff; }
    .inv-title { text-align: center; font-size: 22px; font-weight: 700; }
    .inv-company { font-size: 14px; line-height: 1.6; }
    .inv-stamp { border: 1px solid #c1121f; color: #c1121f; border-radius: 6px; padding: 4px 10px; font-size: 13px; text-align: center; display: inline-block; }
    .inv-box { border: 1px solid #333; border-radius: 4px; padding: 10px 12px; font-size: 14px; }
    .inv-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .inv-table th, .inv-table td { border: 1px solid #333; padding: 6px 8px; }
    .inv-table thead th { text-align: center; font-weight: 600; }
    .inv-sum td { border: 1px solid #333; padding: 6px 8px; }
    .inv-sum .lbl { background: #eaf5ea; text-align: center; }
    @media print {
        body * { visibility: hidden; }
        #invoiceArea, #invoiceArea * { visibility: visible; }
        #invoiceArea { position: absolute; left: 0; top: 0; width: 100%; padding: 0; }
        .no-print { display: none !important; }
    }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <!-- ปุ่ม (ไม่พิมพ์) -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
                <a href="etax" class="btn btn-light d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> กลับ
                </a>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" onclick="window.print()">
                    <span class="material-symbols-outlined" style="font-size:18px;">print</span> พิมพ์ / บันทึก PDF
                </button>
            </div>

            <!-- เอกสารใบกำกับภาษี -->
            <div class="card bg-white border-0 rounded-3 mb-4" id="invoiceArea">
                <div class="card-body p-4">
                    <div class="inv-paper">
                        <div class="inv-title mb-4">ใบเสร็จรับเงิน/ใบกำกับภาษี</div>

                        <!-- หัว: ผู้ขาย + ต้นฉบับ -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="inv-company">
                                <div class="fw-bold"><?php echo htmlspecialchars($COMPANY['name']); ?></div>
                                <div>ที่อยู่ <?php echo htmlspecialchars($COMPANY['address']); ?></div>
                                <div>โทร : <?php echo htmlspecialchars($COMPANY['phone']); ?> อีเมล : <?php echo htmlspecialchars($COMPANY['email']); ?></div>
                                <div>เลขประจำตัวผู้เสียภาษีอากร <?php echo htmlspecialchars($COMPANY['tax_id']); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold mb-2" style="font-size:18px;color:#1d3557;">AM GROUP</div>
                                <span class="inv-stamp">ต้นฉบับ<br>ใบเสร็จรับเงิน/ใบกำกับภาษี</span>
                            </div>
                        </div>

                        <!-- ลูกค้า + เลขที่/วันที่ -->
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <div class="inv-box h-100">
                                    <div>ชื่อลูกค้า / Customers: <span id="InvName">-</span></div>
                                    <div>ที่อยู่ / Address: <span id="InvAddress">-</span></div>
                                    <div>เลขประจำตัวผู้เสียภาษี: <span id="InvTaxId">-</span></div>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="inv-box h-100">
                                    <div>เลขที่ / No. <span id="InvDocNo">-</span></div>
                                    <div>วันที่ / Date <span id="InvDate">-</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- รายการ -->
                        <table class="inv-table mb-3">
                            <thead>
                                <tr>
                                    <th style="width:60px;">ลำดับที่<br>Item</th>
                                    <th>รายการ<br>Description</th>
                                    <th style="width:70px;">จำนวน<br>Quantity</th>
                                    <th style="width:100px;">ราคา/หน่วย<br>Unit Price</th>
                                    <th style="width:110px;">จำนวนเงิน<br>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="InvItems"></tbody>
                        </table>

                        <!-- ชำระเงิน + สรุป -->
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="font-size:14px;">
                                <div id="InvPayBy">ชำระเงินโดย -</div>
                                <div class="mt-3 fw-medium" id="InvBahtText">-</div>
                            </div>
                            <table class="inv-sum" style="font-size:14px;min-width:300px;">
                                <tr><td class="lbl" style="width:60%;">รวม</td><td class="text-end" id="InvBefore">-</td></tr>
                                <tr><td class="lbl">ภาษีมูลค่าเพิ่ม 7%</td><td class="text-end" id="InvVat">-</td></tr>
                                <tr><td class="lbl fw-bold">ยอดเงินสุทธิ</td><td class="text-end fw-bold" id="InvTotal">-</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var ORDER_ID = <?php echo $order_id; ?>;

    function num2(n) { return (typeof NumberFormat === "function" ? NumberFormat(n, 2) : Number(n).toFixed(2)); }

    // แปลงจำนวนเงินเป็นข้อความภาษาไทย
    function BahtText(amount) {
        amount = parseFloat(amount);
        if (isNaN(amount)) { return ""; }
        var t = amount.toFixed(2).split(".");
        var baht = t[0], satang = t[1];
        var thai = ["", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
        var unit = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
        function convert(s) {
            var r = "", len = s.length;
            for (var i = 0; i < len; i++) {
                var d = parseInt(s.charAt(i), 10);
                var pos = len - i;            // ตำแหน่งจากขวา (1-based)
                var u = (pos - 1) % 6;        // หลักในกลุ่มล้าน
                if (d !== 0) {
                    if (u === 1 && d === 1) { r += "สิบ"; }
                    else if (u === 1 && d === 2) { r += "ยี่สิบ"; }
                    else if (u === 0 && d === 1 && len > 1) { r += "เอ็ด"; }
                    else { r += thai[d] + unit[u]; }
                }
                if (pos > 1 && (pos - 1) % 6 === 0) { r += "ล้าน"; }
            }
            return r;
        }
        var txt = (baht === "0") ? "ศูนย์บาท" : convert(baht) + "บาท";
        txt += (satang === "00") ? "ถ้วน" : convert(satang) + "สตางค์";
        return "(" + txt + ")";
    }

    function methodLabel(m) {
        if (m === "1") { return "พร้อมเพย์ (PromptPay)"; }
        if (m === "2") { return "โอนเงินเข้าบัญชีธนาคาร"; }
        if (m === "3") { return "บัตรเครดิต"; }
        return "-";
    }

    $(document).ready(function () {
        if (!ORDER_ID) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">ไม่พบรหัสคำสั่งซื้อ</span>', icon: "error", showConfirmButton: true })
                .then(function () { window.location.href = "etax"; });
            return;
        }
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("body"); },
            type: "POST", url: "core.php",
            data: { request_state: "list_order", request_function: "get_order", order_id: ORDER_ID },
            dataType: "json",
            success: function (res) {
                if (res.result != 1) {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true })
                        .then(function () { window.location.href = "etax"; });
                    return;
                }
                Render(res.data);
            },
            complete: function () { HideLoadingOverlay("body"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    });

    function Render(d) {
        var o = d.order, rc = d.receipt, sm = d.summary;

        // เลขเอกสาร: ET + ปีเดือน(ค.ศ.2หลัก) + order 7 หลัก  (จาก created_at d/m/Y)
        var dm = (o.created_at || "").split(" ")[0].split("/"); // [dd, mm, yyyy]
        var yy = (dm[2] || "").slice(2), mm = dm[1] || "";
        var docNo = "ET" + yy + mm + String(o.order_id).padStart(7, "0");
        var dateBE = dm[0] + "/" + dm[1] + "/" + (parseInt(dm[2], 10) + 543);

        $("#InvDocNo").text(docNo);
        $("#InvDate").text(dateBE);
        $("#InvName").text(rc.name);
        $("#InvAddress").text(rc.address);
        $("#InvTaxId").text(rc.tax_id);
        $("#InvPayBy").text("ชำระเงินโดย " + methodLabel(o.payment_method));

        var rows = "";
        d.items.forEach(function (it, i) {
            rows +=
                '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td>' + EscapeHTML(it.course_name) + '</td>' +
                    '<td class="text-center">1</td>' +
                    '<td class="text-end">' + num2(it.price) + '</td>' +
                    '<td class="text-end">' + num2(it.price) + '</td>' +
                '</tr>';
        });
        if (!d.items.length) { rows = '<tr><td colspan="5" class="text-center">ไม่มีรายการ</td></tr>'; }
        $("#InvItems").html(rows);

        $("#InvBefore").text(num2(sm.before));
        $("#InvVat").text(num2(sm.vat));
        $("#InvTotal").text(num2(sm.total));
        $("#InvBahtText").text(BahtText(sm.total));
    }
</script>
