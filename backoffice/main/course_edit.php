<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $course_id = \App\Utility\Cipher::decrypt($key);
    $breadcrumbs = [
        ['label' => 'คอร์สเรียน', 'url' => 'course'],
        ['label' => 'แก้ไขคอร์สเรียน #' . $course_id],
    ];
?>
<?php include "header.php"; ?>

<style>
    /* ===== ปุ่มลบคอร์ส: เน้นให้เด่น (แดงจริง + ไอคอน + เงา) เพราะเป็น action สำคัญ =====
       (theme --bs-danger เป็นสีส้ม จึง override เป็นแดง var(--danger) ให้สื่อความหมาย "อันตราย") */
    .BtnDeleteCourse.btn {
        background: var(--danger); border-color: var(--danger); color: #fff;
        font-weight: 600; padding: .55rem 1.15rem;
    }
    .BtnDeleteCourse.btn:hover, .BtnDeleteCourse.btn:focus {
        background: #b91c1c; border-color: #b91c1c; color: #fff;
    }
    .BtnDeleteCourse .material-symbols-outlined { font-size: 20px; }
    /* แท็บย้ายไปเป็นคลาสกลาง .app-tabs ใน custom.css แล้ว */
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-3">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="mb-0">ดู/แก้ไขคอร์สเรียน</h2>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="course" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" style="padding: .55rem 1.15rem; font-weight: 600;"><span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับ</a>
                        <button type="button" class="btn btn-danger BtnDeleteCourse d-inline-flex align-items-center gap-2" onclick="DeleteCourse()">
                            <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                            ลบคอร์สเรียน
                        </button>
                    </div>
                </div>

                <div class="card-body p-4 pt-0">
                    <ul class="nav nav-tabs app-tabs mb-3" id="courseTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">ทั่วไป</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lesson" type="button" role="tab">บทเรียน</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-doc" type="button" role="tab">เอกสารประกอบบทเรียน</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exam" type="button" role="tab">ข้อสอบ</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                            <div id="GetFormEdit"></div>
                        </div>
                        <div class="tab-pane fade" id="tab-lesson" role="tabpanel">
                            <div id="GetLessonTab"></div>
                        </div>
                        <div class="tab-pane fade" id="tab-doc" role="tabpanel">
                            <div id="GetDocTab"></div>
                        </div>
                        <div class="tab-pane fade" id="tab-exam" role="tabpanel">
                            <div id="GetExamTab"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>

    </div>
</div>

<!-- ===== Modal: เพิ่มเอกสารประกอบ ===== -->
<div class="modal fade" id="modalLessonFile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มเอกสารประกอบการเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formLessonFile" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="lf_lesson_id">บทเรียน <span class="text-danger">*</span></label>
                        <select class="form-select" name="lesson_id" id="lf_lesson_id">
                            <option value="">กรุณาเลือกบทเรียน</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ชื่อเอกสาร <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lesson_file_name" placeholder="กรอกชื่อเอกสาร">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-medium">ไฟล์ <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="lesson_file">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 BtnAddLessonFile" onclick="SubmitAddLessonFile()">เพิ่มเอกสารใหม่</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal: เพิ่ม/แก้ไขข้อสอบ ===== -->
<div class="modal fade" id="modalExam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExamTitle">สร้างคำถามใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formExam" enctype="multipart/form-data">
                    <input type="hidden" name="exam_id" id="e_id">
                    <input type="hidden" name="exam_text" id="e_text">
                    <div class="mb-3">
                        <label class="form-label fw-medium">คำถาม <span class="text-danger">*</span></label>
                        <textarea id="editor_exam_text"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ภาพ</label>
                        <input type="file" class="form-control" name="exam_image" accept="image/*">
                        <div id="e_image_current" class="mt-2" style="display:none;">
                            <span class="text-muted small d-block mb-1">ภาพปัจจุบัน</span>
                            <img id="e_image_preview" src="" alt="ภาพคำถาม"
                                 style="max-height:160px; max-width:100%; border-radius:var(--radius-sm); border:1px solid var(--border);"
                                 onerror="this.style.display='none'">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ไฟล์</label>
                        <input type="file" class="form-control" name="exam_file">
                        <div id="e_file_current" class="mt-2" style="display:none;">
                            <a id="e_file_link" href="#" target="_blank" class="small text-primary d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-outlined" style="font-size:16px;" aria-hidden="true">description</span>ไฟล์ปัจจุบัน
                            </a>
                        </div>
                    </div>
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label fw-medium mb-0">ตัวเลือก <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-sm btn-success" onclick="AddExamChoiceRow('')">เพิ่มตัวเลือก</button>
                    </div>
                    <div id="examChoiceList"></div>
                    <div class="mb-2">
                        <label class="form-label fw-medium" for="examCorrectSelect">คำตอบที่ถูกต้อง <span class="text-danger">*</span></label>
                        <select class="form-select" id="examCorrectSelect"></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success w-100 BtnSaveExam" onclick="SubmitExam()">บันทึกคำถาม</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal: อัพโหลด Excel (ข้อสอบ) ===== -->
