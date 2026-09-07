<?php $breadcrumbs = [['label' => 'ผู้ใช้/ลูกค้าทั้งหมด']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">ผู้ใช้/ลูกค้าทั้งหมด</h2>
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" onclick="ExportExcel()">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">download</span> ดาวน์โหลดรายงานผู้ใช้
                    </button>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-4">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ชื่อ / อีเมล / เลขบัตรประชาชน / เลขที่ผู้ทำบัญชี / เลขที่ผู้สอบบัญชี">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="f_verify" class="form-label fw-medium">สถานะการยืนยัน</label>
                            <select class="form-select" id="f_verify" >
                                <option value="">ทั้งหมด</option>
                                <option value="0">ยังไม่ได้ยืนยันบัตรประชาชน</option>
                                <option value="1">ระหว่างดำเนินการ</option>
                                <option value="2">ยืนยันบัตรประชาชนแล้ว</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="f_status" class="form-label fw-medium">สถานะการใช้งาน</label>
                            <select class="form-select" id="f_status" >
                                <option value="">ทั้งหมด</option>
                                <option value="1">ปกติ</option>
                                <option value="0">ระงับ</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listUser/GetTable.php -->
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

    $(document).ready(function () {
        $('#f_search').on('keypress', function (e) { if (e.which === 13) { SearchData(); } });
        GetData(1);
    });

    function SearchData() { GetData(1); }

    // สเต็ป 1: ดึงข้อมูล (JSON) จาก handler
    function GetData(page) {
        page = page || 1;
        currentPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_user",
                request_function: "get_list_user",
                search: $("#f_search").val(),
                filter_status: $("#f_status").val(),
                filter_verify: $("#f_verify").val(),
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
            type: "POST",
            url: "view/listUser/GetTable.php",
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

    // ดู/แก้ไข -> ไปหน้ารายละเอียดผู้ใช้/ลูกค้า
    function GetEditUser(user_id) {
        window.location.href = "user_edit.php?key=" + user_id;
    }

    // สลับสถานะใช้งาน/ไม่ใช้งานของผู้ใช้ (คลิกที่ป้ายสถานะในตาราง) -> update user_status
    function ToggleUserStatus(user_id, newStatus) {
        var turnOn = String(newStatus) === '1';
        Swal.fire({
            title: turnOn ? 'เปิดใช้งานผู้ใช้นี้?' : 'ปิดใช้งานผู้ใช้นี้?',
            html: turnOn
                ? '<span class="text-secondary">ผู้ใช้จะกลับมาใช้งานระบบได้</span>'
                : '<span class="text-secondary">ผู้ใช้จะถูกระงับการใช้งาน</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: turnOn ? '#198754' : '#6c757d'
        }).then(function (res) {
            if (!res.isConfirmed) { return; }
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_user",
                    request_function: "update_user_status",
                    user_id: user_id,
                    status: newStatus
                },
                dataType: "json",
                success: function (r) {
                    if (r.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true });
                        GetData(currentPage);
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถอัปเดตสถานะได้') + '</span>', icon: "error" });
                    }
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
        });
    }

    // ล็อกอินเข้าเว็บไซต์ (cpdth) แทนผู้ใช้ — มินต์ token แล้วเปิดเว็บไซต์เป็นผู้ใช้นั้น
    function LoginAsUser(user_id) {
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "list_user", request_function: "login_as_user", user_id: user_id },
                dataType: "json",
                success: function (r) {
                    if (r.result != 1) {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สำเร็จ') + '</span>', icon: "error" });
                        return;
                    }
                    var token = r.data.token;
                    // cookie: ให้ cpdth อ่านตอนโหลดหน้า PHP (path=/ ใช้ร่วมทั้งสองแอปบนโดเมนเดียวกัน)
                    document.cookie = "access_token=" + token + "; path=/; max-age=25200";
                    // localStorage: ให้ ajax ฝั่ง cpdth แนบ Bearer (same-origin ใช้ร่วมกัน)
                    try { localStorage.setItem("access_token", token); } catch (e) {}
                    window.open("../../cpdth/index.php", "_blank");
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
    }

    // ส่งออกรายการผู้ใช้/ลูกค้าเป็น Excel ตามฟิลเตอร์ที่เลือก
    function ExportExcel() {
        var body = new URLSearchParams({
            request_state: "list_user",
            request_function: "export_user",
            search: $("#f_search").val(),
            filter_status: $("#f_status").val(),
            filter_verify: $("#f_verify").val()
        });

        Swal.fire({
            title: "กำลังสร้างไฟล์ Excel...",
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });

        var token = localStorage.getItem("bo_access_token") || localStorage.getItem("access_token") || "";

        fetch("core.php", {
            method: "POST",
            headers: {
                "Authorization": "Bearer " + token
            },
            body: body
        }).then(function (res) {
            var ct = res.headers.get("Content-Type") || "";
            if (ct.indexOf("application/json") !== -1) {
                return res.json().then(function (j) { throw new Error(j.msg || "ดาวน์โหลดไม่สำเร็จ"); });
            }
            return res.blob();
        }).then(function (blob) {
            Swal.close();
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            a.href = url;
            a.download = "user_list_" + new Date().toISOString().slice(0, 10) + ".xlsx";
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }).catch(function (err) {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">' + (err.message || 'เกิดข้อผิดพลาดในการดาวน์โหลด') + '</span>',
                icon: "error",
                showConfirmButton: true
            });
        });
    }
</script>
