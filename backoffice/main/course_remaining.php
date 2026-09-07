<?php
    $breadcrumbs = [['label' => 'คอร์สเรียนคงเหลือในระบบ']];
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $course_options = [];
    $member_options = [];
    try {
        $pdo_c = (new \App\Database\Connection())->getPdo();
        if ($pdo_c) {
            $st = $pdo_c->query("SELECT course_id, course_name FROM tbl_course WHERE delete_at IS NULL ORDER BY course_name ASC");
            $course_options = $st->fetchAll(PDO::FETCH_ASSOC);
            $st->closeCursor();
            $su = $pdo_c->query("SELECT DISTINCT u.user_id, u.user_firstname, u.user_lastname FROM tbl_user u INNER JOIN tbl_course_enrollment e ON u.user_id = e.enroll_user_id WHERE u.delete_at IS NULL AND e.delete_at IS NULL ORDER BY u.user_firstname ASC");
            $member_options = $su->fetchAll(PDO::FETCH_ASSOC);
            $su->closeCursor();
        }
    } catch (\Throwable $e) {}
    $member_label = function ($m) {
        $n = trim(($m['user_firstname'] ?? '') . ' ' . ($m['user_lastname'] ?? ''));
        return $n !== '' ? $n : ('user#' . $m['user_id']);
    };
?>
<?php include "header.php"; ?>