<div class="modal fade" id="modalUploadExam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">อัพโหลดข้อมูลด้วยไฟล์ Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    กรุณาอัพโหลดไฟล์ข้อสอบตามรูปแบบที่กำหนด โดยสามารถดาวน์โหลดตัวอย่างรูปแบบไฟล์ได้
                    <a href="sample/example_question.xlsx" download>ที่นี่</a>
                </div>
                <form id="formUploadExam" enctype="multipart/form-data">
                    <label class="form-label fw-medium">ไฟล์ข้อสอบ</label>
                    <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 BtnUploadExam" onclick="SubmitUploadExam()">อัพโหลด</button>
            </div>
        </div>
    </div>
</div>

<style>
    .tox-tinymce { border-color: #ced4da !important; border-radius: 8px; }
</style>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var COURSE_ID = <?php echo $course_id; ?>;
    var COURSE_KEY = '<?php echo $key; ?>';
    // exam editor = TinyMCE (init ตอน ready — ดู InitExamEditor / SetExamHTML / GetExamHTML)

    $(document).ready(function () {
        if (!COURSE_ID) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">ไม่พบรหัสคอร์สเรียน</span>', icon: "error", showConfirmButton: true })
                .then(() => { window.location.href = "course.php"; });
            return;
        }
        LoadGeneralTab();
        InitExamEditor();
        // TinyMCE dialogs (แทรกลิงก์) render นอก modal — กัน Bootstrap modal แย่ง focus จนพิมพ์ไม่ได้
        document.addEventListener('focusin', function (e) {
            if (e.target.closest && e.target.closest('.tox-tinymce-aux, .tox-dialog')) { e.stopImmediatePropagation(); }
        });

        // โหลดแท็บแบบ lazy ครั้งแรกที่เปิด
        var lessonLoaded = false, examLoaded = false;
        $('button[data-bs-target="#tab-lesson"]').on('shown.bs.tab', function () {
            if (!lessonLoaded && typeof LoadLessonTab === 'function') { lessonLoaded = true; LoadLessonTab(); }
        });
        $('button[data-bs-target="#tab-exam"]').on('shown.bs.tab', function () {
            if (!examLoaded && typeof LoadExamTab === 'function') { examLoaded = true; LoadExamTab(); }
        });
        var docLoaded = false;
        $('button[data-bs-target="#tab-doc"]').on('shown.bs.tab', function () {
            if (!docLoaded && typeof LoadDocTab === 'function') { docLoaded = true; LoadDocTab(); }
        });

        // เปิดแท็บตาม hash (เช่น กลับมาจากหน้าจัดการบทเรียน -> #tab-lesson)
        var hash = window.location.hash;
        if (hash) {
            var trigger = document.querySelector('button[data-bs-target="' + hash + '"]');
            if (trigger) { new bootstrap.Tab(trigger).show(); }
        }
    });

    // ===== แท็บทั่วไป: ดึงข้อมูลคอร์ส + ตัวเลือก แล้ว render ฟอร์ม (dual-mode = edit) =====
    function LoadGeneralTab() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetFormEdit"); },
            type: "POST",
            url: "core.php",
            data: { request_state: "list_course", request_function: "get_course", course_id: COURSE_ID },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    RenderEditForm(response.data);
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: true })
                        .then(() => { window.location.href = "course.php"; });
                }
            },
            complete: function () { HideLoadingOverlay("#GetFormEdit"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function RenderEditForm(data) {
        const payload = {
            groups: data.groups || [],
            types: data.types || [],
            course: data.course || null,
        };
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetFormEdit"); },
            type: "POST",
            url: "view/listCourse/FormAddCourse.php",
            data: JSON.stringify(payload),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (response) { $("#GetFormEdit").html(response); },
            complete: function () { HideLoadingOverlay("#GetFormEdit"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // ===== ลบคอร์ส (soft delete) =====
    function DeleteCourse() {
        Swal.fire({
            title: "ยืนยันการลบ",
            html: '<span class="fw-bold text-danger">ต้องการลบคอร์สเรียนนี้ใช่หรือไม่?</span>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ลบ",
            cancelButtonText: "ยกเลิก",
            confirmButtonColor: "#dc3545"
        }).then((res) => {
            if (!res.isConfirmed) { return; }
            $.ajax({
                type: "POST",
                url: "core.php",
                data: { request_state: "list_course", request_function: "delete_course", course_id: COURSE_ID },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true })
                            .then(() => { window.location.href = "course.php"; });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, timer: 2500, timerProgressBar: true });
                    }
                },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }

    // ===== แท็บบทเรียน =====
    function LoadLessonTab() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetLessonTab"); },
            type: "POST",
            url: "core.php",
            data: { request_state: "lesson", request_function: "get_list_lesson", course_id: COURSE_ID },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) { RenderLessonTable(response.data.list_data); }
                else { Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, timer: 2500 }); }
            },
            complete: function () { HideLoadingOverlay("#GetLessonTab"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function RenderLessonTable(list_data) {
        $.ajax({
            type: "POST",
            url: "view/lesson/GetTable.php",
            data: JSON.stringify({ list_data: list_data }),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (html) { $("#GetLessonTab").html(html); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function OpenAddLesson() {
        // เดิมเปิด modal — เปลี่ยนเป็นเด้งไปหน้าเพิ่มบทเรียน (หน้าเต็ม หน้าตาเดียวกับจัดการบทเรียน)
        window.location.href = "lesson_manage.php?course_key=" + COURSE_KEY;
    }
    function DeleteLesson(lesson_id) {
        Swal.fire({ title: "ยืนยันการลบ", html: '<span class="fw-bold text-danger">ต้องการลบบทเรียนนี้ใช่หรือไม่?</span>', icon: "warning", showCancelButton: true, confirmButtonText: "ลบ", cancelButtonText: "ยกเลิก", confirmButtonColor: "#dc3545" })
        .then((res) => {
            if (!res.isConfirmed) { return; }
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "lesson", request_function: "delete_lesson", lesson_id: lesson_id },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500 });
                        LoadLessonTab();
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, timer: 2500 });
                    }
                },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }
    function GotoLessonManage(lesson_key) {
        window.location.href = "lesson_manage.php?course_key=" + COURSE_KEY + "&lesson_key=" + lesson_key;
    }

    // ===== แท็บเอกสารประกอบบทเรียน =====
    function LoadDocTab() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetDocTab"); },
            type: "POST", url: "core.php",
            data: { request_state: "lesson_file", request_function: "get_list_lesson_file", course_id: COURSE_ID },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) { RenderDocTable(response.data.list_data); }
                else { ToastResult(response); }
            },
            complete: function () { HideLoadingOverlay("#GetDocTab"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function RenderDocTable(list_data) {
        $.ajax({
            type: "POST", url: "view/lessonFile/GetTable.php",
            data: JSON.stringify({ list_data: list_data }),
            contentType: "application/json; charset=utf-8", processData: false, dataType: "html",
            success: function (html) { $("#GetDocTab").html(html); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function OpenAddLessonFile() {
        $('#formLessonFile')[0].reset();
        // โหลดบทเรียนของคอร์สลง dropdown
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "lesson", request_function: "get_list_lesson", course_id: COURSE_ID },
            dataType: "json",
            success: function (response) {
                var opts = '<option value="">กรุณาเลือกบทเรียน</option>';
                if (response.result == 1) {
                    (response.data.list_data || []).forEach(function (l) {
                        opts += '<option value="' + l.lesson_id + '">' + EscapeHTML(l.lesson_name || ('บทเรียน #' + l.lesson_id)) + '</option>';
                    });
                }
                $('#lf_lesson_id').html(opts);
                new bootstrap.Modal(document.getElementById('modalLessonFile')).show();
            },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function SubmitAddLessonFile() {
        if (!$('#lf_lesson_id').val()) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาเลือกบทเรียน</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }
        if ($('#formLessonFile [name="lesson_file_name"]').val().trim() === "") {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณากรอกชื่อเอกสาร</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }
        if (!$('#formLessonFile [name="lesson_file"]').val()) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาเลือกไฟล์</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }
        var fd = new FormData($('#formLessonFile')[0]);
        fd.append('request_state', 'lesson_file');
        fd.append('request_function', 'add_lesson_file');
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnAddLessonFile'); },
            type: "POST", url: "core.php", data: fd, processData: false, contentType: false, dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalLessonFile')).hide();
                    ToastResult(response);
                    LoadDocTab();
                } else { ToastResult(response); }
            },
            complete: function () { HideLoadingButton('.BtnAddLessonFile'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function DeleteLessonFile(file_id) {
        Swal.fire({ title: "ยืนยันการลบ", html: '<span class="fw-bold text-danger">ต้องการลบเอกสารนี้ใช่หรือไม่?</span>', icon: "warning", showCancelButton: true, confirmButtonText: "ลบ", cancelButtonText: "ยกเลิก", confirmButtonColor: "#dc3545" })
        .then((res) => {
            if (!res.isConfirmed) { return; }
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "lesson_file", request_function: "delete_lesson_file", lesson_file_id: file_id },
                dataType: "json",
                success: function (response) { ToastResult(response); if (response.result == 1) { LoadDocTab(); } },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }

    // ===== แท็บข้อสอบ =====
    function LoadExamTab() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetExamTab"); },
            type: "POST", url: "core.php",
            data: {
                request_state: "exam",
                request_function: "get_list_exam",
                course_id: COURSE_ID
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) { 
                    RenderExamTable(response.data.list_data); 
                }
                else { 
                    ToastResult(response); 
                }
            },
            complete: function () { 
                HideLoadingOverlay("#GetExamTab"); 
            },
            error: function (jqXHR, exception) { 
                ShowErrorAjax(jqXHR, exception); 
            }
        });
    }
    function StripTags(h) { var t = document.createElement('div'); t.innerHTML = h || ''; return (t.textContent || t.innerText || '').trim(); }
    // ตัดข้อความยาวให้พอดีตาราง (ดูเต็มได้ตอนกดแก้ไข)
    function ExamTruncate(s, n) { s = s || ''; return s.length > n ? s.substring(0, n) + '…' : s; }
    function RenderExamTable(list_data) {
        list_data = list_data || [];
        var head = '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '<h4 class="mb-0 fw-bold">ข้อสอบ</h4>' +
            '<div class="d-flex gap-2">' +
            '<button type="button" class="btn btn-info text-white" style="display: inline-flex; align-items: center; gap: 5px;" onclick="OpenUploadExam()"><span class="material-symbols-outlined" aria-hidden="true">upload</span>อัพโหลดคำถาม</button>' +
            '<button type="button" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 5px;" onclick="OpenAddExam()"><span class="material-symbols-outlined" aria-hidden="true">add</span>เพิ่มคำถาม</button>' +
            '</div></div>';

        var rows = '';
        if (list_data.length === 0) {
            rows = '<tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อสอบ</td></tr>';
        } else {
            list_data.forEach(function (d, i) {
                var qtext   = EscapeHTML(ExamTruncate(StripTags(d.exam_text), 80));
                var fileCell = (d.exam_image || d.exam_file)
                    ? '<span class="badge bg-success">มี</span>'
                    : '<span class="text-muted">ไม่มีข้อมูล</span>';
                var correctCell = (d.correct_index && d.correct_index > 0)
                    ? 'ข้อ ' + d.correct_index
                    : '<span class="text-muted">-</span>';
                rows += '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td>' + qtext + '</td>' +
                    '<td class="text-center">' + fileCell + '</td>' +
                    '<td class="text-center">' + correctCell + '</td>' +
                    '<td class="text-center"><div class="d-flex gap-2 justify-content-center">' +
                        '<button type="button" class="btn btn-warning table-action-btn" onclick="OpenEditExam(' + d.exam_id + ')">' +
                            '<span class="material-symbols-outlined" aria-hidden="true">edit</span>แก้ไข</button>' +
                        '<button type="button" class="btn btn-danger table-action-btn" onclick="DeleteExam(' + d.exam_id + ')">' +
                            '<span class="material-symbols-outlined" aria-hidden="true">delete</span>ลบ</button>' +
                    '</div></td>' +
                    '</tr>';
            });
        }

        $("#GetExamTab").html(head +
            '<div class="default-table-area"><div class="table-responsive">' +
            '<table id="ExamTable" class="table align-middle w-100" style="width:100%"><thead><tr>' +
            '<th scope="col" class="text-center" style="width:80px">ลำดับ</th>' +
            '<th scope="col">คำถาม</th>' +
            '<th scope="col" class="text-center" style="width:120px">ไฟล์/ภาพ</th>' +
            '<th scope="col" class="text-center" style="width:140px">คำตอบที่ถูกต้อง</th>' +
            '<th scope="col" class="text-center" style="width:180px"></th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>' +
            '</div></div>');
    }

    function InitExamEditor() {
        if (typeof tinymce === 'undefined' || tinymce.get('editor_exam_text')) { return; }
        tinymce.init({
            selector: '#editor_exam_text',
            height: 200,
            menubar: false,
            elementpath: false,
            plugins: 'lists link',
            toolbar: 'bold italic underline | bullist numlist | link | removeformat',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    }
    function SetExamHTML(html) {
        var ed = (typeof tinymce !== 'undefined') ? tinymce.get('editor_exam_text') : null;
        if (ed) { ed.setContent(html || ''); }
    }
    function GetExamHTML() {
        var ed = (typeof tinymce !== 'undefined') ? tinymce.get('editor_exam_text') : null;
        if (!ed) { return ''; }
        return ed.getContent({ format: 'text' }).trim() === '' ? '' : ed.getContent();
    }
    function AddExamChoiceRow(text) {
        var row = $('<div class="input-group mb-2 exam-choice-row">' +
            '<span class="input-group-text">ข้อ</span>' +
            '<textarea class="form-control" name="choice_text[]" rows="1"></textarea>' +
            '<button type="button" class="btn btn-outline-danger" onclick="RemoveExamChoiceRow(this)">ลบ</button>' +
            '</div>');
        row.find('textarea').val(text || '');
        $('#examChoiceList').append(row);
        ReindexExamChoices();
        RefreshExamCorrectOptions();
    }
    function RemoveExamChoiceRow(btn) {
        $(btn).closest('.exam-choice-row').remove();
        ReindexExamChoices();
        RefreshExamCorrectOptions();
    }
    function ReindexExamChoices() {
        $('#examChoiceList .exam-choice-row').each(function (i) {
            $(this).find('.input-group-text').text('ข้อ ' + (i + 1));
        });
    }
    function RefreshExamCorrectOptions() {
        var prev = $('#examCorrectSelect').val();
        var n = $('#examChoiceList .exam-choice-row').length;
        var html = '<option value="">---กรุณาเลือกคำตอบที่ถูก---</option>';
        for (var i = 1; i <= n; i++) { html += '<option value="' + i + '">ตัวเลือกที่ ' + i + '</option>'; }
        $('#examCorrectSelect').html(html);
        if (prev && prev <= n) { $('#examCorrectSelect').val(prev); }
    }
    // แก้ path รูป/ไฟล์ให้แสดงได้ (ถ้าไม่ใช่ URL เต็ม ให้เติม ../ เหมือนที่อื่น)
    function ResolveExamUrl(v) {
        if (!v) { return ''; }
        return /^https?:\/\//i.test(v) ? v : '../' + v;
    }
    // แสดง/ซ่อน ภาพ+ไฟล์เดิมของข้อสอบในโหมดแก้ไข (ค่าว่าง = ซ่อน)
    function SetExamMedia(imgUrl, fileUrl) {
        if (imgUrl) { $('#e_image_preview').attr('src', imgUrl).show(); $('#e_image_current').show(); }
        else { $('#e_image_preview').attr('src', ''); $('#e_image_current').hide(); }
        if (fileUrl) { $('#e_file_link').attr('href', fileUrl); $('#e_file_current').show(); }
        else { $('#e_file_link').attr('href', '#'); $('#e_file_current').hide(); }
    }
    function OpenAddExam() {
        $('#modalExamTitle').text('สร้างคำถามใหม่');
        $('#formExam')[0].reset();
        $('#e_id').val('');
        SetExamHTML('');
        SetExamMedia('', '');
        $('#examChoiceList').empty();
        AddExamChoiceRow(''); AddExamChoiceRow('');
        new bootstrap.Modal(document.getElementById('modalExam')).show();
    }
    function OpenEditExam(eid) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "exam", request_function: "get_exam", exam_id: eid },
            dataType: "json",
            success: function (response) {
                if (response.result != 1) { ToastResult(response); return; }
                $('#modalExamTitle').text('แก้ไขคำถาม');
                $('#formExam')[0].reset();
                $('#e_id').val(eid);
                SetExamHTML(response.data.exam.exam_text || '');
                SetExamMedia(
                    ResolveExamUrl(response.data.exam.exam_image),
                    ResolveExamUrl(response.data.exam.exam_file)
                );
                $('#examChoiceList').empty();
                var correctIdx = 0;
                response.data.choices.forEach(function (c, i) {
                    AddExamChoiceRow(c.exam_choice_text || '');
                    if (String(c.exam_choice_correct) === '1') { correctIdx = i + 1; }
                });
                if (correctIdx > 0) { $('#examCorrectSelect').val(correctIdx); }
                new bootstrap.Modal(document.getElementById('modalExam')).show();
            },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function SubmitExam() {
        $('#e_text').val(GetExamHTML());

        // ===== ตรวจช่องบังคับ (คำถาม + คำตอบที่ถูก + ตัวเลือกอย่างน้อย 2 ข้อ) =====
        var eChoices = $('#examChoiceList .exam-choice-row textarea').map(function () { return $(this).val().trim(); }).get().filter(function (t) { return t !== ''; });
        if (!ValidateRequired([
            { sel: '#editor_exam_text',  label: 'คำถาม', type: 'tinymce', editorId: 'editor_exam_text' },
            { sel: '#examCorrectSelect', label: 'คำตอบที่ถูกต้อง', type: 'select' }
        ])) { return; }
        if (eChoices.length < 2) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณากรอกตัวเลือกอย่างน้อย 2 ข้อ</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }
        var isEdit = $('#e_id').val() !== '';
        var fd = new FormData($('#formExam')[0]);
        fd.append('correct', $('#examCorrectSelect').val());
        fd.append('course_id', COURSE_ID);
        fd.append('request_state', 'exam');
        fd.append('request_function', isEdit ? 'update_exam' : 'add_exam');
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSaveExam'); },
            type: "POST", url: "core.php", data: fd, processData: false, contentType: false, dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalExam')).hide();
                    ToastResult(response);
                    LoadExamTab();
                } else { ToastResult(response); }
            },
            complete: function () { HideLoadingButton('.BtnSaveExam'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function DeleteExam(eid) {
        Swal.fire({ title: "ยืนยันการลบ", html: '<span class="fw-bold text-danger">ต้องการลบข้อสอบนี้ใช่หรือไม่?</span>', icon: "warning", showCancelButton: true, confirmButtonText: "ลบ", cancelButtonText: "ยกเลิก", confirmButtonColor: "#dc3545" })
        .then((res) => {
            if (!res.isConfirmed) { return; }
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "exam", request_function: "delete_exam", exam_id: eid },
                dataType: "json",
                success: function (response) { ToastResult(response); if (response.result == 1) { LoadExamTab(); } },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }
    function OpenUploadExam() {
        $('#formUploadExam')[0].reset();
        new bootstrap.Modal(document.getElementById('modalUploadExam')).show();
    }
    function SubmitUploadExam() {
        if (!$('#formUploadExam [name="excel_file"]').val()) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาเลือกไฟล์</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }
        var fd = new FormData($('#formUploadExam')[0]);
        fd.append('course_id', COURSE_ID);
        fd.append('request_state', 'exam');
        fd.append('request_function', 'upload_exam');
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnUploadExam'); },
            type: "POST", url: "core.php", data: fd, processData: false, contentType: false, dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalUploadExam')).hide();
                    ToastResult(response);
                    LoadExamTab();
                } else { ToastResult(response); }
            },
            complete: function () { HideLoadingButton('.BtnUploadExam'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function ToastResult(response) {
        Swal.fire({
            title: response.result == 1 ? "สำเร็จ" : "แจ้งเตือน",
            html: '<span class="fw-bold ' + (response.result == 1 ? 'text-success' : 'text-danger') + '">' + response.msg + '</span>',
            icon: response.result == 1 ? "success" : "error",
            showConfirmButton: false, timer: 1800, timerProgressBar: true
        });
    }
</script>
