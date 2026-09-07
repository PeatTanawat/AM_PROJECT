<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $link_id = \App\Utility\Cipher::decrypt($key);
    $breadcrumbs = [
        ['label' => 'ลิ้งค์ออกใบกำกับภาษี (E-Tax)', 'url' => 'etax_link'],
        ['label' => 'รายละเอียด'],
    ];
?>
<?php include "header.php"; ?>

<style>
    .etx-row { display: flex; gap: 12px; padding: 9px 0; border-bottom: 1px solid #f0f1f4; }
    .etx-row:last-child { border-bottom: 0; }
    .etx-label { color: #8695AA; width: 170px; flex-shrink: 0; }
    .etx-value { font-weight: 500; color: #212529; flex: 1; word-break: break-word; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">
        <?php include "navbar.php"; ?>
        <div class="px-2">

            <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
                <a href="etax_link" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" 
                            style="font-size: 18px; font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;">
                        arrow_back
                    </span>
                    กลับ
                </a>
                <div class="d-flex flex-wrap gap-2" id="ViewButtons"></div>
            </div>

            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-1">ใบกำกับภาษี <span id="EtxNo">-</span></h4>
                    <div class="text-secondary mb-4">วันที่ในเอกสาร <span id="EtxDate">-</span> · <span id="EtxStatus"></span> <span id="EtxLinkStatus"></span></div>

                    <!-- ลิ้งค์สำหรับลูกค้า -->
                    <div class="mb-4">
                        <label for="PublicLink" class="form-label fw-medium">ลิ้งค์สำหรับลูกค้า (เปิดโหลด PDF โดยไม่ต้องเข้าสู่ระบบ)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="PublicLink" readonly>
                            <button type="button" class="btn btn-secondary d-inline-flex align-items-center gap-1" onclick="CopyPublicLink()">
                                <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">content_copy</span> คัดลอก
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="etx-row"><div class="etx-label">ประเภท</div><div class="etx-value" id="EtxType">-</div></div>
                            <div class="etx-row"><div class="etx-label">ชื่อลูกค้า</div><div class="etx-value" id="EtxName">-</div></div>
                            <div class="etx-row"><div class="etx-label">เลขประจำตัวผู้เสียภาษี</div><div class="etx-value" id="EtxTaxId">-</div></div>
                            <div class="etx-row etx-branch-row" style="display:none;"><div class="etx-label">ชื่อสาขา</div><div class="etx-value" id="EtxBranchName">-</div></div>
                            <div class="etx-row etx-branch-row" style="display:none;"><div class="etx-label">รหัสสาขา</div><div class="etx-value" id="EtxBranch">-</div></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="etx-row"><div class="etx-label">ที่อยู่</div><div class="etx-value" id="EtxAddress">-</div></div>
                            <div class="etx-row"><div class="etx-label">อีเมล</div><div class="etx-value" id="EtxEmail">-</div></div>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3">รายการสินค้า</h6>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr class="text-secondary">
                                    <th class="text-center" style="width:60px;">ลำดับ</th>
                                    <th>ชื่อสินค้า</th>
                                    <th class="text-center">จำนวน</th>
                                    <th class="text-end">ราคา</th>
                                    <th class="text-end">ส่วนลด</th>
                                    <th class="text-center">VAT</th>
                                    <th class="text-end">ราคารวม</th>
                                </tr>
                            </thead>
                            <tbody id="EtxItems"></tbody>
                            <tfoot id="EtxFoot"></tfoot>
                        </table>
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
    var LINK_ID = '<?php echo $link_id; ?>';
    var LINK_KEY = '<?php echo $key; ?>';
    var LINK_DATA = null;

    function money(n) { return (typeof NumberFormat === "function" ? NumberFormat(n, 2) : Number(n).toFixed(2)) + " บาท"; }
    function vatLabel(t) { if (t === "inc") return "รวมภาษี"; if (t === "exc") return "แยกภาษี"; return "ไม่มีภาษี"; }
    function publicLinkUrl(token) { var base = location.href.split('/main/')[0]; return base + '/etax_link_pdf.php?token=' + token; }

    $(document).ready(function () {
        if (!LINK_ID) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">ไม่พบรหัสลิ้งค์</span>', icon: "error", showConfirmButton: true }).then(function () { window.location.href = "etax_link"; });
            return;
        }
        LoadLink();
    });

    function LoadLink() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("body"); },
            type: "POST", url: "core.php",
            data: { request_state: "list_etax_link", request_function: "get", link_id: LINK_ID },
            dataType: "json",
            success: function (res) {
                if (res.result != 1) {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true }).then(function () { window.location.href = "etax_link"; });
                    return;
                }
                Render(res.data);
            },
            complete: function () { HideLoadingOverlay("body"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function Render(d) {
        LINK_DATA = d;
        var l = d.link;
        $("#EtxNo").text(l.etax_no);
        $("#EtxDate").text(l.doc_date);
        $("#EtxStatus").html(l.doc_status === "2" ? '<span class="badge bg-danger">ยกเลิก</span>' : '<span class="badge bg-success">ออกใบกำกับภาษีแล้ว</span>');
        $("#EtxLinkStatus").html(l.link_status === "0" ? '<span class="badge bg-secondary">ลิงค์ปิดใช้งาน</span>' : '<span class="badge bg-success">ลิงค์ใช้งานได้</span>');
        $("#PublicLink").val(publicLinkUrl(l.token));
        $("#EtxType").text(l.type);
        $("#EtxName").text(l.name);
        $("#EtxTaxId").text(l.tax_id || "-");
        
        if (l.type === "นิติบุคคล") {
            $(".etx-branch-row").css("display", "flex");
            $("#EtxBranchName").text(l.branch_name || "-");
            $("#EtxBranch").text(l.branch || "-");
        } else {
            $(".etx-branch-row").css("display", "none");
        }

        $("#EtxAddress").text(l.address || "-");
        $("#EtxEmail").text(l.email || "-");

        var rows = "";
        d.items.forEach(function (it, i) {
            rows +=
                '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td>' + EscapeHTML(it.product_name) + '</td>' +
                    '<td class="text-center">' + (it.qty % 1 === 0 ? it.qty : it.qty.toFixed(2)) + '</td>' +
                    '<td class="text-end">' + money(it.price) + '</td>' +
                    '<td class="text-end">' + money(it.discount) + '</td>' +
                    '<td class="text-center">' + vatLabel(it.vat_type) + '</td>' +
                    '<td class="text-end">' + money(it.line_total) + '</td>' +
                '</tr>';
        });
        if (!d.items.length) { rows = '<tr><td colspan="7" class="text-center text-muted">ไม่มีรายการ</td></tr>'; }
        $("#EtxItems").html(rows);

        $("#EtxFoot").html(
            '<tr><td colspan="5"></td><td class="text-secondary text-end">รวม</td><td class="fw-bold text-end">' + money(l.subtotal) + '</td></tr>' +
            '<tr><td colspan="5"></td><td class="text-secondary text-end">VAT</td><td class="fw-bold text-end">' + money(l.vat) + '</td></tr>' +
            '<tr><td colspan="5"></td><td class="fw-bold text-end">รวมทั้งสิ้น</td><td class="fw-bold text-end text-primary">' + money(l.total) + '</td></tr>'
        );

        var linkOn = l.link_status === "1";
        $("#ViewButtons").html(
            '<button type="button" class="btn ' + (linkOn ? 'btn-outline-secondary' : 'btn-warning') + '" onclick="ToggleLink()">' + (linkOn ? 'ปิดใช้งานลิ้งค์' : 'เปิดใช้งานลิ้งค์') + '</button>' +
            '<button type="button" class="btn btn-success" onclick="DownloadEtaxLink()">ดาวน์โหลดใบกำกับภาษี</button>'
        );
    }

    function CopyPublicLink() {
        var url = $("#PublicLink").val();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                Swal.fire({ toast: true, position: "top-end", icon: "success", title: "คัดลอกลิ้งค์แล้ว", showConfirmButton: false, timer: 1500 });
            }).catch(function () { window.prompt("คัดลอกลิ้งค์:", url); });
        } else { window.prompt("คัดลอกลิ้งค์:", url); }
    }

    function DownloadEtaxLink() {
        if (!LINK_DATA) { return; }
        var taxId = (LINK_DATA.link.tax_id || "").replace(/\D/g, "");
        var pass = taxId.length >= 4 ? taxId.slice(-4) : taxId;
        var displayPwd = pass || "ไม่ได้ตั้งค่าไว้";
        var targetUrl = "pdf_preview.php?type=etaxlink&key=" + LINK_KEY;

        var win = window.open(targetUrl, "_blank");
        if (win) {
            try { win.blur(); } catch (e) {}
        }

        Swal.fire({
            icon: "success",
            title: "ดาวน์โหลดใบกำกับภาษี",
            html: 'รหัสเปิดไฟล์ใบกำกับภาษีของคุณคือ <b style="font-size:1.3em;">' + displayPwd + '</b>',
            confirmButtonText: "คัดลอก",
            confirmButtonColor: "#605DFF",
            showCloseButton: true,
            allowOutsideClick: true,
            didOpen: function () {
                var pullFocus = function () {
                    if (win && !win.closed) {
                        try { win.blur(); } catch (e) {}
                    }
                    window.focus();
                    var confirmBtn = Swal.getConfirmButton();
                    if (confirmBtn) confirmBtn.focus();
                };
                pullFocus();
                [50, 100, 200, 400, 700].forEach(function (delay) {
                    setTimeout(pullFocus, delay);
                });
            },
            preConfirm: function () {
                if (pass) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(pass);
                    } else {
                        var textArea = document.createElement("textarea");
                        textArea.value = pass;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand("copy");
                        document.body.removeChild(textArea);
                    }
                }
                var icon = Swal.getIcon();
                if (icon && icon.parentNode) {
                    var newIcon = icon.cloneNode(true);
                    icon.parentNode.replaceChild(newIcon, icon);
                }
                return false;
            }
        });
    }


    function ToggleLink() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay(); }, complete: function () { HideLoadingOverlay(); },
            type: "POST", url: "core.php",
            data: { request_state: "list_etax_link", request_function: "toggle_link", link_id: LINK_ID },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    Swal.fire({ toast: true, position: "top-end", icon: "success", title: res.msg, showConfirmButton: false, timer: 1400 });
                    LoadLink();
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                }
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }
</script>