<style>
    /* ปรับความสูงของ Select2 ให้เท่ากับ form-select ปกติ (ประมาณ 38px) */
    .select2-container .select2-selection--single {
        height: 38px !important;
        background-color: var(--bs-body-bg) !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        padding: 5px 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        color: var(--bs-body-color) !important;
        padding-left: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">คอร์สเรียนคงเหลือในระบบ</h2>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info text-white" style="display: inline-flex; align-items: center; gap: 5px;" onclick="SendAllExpiryEmails()"> 
                            <span class="material-symbols-outlined" aria-hidden="true">mail</span> แจ้งเตือนคอร์สหมดอายุทั้งหมด
                        </button>
                        <button type="button" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 5px;" onclick="OpenAdd()"> 
                            <span class="material-symbols-outlined" aria-hidden="true">add</span> เพิ่มสิทธิ์การเข้าถึงคอร์สเรียน
                        </button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="f_course">คอร์สเรียน</label>
                            <select class="form-select tom-course" id="f_course">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($course_options as $c): ?>
                                    <option value="<?php echo (int) $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="f_member">สมาชิก</label>
                            <select class="form-select tom-member" id="f_member">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($member_options as $m): ?>
                                    <option value="<?php echo (int) $m['user_id']; ?>"><?php echo htmlspecialchars($member_label($m)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium" for="f_status">สถานะ</label>
                            <select class="form-select" id="f_status">
                                <option value="">ทั้งหมด</option>
                                <option value="1">ใช้งาน</option>
                                <option value="0">ยกเลิก</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                           <button type="button" class="btn btn-primary flex-grow-1" 
                                                 style="display: inline-flex; align-items: center; justify-content: center; gap: 5px;" 
                                                 onclick="SearchData()">
                                <span class="material-symbols-outlined" aria-hidden="true">search</span>ค้นหา
                            </button>
                            <button type="button" class="btn btn-info text-white"  style="display: inline-flex; align-items: center; justify-content: center; gap: 5px;"  onclick="DownloadReport()" title="ดาวน์โหลด">
                                <span class="material-symbols-outlined align-middle" style="font-size:18px;" aria-hidden="true">download</span>
                            </button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listEnrollment/ViewData.php -->
                    <div id="result_box"></div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<!-- ===== Modal: เพิ่มสิทธิ์ ===== -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">เพิ่มสิทธิ์การเข้าถึงคอร์สใหม่</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-medium" for="add_course">คอร์สเรียน</label>
                    <select class="form-select tom-course" id="add_course">
                        <option value="">--- กรุณาเลือกคอร์สเรียน ---</option>
                        <?php foreach ($course_options as $c): ?>
                            <option value="<?php echo (int) $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium" for="add_member">สมาชิก</label>
                    <select class="form-select tom-member" id="add_member">
                        <option value="">--- กรุณาเลือกลูกค้า ---</option>
                        <?php foreach ($member_options as $m): ?>
                            <option value="<?php echo (int) $m['user_id']; ?>"><?php echo htmlspecialchars($member_label($m)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium" for="add_expiry">วันที่หมดอายุ (เว้นว่างไว้หากไม่มีกำหนด)</label>
                    <input type="text" class="form-control" id="add_expiry" placeholder="วัน/เดือน/ปี" autocomplete="off">
                </div>
                <div class="alert alert-danger small mb-0">กรุณาตรวจสอบข้อมูลให้ถูกต้อง ระบบจะเพิ่มสิทธิ์และส่งอีเมลแจ้งเตือนผู้ใช้งานทันทีหลังกดบันทึก</div>
            </div>
            <div class="modal-footer p-3"><button type="button" class="btn btn-primary w-100 BtnAdd" onclick="SubmitAdd()">บันทึก</button></div>
        </div>
    </div>
</div>

<!-- ===== Modal: แก้ไขสิทธิ์ ===== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">แก้ไขสิทธิ์การเข้าถึงคอร์ส</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <input type="hidden" id="edit_id">
                <input type="hidden" id="edit_orig_status">
                <div class="mb-3">
                    <label class="form-label fw-medium" for="edit_status">สถานะ</label>
                    <select class="form-select" id="edit_status" onchange="ToggleEditExpiry()">
                        <option value="1">ให้สิทธิ์การใช้งาน</option>
                        <option value="0">ยกเลิกสิทธิ์การใช้งาน</option>
                    </select>
                </div>
                <div class="mb-3" id="edit_expiry_wrap">
                    <label class="form-label fw-medium" for="edit_expiry">วันที่หมดอายุ (เว้นว่างไว้หากไม่มีกำหนด)</label>
                    <input type="text" class="form-control" id="edit_expiry" placeholder="วัน/เดือน/ปี" autocomplete="off">
                </div>
                <div class="alert alert-warning small mb-0" id="edit_cancel_note" style="display:none;">
                    ยกเลิกสิทธิ์แล้วผู้เรียนจะเข้าคอร์สไม่ได้ แต่กลับมาเปิดใช้งานใหม่ได้ภายหลัง
                </div>
            </div>
            <div class="modal-footer p-3"><button type="button" class="btn btn-primary w-100 BtnEdit" onclick="SubmitEdit()">บันทึก</button></div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var currentPage = 1;

    $(document).ready(function () {
        // dropdown ค้นหาได้ (select2)
        if ($.fn.select2) {
            $('#f_course, #f_member').select2({ width: '100%' });
            $('#add_course, #add_member').select2({ 
                width: '100%',
                dropdownParent: $('#modalAdd')
            });
        }
        if (typeof flatpickr !== "undefined") {
            flatpickr("#add_expiry", { dateFormat: "d/m/Y", allowInput: true });
            flatpickr("#edit_expiry", { dateFormat: "d/m/Y", allowInput: true });
        }
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
                request_state: "list_enrollment",
                request_function: "get_list_enrollment",
                f_course: $("#f_course").val(),
                f_member: $("#f_member").val(),
                f_status: $("#f_status").val(),
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
            type: "POST", url: "view/listEnrollment/ViewData.php",
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

    // ===== เพิ่มสิทธิ์ =====
    function OpenAdd() {
        $("#add_course").val("").trigger("change");
        $("#add_member").val("").trigger("change");
        $("#add_expiry").val("");
        new bootstrap.Modal(document.getElementById("modalAdd")).show();
    }
    function SubmitAdd() {
        var course = $("#add_course").val(), member = $("#add_member").val();
        if (!course) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาเลือกคอร์สเรียน</span>', icon: "warning", showConfirmButton: false, timer: 1500 }); return; }
        if (!member) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาเลือกสมาชิก</span>', icon: "warning", showConfirmButton: false, timer: 1500 }); return; }
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnAdd'); },
            type: "POST", url: "core.php",
            data: { request_state: "list_enrollment", request_function: "add_enrollment", course_id: course, user_id: member, expiry: $("#add_expiry").val() },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById("modalAdd")).hide();
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1800 }).then(function () { GetData(1); });
                } else { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error", showConfirmButton: true }); }
            },
            complete: function () { HideLoadingButton('.BtnAdd'); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // ===== แก้ไขสิทธิ์ =====
    function OpenEdit(id) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_enrollment", request_function: "get_enrollment", enroll_id: id },
            dataType: "json",
            success: function (r) {
                if (r.result != 1) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error" }); return; }
                $("#edit_id").val(r.data.enroll_id);
                $("#edit_status").val(r.data.status || "1");
                $("#edit_orig_status").val(r.data.status || "1");
                $("#edit_expiry").val(r.data.expiry || "");
                ToggleEditExpiry();
                new bootstrap.Modal(document.getElementById("modalEdit")).show();
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }
    // ปรับการแสดงช่องวันหมดอายุ/โน้ตตามสถานะที่เลือก
    function ToggleEditExpiry() {
        var sel = $("#edit_status").val();
        var orig = $("#edit_orig_status").val();
        var reactivating = (sel === "1" && orig === "0");   // ยกเลิก -> ใช้งาน
        // ช่องวันหมดอายุ: แสดงเมื่อสถานะ = ใช้งาน (ทั้งแก้ไขปกติ + เปิดใหม่ — ว่าง=ไม่มีกำหนด)
        $("#edit_expiry_wrap").toggle(sel === "1");
        $("#edit_reactivate_note").toggle(reactivating);
        $("#edit_cancel_note").toggle(sel === "0");
        // เปิดใหม่จากที่ยกเลิก -> ตั้ง default วันหมดอายุ = วันนี้ + 90 วัน (แก้ไข/ล้างได้)
        if (reactivating) { SetEditExpiry(Plus90Days()); }
    }
    function Plus90Days() {
        var d = new Date(); d.setDate(d.getDate() + 90);
        return ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth() + 1)).slice(-2) + "/" + d.getFullYear();
    }
    function SetEditExpiry(val) {
        var el = document.getElementById("edit_expiry");
        if (el && el._flatpickr) { el._flatpickr.setDate(val, false, "d/m/Y"); }
        else { $("#edit_expiry").val(val); }
    }
    function SubmitEdit() {
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnEdit'); },
            type: "POST", url: "core.php",
            data: { request_state: "list_enrollment", request_function: "update_enrollment", enroll_id: $("#edit_id").val(), status: $("#edit_status").val(), expiry: $("#edit_expiry").val() },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById("modalEdit")).hide();
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 }).then(function () { GetData(currentPage); });
                } else { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error", showConfirmButton: true }); }
            },
            complete: function () { HideLoadingButton('.BtnEdit'); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // ===== ปลดล็อกสิทธิ์ =====
    function UnlockCourse(id) {
        Swal.fire({
            title: 'ยืนยันการปลดล็อก?',
            text: 'คุณต้องการปลดล็อกสิทธิ์การเข้าสอบสำหรับคอร์สนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    beforeSend: function () { Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); },
                    type: "POST", url: "core.php",
                    data: { request_state: "list_enrollment", request_function: "unlock_enrollment", enroll_id: id },
                    dataType: "json",
                    success: function (r) {
                        if (r.result == 1) {
                            Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 }).then(function () { GetData(currentPage); });
                        } else { 
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error", showConfirmButton: true }); 
                        }
                    },
                    error: function (j, e) { ShowErrorAjax(j, e); }
                });
            }
        });
    }

    // ===== ส่งเมลแจ้งเตือนหมดอายุ =====
    function SendExpiryEmail(id) {
        Swal.fire({
            title: 'ยืนยันการส่งอีเมล?',
            text: 'คุณต้องการส่งอีเมลแจ้งเตือนการใกล้หมดอายุไปยังผู้เรียนใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยันการส่ง',
            cancelButtonText: 'ยกเลิก'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    beforeSend: function () { Swal.fire({ title: 'กำลังส่งอีเมล...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); },
                    type: "POST", url: "core.php",
                    data: { request_state: "list_enrollment", request_function: "send_expiry_email", enroll_id: id },
                    dataType: "json",
                    success: function (r) {
                        if (r.result == 1) {
                            Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 2000 });
                        } else { 
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error", showConfirmButton: true }); 
                        }
                    },
                    error: function (j, e) { ShowErrorAjax(j, e); }
                });
            }
        });
    }

    // ===== ส่งเมลแจ้งเตือนหมดอายุสำหรับทุกคนที่มีรายชื่อคอร์สหมดอายุใน 10 วัน =====
    function SendAllExpiryEmails() {
        Swal.fire({
            title: 'แจ้งเตือนหมดอายุทั้งหมด?',
            text: 'ระบบจะส่งอีเมลแจ้งเตือนไปยังผู้เรียนทุกคนที่มีคอร์สเรียนใกล้หมดอายุภายใน 10 วัน คุณต้องการดำเนินการใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยันการส่งทั้งหมด',
            cancelButtonText: 'ยกเลิก'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    beforeSend: function () { Swal.fire({ title: 'กำลังส่งอีเมลแจ้งเตือนแบบกลุ่ม...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); },
                    type: "POST", url: "core.php",
                    data: { request_state: "list_enrollment", request_function: "send_all_expiry_emails" },
                    dataType: "json",
                    success: function (r) {
                        if (r.result == 1) {
                            Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: true });
                        } else { 
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error", showConfirmButton: true }); 
                        }
                    },
                    error: function (j, e) { ShowErrorAjax(j, e); }
                });
            }
        });
    }

    // ===== ดาวน์โหลด Excel (ตามฟิลเตอร์) =====
    function DownloadReport() {
        var body = new URLSearchParams({ request_state: "list_enrollment", request_function: "export_report", f_course: $("#f_course").val() || "", f_member: $("#f_member").val() || "" });
        Swal.fire({ title: "กำลังสร้างรายงาน...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        fetch("core.php", { method: "POST", headers: { "Authorization": "Bearer " + (localStorage.getItem("bo_access_token") || "") }, body: body })
            .then(function (res) {
                var ct = res.headers.get("Content-Type") || "";
                if (ct.indexOf("application/json") !== -1) { return res.json().then(function (j) { throw new Error(j.msg || "ดาวน์โหลดไม่สำเร็จ"); }); }
                return res.blob();
            })
            .then(function (blob) {
                Swal.close();
                var url = URL.createObjectURL(blob);
                var a = document.createElement("a"); a.href = url; a.download = "course_remaining.xlsx";
                document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
            })
            .catch(function (err) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + err.message + '</span>', icon: "error", showConfirmButton: true }); });
    }
</script>
