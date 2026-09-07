<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $course_key  = isset($_GET['course_key']) ? trim($_GET['course_key']) : '';
    $lesson_key  = isset($_GET['lesson_key']) ? trim($_GET['lesson_key']) : '';
    $course_id   = \App\Utility\Cipher::decrypt($course_key);
    $lesson_id   = \App\Utility\Cipher::decrypt($lesson_key);
    $is_add      = $lesson_id <= 0; // ไม่มี lesson_id = โหมดเพิ่มบทเรียนใหม่ (หน้าเต็ม แทน modal เดิม)
    $course_key  = \App\Utility\Cipher::encrypt($course_id);
    $breadcrumbs = [
    ['label' => 'คอร์สเรียน', 'url' => 'course'],
    ['label' => 'แก้ไขคอร์สเรียน #' . $course_id, 'url' => 'course_edit.php?key=' . $course_key],
    ['label' => $is_add ? 'เพิ่มบทเรียนใหม่' : 'จัดการบทเรียน'],
    ];
?>
<?php include "header.php"; ?>

<style>
    .tox-tinymce { border-color: #ced4da !important; border-radius: 8px; }
    /* ช่องกรอกพื้นหลังขาว (ธีมตั้ง .form-control เป็นเทา #F6F7F9) */
    #formLesson .form-control,
    #formLesson .form-select,
    #formLesson .form-control:focus,
    #formLesson .form-select:focus { background-color: #fff !important; }
    /* เส้นคั่นแนวตั้งระหว่าง 2 คอลัมน์ (เฉพาะจอใหญ่) */
    @media (min-width: 992px) {
        .lesson-col-right { border-left: 1px solid var(--border); }
    }
    /* ===== โซนลากวางไฟล์วิดีโอ ===== */
    .video-dropzone {
        border: 2px dashed #c7cbe0; border-radius: 12px; background: #fbfbff;
        padding: 28px 20px; text-align: center; cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .video-dropzone:hover { border-color: var(--brand-500); background: var(--brand-soft); }
    .video-dropzone.dragover { border-color: var(--brand-500); background: var(--brand-soft); }
    .video-dropzone.is-disabled { opacity: .55; cursor: not-allowed; pointer-events: none; }
    .video-dropzone { min-height: 260px; text-align: center; }
    .vdz-icon { font-size: 48px; color: var(--brand-500); }
    .vdz-title { font-weight: 500; margin-top: 6px; }
    /* ===== พรีวิววิดีโอ (เต็มพื้นที่คอลัมน์ขวา) ===== */
    #videoPreview iframe { width: 100% !important; height: 100% !important; border: 0; background: #000; }
    /* พรีวิว local <video>: ปรับขนาดตามอัตราส่วนจริง (กันช่องว่างเมื่อวิดีโอแนวตั้ง) สูงสุด 60vh + จัดกึ่งกลาง */
    #videoPreview video { display: block; max-width: 100%; max-height: 60vh; margin: 0 auto; border: 0; background: #000; }
    /* ปุ่มลบวิดีโอ (X มุมขวาบนของวิดีโอ) */
    .video-preview-wrap { position: relative; }
    .video-remove-x {
        position: absolute; top: 8px; right: 8px; z-index: 5;
        width: 32px; height: 32px; padding: 0; border: 0; border-radius: 50%;
        background: rgba(0, 0, 0, .6); color: #fff; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .15s;
    }
    .video-remove-x:hover { background: #dc3545; }
    .video-remove-x .material-symbols-outlined { font-size: 20px; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-3">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="mb-0"><?php echo $is_add ? 'เพิ่มบทเรียนใหม่' : 'จัดการบทเรียน'; ?></h2>
                        <a href="course_edit.php?key=<?php echo $course_key; ?>#tab-lesson"
                           class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับไปหน้าบทเรียน
                        </a>
                    </div>

                    <ul class="nav nav-tabs app-tabs mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-l-general" type="button">บทเรียน &amp; วิดีโอ</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-l-question" type="button">คำถามระหว่างรับชม</button></li>
                    </ul>

                    <div class="tab-content">
                        <!-- ===== บทเรียน & วิดีโอ (รวมแท็บทั่วไป + วีดีโอ) ===== -->
                        <div class="tab-pane fade show active" id="tab-l-general" role="tabpanel">
                            <div class="row g-4">

                                <!-- ===== ซ้าย: ตั้งค่าบทเรียน + คำถาม/OTP ===== -->
                                <div class="col-lg-6">
                                    <form id="formLesson">
                                        <h6 class="fw-bold text-secondary text-uppercase mb-3" style="letter-spacing:.02em;">ข้อมูลบทเรียน</h6>
                                        <div class="row g-3">
                                            <div class="col-4">
                                                <label class="form-label fw-medium">ลำดับ/บทเรียนที่ <span class="text-danger">*</span></label>
                                                <input type="number" min="0" class="form-control" name="lesson_order" placeholder="0">
                                            </div>
                                            <div class="col-8">
                                                <label class="form-label fw-medium">ชื่อบทเรียน <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="lesson_name" placeholder="กรอกชื่อบทเรียน">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium">รายละเอียดโดยย่อ</label>
                                                <textarea class="form-control" name="lesson_overview" rows="4"></textarea>
                                            </div>
                                        </div>

                                        <!-- <h6 class="fw-bold text-secondary text-uppercase mb-3 mt-4" style="letter-spacing:.02em;">การตั้งค่าคำถาม &amp; OTP ระหว่างรับชม</h6> -->
                                        <!-- <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium">สถานะการแสดงคำถามและ OTP ระหว่างรับชม <span class="text-danger">*</span></label>
                                                <select class="form-select" name="lesson_question">
                                                    <option value="0">ปิดใช้งาน </option>
                                                    <option value="1">เปิดใช้งาน</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label fw-medium">จำนวนคำถาม/OTP ระหว่างรับชม</label>
                                                <input type="number" min="0" class="form-control" name="lesson_question_limit" placeholder="0">
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label fw-medium">ระยะเวลาในการตอบ (วินาที)</label>
                                                <input type="number" min="0" class="form-control" name="lesson_question_time" placeholder="0">
                                            </div>
                                        </div> -->
                                    </form>
                                </div>

                                <!-- ===== ขวา: วิดีโอ (ลากวางเต็มพื้นที่ / วิดีโอเต็ม + ลบ) + ปุ่มบันทึก ===== -->
                                <div class="col-lg-6 lesson-col-right d-flex flex-column">
                                    <h6 class="fw-bold text-secondary text-uppercase mb-3" style="letter-spacing:.02em;">วิดีโอบทเรียน</h6>

                                    <form id="formVideo" enctype="multipart/form-data" class="flex-grow-1 d-flex flex-column">
                                        <input type="hidden" name="lesson_video">
                                        <input type="file" id="videoFileInput" name="video_file" accept="video/*" hidden>

                                        <!-- โซนลากวาง (เต็มพื้นที่ — แสดงเมื่อยังไม่มีวิดีโอ) -->
                                        <div id="videoDropZone" class="video-dropzone flex-grow-1 d-flex flex-column justify-content-center align-items-center">
                                            <span class="material-symbols-outlined vdz-icon" aria-hidden="true">cloud_upload</span>
                                            <div class="vdz-title">ลากไฟล์วิดีโอมาวางที่นี่ หรือ <span class="text-primary">คลิกเพื่อเลือกไฟล์</span></div>
                                        </div>

                                        <!-- วิดีโอเต็ม + ปุ่มลบ (X มุมขวาบน) แสดงเมื่อมีวิดีโอแล้ว -->
                                        <div id="videoBox" class="d-none">
                                            <div class="video-preview-wrap">
                                                <div id="videoPreview"></div>
                                                <button type="button" class="video-remove-x" onclick="RemoveVideo()" title="ลบวิดีโอ / อัปใหม่" aria-label="ลบวิดีโอ">
                                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <button type="button" class="btn btn-primary w-100 mt-3 BtnSaveLesson" onclick="SubmitLesson()">
                                        <?php echo $is_add ? 'เพิ่มบทเรียน' : 'บันทึกการแก้ไข'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ===== คำถามระหว่างรับชม (ใส่ได้ทั้งโหมดเพิ่ม/จัดการ — ไม่บังคับ) ===== -->
                        <div class="tab-pane fade" id="tab-l-question" role="tabpanel">
                            <div id="GetQuestionTab"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>

    </div>
</div>

<!-- ===== Modal: เพิ่ม/แก้ไขคำถาม ===== -->
<div class="modal fade" id="modalQuestion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalQuestionTitle">สร้างคำถามใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formQuestion" enctype="multipart/form-data">
                    <input type="hidden" name="question_id" id="q_id">
                    <input type="hidden" name="question_text" id="q_text">
                    <div class="mb-3">
                        <label class="form-label fw-medium">คำถาม <span class="text-danger">*</span></label>
                        <textarea id="editor_question_text"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ภาพ</label>
                        <input type="file" class="form-control" name="question_image" accept="image/*">
                        <div id="q_image_current" class="mt-2" style="display:none;">
                            <span class="text-muted small d-block mb-1">ภาพปัจจุบัน</span>
                            <img id="q_image_preview" src="" alt="ภาพคำถาม"
                                 style="max-height:160px; max-width:100%; border-radius:var(--radius-sm); border:1px solid var(--border);"
                                 onerror="this.style.display='none'">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ไฟล์</label>
                        <input type="file" class="form-control" name="question_file">
                        <div id="q_file_current" class="mt-2" style="display:none;">
                            <a id="q_file_link" href="#" target="_blank" class="small text-primary d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-outlined" style="font-size:16px;" aria-hidden="true">description</span>ไฟล์ปัจจุบัน
                            </a>
                        </div>
                    </div>
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label fw-medium mb-0">ตัวเลือก <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-sm btn-success" onclick="AddChoiceRow('')">เพิ่มตัวเลือก</button>
                    </div>
                    <div id="choiceList"></div>
                    <div class="mb-2">
                        <label for="correctSelect" class="form-label fw-medium">คำตอบที่ถูกต้อง <span class="text-danger">*</span></label>
                        <select class="form-select" id="correctSelect"></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success w-100 BtnSaveQuestion" onclick="SubmitQuestion()">บันทึกคำถาม</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal: อัพโหลด Excel ===== -->
<div class="modal fade" id="modalUploadQuestion" tabindex="-1" aria-hidden="true">
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
                <form id="formUploadQuestion" enctype="multipart/form-data">
                    <label class="form-label fw-medium">ไฟล์ข้อสอบ</label>
                    <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 BtnUploadQuestion" onclick="SubmitUploadQuestion()">อัพโหลด</button>
            </div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/tus-js-client@4.1.0/dist/tus.min.js"></script>

</body>

</html>

<script>
    var COURSE_ID = <?php echo $course_id; ?>;
    var COURSE_KEY = '<?php echo $course_key; ?>';
    var LESSON_ID = <?php echo $lesson_id; ?>;
    var IS_ADD    = <?php echo $is_add ? 'true' : 'false'; ?>;
    // question editor = TinyMCE (init ตอน ready — ดู InitQuestionEditor / SetQuestionHTML / GetQuestionHTML)
    var questionTabLoaded = false;
    var _pickedVideoURL = null;   // object URL ของไฟล์ที่เพิ่งเลือก (ไว้ revoke)
    var questionBuffer = [];      // โหมดเพิ่ม: พักคำถามไว้ก่อน แล้วบันทึกทีเดียวตอนกด "เพิ่มบทเรียน"
    var editingBufferIdx = -1;    // index คำถามใน buffer ที่กำลังแก้ (-1 = เพิ่มใหม่)

    $(document).ready(function () {
        if (!COURSE_ID) {
            Swal.fire({ 
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">ข้อมูลไม่ครบ</span>',
                        icon: "error",
                        showConfirmButton: true
                    })
                .then(() => { window.location.href = "course.php"; });
            return;
        }
        InitVideoDropZone();
        InitQuestionEditor();
        // TinyMCE dialogs (แทรกลิงก์) render นอก modal — กัน Bootstrap modal แย่ง focus จนพิมพ์ไม่ได้
        document.addEventListener('focusin', function (e) {
            if (e.target.closest && e.target.closest('.tox-tinymce-aux, .tox-dialog')) { e.stopImmediatePropagation(); }
        });
        $('button[data-bs-target="#tab-l-question"]').on('shown.bs.tab', function () {
            if (!questionTabLoaded) {
                questionTabLoaded = true;
                LoadQuestionTab();
            }
        });
        if (!IS_ADD) { LoadLesson(); }
    });

    // ===== โซนลากวางไฟล์วิดีโอ =====
    function InitVideoDropZone() {
        var zone = document.getElementById('videoDropZone');
        var input = document.getElementById('videoFileInput');
        if (!zone || !input) { return; }

        zone.addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () { if (input.files && input.files.length) { PickVideoFile(input.files); } });

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
            if (!/^video\//.test(files[0].type) && !/\.(mp4|mov|avi|wmv|mkv|webm)$/i.test(files[0].name)) {
                Swal.fire({ 
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">กรุณาวางไฟล์วิดีโอเท่านั้น</span>',
                    icon: "warning",
                    showConfirmButton: false, 
                    timer: 2000 
                });
                return;
            }
            input.files = files; 
            PickVideoFile(files);
        });
    }

    // เลือกไฟล์ใหม่ (คลิก/ลากวาง) -> พรีวิวไฟล์ทันที แล้วซ่อนโซนลากวาง
    function PickVideoFile(files) {
        var f = files[0];
        if (_pickedVideoURL) { try { URL.revokeObjectURL(_pickedVideoURL); } catch (e) {} }
        _pickedVideoURL = URL.createObjectURL(f);
        ShowVideo('<div class="rounded-3 overflow-hidden" style="background:#000;"><video src="' + _pickedVideoURL + '" controls></video></div>', true);
    }

    // แสดงวิดีโอเต็มพื้นที่ + ปุ่มลบ (ซ่อนโซนลากวาง). isNew=true = ไฟล์ใหม่ที่ยังไม่อัป
    function ShowVideo(html, isNew) {
        $('#videoPreview').html(html);
        $('#videoDropZone').addClass('d-none');
        $('#videoBox').removeClass('d-none');
        // ไฟล์ใหม่ที่ยังไม่อัป -> โชว์โน้ตว่าจะอัปตอนกดบันทึก; วิดีโอเดิม (จาก DB) -> ซ่อน
        $('#videoPendingNote').toggleClass('d-none', !isNew);
    }

    // ลบวิดีโอ/เลือกใหม่ -> กลับไปโชว์โซนลากวาง
    function RemoveVideo() {
        var input = document.getElementById('videoFileInput');
        if (input) { input.value = ''; }
        if (_pickedVideoURL) { try { URL.revokeObjectURL(_pickedVideoURL); } catch (e) {} _pickedVideoURL = null; }
        $('#videoPreview').empty();
        $('#videoBox').addClass('d-none');
        $('#videoPendingNote').addClass('d-none');
        $('#videoDropZone').removeClass('d-none');
    }

    // สร้าง iframe วิดีโอ Vimeo หรือ video player สำหรับไฟล์ Local
    function VideoFrameHTML(url, w, h) {
        if (!url) return '';
        // ถ้าเป็นไฟล์ Local (อยู่ในโฟลเดอร์ upload/ หรือไม่ใช่ Vimeo)
        if (url.indexOf('upload/') !== -1 || !/(?:player\.)?vimeo\.com/i.test(url)) {
            var videoSrc = (url.indexOf('http') === 0 || url.indexOf('/') === 0) ? url : ('../' + url);
            return '<div class="rounded-3 overflow-hidden" style="max-width:100%;max-height:60vh;margin:0 auto;background:#000;">'
                + '<video src="' + EscapeHTML(videoSrc) + '" controls style="width:100%;max-height:60vh;display:block;"></video></div>';
        }
        w = parseInt(w, 10) || 16;
        h = parseInt(h, 10) || 9;
        var portrait = h > w;
        return '<div class="rounded-3 overflow-hidden" style="aspect-ratio:' + w + '/' + h + ';max-width:100%;max-height:60vh;'
            + (portrait ? 'height:60vh;width:auto;' : 'width:100%;height:auto;')
            + 'margin:0 auto;background:#000;">'
            + '<iframe src="' + EscapeHTML(url) + '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen '
            + 'style="width:100%;height:100%;border:0;display:block;"></iframe></div>';
    }

    // ฝังวิดีโอบทเรียน: เช็คสถานะก่อน
    function EmbedLessonVideo(url) {
        if (!url) { RemoveVideo(); return; }
        // ถ้าเป็นไฟล์ Local -> เล่นได้ทันทีโดยไม่ต้อง poll Vimeo
        if (url.indexOf('upload/') !== -1 || !/(?:player\.)?vimeo\.com/i.test(url)) {
            ShowVideo(VideoFrameHTML(url), false);
            return;
        }

        ShowVideo(VideoProcessingHTML(), false);   // โชว์ placeholder ทันที (กันฝัง iframe ตอนยังไม่พร้อม)
        var polls = 0, MAX_POLLS = 200;            // poll สูงสุด ~15 นาที
        (function check() {
            if ($('#videoBox').hasClass('d-none')) { return; }   // วิดีโอถูกลบ/ปิดไปแล้ว -> เลิก poll
            $.ajax({
                type: "POST", url: "core.php",
                data: {
                    request_state: "lesson",
                    request_function: "get_video_status",
                    lesson_id: LESSON_ID
                },
                dataType: "json",
                global: false,      // ไม่ trigger spinner กลางจอ (poll ถี่)
                headers: { "Authorization": "Bearer " + (localStorage.getItem("bo_access_token") || "") },
                timeout: 15000      // กัน request ค้าง
            }).done(function (r) {
                var d = (r && r.result == 1) ? r.data : null;
                if (d && d.missing) {
                    ShowVideo(VideoMissingHTML(), false);   // วิดีโอถูกลบ/ไม่พบบน Vimeo -> หยุด poll
                    return;
                }
                if (d && (d.is_playable || d.status === 'available')) {
                    ShowVideo(VideoFrameHTML(url, d.width || 16, d.height || 9), false);   // พร้อมแล้ว -> ฝังจริง
                    return;
                }
                if (++polls < MAX_POLLS) { setTimeout(check, 4000); }   // ยังไม่พร้อม -> รอแล้วเช็คใหม่
            }).fail(function () {
                if (++polls < MAX_POLLS) { setTimeout(check, 4000); }   // เช็คพลาด (เน็ต/timeout) -> retry ต่อ
            });
        })();
    }

    // กล่องแสดงระหว่าง Vimeo ประมวลผลวิดีโอ (แทน iframe ที่จะขึ้น "does not exist" ตอนยัง transcode)
    function VideoProcessingHTML() {
        return '<div class="rounded-3 d-flex flex-column justify-content-center align-items-center text-center" '
            + 'style="aspect-ratio:16/9;max-height:60vh;background:#f1f2f7;color:#6b7280;padding:24px;">'
            + '<div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>'
            + '<div class="fw-semibold">Vimeo กำลังประมวลผลวิดีโอ…</div>'
            + '<div class="small mt-1">วิดีโอจะเล่นได้เองเมื่อประมวลผลเสร็จ (ไม่ต้องรีเฟรช)</div></div>';
    }

    // กล่องแสดงเมื่อวิดีโอถูกลบ/ไม่พบบน Vimeo (404) — หยุด poll แล้วให้อัปโหลดใหม่
    function VideoMissingHTML() {
        return '<div class="rounded-3 d-flex flex-column justify-content-center align-items-center text-center" '
            + 'style="aspect-ratio:16/9;max-height:60vh;background:#fdf1f1;color:#c0392b;padding:24px;">'
            + '<span class="material-symbols-outlined mb-2" style="font-size:40px;" aria-hidden="true">error</span>'
            + '<div class="fw-semibold">ไม่พบวิดีโอ</div>'
            + '<div class="small mt-1 text-secondary">วิดีโออาจถูกลบจาก Vimeo — กรุณาอัปโหลดวิดีโอใหม่แล้วกดบันทึก</div></div>';
    }

    // ===== โหลดข้อมูลบทเรียน (เติมฟอร์มตั้งค่า + วีดีโอ) =====
    function LoadLesson() {
        $.ajax({
            type: "POST", url: "core.php",
            data: {
                    request_state: "lesson",
                    request_function: "get_lesson",
                    lesson_id: LESSON_ID
                },
            dataType: "json",
            success: function (response) {
                if (response.result != 1) {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error", showConfirmButton: true
                    })
                        .then(() => { window.location.href = "course_edit.php?key=" + COURSE_KEY; });
                    return;
                }
                var L = response.data.lesson;
                $('#formLesson [name="lesson_order"]').val(L.lesson_order);
                $('#formLesson [name="lesson_name"]').val(L.lesson_name);
                $('#formLesson [name="lesson_question"]').val(L.lesson_question || '0');
                $('#formLesson [name="lesson_question_limit"]').val(L.lesson_question_limit);
                $('#formLesson [name="lesson_question_time"]').val(L.lesson_question_time);
                $('#formLesson [name="lesson_overview"]').val(L.lesson_overview || '');
                $('#formVideo [name="lesson_video"]').val(L.lesson_video || '');
                if (L.lesson_video) {
                    EmbedLessonVideo(L.lesson_video);
                } else {
                    RemoveVideo();
                }
            },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // ===== บันทึกบทเรียน (เพิ่มใหม่ หรือ แก้ไข) — ปุ่มเดียว: อัปวิดีโอ (ถ้ามีไฟล์ใหม่) แล้วบันทึก =====
    function SubmitLesson() {
        var name = $('#formLesson [name="lesson_name"]').val().trim();
        if (name === "") {
            Swal.fire({ 
                title: "แจ้งเตือน", 
                html: '<span class="fw-bold text-danger">กรุณากรอกชื่อบทเรียน</span>', 
                icon: "warning", 
                showConfirmButton: false, 
                timer: 2000 
            });
            return;
        }
        var vf = document.getElementById('videoFileInput');
        var newFile = (vf && vf.files && vf.files.length) ? vf.files[0] : null;

        // บังคับต้องมีวิดีโอ (ถ้าวิดีโอถูกลบออกไป โซนลากวางจะไม่ถูกซ่อน)
        if (!newFile && !$('#videoDropZone').hasClass('d-none')) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณาเลือกวิดีโอก่อน' + (IS_ADD ? 'เพิ่มบทเรียน' : 'บันทึก') + '</span>', icon: "warning", showConfirmButton: false, timer: 2200 });
            return;
        }

        // บันทึกข้อมูลบทเรียน (ข้อความ) -> callback(response)
        function saveLessonData(onSaved) {
            var data = $('#formLesson').serializeArray();
            data.push({ 
                name: "request_state", 
                value: "lesson" 
            });
            if (IS_ADD) {
                data.push({
                    name: "request_function", 
                    value: "add_lesson" 
                });
                data.push({ 
                    name: "course_id", 
                    value: COURSE_ID 
                });
            } else {
                data.push({ 
                    name: "request_function", 
                    value: "update_lesson" 
                });
                data.push({ 
                    name: "lesson_id", 
                    value: LESSON_ID 
                });
            }
            $.ajax({
                beforeSend: function () { ShowLoadingButton('.BtnSaveLesson'); },
                type: "POST", 
                url: "core.php", 
                data: $.param(data), 
                dataType: "json",
                success: function (response) { onSaved(response); },
                complete: function () { HideLoadingButton('.BtnSaveLesson'); },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        }

        if (IS_ADD) {
            // เพิ่มบทเรียนก่อน (ได้ lesson_id) -> อัปวิดีโอขึ้น Vimeo -> บันทึกคำถามที่พักไว้ -> เด้งเข้าหน้าจัดการ
            saveLessonData(function (response) {
                if (response.result != 1) { ToastResult(response); return; }
                var newId = response.data.lesson_id;
                var gotoManage = function () {
                    window.location.href = "lesson_manage.php?course_key=" + COURSE_KEY + "&lesson_key=" + response.data.newKey;
                };
                var afterVideo = function () { FlushQuestionBuffer(newId, gotoManage); };
                if (newFile) { RunVimeoUpload(newId, newFile, afterVideo); }
                else { afterVideo(); }
            });
            return;
        }

        // โหมดแก้ไข (ปุ่มเดียว): มีวิดีโอใหม่ -> อัปขึ้น Vimeo ก่อน แล้วบันทึก; ไม่มี -> บันทึกอย่างเดียว (คงวิดีโอเดิม)
        if (newFile) {
            RunVimeoUpload(LESSON_ID, newFile, function (ok) {
                if (!ok) { return; }   // อัปไม่สำเร็จ -> ไม่บันทึกต่อ (แจ้งเตือนใน RunVimeoUpload แล้ว)
                saveLessonData(AfterEditSaved);
            });
        } else {
            saveLessonData(AfterEditSaved);
        }
    }

    // โหมดแก้ไข: บันทึกสำเร็จ -> แจ้งเตือนสั้น ๆ แล้วเด้งกลับหน้าบทเรียน (course_edit) ; ล้มเหลว -> แค่แจ้งเตือน
    function AfterEditSaved(response) {
        if (response.result != 1) { ToastResult(response); return; }
        Swal.fire({
            title: "สำเร็จ",
            html: '<span class="fw-bold text-success">' + response.msg + '</span>',
            icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true
        }).then(function () {
            window.location.href = "course_edit.php?key=" + COURSE_KEY + "#tab-lesson";
        });
    }

    // อัปโหลดไฟล์วิดีโอเข้าเซิร์ฟเวอร์ Local สำหรับ lessonId ที่ระบุ -> เรียก onDone(ok) เมื่อจบ
    function RunVimeoUpload(lessonId, file, onDone) {
        Swal.fire({
            title: "กำลังอัปโหลดวิดีโอ",
            html:
                '<div class="text-secondary mb-2" id="upPhase">กำลังส่งไฟล์วิดีโอเข้าสู่เซิร์ฟเวอร์...</div>' +
                '<div class="progress" style="height:20px;border-radius:10px;">' +
                '<div id="upBar" class="progress-bar progress-bar-striped progress-bar-animated" ' +
                'role="progressbar" style="width:0%;background:#605DFF;">0%</div></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        var formData = new FormData();
        formData.append('request_state', 'lesson');
        formData.append('request_function', 'upload_video_local');
        formData.append('lesson_id', lessonId);
        formData.append('video_file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'core.php', true);
        var token = localStorage.getItem("bo_access_token") || "";
        if (token) {
            xhr.setRequestHeader("Authorization", "Bearer " + token);
        }

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 100);
                $("#upBar").css("width", pct + "%").text(pct + "%");
                if (pct >= 100) {
                    $("#upPhase").text("อัปโหลดเสร็จสิ้น — กำลังบันทึกข้อมูล...");
                }
            }
        };

        xhr.onload = function () {
            Swal.close();
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.result == 1) {
                    Swal.fire({
                        title: "อัปโหลดสำเร็จ",
                        html: '<span class="fw-bold text-success">' + res.msg + '</span>',
                        icon: "success",
                        timer: 1500,
                        showConfirmButton: false
                    });
                    if (onDone) { onDone(true); }
                } else {
                    Swal.fire({
                        title: "อัปโหลดไม่สำเร็จ",
                        html: '<span class="fw-bold text-danger">' + (res.msg || 'เกิดข้อผิดพลาดในการอัปโหลด') + '</span>',
                        icon: "error",
                        showConfirmButton: true
                    });
                    if (onDone) { onDone(false); }
                }
            } catch (err) {
                Swal.fire({
                    title: "เกิดข้อผิดพลาด",
                    html: '<span class="fw-bold text-danger">การตอบกลับจากเซิร์ฟเวอร์ไม่ถูกต้อง: ' + (xhr.responseText ? xhr.responseText.substring(0, 150) : '') + '</span>',
                    icon: "error",
                    showConfirmButton: true
                });
                if (onDone) { onDone(false); }
            }
        };

        xhr.onerror = function () {
            Swal.close();
            Swal.fire({
                title: "อัปโหลดล้มเหลว",
                html: '<span class="fw-bold text-danger">เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์</span>',
                icon: "error",
                showConfirmButton: true
            });
            if (onDone) { onDone(false); }
        };

        xhr.send(formData);
    }

    // ===== แท็บคำถาม =====
    function LoadQuestionTab() {
        if (IS_ADD) { RenderBufferQuestionTable(); return; }   // โหมดเพิ่ม: แสดงจาก buffer ในเครื่อง
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetQuestionTab"); },
            type: "POST", 
            url: "core.php",
            data: { 
                request_state: "question", 
                request_function: "get_list_question", 
                lesson_id: LESSON_ID 
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) { RenderQuestionTable(response.data.list_data); }
                else { ToastResult(response); }
            },
            complete: function () { HideLoadingOverlay("#GetQuestionTab"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function RenderQuestionTable(list_data) {
        $.ajax({
            type: "POST", url: "view/question/GetTable.php",
            data: JSON.stringify({ 
                    list_data: list_data 
                }),
            contentType: "application/json; charset=utf-8", 
            processData: false, 
            dataType: "html",
            success: function (html) { $("#GetQuestionTab").html(html); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // โหมดเพิ่ม: แสดงตารางคำถามจาก buffer (โครงเดียวกับ view/question/GetTable.php)
    function RenderBufferQuestionTable() {
        var rows = '';
        if (questionBuffer.length === 0) {
            rows = '<tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีคำถาม</td></tr>';
        } else {
            questionBuffer.forEach(function (q, i) {
                var plain = $('<div>').html(q.text || '').text().replace(/\s+/g, ' ').trim();
                if (plain.length > 80) { plain = plain.substring(0, 80) + '…'; }
                var fileCell = (q.imageFile || q.docFile)
                    ? '<span class="badge bg-success">มี</span>'
                    : '<span class="text-muted">ไม่มีข้อมูล</span>';
                var correctCell = q.correct > 0 ? ('ข้อ ' + q.correct) : '<span class="text-muted">-</span>';
                rows += '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td>' + EscapeHTML(plain) + '</td>' +
                    '<td class="text-center">' + fileCell + '</td>' +
                    '<td class="text-center">' + correctCell + '</td>' +
                    '<td class="text-center"><div class="d-flex gap-2 justify-content-center">' +
                    '<button type="button" class="btn btn-warning table-action-btn" onclick="OpenEditQuestion(' + i + ')"><span class="material-symbols-outlined" aria-hidden="true">edit</span>แก้ไข</button>' +
                    '<button type="button" class="btn btn-danger table-action-btn" onclick="DeleteQuestion(' + i + ')"><span class="material-symbols-outlined" aria-hidden="true">delete</span>ลบ</button>' +
                    '</div></td></tr>';
            });
        }
        var html =
            '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '<h4 class="mb-0 fw-bold">คำถามระหว่างรับชม</h4>' +
            '<button type="button" class="btn btn-primary" onclick="OpenAddQuestion()">เพิ่มคำถาม</button>' +
            '</div>' +
            '<div class="default-table-area"><div class="table-responsive"><table class="table align-middle w-100">' +
            '<thead><tr>' +
            '<th class="text-center" style="width:80px;">ลำดับ</th><th>คำถาม</th>' +
            '<th class="text-center" style="width:120px;">ไฟล์/ภาพ</th>' +
            '<th class="text-center" style="width:140px;">คำตอบที่ถูกต้อง</th>' +
            '<th class="text-center" style="width:180px;">จัดการ</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div></div>';
        $('#GetQuestionTab').html(html);
    }

    // โหมดเพิ่ม: บันทึกคำถามที่พักไว้ทั้งหมดเข้าบทเรียนใหม่ (ทีละข้อ) แล้วเรียก done()
    function FlushQuestionBuffer(lessonId, done) {
        if (!questionBuffer.length) { done(); return; }
        Swal.fire({ title: "กำลังบันทึกคำถาม...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        var i = 0;
        (function next() {
            if (i >= questionBuffer.length) { Swal.close(); done(); return; }
            var q = questionBuffer[i++];
            var fd = new FormData();
            fd.append('request_state', 'question');
            fd.append('request_function', 'add_question');
            fd.append('lesson_id', lessonId);
            fd.append('question_text', q.text);
            (q.choices || []).forEach(function (c) { fd.append('choice_text[]', c); });
            fd.append('correct', q.correct);
            if (q.imageFile) { fd.append('question_image', q.imageFile); }
            if (q.docFile) { fd.append('question_file', q.docFile); }
            $.ajax({ type: "POST", url: "core.php", data: fd, processData: false, contentType: false, dataType: "json" })
                .always(function () { next(); });   // ข้อไหนพลาดก็บันทึกข้ออื่นต่อ
        })();
    }

    function InitQuestionEditor() {
        if (typeof tinymce === 'undefined' || tinymce.get('editor_question_text')) { return; }
        tinymce.init({
            selector: '#editor_question_text',
            height: 200,
            menubar: false,
            elementpath: false,
            plugins: 'lists link',
            toolbar: 'bold italic underline | bullist numlist | link | removeformat',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    }
    function SetQuestionHTML(html) {
        var ed = (typeof tinymce !== 'undefined') ? tinymce.get('editor_question_text') : null;
        if (ed) { ed.setContent(html || ''); }
    }
    function GetQuestionHTML() {
        var ed = (typeof tinymce !== 'undefined') ? tinymce.get('editor_question_text') : null;
        if (!ed) { return ''; }
        return ed.getContent({ format: 'text' }).trim() === '' ? '' : ed.getContent();
    }
    function AddChoiceRow(text) {
        var idx = $('#choiceList .choice-row').length + 1;
        var row = $('<div class="input-group mb-2 choice-row">' +
            '<span class="input-group-text">ข้อ ' + idx + '</span>' +
            '<textarea class="form-control" name="choice_text[]" rows="1"></textarea>' +
            '<button type="button" class="btn btn-outline-danger" onclick="RemoveChoiceRow(this)">ลบ</button>' +
            '</div>');
        row.find('textarea').val(text || '');
        $('#choiceList').append(row);
        RefreshCorrectOptions();
    }
    function RemoveChoiceRow(btn) {
        $(btn).closest('.choice-row').remove();
        ReindexChoices();
        RefreshCorrectOptions();
    }
    function ReindexChoices() {
        $('#choiceList .choice-row').each(function (i) {
            $(this).find('.input-group-text').text('ข้อ ' + (i + 1));
        });
    }
    function RefreshCorrectOptions() {
        var prev = $('#correctSelect').val();
        var n = $('#choiceList .choice-row').length;
        var html = '<option value="">---กรุณาเลือกคำตอบที่ถูก---</option>';
        for (var i = 1; i <= n; i++) { html += '<option value="' + i + '">ตัวเลือกที่ ' + i + '</option>'; }
        $('#correctSelect').html(html);
        if (prev && prev <= n) { $('#correctSelect').val(prev); }
    }

    // แก้ path รูป/ไฟล์ให้แสดงได้ (ถ้าไม่ใช่ URL เต็ม ให้เติม ../ เหมือนที่อื่น)
    function ResolveQuestionUrl(v) {
        if (!v) { return ''; }
        return /^https?:\/\//i.test(v) ? v : '../' + v;
    }
    // แสดง/ซ่อน ภาพ+ไฟล์เดิมของคำถามในโหมดแก้ไข (ค่าว่าง = ซ่อน)
    function SetQuestionMedia(imgUrl, fileUrl) {
        if (imgUrl) { $('#q_image_preview').attr('src', imgUrl).show(); $('#q_image_current').show(); }
        else { $('#q_image_preview').attr('src', ''); $('#q_image_current').hide(); }
        if (fileUrl) { $('#q_file_link').attr('href', fileUrl); $('#q_file_current').show(); }
        else { $('#q_file_link').attr('href', '#'); $('#q_file_current').hide(); }
    }

    function OpenAddQuestion() {
        editingBufferIdx = -1;
        $('#modalQuestionTitle').text('สร้างคำถามใหม่');
        $('#formQuestion')[0].reset();
        $('#q_id').val('');
        SetQuestionHTML('');
        SetQuestionMedia('', '');
        $('#choiceList').empty();
        AddChoiceRow(''); AddChoiceRow('');
        RefreshCorrectOptions();
        new bootstrap.Modal(document.getElementById('modalQuestion')).show();
    }
    function OpenEditQuestion(qid) {
        // โหมดเพิ่ม: qid = index ใน buffer
        if (IS_ADD) {
            var q = questionBuffer[qid];
            if (!q) { return; }
            editingBufferIdx = qid;
            $('#modalQuestionTitle').text('แก้ไขคำถาม');
            $('#formQuestion')[0].reset();
            $('#q_id').val('');
            SetQuestionHTML(q.text || '');
            SetQuestionMedia(
                q.imageFile ? URL.createObjectURL(q.imageFile) : '',
                q.docFile ? URL.createObjectURL(q.docFile) : ''
            );
            $('#choiceList').empty();
            (q.choices || []).forEach(function (c) { AddChoiceRow(c || ''); });
            RefreshCorrectOptions();
            if (q.correct > 0) { $('#correctSelect').val(q.correct); }
            new bootstrap.Modal(document.getElementById('modalQuestion')).show();
            return;
        }
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "question", request_function: "get_question", question_id: qid },
            dataType: "json",
            success: function (response) {
                if (response.result != 1) { ToastResult(response); return; }
                $('#modalQuestionTitle').text('แก้ไขคำถาม');
                $('#formQuestion')[0].reset();
                $('#q_id').val(qid);
                SetQuestionHTML(response.data.question.question_text || '');
                SetQuestionMedia(
                    ResolveQuestionUrl(response.data.question.question_image),
                    ResolveQuestionUrl(response.data.question.question_file)
                );
                $('#choiceList').empty();
                var correctIdx = 0;
                response.data.choices.forEach(function (c, i) {
                    AddChoiceRow(c.question_choice_text || '');
                    if (String(c.question_choice_correct) === '1') { correctIdx = i + 1; }
                });
                RefreshCorrectOptions();
                if (correctIdx > 0) { $('#correctSelect').val(correctIdx); }
                new bootstrap.Modal(document.getElementById('modalQuestion')).show();
            },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function SubmitQuestion() {
        $('#q_text').val(GetQuestionHTML());

        // ===== ตรวจช่องบังคับ (คำถาม + คำตอบที่ถูก + ตัวเลือกอย่างน้อย 2 ข้อ) =====
        var qChoices = $('#choiceList .choice-row textarea').map(function () { return $(this).val().trim(); }).get().filter(function (t) { return t !== ''; });
        if (!ValidateRequired([
            { 
                sel: '#editor_question_text', 
                label: 'คำถาม', 
                type: 'tinymce', 
                editorId: 'editor_question_text' 
            },
            { 
                sel: '#correctSelect',        
                label: 'คำตอบที่ถูกต้อง', 
                type: 'select' 
            }
        ])) { return; }
        if (qChoices.length < 2) {
            Swal.fire({ 
                title: "แจ้งเตือน", 
                html: '<span class="fw-bold text-danger">กรุณากรอกตัวเลือกอย่างน้อย 2 ข้อ</span>', 
                icon: "warning", 
                showConfirmButton: false, 
                timer: 2000 
            });
            return;
        }
        // โหมดเพิ่ม: พักคำถามไว้ใน buffer (ยังไม่มี lesson_id) แล้วบันทึกทีเดียวตอนกด "เพิ่มบทเรียน"
        if (IS_ADD) {
            var choices = [];
            $('#choiceList .choice-row textarea').each(function () { choices.push($(this).val()); });
            var imgInput = $('#formQuestion [name="question_image"]')[0];
            var docInput = $('#formQuestion [name="question_file"]')[0];
            var item = {
                text: $('#q_text').val(),
                choices: choices,
                correct: parseInt($('#correctSelect').val(), 10) || 0,
                imageFile: (imgInput && imgInput.files.length) ? imgInput.files[0] : null,
                docFile: (docInput && docInput.files.length) ? docInput.files[0] : null
            };
            if (editingBufferIdx >= 0) {
                var old = questionBuffer[editingBufferIdx] || {};
                if (!item.imageFile) { item.imageFile = old.imageFile || null; }  // ไม่เลือกไฟล์ใหม่ = คงไฟล์เดิม
                if (!item.docFile) { item.docFile = old.docFile || null; }
                questionBuffer[editingBufferIdx] = item;
            } else {
                questionBuffer.push(item);
            }
            editingBufferIdx = -1;
            bootstrap.Modal.getInstance(document.getElementById('modalQuestion')).hide();
            RenderBufferQuestionTable();
            ToastResult({ result: 1, msg: "บันทึกสำเร็จ" });
            return;
        }
        var isEdit = $('#q_id').val() !== '';
        var fd = new FormData($('#formQuestion')[0]);
        fd.append('correct', $('#correctSelect').val());
        fd.append('lesson_id', LESSON_ID);
        fd.append('request_state', 'question');
        fd.append('request_function', isEdit ? 'update_question' : 'add_question');
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSaveQuestion'); },
            type: "POST", 
            url: "core.php", 
            data: fd, 
            processData: false, 
            contentType: false, 
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalQuestion')).hide();
                    ToastResult(response);
                    LoadQuestionTab();
                } else { 
                    ToastResult(response); 
                }
            },
            complete: function () { HideLoadingButton('.BtnSaveQuestion'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
    function DeleteQuestion(qid) {
        Swal.fire({ 
            title: "ยืนยันการลบ", 
            html: '<span class="fw-bold text-danger">ต้องการลบคำถามนี้ใช่หรือไม่?</span>', 
            icon: "warning", 
            showCancelButton: true, 
            confirmButtonText: "ลบ", 
            cancelButtonText: "ยกเลิก", 
            confirmButtonColor: "#dc3545" 
        }).then((res) => {
            if (!res.isConfirmed) { return; }
            // โหมดเพิ่ม: qid = index ใน buffer
            if (IS_ADD) { 
                questionBuffer.splice(qid, 1); 
                RenderBufferQuestionTable(); 
                return; 
            }
            $.ajax({
                type: "POST", 
                url: "core.php",
                data: { 
                    request_state: "question", 
                    request_function: "delete_question", 
                    question_id: qid 
                },
                dataType: "json",
                success: function (response) { 
                    ToastResult(response); 
                    if (response.result == 1) { LoadQuestionTab(); } 
                },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }

    // ===== อัพโหลด Excel =====
    function OpenUploadQuestion() {
        $('#formUploadQuestion')[0].reset();
        new bootstrap.Modal(document.getElementById('modalUploadQuestion')).show();
    }
    function SubmitUploadQuestion() {
        if (!$('#formUploadQuestion [name="excel_file"]').val()) {
            Swal.fire({ 
                title: "แจ้งเตือน", 
                html: '<span class="fw-bold text-danger">กรุณาเลือกไฟล์</span>', 
                icon: "warning", 
                showConfirmButton: false, 
                timer: 2000 
            });
            return;
        }
        var fd = new FormData($('#formUploadQuestion')[0]);
        fd.append('lesson_id', LESSON_ID);
        fd.append('request_state', 'question');
        fd.append('request_function', 'upload_question');
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnUploadQuestion'); },
            type: "POST", 
            url: "core.php", 
            data: fd, 
            processData: false, 
            contentType: false, 
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalUploadQuestion')).hide();
                    ToastResult(response);
                    LoadQuestionTab();
                } else { ToastResult(response); }
            },
            complete: function () { HideLoadingButton('.BtnUploadQuestion'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // helper แจ้งผล
    function ToastResult(response) {
        Swal.fire({
            title: response.result == 1 ? "สำเร็จ" : "แจ้งเตือน",
            html: '<span class="fw-bold ' + (response.result == 1 ? 'text-success' : 'text-danger') + '">' + response.msg + '</span>',
            icon: response.result == 1 ? "success" : "error",
            showConfirmButton: false, timer: 1800, timerProgressBar: true
        });
    }
</script>
