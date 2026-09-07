<?php
    $breadcrumbs = [['label' => 'ใบรับรองผลการสอบ']];
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $course_options = [];
    $member_options = [];
    try {
        $pdo_c = (new \App\Database\Connection())->getPdo();
        if ($pdo_c) {
            $st = $pdo_c->query("SELECT course_id, course_name FROM tbl_course WHERE delete_at IS NULL ORDER BY course_name ASC");
            $course_options = $st->fetchAll(PDO::FETCH_ASSOC);
            $st->closeCursor();
            $su = $pdo_c->query("SELECT user_id, user_firstname, user_lastname FROM tbl_user WHERE delete_at IS NULL ORDER BY user_firstname ASC");
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
                    <h2 class="mb-0">ใบรับรองผลการสอบ</h2>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-3">
                            <label for="f_course" class="form-label fw-medium">คอร์สเรียน</label>
                            <select class="form-select" id="f_course">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($course_options as $c): ?>
                                    <option value="<?php echo (int) $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="f_member" class="form-label fw-medium">ผู้เรียน</label>
                            <select class="form-select" id="f_member">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($member_options as $m): ?>
                                    <option value="<?php echo (int) $m['user_id']; ?>"><?php echo htmlspecialchars($member_label($m)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="f_approve" class="form-label fw-medium">การอนุมัติ</label>
                            <select class="form-select" id="f_approve">
                                <option value="">ทั้งหมด</option>
                                <option value="1">อนุมัติ</option>
                                <option value="0">รออนุมัติ</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listCertificate/ViewData.php -->
                    <div id="result_box"></div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<!-- ===== Modal 1: ดำเนินการอนุมัติออกใบรับรองการสอบ (เมื่อยัง "รออนุมัติ") ===== -->
<div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">ดำเนินการอนุมัติออกใบรับรองการสอบ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <table class="table table-sm mb-0 align-middle">
                            <tbody>
                                <tr><td class="text-secondary" style="width:42%">ผู้สอบ</td><td class="fw-medium" id="ap_fullname">-</td></tr>
                                <tr><td class="text-secondary">เลขที่บัตรประชาชน</td><td class="fw-medium" id="ap_citizen">-</td></tr>
                                <tr><td class="text-secondary">เลขที่ผู้ทำบัญชี</td><td class="fw-medium" id="ap_cpd">-</td></tr>
                                <tr><td class="text-secondary">เลขที่ผู้สอบบัญชี</td><td class="fw-medium" id="ap_cpa">-</td></tr>
                                <tr><td class="text-secondary">หมายเลขโทรศัพท์</td><td class="fw-medium" id="ap_phone">-</td></tr>
                                <tr><td class="text-secondary">อีเมล</td><td class="fw-medium" id="ap_email">-</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0 align-middle">
                            <tbody>
                                <tr><td class="text-secondary" style="width:42%">คอร์สเรียน</td><td class="fw-medium" id="ap_course">-</td></tr>
                                <tr><td class="text-secondary">หมายเลขใบรับรอง</td><td class="fw-medium" id="ap_certno">-</td></tr>
                                <tr><td class="text-secondary">จำนวนข้อ / จำนวนข้อที่ทำ</td><td class="fw-medium" id="ap_num">-</td></tr>
                                <tr><td class="text-secondary">คะแนนขั้นต่ำ</td><td class="fw-medium" id="ap_min">-</td></tr>
                                <tr><td class="text-secondary">คะแนนที่ได้รับ</td><td class="fw-medium" id="ap_score">-</td></tr>
                                <tr><td class="text-secondary">คิดเป็นเปอร์เซ็นต์</td><td class="fw-medium" id="ap_percent">-</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="alert alert-danger mt-3 mb-0 py-2 px-3 small">กรุณาตรวจสอบข้อมูลก่อนยืนยันการทำรายการ ไม่สามารถเปลี่ยนแปลงสถานะได้ในภายหลัง</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 BtnApprove" onclick="ConfirmApprove()">ยืนยันการออกใบรับรอง</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal 2: ปรับปรุงไฟล์ยืนยันตัวตนในเอกสาร (เมื่อ "อนุมัติแล้ว") ===== -->
<div class="modal fade" id="modalIdentity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">ปรับปรุงไฟล์รูปใบรับรอง (บัตร ปชช.)
            </h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <h6 class="fw-bold mb-2 text-primary">รูปในใบรับรอง (บัตร ปชช.)</h6>
                    <div id="ManageIdCardWrapper" class="bg-light p-2 rounded position-relative d-inline-block w-100" style="cursor: pointer; border: 2px dashed #adb5bd;" onclick="document.getElementById('UploadNewIdentity').click()" title="คลิกเพื่ออัปโหลดรูปใหม่">
                        <div id="ManageIdCard"><div class="text-muted">-</div></div>
                        <div class="mt-2 text-primary small fw-bold">คลิกที่นี่เพื่อเปลี่ยนรูป</div>
                    </div>
                    <input type="file" class="d-none" id="UploadNewIdentity" accept="image/*" onchange="PreviewNewIdentity(this)">
                </div>
                
                <div class="alert alert-danger mt-3 mb-0 py-2 px-3 small">
                    <strong>หมายเหตุ:</strong> ต้องเลือกไฟล์รูปภาพใหม่ก่อนกดยืนยัน รูปภาพใหม่จะถูกนำไปใช้ในใบรับรองทันที
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 BtnUpdateIdentity" onclick="ConfirmUpdateIdentity()">ยืนยันการอัปเดต</button>
            </div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var currentEnrollId = 0;
    var certPage = 1;

    $(document).ready(function () {
        if ($.fn.select2) {
            $('#f_course').select2({ width: '100%' });
            $('#f_member').select2({ width: '100%' });
        }
        $(document).on('input change', '#ed_exam_score, #ed_exam_total', function () {
            var score = parseFloat($('#ed_exam_score').val());
            var total = parseFloat($('#ed_exam_total').val());
            if (!isNaN(score) && !isNaN(total) && total > 0) {
                var pct = (score / total * 100).toFixed(2);
                $('#ed_score_percent').val(pct);
            }
        });
        GetData(1);
    });

    function SearchData() { GetData(1); }

    // สเต็ป 1: ดึงข้อมูล (JSON) จาก handler
    function GetData(page) {
        page = page || 1;
        certPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_certificate",
                request_function: "get_list_certificate",
                f_course: $("#f_course").val(),
                f_member: $("#f_member").val(),
                f_status: $("#f_status").val(),
                f_approve: $("#f_approve").val(),
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
            url: "view/listCertificate/ViewData.php",
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

    // ดูใบรับรอง -> ดึงเลขทะเบียนของผู้ใช้ก่อน แล้วให้เลือกเฉพาะประเภทที่ "มีเลข"
    //   ผู้ทำบัญชี (cpd) = user_cpd_no, ผู้สอบบัญชี (cpa) = user_cpa_no
    //   ถ้าคอลัมน์ไหนว่าง จะไม่โชว์ประเภทนั้นใน dropdown
    // ประเภทที่เลือกจะเปลี่ยนคำในใบรับรอง (เลขทะเบียน/รหัสหลักสูตร/ข้อความ "สำหรับผู้ทำบัญชี/ผู้สอบบัญชี")
    function DownloadCert(id, enroll_key) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { 
                    request_state: "list_certificate", 
                    request_function: "get_certificate", 
                    enroll_id: id },    
            dataType: "json",
            success: function (r) {
                if (r.result != 1) {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error" });
                    return;
                }
                // สร้างตัวเลือกเฉพาะประเภทที่ผู้ใช้มีเลขทะเบียน
                var opts = {};
                if (String(r.data.cpd_no || '').trim() !== '') { opts.cpd = "ผู้ทำบัญชี"; }
                if (String(r.data.cpa_no || '').trim() !== '') { opts.cpa = "ผู้สอบบัญชี"; }
                var keys = Object.keys(opts);

                if (keys.length === 0) {
                    Swal.fire({ title: "ไม่สามารถออกใบรับรองได้", html: '<span class="text-secondary">ผู้ใช้นี้ยังไม่มีเลขที่ผู้ทำบัญชีหรือเลขที่ผู้สอบบัญชี</span>', icon: "warning" });
                    return;
                }
                // เปิดหน้าพรีวิว PDF ทันที ไม่ต้องเลือกประเภทแล้ว
                OpenCertPreview(id, "", enroll_key);
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // เปิดหน้าพรีวิว PDF ใบรับรองตามประเภทที่เลือก
    function OpenCertPreview(id, certType, enroll_key) {
        window.open("pdf_preview.php?type=certificate&key=" + enroll_key + "&cert_type=" + certType, "_blank");
    }

    // ดำเนินการ: ดึงข้อมูลแล้วเลือกโมดัลตามสถานะอนุมัติ
    // ยังไม่อนุมัติ (cert_approved=0) -> โมดัลอนุมัติออกใบรับรอง
    // อนุมัติแล้ว (cert_approved=1)   -> โมดัลปรับปรุงไฟล์ยืนยันตัวตน
    function OpenManage(id) {
        currentEnrollId = id;
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_certificate", request_function: "get_certificate", enroll_id: id },
            dataType: "json",
            success: function (r) {
                if (r.result != 1) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error" }); return; }
                if (r.data.cert_approved == 1) { OpenIdentityModal(r.data); }
                else { OpenApproveModal(r.data); }
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    function EditData(id) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_certificate", request_function: "get_certificate", enroll_id: id },
            dataType: "json",
            success: function (r) {
                if (r.result != 1) { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error" }); return; }
                window.location.href = "view/listCertificate/EditData.php?key=" + r.data.enroll_key;
            },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    function OpenApproveModal(d) {
        var dash = function (v) { return (v === null || v === undefined || v === '') ? '-' : v; };
        $("#ap_fullname").text(dash(d.fullname));
        $("#ap_citizen").text(dash(d.citizen_id));
        $("#ap_cpd").text(dash(d.cpd_no));
        $("#ap_cpa").text(dash(d.cpa_no));
        $("#ap_phone").text(dash(d.phone));
        $("#ap_email").text(dash(d.email));
        $("#ap_course").text(dash(d.course_name));
        $("#ap_certno").text("รอดำเนินการอนุมัติ");
        $("#ap_num").text(d.num_exam + " ข้อ / " + d.num_exam + " ข้อ");
        $("#ap_min").text(d.min_score + " คะแนน");
        $("#ap_score").text((d.score === null ? "-" : d.score) + " คะแนน");
        $("#ap_percent").text((d.percent === null ? "-" : d.percent) + " %");
        new bootstrap.Modal(document.getElementById("modalApprove")).show();
    }

    function OpenIdentityModal(d) {
        var imgTag = function (src) {
            return src ? '<img src="' + src + '" class="img-fluid rounded border" style="max-height: 200px;" onerror="this.parentNode.innerHTML=\'<div class=&quot;text-muted&quot;>ไม่พบรูป</div>\'">' : '<div class="text-muted">ไม่มีรูป</div>';
        };
        $("#ManageIdCard").html(imgTag(d.id_card_image));
        $("#UploadNewIdentity").val('');
        new bootstrap.Modal(document.getElementById("modalIdentity")).show();
    }

    function PreviewNewIdentity(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#ManageIdCard').html('<img src="' + e.target.result + '" class="img-fluid rounded border" style="max-height: 200px;">');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ยืนยันการออกใบรับรอง -> อนุมัติ (set enroll_is_completed='1')
    function ConfirmApprove() {
        if (!currentEnrollId) { return; }
        Swal.fire({
            title: "ยืนยันการออกใบรับรอง",
            html: '<span class="text-secondary">ยืนยันอนุมัติออกใบรับรองรายการนี้? ไม่สามารถเปลี่ยนแปลงสถานะได้ในภายหลัง</span>',
            icon: "warning", showCancelButton: true, confirmButtonText: "ยืนยัน", cancelButtonText: "ยกเลิก"
        }).then(function (res) {
            if (!res.isConfirmed) { return; }
            ShowLoadingButton('.BtnApprove');
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "list_certificate", request_function: "approve_certificate", enroll_id: currentEnrollId },
                dataType: "json",
                success: function (r) {
                    HideLoadingButton('.BtnApprove');
                    if (r.result == 1) {
                        bootstrap.Modal.getInstance(document.getElementById("modalApprove")).hide();
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 })
                            .then(function () { GetData(certPage); });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error" });
                    }
                },
                error: function (j, e) { HideLoadingButton('.BtnApprove'); ShowErrorAjax(j, e); }
            });
        });
    }

    // ยืนยันการอัปเดต -> ปรับรูปยืนยันตัวตนในใบรับรองให้เป็นรูปปัจจุบัน หรือรูปใหม่ที่อัปโหลด
    function ConfirmUpdateIdentity() {
        if (!currentEnrollId) { return; }
        Swal.fire({
            title: "ยืนยันการอัปเดต",
            html: '<span class="text-secondary">ปรับปรุงรูปยืนยันตัวตนในใบรับรองให้เป็นรูปเอกสารปัจจุบัน หรือรูปที่อัปโหลดใหม่?</span>',
            icon: "warning", showCancelButton: true, confirmButtonText: "ยืนยัน", cancelButtonText: "ยกเลิก"
        }).then(function (res) {
            if (!res.isConfirmed) { return; }
            ShowLoadingButton('.BtnUpdateIdentity');
            
            var formData = new FormData();
            formData.append('request_state', 'list_certificate');
            formData.append('request_function', 'update_identity');
            formData.append('enroll_id', currentEnrollId);
            
            var fileInput = document.getElementById('UploadNewIdentity');
            if (fileInput.files.length > 0) {
                formData.append('new_identity', fileInput.files[0]);
            } else {
                Swal.fire("กรุณาเลือกไฟล์", "กรุณาอัปโหลดรูปภาพใหม่ก่อนกดยืนยัน", "warning");
                HideLoadingButton('.BtnUpdateIdentity');
                return;
            }
            
            $.ajax({
                type: "POST", url: "core.php",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (r) {
                    HideLoadingButton('.BtnUpdateIdentity');
                    if (r.result == 1) {
                        bootstrap.Modal.getInstance(document.getElementById("modalIdentity")).hide();
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error" });
                    }
                },
                error: function (j, e) { HideLoadingButton('.BtnUpdateIdentity'); ShowErrorAjax(j, e); }
            });
        });
    }
</script>
