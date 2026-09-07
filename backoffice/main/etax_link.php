<?php $breadcrumbs = [['label' => 'ลิ้งค์ออกใบกำกับภาษี (E-Tax)']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">
        <?php include "navbar.php"; ?>
        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">ลิงก์ออกใบกำกับภาษี (E-Tax)</h2>
                    <a href="etax_link_fromadd" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">add</span> สร้างลิ้งค์ใหม่
                    </a>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="f_search">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="เลขใบกำกับ / ชื่อลูกค้า">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium" for="f_doc_status">สถานะเอกสาร</label>
                            <select class="form-select" id="f_doc_status">
                                <option value="">ทั้งหมด</option>
                                <option value="1">ออกใบกำกับภาษีแล้ว</option>
                                <option value="2">ยกเลิก</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium" for="f_link_status">สถานะลิงค์</label>
                            <select class="form-select" id="f_link_status">
                                <option value="">ทั้งหมด</option>
                                <option value="1">ใช้งานได้</option>
                                <option value="0">ปิดใช้งาน</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listEtaxLink/ViewData.php -->
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
    var currentPage = 1;

    function publicLinkUrl(token) {
        var base = location.href.split('/main/')[0];
        return base + '/etax_link_pdf.php?token=' + token;
    }

    $(document).ready(function () {
        $('#f_search').on('keypress', function (e) { if (e.which === 13) { SearchData(); } });
        $('#f_doc_status, #f_link_status').on('change', function () { GetData(1); });
        GetData(1);
    });

    function SearchData() { GetData(1); }

    // สเต็ป 1: ดึงข้อมูล (JSON) จาก handler
    function GetData(page) {
        page = page || 1;
        currentPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_etax_link",
                request_function: "get_list",
                f_doc_status: $("#f_doc_status").val(),
                f_link_status: $("#f_link_status").val(),
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
            type: "POST", url: "view/listEtaxLink/ViewData.php",
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

    function CopyLink(token) {
        var url = publicLinkUrl(token);
        var done = function () {
            Swal.fire({ toast: true, position: "top-end", icon: "success", title: "คัดลอกลิ้งค์แล้ว", showConfirmButton: false, timer: 1500 });
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(function () { window.prompt("คัดลอกลิ้งค์:", url); });
        } else {
            window.prompt("คัดลอกลิ้งค์:", url);
        }
    }

    function DownloadEtaxLink(id, link_key) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_etax_link", request_function: "get", link_id: id },
            dataType: "json",
            success: function (r) {
                if (r.result != 1) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || "ไม่พบข้อมูล") + '</span>', icon: "error" }); return; }
                var taxId = ((r.data.link && r.data.link.tax_id) || "").replace(/\D/g, "");
                var pass = taxId.length >= 4 ? taxId.slice(-4) : taxId;
                var displayPwd = pass || "ไม่ได้ตั้งค่าไว้";
                var targetUrl = "pdf_preview.php?type=etaxlink&key=" + link_key;

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
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    function ToggleLink(id) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_etax_link", request_function: "toggle_link", link_id: id },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    Swal.fire({ toast: true, position: "top-end", icon: "success", title: res.msg, showConfirmButton: false, timer: 1400 });
                    GetData(currentPage);
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                }
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    function DeleteLink(id) {
        Swal.fire({
            title: "ยืนยันการลบ",
            html: '<span class="text-secondary">ต้องการลบลิ้งค์ใบกำกับภาษีนี้ใช่หรือไม่?</span>',
            icon: "warning", showCancelButton: true, confirmButtonText: "ลบ", cancelButtonText: "ปิด", confirmButtonColor: "#dc3545"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "list_etax_link", request_function: "delete", link_id: id },
                dataType: "json",
                success: function (res) {
                    if (res.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + res.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1400 }).then(function () { GetData(currentPage); });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error", showConfirmButton: true });
                    }
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
        });
    }
</script>
