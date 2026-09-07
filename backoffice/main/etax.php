<?php $breadcrumbs = [['label' => 'ใบกำกับภาษี (E-Tax)']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h4 class="mb-0">ใบกำกับภาษี (E-Tax)</h4>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="เลขที่เอกสาร / ชื่อลูกค้า / เลขประจำตัวผู้เสียภาษี">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listEtax/ViewData.php -->
                    <div id="result_box"></div>
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
    var etaxPage = 1;

    $(document).ready(function () {
        $('#f_search').on('keypress', function (e) { if (e.which === 13) { SearchData(); } });
        GetData(1);
    });

    function SearchData() { GetData(1); }

    // สเต็ป 1: ดึงข้อมูล (JSON) จาก handler
    function GetData(page) {
        page = page || 1;
        etaxPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_etax",
                request_function: "get_list_etax",
                search: $("#f_search").val(),
                page: page
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    view_data(r.data);
                } else {
                    $("#result_box").html('');
                    HideLoadingOverlay("#result_box");
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error" });
                }
            },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // สเต็ป 2: ส่งข้อมูลไป render เป็น HTML แล้วแปะใน #result_box
    function view_data(payload) {
        $.ajax({
            type: "POST", url: "view/listEtax/ViewData.php",
            data: {
                data:     payload.list,
                total:    payload.total,
                page:     payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) { $("#result_box").html(html); HideLoadingOverlay("#result_box"); },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // ดูใบกำกับภาษี -> แจ้งรหัสผ่าน (4 ตัวท้ายเลขผู้เสียภาษี) แล้วเปิดหน้าพรีวิว PDF
    function DownloadEtax(order_id, order_key) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_order", request_function: "get_order", order_id: order_id },
            dataType: "json",
            success: function (r) {
                if (r.result != 1) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || "ไม่พบข้อมูล") + '</span>', icon: "error" }); return; }
                var citizenId = ((r.data.order && r.data.order.user_citizen_id) || "").replace(/\D/g, "");
                var pass = citizenId.length >= 4 ? citizenId.slice(-4) : citizenId;
                var displayPwd = pass || "ไม่ได้ตั้งค่าไว้";
                var targetUrl = "pdf_preview.php?type=etax&key=" + order_key;

                var win = window.open(targetUrl, "_blank");
                if (win) {
                    try { win.blur(); } catch (e) {}
                }

                Swal.fire({
                    icon: "success",
                    title: "ดาวน์โหลดใบกำกับภาษี",
                    html: 'รหัสผ่านใบกำกับภาษีของคุณคือ <b style="font-size:1.3em;">' + displayPwd + '</b>',
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
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // ส่งใบกำกับภาษีทางอีเมลให้ลูกค้า
    function SendEmail(order_id) {
        Swal.fire({
            title: "ส่งใบกำกับภาษีทางอีเมล?",
            html: '<span class="text-secondary">ระบบจะส่งใบกำกับภาษีไปยังอีเมลของลูกค้า</span>',
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "ส่งอีเมล",
            cancelButtonText: "ยกเลิก",
            confirmButtonColor: "#605DFF"
        }).then(function (res) {
            if (!res.isConfirmed) { return; }
            Swal.fire({ title: "กำลังส่งอีเมล...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "list_etax", request_function: "send_email", order_id: order_id },
                dataType: "json",
                success: function (r) {
                    Swal.close();
                    Swal.fire({
                        title: r.result == 1 ? "สำเร็จ" : "แจ้งเตือน",
                        html: '<span class="fw-bold ' + (r.result == 1 ? 'text-success' : 'text-danger') + '">' + r.msg + '</span>',
                        icon: r.result == 1 ? "success" : "error",
                        showConfirmButton: true
                    });
                },
                error: function (jqXHR, exception) { Swal.close(); ShowErrorAjax(jqXHR, exception); }
            });
        });
    }
</script>
