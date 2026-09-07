<?php $breadcrumbs = [['label' => 'ลิ้งค์ออกใบกำกับภาษี (E-Tax)', 'url' => 'etax_link'], ['label' => 'สร้างลิ้งค์ใหม่']]; ?>
<?php include "header.php"; ?>

<style>
    #ItemsTable td, #ItemsTable th { vertical-align: middle; }
    #ItemsTable .form-control-sm, #ItemsTable .form-select-sm { min-width: 70px; }
    .etl-summary { min-width: 320px; }
    .etl-summary .etl-srow { display: flex; justify-content: space-between; padding: 7px 0; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">
        <?php include "navbar.php"; ?>
        <div class="px-2">
            
            <!-- แถบบน: กลับ -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <a href="etax_link.php" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับ
                </a>
            </div>

            <div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white p-4">
                    <h4 class="mb-0">สร้างลิ้งค์ออกใบกำกับภาษี (E-TAX)</h4>
                </div>
                <div class="card-body p-4">
                    <form id="FormAddEtaxLink" autocomplete="off">
                        <!-- ข้อมูลลูกค้า -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label fw-medium">ชื่อลูกค้า / บริษัท <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" placeholder="กรอกชื่อลูกค้า">
                            </div>
                            <div class="col-md-6">
                                <label for="doc_date" class="form-label fw-medium">วันที่ในเอกสาร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="doc_date" value="<?php echo date('Y-m-d'); ?>" placeholder="วว/ดด/ปปปป" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label for="customer_type" class="form-label fw-medium">ประเภท</label>
                                <select class="form-select" id="customer_type">
                                    <option value="1">บุคคลธรรมดา</option>
                                    <option value="2">นิติบุคคล</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="customer_tax_id" class="form-label fw-medium">เลขประจำตัวผู้เสียภาษี <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_tax_id" maxlength="13" placeholder="13 หลัก">
                            </div>
                            <div class="col-md-4">
                                <label for="customer_email" class="form-label fw-medium">อีเมล</label>
                                <input type="email" class="form-control" id="customer_email" placeholder="(ถ้ามี)">
                            </div>
                            <div class="col-md-6 branch-group" style="display:none;">
                                <label for="addr_branch_name" class="form-label fw-medium">ชื่อสาขา</label>
                                <input type="text" class="form-control" id="addr_branch_name" placeholder="สำนักงานใหญ่ หรือชื่อสาขา">
                            </div>
                            <div class="col-md-6 branch-group" style="display:none;">
                                <label for="addr_branch" class="form-label fw-medium">รหัสสาขา</label>
                                <input type="text" class="form-control" id="addr_branch" placeholder="เช่น 00000">
                            </div>
                            <div class="col-12">
                                <label for="customer_address" class="form-label fw-medium">ที่อยู่</label>
                                <textarea class="form-control" id="customer_address" rows="2" placeholder="ที่อยู่สำหรับออกใบกำกับภาษี"></textarea>
                            </div>
                        </div>

                        <!-- รายการสินค้า -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">รายการสินค้า</h6>
                            <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" onclick="AddItemRow()">
                                <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">add</span> เพิ่มใหม่
                            </button>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table align-middle" id="ItemsTable">
                                <thead>
                                    <tr class="text-secondary">
                                        <th style="width:40px;">#</th>
                                        <th>ชื่อสินค้า</th>
                                        <th class="text-center" style="width:90px;">จำนวน</th>
                                        <th class="text-end" style="width:120px;">ราคา</th>
                                        <th class="text-end" style="width:120px;">ส่วนลด</th>
                                        <th class="text-center" style="width:130px;">VAT TYPE</th>
                                        <th class="text-end" style="width:130px;">ราคารวม</th>
                                        <th class="text-center" style="width:60px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="ItemsBody"></tbody>
                            </table>
                        </div>

                        <!-- สรุป -->
                        <div class="d-flex justify-content-end mb-4">
                            <div class="etl-summary">
                                <div class="etl-srow text-secondary"><span>รวม (ไม่รวมภาษี)</span><span id="SumBefore">0.00</span></div>
                                <div class="etl-srow text-secondary"><span>ภาษีมูลค่าเพิ่ม (7%)</span><span id="SumVat">0.00</span></div>
                                <div class="etl-srow fw-bold border-top pt-2 mt-1" style="font-size:16px;"><span>รวมทั้งสิ้น</span><span id="SumTotal" class="text-primary">0.00</span></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 BtnCreateLink">สร้างลิ้งค์</button>
                    </form>
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
    var ROW_SEQ = 0;

    function money(n) { return (typeof NumberFormat === "function" ? NumberFormat(n, 2) : Number(n).toFixed(2)); }

    $(document).ready(function () { 
        AddItemRow(); 
        if (typeof flatpickr !== "undefined") {
            flatpickr("#doc_date", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: true
            });
        }

        $("#customer_type").on("change", function() {
            if ($(this).val() == "2") {
                $(".branch-group").show();
            } else {
                $(".branch-group").hide();
                $("#addr_branch_name").val("");
                $("#addr_branch").val("");
            }
        });
    });

    function AddItemRow() {
        var id = ++ROW_SEQ;
        var html =
            '<tr id="r-' + id + '">' +
                '<td class="text-center idx"></td>' +
                '<td><input type="text" class="form-control form-control-sm i-name" placeholder="ชื่อสินค้า"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-center i-qty" value="1"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end i-price" value="0"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end i-disc" value="0"></td>' +
                '<td><select class="form-select form-select-sm i-vat">' +
                    '<option value="inc">รวมภาษี</option>' +
                    '<option value="exc">แยกภาษี</option>' +
                    '<option value="none">ไม่มีภาษี</option>' +
                '</select></td>' +
                '<td class="text-end fw-medium i-total">0.00</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="RemoveItemRow(' + id + ')"><span class="material-symbols-outlined" style="font-size:18px;">delete</span></button></td>' +
            '</tr>';
        $("#ItemsBody").append(html);
        $("#r-" + id).find(".i-qty, .i-price, .i-disc, .i-vat").on("input change", Recompute);
        RenumberRows();
        Recompute();
    }

    function RemoveItemRow(id) {
        $("#r-" + id).remove();
        if ($("#ItemsBody tr").length === 0) { AddItemRow(); }
        RenumberRows();
        Recompute();
    }

    function RenumberRows() {
        $("#ItemsBody tr").each(function (i) { $(this).find(".idx").text(i + 1); });
    }

    function r2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }

    function lineCalc($tr) {
        var qty = parseFloat($tr.find(".i-qty").val()) || 0;
        var price = parseFloat($tr.find(".i-price").val()) || 0;
        var disc = parseFloat($tr.find(".i-disc").val()) || 0;
        var vt = $tr.find(".i-vat").val();
        var amount = r2(qty * price - disc);
        if (amount < 0) { amount = 0; }
        var before, vat, total;
        // ปัดเศษต่อบรรทัดให้ตรงกับฝั่ง server (CreateEtaxLink.php)
        if (vt === "inc") { before = r2(amount / 1.07); vat = r2(amount - before); total = amount; }
        else if (vt === "exc") { before = amount; vat = r2(amount * 0.07); total = r2(before + vat); }
        else { before = amount; vat = 0; total = amount; }
        return { before: before, vat: vat, total: total };
    }

    function Recompute() {
        var sumB = 0, sumV = 0, sumT = 0;
        $("#ItemsBody tr").each(function () {
            var c = lineCalc($(this));
            $(this).find(".i-total").text(money(c.total));
            sumB += c.before; sumV += c.vat; sumT += c.total;
        });
        $("#SumBefore").text(money(sumB));
        $("#SumVat").text(money(sumV));
        $("#SumTotal").text(money(sumT));
    }

    $("#FormAddEtaxLink").on("submit", function (e) {
        e.preventDefault();
        var name = $("#customer_name").val().trim();
        var tax = $("#customer_tax_id").val().trim();
        var date = $("#doc_date").val().trim();

        if (!name || !tax || !date) {
            Swal.fire({ 
                        title: "แจ้งเตือน", 
                        html: '<span class="fw-bold text-danger">กรุณากรอก ชื่อลูกค้า / เลขผู้เสียภาษี / วันที่ในเอกสาร</span>', 
                        icon: "warning", 
                        showConfirmButton: false, 
                        timer: 2200 
                    });
            return;
        }

        var items = [];
        $("#ItemsBody tr").each(function () {
            var pname = $(this).find(".i-name").val().trim();
            if (!pname) { return; }
            items.push({
                product_name: pname,
                qty: parseFloat($(this).find(".i-qty").val()) || 0,
                price: parseFloat($(this).find(".i-price").val()) || 0,
                discount: parseFloat($(this).find(".i-disc").val()) || 0,
                vat_type: $(this).find(".i-vat").val()
            });
        });
        if (!items.length) {
            Swal.fire({ 
                        title: "แจ้งเตือน", 
                        html: '<span class="fw-bold text-danger">กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ</span>', 
                        icon: "warning", 
                        showConfirmButton: false, 
                        timer: 2200 
                    });
            return;
        }
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnCreateLink'); },
            type: "POST", 
            url: "core.php",
            data: {
                request_state: "list_etax_link", 
                request_function: "create",
                customer_name: name, 
                customer_type: $("#customer_type").val(),
                customer_tax_id: tax, 
                customer_address: $("#customer_address").val(),
                customer_email: $("#customer_email").val(), 
                doc_date: date,
                addr_branch_name: $("#addr_branch_name").val(),
                addr_branch: $("#addr_branch").val(),
                items: JSON.stringify(items)
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    Swal.fire({ 
                                title: "สำเร็จ", 
                                html: '<span class="fw-bold text-success">' + res.msg + '</span>', icon: "success", 
                                showConfirmButton: false, 
                                timer: 1500 
                            })
                        .then(function () { 
                            window.location.href = "etax_link_view.php?key=" + res.data.link_key; 
                                });
                } else {
                    Swal.fire({ 
                        title: "แจ้งเตือน", 
                        html: '<span class="fw-bold text-danger">' + res.msg + '</span>', 
                        icon: "error", showConfirmButton: true 
                    });
                }
            },
            complete: function () { HideLoadingButton('.BtnCreateLink'); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    });
</script>
