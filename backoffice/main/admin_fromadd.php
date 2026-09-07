<?php
    $breadcrumbs = [
        ['label' => 'ผู้ดูแลระบบทั้งหมด', 'url' => 'admin'],
        ['label' => 'เพิ่มผู้ดูแลระบบใหม่'],
    ];

?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center p-4">
                    <h2 class="mb-0">เพิ่มผู้ดูแลระบบใหม่</h2>
                    <a href="admin" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับ
                    </a>
                </div>

                <div class="card-body p-4">
                    <form id="FormAddAdmin" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="admin_name" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="">
                            </div>
                            <div class="col-md-6">
                                <label for="user_email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="user_email" name="user_email" value="">
                            </div>

                            <div class="col-md-6">
                                <label for="user_password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                <div class="login-field position-relative">
                                    <input type="password" class="form-control" id="user_password" name="user_password" value="" autocomplete="new-password">
                                    <button type="button" class="login-eye" aria-label="แสดงหรือซ่อนรหัสผ่าน" aria-pressed="false" onclick="(function(b){var i=document.getElementById('user_password');var show=i.type==='password';i.type=show?'text':'password';b.setAttribute('aria-pressed',String(show));var g=b.querySelector('.material-symbols-outlined');if(g){g.textContent=show?'visibility':'visibility_off';}})(this)">
                                        <span class="material-symbols-outlined" aria-hidden="true">visibility_off</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="user_password_confirm" class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                <div class="login-field position-relative">
                                    <input type="password" class="form-control" id="user_password_confirm" name="user_password_confirm" value="" autocomplete="new-password">
                                    <button type="button" class="login-eye" aria-label="แสดงหรือซ่อนรหัสผ่าน" aria-pressed="false" onclick="(function(b){var i=document.getElementById('user_password_confirm');var show=i.type==='password';i.type=show?'text':'password';b.setAttribute('aria-pressed',String(show));var g=b.querySelector('.material-symbols-outlined');if(g){g.textContent=show?'visibility':'visibility_off';}})(this)">
                                        <span class="material-symbols-outlined" aria-hidden="true">visibility_off</span>
                                    </button>
                                </div>
                            </div>

                            <!-- สิทธิ์การใช้งาน: โหลดจาก tbl_slidebar (ค่าเริ่มต้นติ๊กทุกเมนูยกเว้น "ผู้ดูแลระบบ") -->
                            <div class="col-12">
                                <label class="form-label d-block mb-2">สิทธิ์การใช้งาน</label>
                                <div class="row g-2" id="PermissionList"></div>
                            </div>

                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary w-100 py-2">ยืนยันการเพิ่มผู้ดูแลระบบ</button>
                            </div>
                        </div>
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
    $(document).ready(function () { LoadMenus(); });

    // โหลดเมนู -> ติ๊กทุกเมนูยกเว้น "ผู้ดูแลระบบ" (default ตามที่กำหนด)
    function LoadMenus() {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_admin", request_function: "get_menus" },
            dataType: "json",
            success: function (response) {
                if (response.result != 1) { return; }
                var html = "";
                (response.data.menus || []).forEach(function (m) {
                    var checked = (m.menu_name === "ผู้ดูแลระบบ") ? "" : "checked";
                    html +=
                        '<div class="col-md-4">' +
                            '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" name="menu_ids[]" value="' + m.menu_id + '" id="perm_' + m.menu_id + '" ' + checked + '>' +
                                '<label class="form-check-label" for="perm_' + m.menu_id + '">' + EscapeHTML(m.menu_name) + '</label>' +
                            '</div>' +
                        '</div>';
                });
                $("#PermissionList").html(html);
            },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    $(document).on('submit', '#FormAddAdmin', function (e) {
        e.preventDefault();

        // ===== ตรวจช่องบังคับให้ครบ =====
        if (!ValidateRequired([
            { sel: '#admin_name',            label: 'ชื่อ-นามสกุล' },
            { sel: '#user_email',            label: 'อีเมล' },
            { sel: '#user_password',         label: 'รหัสผ่าน' },
            { sel: '#user_password_confirm', label: 'ยืนยันรหัสผ่าน' }
        ])) { return; }

        var pwd  = $('[name="user_password"]').val();
        var pwd2 = $('[name="user_password_confirm"]').val();
        if (pwd !== pwd2) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน</span>', icon: "warning", confirmButtonText: "ตกลง" });
            return;
        }

        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#FormAddAdmin"); },
            type: "POST",
            url: "core.php",
            data: $(this).serialize() + "&request_state=list_admin&request_function=add_admin",
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true, didClose: function () { window.location.href = "admin"; } });
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", confirmButtonText: "ตกลง" });
                }
            },
            complete: function () { HideLoadingOverlay("#FormAddAdmin"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    });
</script>
