<?php
    $breadcrumbs = [['label' => 'ตั้งค่าเว็บไซต์']];
?>
<?php include "header.php"; ?>


<style>
    /* ช่องกรอกพื้นหลังขาว (ธีมตั้ง .form-control เป็นเทา #F6F7F9) */
    #FormSetting .form-control,
    #FormSetting .form-select,
    #FormSetting .form-control:focus,
    #FormSetting .form-select:focus { background-color: #fff !important; }
    /* TinyMCE Border Styling */
    .tox-tinymce { border-color: #ced4da !important; border-radius: 8px; }
    .setting-section-title { letter-spacing: .02em; }
    /* ===== อัปโหลดรูป (ลากวาง/คลิก) + ปุ่ม X ลบ ===== */
    .img-dropzone {
        border: 2px dashed #c7cbe0; border-radius: 12px; background: #fbfbff;
        padding: 28px 20px; text-align: center; cursor: pointer; min-height: 160px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
        transition: border-color .15s, background .15s;
    }
    .img-dropzone:hover, .img-dropzone.dragover { border-color: var(--brand-500); background: var(--brand-soft); }
    .img-dz-icon { font-size: 44px; color: var(--brand-500); }
    .img-preview-wrap { position: relative; display: inline-block; max-width: 100%; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: #f7f8fb; }
    .img-preview-wrap img { max-width: 100%; max-height: 320px; display: block; }
    .img-remove-x {
        position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; padding: 0; border: 0;
        border-radius: 50%; background: rgba(0,0,0,.6); color: #fff; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; transition: background .15s;
    }
    .img-remove-x:hover { background: #dc3545; }
    .img-remove-x .material-symbols-outlined { font-size: 20px; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center p-4">
                    <h2 class="mb-0">ตั้งค่าเว็บไซต์</h2>
                </div>

                <div class="card-body p-4">
                    <form id="FormSetting" autocomplete="off" enctype="multipart/form-data">

                        <!-- ===== การชำระเงิน ===== -->
                        <h6 class="fw-bold text-secondary text-uppercase mb-3 setting-section-title">การชำระเงิน</h6>
                        <label class="form-label fw-medium d-block">ช่องทางการชำระเงิน</label>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm_credit" name="credit_card" value="1">
                                <label class="form-check-label" for="pm_credit">บัตรเครดิต / บัตรเดบิต</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm_qr" name="qr_promptpay" value="1">
                                <label class="form-check-label" for="pm_qr">พร้อมเพย์ (QR PromptPay)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pm_bank" name="bank_transfer" value="1">
                                <label class="form-check-label" for="pm_bank">โอนเงินผ่านธนาคาร</label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- ===== ทั่วไป ===== -->
                        <h6 class="fw-bold text-secondary text-uppercase mb-3 setting-section-title">ทั่วไป</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">รหัสหน่วยงาน <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="department_code" placeholder="เช่น 06-330">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">การกดข้ามวิดีโอในบทเรียน</label>
                                <select class="form-select" name="allow_skip_video">
                                    <option value="0">ปิด (กดข้ามไม่ได้)</option>
                                    <option value="1">เปิด (กดข้ามได้)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">การส่ง OTP ระหว่างเรียน</label>
                                <select class="form-select" name="otp_enabled">
                                    <option value="0">ปิดใช้งาน</option>
                                    <option value="1">เปิดใช้งาน</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">คำนวณภาษีมูลค่าเพิ่ม (VAT 7%)</label>
                                <select class="form-select" name="tax_enabled">
                                    <option value="0">ปิดใช้งาน</option>
                                    <option value="1">เปิดใช้งาน</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">เลขผู้เสียภาษี 13 หลัก <small class="text-muted">(ระบบ ETAX(G))</small></label>
                                <input type="text" class="form-control" name="tax_id" maxlength="13" placeholder="0105565002221">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">รหัสสาขา <small class="text-muted">(ระบบ ETAX(G))</small></label>
                                <input type="text" class="form-control" name="branch_code" placeholder="00000">
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- ===== หน้าแรก ===== -->
                        <h6 class="fw-bold text-secondary text-uppercase mb-3 setting-section-title">หน้าแรก</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">ลิงก์ Youtube </label>
                                <input type="text" class="form-control" name="youtube_id" placeholder="วางลิงก์ Youtube">
                            </div>
                            <!-- <div class="col-md-6">
                                <label class="form-label fw-medium">ลิงก์ตอบแบบสอบถาม</label>
                                <input type="text" class="form-control" name="question_link" placeholder="วางลิงก์ตอบแบบสอบถาม">
                            </div> -->
                            <div class="col-12">
                                <label class="form-label fw-medium">ข้อความแถวที่ 1</label>
                                 <textarea id="editor_text_1"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">รูปภาพหน้าแรก <small class="text-muted">(jpg, png, webp, gif · ไม่เกิน 5MB)</small></label>
                                <input type="file" id="image_file_input" name="image_file" accept="image/*" hidden>
                                <input type="hidden" name="remove_image" id="remove_image" value="0">
                                <!-- ยังไม่มีรูป: โซนคลิก/ลากวางเพื่อใส่รูป -->
                                <div id="imgDrop" class="img-dropzone">
                                    <span class="material-symbols-outlined img-dz-icon" aria-hidden="true">add_photo_alternate</span>
                                    <div class="fw-medium">คลิกเพื่อเลือกรูป หรือ ลากไฟล์มาวางที่นี่</div>
                                </div>
                                <!-- มีรูปแล้ว: แสดงรูป + ปุ่ม X ลบ (มุมขวาบน) -->
                                <div id="imgBox" class="img-preview-wrap d-none">
                                    <img id="ImgPreview" src="" alt="รูปภาพหน้าแรก">
                                    <button type="button" class="img-remove-x" onclick="RemoveImage()" title="ลบรูป" aria-label="ลบรูป">
                                        <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">ข้อความแถวที่ 2</label>
                                 <textarea id="editor_text_2"></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- ===== โซเชียล & ข้อมูลติดต่อ ===== -->
                        <h6 class="fw-bold text-secondary text-uppercase mb-3 setting-section-title">โซเชียล & ข้อมูลติดต่อ</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Facebook</label>
                                <input type="text" class="form-control" name="facebook_link" placeholder="https://facebook.com/...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">X (Twitter)</label>
                                <input type="text" class="form-control" name="x_link" placeholder="https://x.com/...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">LINE</label>
                                <input type="text" class="form-control" name="line_link" placeholder="https://line.me/...">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-medium">ลิงก์ประเมินตอบแบบสอบถาม</label>
                                <input type="text" class="form-control" name="question_link" placeholder="https://...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">เกี่ยวกับเรา</label>
                                 <textarea id="editor_about_us"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">ติดต่อเรา</label>
                                <textarea id="editor_contact_us"></textarea>
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-4">
                            <button type="submit" class="btn btn-primary w-100 py-2">บันทึกการตั้งค่า</button>
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
    $(document).ready(function () {
        InitTinyMCEs();
        InitImgDrop();
    });

    function InitTinyMCEs() {
        if (typeof tinymce === 'undefined') { return; }
        
        var initCount = 0;
        function checkInit() {
            initCount++;
            if (initCount === 4) {
                LoadSetting();
            }
        }

        var commonConfig = {
            menubar: false,
            elementpath: false,
            plugins: 'lists wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            setup: function (editor) {
                editor.on('init', checkInit);
            }
        };

        tinymce.init($.extend({}, commonConfig, {
            selector: '#editor_text_1',
            height: 220,
            placeholder: 'เขียนข้อความแถวที่ 1 ตรงนี้...'
        }));

        tinymce.init($.extend({}, commonConfig, {
            selector: '#editor_text_2',
            height: 220,
            placeholder: 'เขียนข้อความแถวที่ 2 ตรงนี้...'
        }));

        tinymce.init($.extend({}, commonConfig, {
            selector: '#editor_about_us',
            height: 180,
            placeholder: 'เขียนข้อความเกี่ยวกับเรา...'
        }));

        tinymce.init($.extend({}, commonConfig, {
            selector: '#editor_contact_us',
            height: 180,
            placeholder: 'เขียนข้อความติดต่อเรา...'
        }));
    }

    function LoadSetting() {
        $.ajax({
            beforeSend: function () {
                ShowLoadingOverlay("#FormSetting");
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "website_setting",
                request_function: "get_setting"
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    FillForm(response.data);
                }
                else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    });
                }
            },
            complete: function () {
                HideLoadingOverlay("#FormSetting");
            },
            error: function (jqXHR, exception) {
                ShowErrorAjax(jqXHR, exception);
            }
        });
    }

    function FillForm(d) {
        var s = (d && d.setting) ? d.setting : null;
        var p = (d && d.payment) ? d.payment : null;
        var f = $("#FormSetting");

        // ช่องทางชำระเงิน: ถ้ายังไม่มีแถว -> ค่าเริ่มต้นเปิดทั้งหมด (ตาม default ของตาราง)
        $('#pm_credit').prop('checked', p ? String(p.credit_card) === "1" : true);
        $('#pm_qr').prop('checked', p ? String(p.qr_promptpay) === "1" : true);
        $('#pm_bank').prop('checked', p ? String(p.bank_transfer) === "1" : true);

        // ทั่วไป
        f.find('[name="department_code"]').val(s ? (s.department_code || "") : "");
        f.find('[name="allow_skip_video"]').val(s ? String(s.allow_skip_video || "0") : "0");
        f.find('[name="otp_enabled"]').val(s ? String(s.otp_enabled || "0") : "0");
        f.find('[name="tax_enabled"]').val(s ? String(s.tax_enabled || "0") : "0");
        f.find('[name="tax_id"]').val(s ? (s.tax_id || "") : "");
        f.find('[name="branch_code"]').val(s ? (s.branch_code || "") : "");

        // หน้าแรก
        var yt_val = s ? (s.youtube_id || "") : "";
        f.find('[name="youtube_id"]').val(yt_val ? "https://www.youtube.com/watch?v=" + yt_val : "");
        if (typeof tinymce !== 'undefined') {
            if (tinymce.get('editor_text_1')) { tinymce.get('editor_text_1').setContent(s ? (s.text_1 || "") : ""); }
            if (tinymce.get('editor_text_2')) { tinymce.get('editor_text_2').setContent(s ? (s.text_2 || "") : ""); }
        }

        // รูปภาพปัจจุบัน -> มีรูป: แสดงรูป+ปุ่มลบ, ไม่มี: โซนลากวาง
        $('#remove_image').val('0');
        var _imgInput = document.getElementById('image_file_input'); if (_imgInput) { _imgInput.value = ''; }
        if (s && s.image_path) {
            $('#ImgPreview').attr('src', /^https?:\/\//.test(s.image_path) ? s.image_path : '../' + s.image_path);
            $('#imgDrop').addClass('d-none');
            $('#imgBox').removeClass('d-none');
        } else {
            $('#imgBox').addClass('d-none');
            $('#imgDrop').removeClass('d-none');
        }

        // โซเชียล & ติดต่อ
        f.find('[name="facebook_link"]').val(s ? (s.facebook_link || "") : "");
        f.find('[name="x_link"]').val(s ? (s.x_link || "") : "");
        f.find('[name="line_link"]').val(s ? (s.line_link || "") : "");
        f.find('[name="question_link"]').val(s ? (s.question_link || "") : "");
        if (typeof tinymce !== 'undefined') {
            if (tinymce.get('editor_about_us')) { tinymce.get('editor_about_us').setContent(s ? (s.about_us || "") : ""); }
            if (tinymce.get('editor_contact_us')) { tinymce.get('editor_contact_us').setContent(s ? (s.contact_us || "") : ""); }
        }
    }

    // ===== อัปโหลดรูป (ลากวาง/คลิก) + พรีวิว + ลบ =====
    function InitImgDrop() {
        var zone = document.getElementById('imgDrop');
        var input = document.getElementById('image_file_input');
        if (!zone || !input) { return; }
        zone.addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () { if (input.files && input.files.length) { ShowPickedImg(input.files[0]); } });
        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); zone.classList.add('dragover'); });
        });
        ['dragleave', 'dragend'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); zone.classList.remove('dragover'); });
        });
        zone.addEventListener('drop', function (e) {
            e.preventDefault(); e.stopPropagation(); zone.classList.remove('dragover');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) { return; }
            if (!/^image\//.test(files[0].type)) {
                Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาวางไฟล์รูปภาพเท่านั้น</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
                return;
            }
            input.files = files;
            ShowPickedImg(files[0]);
        });
    }
    // เลือกรูปใหม่ -> พรีวิวทันที (ยกเลิกสถานะ "ลบรูป")
    function ShowPickedImg(file) {
        var reader = new FileReader();
        reader.onload = function (e) { ShowImg(e.target.result); };
        reader.readAsDataURL(file);
        $('#remove_image').val('0');
    }
    // แสดงรูป (ซ่อนโซนลากวาง)
    function ShowImg(src) {
        $('#ImgPreview').attr('src', src);
        $('#imgDrop').addClass('d-none');
        $('#imgBox').removeClass('d-none');
    }
    // กด X ลบรูป -> กลับไปโซนลากวาง + สั่ง backend ลบรูปเดิม (ถ้ามี)
    function RemoveImage() {
        var input = document.getElementById('image_file_input');
        if (input) { input.value = ''; }
        $('#ImgPreview').attr('src', '');
        $('#imgBox').addClass('d-none');
        $('#imgDrop').removeClass('d-none');
        $('#remove_image').val('1');
    }

    $(document).on('submit', '#FormSetting', function (e) {
        e.preventDefault();

        var fd = new FormData(this);
        // TinyMCE -> ฟิลด์
        if (typeof tinymce !== 'undefined') {
            fd.set('text_1', tinymce.get('editor_text_1') ? tinymce.get('editor_text_1').getContent() : '');
            fd.set('text_2', tinymce.get('editor_text_2') ? tinymce.get('editor_text_2').getContent() : '');
            fd.set('about_us', tinymce.get('editor_about_us') ? tinymce.get('editor_about_us').getContent() : '');
            fd.set('contact_us', tinymce.get('editor_contact_us') ? tinymce.get('editor_contact_us').getContent() : '');
        }
        // checkbox -> 0/1 (unchecked จะไม่ถูกส่งใน FormData จึงกำหนดเอง)
        fd.set('credit_card', $('#pm_credit').prop('checked') ? '1' : '0');
        fd.set('qr_promptpay', $('#pm_qr').prop('checked') ? '1' : '0');
        fd.set('bank_transfer', $('#pm_bank').prop('checked') ? '1' : '0');
        fd.append('request_state', 'website_setting');
        fd.append('request_function', 'update_setting');

        Swal.fire({
            title: "ยืนยันการบันทึก?",
            text: "คุณต้องการบันทึกการตั้งค่าเว็บไซต์ใช่หรือไม่",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยืนยัน",
            cancelButtonText: "ยกเลิก"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            $.ajax({
                beforeSend: function () { ShowLoadingOverlay("#FormSetting"); },
                type: "POST",
                url: "core.php",
                data: fd,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true, didClose: function () { LoadSetting(); } });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", confirmButtonText: "ตกลง" });
                    }
                },
                complete: function () { HideLoadingOverlay("#FormSetting"); },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    });
</script>
