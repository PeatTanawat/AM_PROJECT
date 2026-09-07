<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key         = isset($_GET['key']) ? trim($_GET['key']) : '';
    $order_id    = \App\Utility\Cipher::decrypt($key);
    $breadcrumbs = [
    ['label' => 'คำสั่งซื้อทั้งหมด', 'url' => 'order'],
    ['label' => 'รายละเอียดคำสั่งซื้อ'],
    ];
?>
<?php include "header.php"; ?>

<style>
    .od-hero .od-num { font-size: 22px; font-weight: 700; line-height: 1.2; }
    .od-hero .od-total { font-size: 26px; font-weight: 700; line-height: 1.1; color: #605DFF; }
    .od-section-title { display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 4px; }
    .od-section-title .material-symbols-outlined { color: #605DFF; font-size: 22px; }
    .od-row { display: flex; gap: 12px; padding: 11px 0; border-bottom: 1px solid #f0f1f4; align-items: flex-start; }
    .od-row:last-child { border-bottom: 0; }
    .od-row .od-label { color: #8695AA; width: 150px; flex-shrink: 0; font-size: 14px; }
    .od-row .od-value { font-weight: 500; flex: 1; word-break: break-word; color: #212529; }
    .od-summary { min-width: 320px; }
    .od-summary .od-srow { display: flex; justify-content: space-between; padding: 7px 0; }
    #OrdItems td, #OrdItems th { vertical-align: middle; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">

            <!-- แถบบน: กลับ + ปุ่มจัดการ -->
            <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
                <div class="d-flex flex-wrap gap-2">
                    <a href="order" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size: 18px;" aria-hidden="true">arrow_back</span> กลับ
                </a>
                    <button type="button" id="BtnConfirmPayment" class="btn btn-success d-inline-flex align-items-center gap-1 d-none" onclick="ConfirmPayment()">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">check_circle</span> ยืนยันการชำระเงิน
                    </button>
                    <a href="etax_view.php?key=<?php echo urlencode($key); ?>" id="BtnEtax" class="btn btn-info text-white d-inline-flex align-items-center gap-1 d-none">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">receipt</span> ใบกำกับภาษี E-TAX
                    </a>
                    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" onclick="OpenEditAddress()">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">edit_location</span> แก้ไขที่อยู่
                    </button>
                    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" onclick="OpenEditNote()">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">edit_note</span> แก้ไขหมายเหตุ
                    </button>
                    <button type="button" id="BtnCancelOrder" class="btn btn-danger d-inline-flex align-items-center gap-1 d-none" onclick="CancelOrder()">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">cancel</span> ยกเลิกคำสั่งซื้อ
                    </button>
                </div>
            </div>

            <!-- หัว: เลขออเดอร์ + ยอดรวม + สถานะ -->
            <div class="card app-card od-hero bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-7">
                            <div class="text-secondary fs-14 mb-1">คำสั่งซื้อ</div>
                            <div class="od-num" id="OrdRefTitle">#-</div>
                            <div class="text-secondary fs-14 mt-1">
                                <span class="material-symbols-outlined align-middle" style="font-size:16px;" aria-hidden="true">schedule</span>
                                สั่งซื้อเมื่อ <span id="OrdCreated">-</span>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <div class="text-secondary fs-14 mb-1">ยอดรวมทั้งสิ้น</div>
                            <div class="od-total" id="OrdTotal">-</div>
                            <div class="d-flex flex-wrap gap-2 justify-content-md-end mt-2">
                                <span id="OrdStatus"></span>
                                <span id="OrdPayment"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- ข้อมูลทั่วไป -->
                <div class="col-lg-6">
                    <div class="card app-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-4">
                            <div class="od-section-title mb-3">
                                <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span> ข้อมูลทั่วไป
                            </div>
                            <div class="od-row"><div class="od-label">หมายเลขคำสั่งซื้อ</div><div class="od-value" id="OrdRef">-</div></div>
                            <div class="od-row"><div class="od-label">ชื่อลูกค้า</div><div class="od-value" id="OrdCustomer">-</div></div>
                            <div class="od-row" id="RowCouponInfo" style="display:none;"><div class="od-label">โค้ดส่วนลด</div><div class="od-value" id="OrdCoupon">-</div></div>
                            <div class="od-row"><div class="od-label">หมายเหตุภายใน</div><div class="od-value" id="OrdNoteInternal"><span class="text-muted">ไม่มีข้อมูล</span></div></div>
                        </div>
                    </div>
                </div>

                <!-- ข้อมูลใบกำกับภาษี -->
                <div class="col-lg-6">
                    <div class="card app-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-4">
                            <div class="od-section-title mb-3">
                                <span class="material-symbols-outlined" aria-hidden="true">description</span> ข้อมูลใบเสร็จ/ใบกำกับภาษี
                            </div>
                            <div class="od-row"><div class="od-label">ประเภท</div><div class="od-value" id="RcType">-</div></div>
                            <div class="od-row"><div class="od-label">เลขประจำตัวผู้เสียภาษี</div><div class="od-value" id="RcTaxId">-</div></div>
                            <div class="od-row"><div class="od-label">สาขา</div><div class="od-value" id="RcBranch">-</div></div>
                            <div class="od-row"><div class="od-label">ชื่อ</div><div class="od-value" id="RcName">-</div></div>
                            <div class="od-row"><div class="od-label">ที่อยู่</div><div class="od-value" id="RcAddress">-</div></div>
                            <div class="od-row"><div class="od-label">เบอร์โทรศัพท์</div><div class="od-value" id="RcPhone">-</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- แท็บ: รายการ / ธุรกรรม -->
            <div class="card app-card bg-white border-0 rounded-3 mt-4 mb-4">
                <div class="card-body p-4">
                    <ul class="nav nav-tabs app-tabs mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-items" type="button">รายการในคำสั่งซื้อ</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pay" type="button">ธุรกรรมชำระเงิน</button></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-items" role="tabpanel">
                            <div class="default-table-area">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-secondary">
                                            <th class="text-center" style="width:60px;">#</th>
                                            <th>รายการ</th>
                                            <th>ประเภท</th>
                                            <th class="text-center">จำนวน</th>
                                            <th class="text-end">ราคารวม</th>
                                        </tr>
                                    </thead>
                                    <tbody id="OrdItems"></tbody>
                                </table>
                            </div>
                            </div>
                            <!-- สรุป VAT ชิดขวา -->
                            <div class="d-flex justify-content-end mt-2">
                                <div class="od-summary">
                                    <div class="od-srow text-secondary"><span>รวมราคาสินค้า</span><span id="SumSubtotal">-</span></div>
                                    <div class="od-srow text-secondary" id="RowDiscount" style="display:none;"><span>ส่วนลด <span id="CouponCode" class="badge bg-danger ms-1"></span></span><span id="SumDiscount" class="text-danger">-</span></div>
                                    <div class="od-srow text-secondary"><span>ราคาไม่รวมภาษีมูลค่าเพิ่ม</span><span id="SumBefore">-</span></div>
                                    <div class="od-srow text-secondary"><span>ภาษีมูลค่าเพิ่ม (7%)</span><span id="SumVat">-</span></div>
                                    <div class="od-srow fw-bold border-top pt-2 mt-1" style="font-size:16px;"><span>รวมทั้งสิ้น</span><span id="SumTotal" class="text-primary">-</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-pay" role="tabpanel">
                            <div class="default-table-area">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-secondary">
                                            <th>วิธีชำระเงิน</th>
                                            <th>เลขอ้างอิง</th>
                                            <th class="text-end">จำนวนเงิน</th>
                                            <th class="text-center">สถานะ</th>
                                            <th>วันที่</th>
                                        </tr>
                                    </thead>
                                    <tbody id="OrdPayTab"></tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<!-- ===== Modal: แก้ไขหมายเหตุ ===== -->
<div class="modal fade" id="modalNote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขหมายเหตุ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label for="note_internal" class="form-label fw-medium">หมายเหตุภายใน</label>
                <textarea class="form-control" id="note_internal" rows="4"></textarea>
            </div>
            <div class="modal-footer p-3">
                <button type="button" class="btn btn-primary w-100 BtnSaveNote" onclick="SubmitNote()">บันทึกข้อมูล</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal: แก้ไขที่อยู่ใบเสร็จ/ใบกำกับภาษี ===== -->
<div class="modal fade" id="modalAddress" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขที่อยู่ในการออกใบเสร็จ/ใบกำกับภาษี</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formAddress">
                    <input type="hidden" id="addr_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="addr_name" class="form-label fw-medium">ชื่อ/บริษัท</label>
                            <input type="text" class="form-control" id="addr_name">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_type" class="form-label fw-medium">ประเภท</label>
                            <select class="form-select" id="addr_type">
                                <option value="1">บุคคลธรรมดา</option>
                                <option value="2">นิติบุคคล</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addr_tax_id" class="form-label fw-medium">เลขประจำตัวผู้เสียภาษี</label>
                            <input type="text" class="form-control" id="addr_tax_id">
                        </div>
                        <div class="col-md-6 addr-corporate-field">
                            <label for="addr_branch_name" class="form-label fw-medium">ชื่อสาขา</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="addr_branch_name" placeholder="ชื่อสาขา">
                                <div class="input-group-text">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input mt-0" type="checkbox" id="chk_hq">
                                        <label class="form-check-label ms-1" for="chk_hq" style="cursor: pointer;">สำนักงานใหญ่</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 addr-corporate-field">
                            <label for="addr_branch" class="form-label fw-medium">รหัสสาขา</label>
                            <input type="text" class="form-control" id="addr_branch" placeholder="เช่น 00000">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_phone" class="form-label fw-medium">เบอร์ติดต่อ</label>
                            <input type="text" class="form-control" id="addr_phone">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_zipcode" class="form-label fw-medium">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_zipcode">
                        </div>
                        <div class="col-12">
                            <label for="addr_detail" class="form-label fw-medium">ที่อยู่</label>
                            <textarea class="form-control" id="addr_detail" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="addr_subdistrict" class="form-label fw-medium">ตำบล/แขวง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_subdistrict">
                        </div>
                        <div class="col-md-4">
                            <label for="addr_district" class="form-label fw-medium">อำเภอ/เขต <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_district">
                        </div>
                        <div class="col-md-4">
                            <label for="addr_province" class="form-label fw-medium">จังหวัด <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_province">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer p-3">
                <button type="button" class="btn btn-success w-100 BtnSaveAddress" onclick="SubmitAddress()">บันทึกข้อมูล</button>
            </div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var ORDER_ID = <?php echo $order_id; ?>;

    function statusBadge(s) {
        if (s === "1") { return '<span class="badge bg-success">สำเร็จแล้ว</span>'; }
        if (s === "2") { return '<span class="badge bg-danger">ยกเลิก</span>'; }
        return '<span class="badge bg-secondary">รอชำระเงิน</span>';
    }
    function paymentBadge(s) {
        if (s === "1") { return '<span class="badge bg-success">ชำระแล้ว</span>'; }
        if (s === "2") { return '<span class="badge bg-secondary">ยกเลิก</span>'; }
        return '<span class="badge bg-danger">ยังไม่ได้ชำระ</span>';
    }
    function methodLabel(m) {
        if (m === "1") { return "PromptPay"; }
        if (m === "2") { return "โอนเงินผ่านธนาคาร"; }
        if (m === "3") { return "บัตรเครดิต"; }
        return "-";
    }
    function money(n) { return (typeof NumberFormat === "function" ? NumberFormat(n, 2) : Number(n).toFixed(2)) + " บาท"; }

    $(document).ready(function () {
        if (!ORDER_ID) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">ไม่พบรหัสคำสั่งซื้อ</span>', icon: "error", showConfirmButton: true })
                .then(function () { window.location.href = "order"; });
            return;
        }

        $("#addr_type").on("change", function () {
            if ($(this).val() === "2") {
                $(".addr-corporate-field").show();
            } else {
                $(".addr-corporate-field").hide();
            }
        });

        $("#chk_hq").on("change", function () {
            if ($(this).is(":checked")) {
                $("#addr_branch_name").val("สำนักงานใหญ่").prop("readonly", true);
            } else {
                $("#addr_branch_name").val("").prop("readonly", false);
            }
        });

        LoadOrder();
    });

    function LoadOrder() {
        $.ajax({
            beforeSend: function () {
                ShowLoadingOverlay("body");
            },
            type: "POST", url: "core.php",
            data: {
                    request_state: "list_order",
                    request_function: "get_order",
                    order_id: ORDER_ID
                },
            dataType: "json",
            success: function (res) {
                if (res.result != 1) {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + res.msg + '</span>',
                        icon: "error",
                        showConfirmButton: true
                    }).then(function () { window.location.href = "order"; });
                    return;
                }
                RenderOrder(res.data);
            },
            complete: function () { HideLoadingOverlay("body"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function RenderOrder(d) {
        window.ORDER_DATA = d; // เก็บไว้ใช้เติมโมดัลแก้ไข
        var o = d.order, rc = d.receipt, sm = d.summary;

        $("#OrdRefTitle").html("#" + (o.ref ? EscapeHTML(o.ref) : "-"));
        $("#OrdCreated").text(o.created_at);
        $("#OrdTotal").text(money(o.total));
        $("#OrdStatus").html(statusBadge(o.payment_status));
        $("#OrdPayment").html(paymentBadge(o.payment_status));

        // ปุ่มยืนยัน/ยกเลิก แสดงเฉพาะออเดอร์โอนเงินที่รอยืนยัน (payment_status='0' AND payment_method='2')
        // ปุ่มใบกำกับภาษี E-TAX แสดงเฉพาะออเดอร์ที่ชำระเงินแล้ว (payment_status='1')
        // ใช้ d-none toggle (ซ่อนได้แม้ปุ่มเป็น d-inline-flex) เพราะ display:none แบบ inline จะโดน !important ของ d-inline-flex ทับ
        var canConfirm = (o.payment_status === "0" && o.payment_method === "2");
        $("#BtnConfirmPayment").toggleClass("d-none", !canConfirm);
        $("#BtnCancelOrder").toggleClass("d-none", !canConfirm);
        $("#BtnEtax").toggleClass("d-none", o.payment_status !== "1");

        $("#OrdRef").html(o.ref ? EscapeHTML(o.ref) : '<span class="text-muted">ไม่มีข้อมูล</span>');
        $("#OrdCustomer").text(o.customer);
        if (d.coupon && d.coupon.coupon_code) {
            $("#RowCouponInfo").show();
            $("#OrdCoupon").html('<span class="badge bg-danger">' + EscapeHTML(d.coupon.coupon_code) + '</span> (ส่วนลด ' + money(d.coupon.discount_amount) + ')');
        } else {
            $("#RowCouponInfo").hide();
        }
        $("#OrdNoteInternal").html(o.internal_note ? EscapeHTML(o.internal_note).replace(/\n/g, '<br>') : '<span class="text-muted">ไม่มีข้อมูล</span>');

        $("#RcType").text(rc.type);
        $("#RcTaxId").text(rc.tax_id);
        $("#RcBranch").text(rc.branch);
        $("#RcName").text(rc.name);
        $("#RcAddress").text(rc.address);
        $("#RcPhone").text(rc.phone);

        // รายการ
        var rows = "";
        d.items.forEach(function (it, i) {
            rows +=
                '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td class="fw-medium">' + EscapeHTML(it.course_name) + '</td>' +
                    '<td><span class="badge bg-primary bg-opacity-10 text-primary">คอร์สเรียน</span></td>' +
                    '<td class="text-center">1 รายการ</td>' +
                    '<td class="text-end">' + money(it.price) + '</td>' +
                '</tr>';
        });

        // เพิ่มส่วนลดเป็นรายการต่อท้าย
        if (d.coupon && d.coupon.discount_amount > 0) {
            var itemIdx = d.items.length + 1;
            rows +=
                '<tr>' +
                    '<td class="text-center">' + itemIdx + '</td>' +
                    '<td class="fw-medium">ส่วนลดจากโค้ด: ' + EscapeHTML(d.coupon.coupon_code) + '</td>' +
                    '<td><span class="badge bg-danger bg-opacity-10 text-danger" style="font-weight: 500;">ส่วนลด</span></td>' +
                    '<td class="text-center">1 รายการ</td>' +
                    '<td class="text-end text-danger">-' + money(d.coupon.discount_amount) + '</td>' +
                '</tr>';
        }

        if (!d.items.length && (!d.coupon || d.coupon.discount_amount <= 0)) {
            rows = '<tr><td colspan="5" class="text-center text-muted">ไม่มีรายการ</td></tr>';
        }
        $("#OrdItems").html(rows);

        // สรุป VAT
        $("#SumSubtotal").text(money(sm.subtotal));
        if (d.coupon && d.coupon.discount_amount > 0) {
            $("#RowDiscount").show();
            $("#CouponCode").text(d.coupon.coupon_code);
            $("#SumDiscount").text("-" + money(d.coupon.discount_amount));
        } else {
            $("#RowDiscount").hide();
        }
        $("#SumBefore").text(money(sm.before));
        $("#SumVat").text(money(sm.vat));
        $("#SumTotal").text(money(sm.total));

        // ธุรกรรมชำระเงิน
        if (o.payment_status === "1") {
            $("#OrdPayTab").html(
                '<tr>' +
                    '<td>' + methodLabel(o.payment_method) + '</td>' +
                    '<td>' + (o.ref ? EscapeHTML(o.ref) : '-') + '</td>' +
                    '<td class="text-end">' + money(o.total) + '</td>' +
                    '<td class="text-center">' + paymentBadge(o.payment_status) + '</td>' +
                    '<td>' + o.created_at + '</td>' +
                '</tr>'
            );
        } else {
            $("#OrdPayTab").html('<tr><td colspan="5" class="text-center text-muted">ยังไม่มีธุรกรรมชำระเงิน</td></tr>');
        }
    }

    // ===== แก้ไขหมายเหตุ =====
    function OpenEditNote() {
        if (!window.ORDER_DATA) { return; }
        $("#note_internal").val(window.ORDER_DATA.order.internal_note || "");
        new bootstrap.Modal(document.getElementById("modalNote")).show();
    }
    function SubmitNote() {
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSaveNote'); },
            type: "POST",
            url: "core.php",
            data: {
                 request_state: "list_order",
                request_function: "update_note",
                order_id: ORDER_ID,
                internal_note: $("#note_internal").val() 
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById("modalNote")).hide();
                    Swal.fire({ 
                        title: "สำเร็จ", 
                        html: '<span class="fw-bold text-success">' + res.msg + '</span>', 
                        icon: "success", 
                        showConfirmButton: false, 
                        timer: 1500 
                    }).then(function () {
                         LoadOrder(); 
                        });
                } else {
                    Swal.fire({ 
                        title: "แจ้งเตือน", 
                        html: '<span class="fw-bold text-danger">' + res.msg + '</span>', 
                        icon: "error", 
                        showConfirmButton: true 
                    });
                }
            },
            complete: function () { 
                HideLoadingButton('.BtnSaveNote'); 
            },
            error: function (j, e) { 
                ShowErrorAjax(j, e); 
            }
        });
    }

    // ===== แก้ไขที่อยู่ใบกำกับภาษี =====
    function OpenEditAddress() {
        if (!window.ORDER_DATA) { return; }
        var r = window.ORDER_DATA.receipt_raw || {};
        $("#addr_id").val(r.addr_id || 0);
        $("#addr_type").val(r.type || "1");
        $("#addr_name").val(r.name || "");
        $("#addr_tax_id").val(r.tax_id || "");
        $("#addr_branch").val(r.branch || "");
        $("#addr_branch_name").val(r.branch_name || "");
        $("#addr_phone").val(r.phone || "");
        $("#addr_detail").val(r.detail || "");
        $("#addr_subdistrict").val(r.subdistrict || "");
        $("#addr_district").val(r.district || "");
        $("#addr_province").val(r.province || "");
        $("#addr_zipcode").val(r.zipcode || "");
        $("#addr_type").trigger("change");
        
        if (r.branch_name === "สำนักงานใหญ่") {
            $("#chk_hq").prop("checked", true).trigger("change");
        } else {
            $("#chk_hq").prop("checked", false);
            $("#addr_branch_name").prop("readonly", false);
        }
        new bootstrap.Modal(document.getElementById("modalAddress")).show();
    }
    function SubmitAddress() {
        var zip = $("#addr_zipcode").val().trim(), sub = $("#addr_subdistrict").val().trim(),
            dist = $("#addr_district").val().trim(), prov = $("#addr_province").val().trim();
        if (!zip || !sub || !dist || !prov) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณากรอก รหัสไปรษณีย์ / ตำบล / อำเภอ / จังหวัด</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }
        var addrType = $("#addr_type").val();
        var bCode = (addrType === "2") ? $("#addr_branch").val() : "";
        var bName = (addrType === "2") ? $("#addr_branch_name").val() : "";
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSaveAddress'); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_order", request_function: "update_address",
                order_id: ORDER_ID, addr_id: $("#addr_id").val(), type: addrType,
                name: $("#addr_name").val(), tax_id: $("#addr_tax_id").val(), branch: bCode, branch_name: bName,
                phone: $("#addr_phone").val(), detail: $("#addr_detail").val(),
                subdistrict: sub, district: dist, province: prov, zipcode: zip
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById("modalAddress")).hide();
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + res.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 }).then(function () { LoadOrder(); });
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                }
            },
            complete: function () { HideLoadingButton('.BtnSaveAddress'); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // ===== ยืนยันการชำระเงิน =====
    function ConfirmPayment() {
        Swal.fire({
            title: "ยืนยันการชำระเงิน",
            html: '<span class="text-secondary">ยืนยันว่าได้รับการโอนเงินแล้ว? ระบบจะตั้งสถานะเป็นชำระแล้วและให้สิทธิ์เข้าเรียนแก่ลูกค้า</span>',
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "ยืนยันการชำระเงิน",
            cancelButtonText: "ปิด",
            confirmButtonColor: "#28a745"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            $.ajax({
                beforeSend: function () { Swal.fire({ title: "กำลังดำเนินการ...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); },
                type: "POST", url: "core.php",
                data: { request_state: "list_order", request_function: "confirm_payment", order_id: ORDER_ID },
                dataType: "json",
                success: function (res) {
                    if (res.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + res.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1600 }).then(function () { LoadOrder(); });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                    }
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
        });
    }

    // ===== ยกเลิกคำสั่งซื้อ =====
    function CancelOrder() {
        Swal.fire({
            title: "ยืนยันการยกเลิก",
            html: '<span class="text-secondary">ต้องการยกเลิกคำสั่งซื้อนี้ใช่หรือไม่?</span>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยกเลิกคำสั่งซื้อ",
            cancelButtonText: "ปิด",
            confirmButtonColor: "#dc3545"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            $.ajax({
                beforeSend: function () { Swal.fire({ title: "กำลังดำเนินการ...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); },
                type: "POST", url: "core.php",
                data: { request_state: "list_order", request_function: "cancel_order", order_id: ORDER_ID },
                dataType: "json",
                success: function (res) {
                    if (res.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + res.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 }).then(function () { LoadOrder(); });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                    }
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
        });
    }

    // ปุ่มที่ยังไม่เปิดใช้งาน (รอเชื่อมระบบ E-Tax)
    function ComingSoon() {
        Swal.fire({ title: "แจ้งเตือน", html: '<span class="text-secondary">ฟังก์ชันนี้ยังไม่เปิดใช้งาน (รอเชื่อมระบบ)</span>', icon: "info", confirmButtonText: "ตกลง" });
    }
</script>
