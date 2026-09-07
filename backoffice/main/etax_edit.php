<?php
    // แก้ไขใบกำกับภาษีอิเล็กทรอนิกส์ (E-TAX INVOICE)
    // ข้อมูลลูกค้า/ที่อยู่ = tbl_user_address (บันทึกจริงผ่าน update_address)
    // เลขเอกสาร/วันที่/อีเมล = อ่านอย่างเดียว, รายการ = แสดงจากออเดอร์ (ยังไม่มีระบบ e-Tax ให้แก้รายการ)
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $order_id = \App\Utility\Cipher::decrypt($key);
    $doc_no   = 'ET' . date('ym') . str_pad((string) $order_id, 7, '0', STR_PAD_LEFT);
    $breadcrumbs = [['label' => 'แก้ไขใบกำกับภาษีอิเล็กทรอนิกส์']];
?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <a href="etax_view.php?id=<?php echo $order_id; ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับ</a>
                        <h4 class="mb-0">แก้ไขใบกำกับภาษีอิเล็กทรอนิกส์ (E-TAX INVOICE)</h4>
                    </div>

                    <h6 class="mb-3">ข้อมูลในใบกำกับภาษี</h6>
                    <form id="formEtax">
                        <input type="hidden" id="addr_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">เลขที่เอกสาร <span class="text-danger">*</span> <span class="text-secondary fs-12">(เลขเอกสารอาจเปลี่ยนแปลงได้หลังยืนยัน)</span></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($doc_no); ?>" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">วันที่ในเอกสาร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly disabled>
                            </div>

                            <div class="col-md-6">
                                <label for="etx_type" class="form-label fw-medium">ประเภทลูกค้า <span class="text-danger">*</span></label>
                                <select class="form-select" id="etx_type">
                                    <option value="1">บุคคลธรรมดา</option>
                                    <option value="2">นิติบุคคล</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="etx_name" class="form-label fw-medium">ชื่อ – นามสกุลลูกค้า <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_name">
                            </div>

                            <div class="col-md-6">
                                <label for="etx_tax_id" class="form-label fw-medium">หมายเลขผู้เสียภาษี 13 หลัก <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_tax_id" maxlength="13">
                            </div>
                            <div class="col-md-6">
                                <label for="etx_branch" class="form-label fw-medium">สาขาที่</label>
                                <input type="text" class="form-control" id="etx_branch" placeholder="เช่น สำนักงานใหญ่">
                            </div>

                            <div class="col-12">
                                <label for="etx_detail" class="form-label fw-medium">ที่อยู่ <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="etx_detail" rows="2"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="etx_subdistrict" class="form-label fw-medium">แขวง/ตำบล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_subdistrict">
                            </div>
                            <div class="col-md-6">
                                <label for="etx_district" class="form-label fw-medium">เขต/อำเภอ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_district">
                            </div>
                            <div class="col-md-6">
                                <label for="etx_province" class="form-label fw-medium">จังหวัด <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_province">
                            </div>
                            <div class="col-md-6">
                                <label for="etx_zipcode" class="form-label fw-medium">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_zipcode" maxlength="5">
                            </div>

                            <div class="col-md-6">
                                <label for="etx_email" class="form-label fw-medium">อีเมล <span class="text-secondary fs-12">(อีเมลผู้ใช้ แก้ที่หน้าผู้ใช้)</span></label>
                                <input type="text" class="form-control" id="etx_email" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="etx_phone" class="form-label fw-medium">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etx_phone">
                            </div>
                        </div>
                    </form>

                    <!-- รายการสินค้า (แสดงจากออเดอร์ — ยังไม่มีระบบแก้รายการ e-Tax) -->
                    <div class="card app-card border rounded-3 mt-4">
                        <div class="card-body p-3">
                            <button type="button" class="btn btn-primary mb-3" onclick="ComingSoon()">เพิ่มใหม่</button>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr class="text-secondary">
                                            <th>ชื่อสินค้า</th>
                                            <th class="text-center">จำนวน</th>
                                            <th class="text-end">ราคา</th>
                                            <th class="text-end">ส่วนลด</th>
                                            <th class="text-center">VAT TYPE</th>
                                            <th class="text-end">ราคารวม</th>
                                            <th class="text-center">ดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="EtxItems"></tbody>
                                    <tfoot id="EtxFoot"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100 mt-4 BtnSaveEtax" onclick="SubmitEtax()">แก้ไขใบกำกับภาษี</button>
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

    function money(n) { return (typeof NumberFormat === "function" ? NumberFormat(n, 2) : Number(n).toFixed(2)) + " บาท"; }

    $(document).ready(function () {
        if (!ORDER_ID) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">ไม่พบรหัสคำสั่งซื้อ</span>', icon: "error", showConfirmButton: true })
                .then(function () { window.location.href = "order"; });
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
                        .then(function () { window.location.href = "order"; });
                    return;
                }
                Fill(res.data);
            },
            complete: function () { HideLoadingOverlay("body"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    });

    function Fill(d) {
        var r = d.receipt_raw || {}, sm = d.summary;
        $("#addr_id").val(r.addr_id || 0);
        $("#etx_type").val(r.type || "1");
        $("#etx_name").val(r.name || "");
        $("#etx_tax_id").val(r.tax_id || "");
        $("#etx_branch").val(r.branch || "");
        $("#etx_detail").val(r.detail || "");
        $("#etx_subdistrict").val(r.subdistrict || "");
        $("#etx_district").val(r.district || "");
        $("#etx_province").val(r.province || "");
        $("#etx_zipcode").val(r.zipcode || "");
        $("#etx_email").val(r.email || "");
        $("#etx_phone").val(r.phone || "");

        // รายการ
        var rows = "";
        d.items.forEach(function (it) {
            rows +=
                '<tr>' +
                    '<td>' + EscapeHTML(it.course_name) + '</td>' +
                    '<td class="text-center">1</td>' +
                    '<td class="text-end">' + money(it.price) + '</td>' +
                    '<td class="text-end">' + money(0) + '</td>' +
                    '<td class="text-center">7%</td>' +
                    '<td class="text-end">' + money(it.price) + '</td>' +
                    '<td class="text-center">' +
                        '<button type="button" class="btn btn-sm btn-secondary" onclick="ComingSoon()">แก้ไข</button> ' +
                        '<button type="button" class="btn btn-sm btn-danger" onclick="ComingSoon()">ลบ</button>' +
                    '</td>' +
                '</tr>';
        });
        if (!d.items.length) { rows = '<tr><td colspan="7" class="text-center text-muted">ไม่มีรายการ</td></tr>'; }
        $("#EtxItems").html(rows);

        $("#EtxFoot").html(
            '<tr><td colspan="4"></td><td class="text-secondary">รวม</td><td class="fw-bold text-end">' + money(sm.before) + '</td><td></td></tr>' +
            '<tr><td colspan="4"></td><td class="text-secondary">VAT</td><td class="fw-bold text-end">' + money(sm.vat) + '</td><td></td></tr>' +
            '<tr><td colspan="4"></td><td class="fw-bold">รวมทั้งสิ้น</td><td class="fw-bold text-end text-primary">' + money(sm.total) + '</td><td></td></tr>'
        );
    }

    function SubmitEtax() {
        var name = $("#etx_name").val().trim(), zip = $("#etx_zipcode").val().trim(),
            sub = $("#etx_subdistrict").val().trim(), dist = $("#etx_district").val().trim(),
            prov = $("#etx_province").val().trim(), detail = $("#etx_detail").val().trim();
        // ===== ตรวจช่องบังคับให้ครบ (โชว์ทุกช่องที่ขาด + เลื่อนไปช่องแรก) =====
        if (!ValidateRequired([
            { sel: '#etx_type',        label: 'ประเภทลูกค้า', type: 'select' },
            { sel: '#etx_name',        label: 'ชื่อ – นามสกุลลูกค้า' },
            { sel: '#etx_tax_id',      label: 'หมายเลขผู้เสียภาษี' },
            { sel: '#etx_detail',      label: 'ที่อยู่' },
            { sel: '#etx_subdistrict', label: 'แขวง/ตำบล' },
            { sel: '#etx_district',    label: 'เขต/อำเภอ' },
            { sel: '#etx_province',    label: 'จังหวัด' },
            { sel: '#etx_zipcode',     label: 'รหัสไปรษณีย์' },
            { sel: '#etx_phone',       label: 'เบอร์โทรศัพท์' }
        ])) { return; }
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSaveEtax'); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_order", request_function: "update_address",
                order_id: ORDER_ID, addr_id: $("#addr_id").val(), type: $("#etx_type").val(),
                name: name, tax_id: $("#etx_tax_id").val(), branch: $("#etx_branch").val(),
                phone: $("#etx_phone").val(), detail: detail,
                subdistrict: sub, district: dist, province: prov, zipcode: zip
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">บันทึกข้อมูลใบกำกับภาษีสำเร็จ</span>', icon: "success", showConfirmButton: false, timer: 1500 })
                        .then(function () { window.location.href = "etax_view.php?id=" + ORDER_ID; });
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                }
            },
            complete: function () { HideLoadingButton('.BtnSaveEtax'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function ComingSoon() {
        Swal.fire({ title: "แจ้งเตือน", html: '<span class="text-secondary">การแก้ไขรายการสินค้ายังไม่เปิดใช้งาน (รอเชื่อมระบบ e-Tax)</span>', icon: "info", confirmButtonText: "ตกลง" });
    }
</script>
