<?php

    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    // รองรับทั้งรูปแบบใหม่ (groups/types) และเก่า (list_data = groups)
    $groups = $data["groups"] ?? $data["list_data"] ?? [];
    $types  = $data["types"] ?? [];

    // dual-mode: ถ้ามี course = โหมดแก้ไข (prefill), ไม่มี = โหมดเพิ่ม (เหมือนเดิมทุกประการ)
    $course = $data["course"] ?? null;
    $mode   = $course ? 'edit' : 'add';
    // helper ดึงค่าเดิมมาใส่ value attribute (escape ให้เรียบร้อย)
    $cv = function (string $key) use ($course) {
    return htmlspecialchars((string) ($course[$key] ?? ''), ENT_QUOTES);
    };
?>
<style>
    .form-control, .form-select { background-color: #ffffff !important; }
    .form-control:focus, .form-select:focus { background-color: #ffffff !important; }
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important; padding-left: 0.75rem !important; color: var(--bs-body-color) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #6c757d !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
    /* TinyMCE border ให้เข้ากับ .form-control */
    .tox-tinymce { border-color: #ced4da !important; border-radius: 8px; }
    /* ช่อง disabled ให้เป็นสีเทา (override .form-control สีขาวด้านบน) */
    .form-control:disabled, .form-select:disabled, .form-control[readonly] { background-color: #e9ecef !important; }
    /* ตัวสร้างรหัสหลักสูตร — แสดงแนวนอนบรรทัดเดียว เลื่อนในช่องได้ถ้ายาวเกิน */
    .code-builder .cb-row { border: 1px solid #ced4da; border-radius: .375rem; padding: 6px 6px; min-height: 38px; background:#fff; flex-wrap: nowrap !important; overflow: hidden; gap: 0 !important; }
    .code-builder .cb-seg-text { border:0; outline:0; flex:0 0 auto; width:1ch; min-width:1ch; padding:2px 0; background:transparent; box-sizing:content-box; }
    .code-builder .cb-seg-text.cb-seg-last { flex:1 1 40px !important; width:auto !important; min-width:40px; }
    .code-builder .cb-seg-year { border:0; outline:0; flex:0 0 auto; min-width:0; width:auto; padding:2px 0; background:#fff; cursor:pointer; }
    .code-builder .cb-seg-quarter { flex:0 0 auto; width:34px; min-width:34px; text-align:center; padding:2px 1px; background:#e9ecef !important; }
</style>

<?php if ($mode === 'edit'): ?>
<!-- โหมดแก้ไข: ไม่หุ้มการ์ด เพราะอยู่ในแท็บของการ์ดหลักอยู่แล้ว (กันการ์ดซ้อนการ์ด) -->
<div class="course-form-plain">
    <h2 class="mb-4">ข้อมูลทั่วไปของคอร์สเรียน</h2>
<?php else: ?>
<div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-4 border-0">
        <h2 class="mb-0">เพิ่มคอร์สเรียนใหม่</h2>
        <a href="course" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับ</a>
    </div>
    <div class="px-4 mb-3">
        <div class="alert alert-success mb-0" role="alert">
            คุณสามารถเพิ่มข้อมูลบทเรียนและข้อสอบได้หลังจากเพิ่มคอร์สเรียน
        </div>
    </div>

    <div class="card-body p-4">
<?php endif; ?>
        <form id="formAddCourse" enctype="multipart/form-data">
            <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="course_id" value="<?php echo $cv('course_id'); ?>">
            <?php endif; ?>

            <!-- ===== พรีวิวรูปหน้าปก (ทั้งเพิ่มและแก้ไข) ===== -->
            <div id="coverPreviewWrap" class="text-center mb-4" style="display:none;">
                <img id="coverPreview" src="" alt="พรีวิวรูปหน้าปก"
                     style="max-width:100%; width:600px; border-radius:12px; border:1px solid #e5e7eb;">
            </div>

            <!-- ===== ข้อมูลทั่วไป ===== -->
            <h4 class="mb-3 fw-bold">ข้อมูลทั่วไป</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="course_cover_image" class="form-label fw-medium"><?php echo $mode === 'edit' ? 'เปลี่ยนรูปหน้าปก' : 'รูปหน้าปก'; ?> <?php if ($mode !== 'edit'): ?><span class="text-danger">*</span><?php endif; ?></label>
                    <input type="file" class="form-control" id="course_cover_image" name="course_cover_image" accept="image/*">
                </div>
                <div class="col-md-8">
                    <label for="course_name" class="form-label fw-medium">ชื่อคอร์สเรียน <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="course_name" name="course_name" placeholder="กรอกชื่อคอร์สเรียน">
                </div>

                <div class="col-md-4">
                    <label for="course_type" class="form-label fw-medium">ประเภท <span class="text-danger">*</span></label>
                    <select class="form-select" id="course_type" name="course_type">
                        <option value="">เลือกประเภท</option>
                        <?php $type_selected = false; ?>
                        <?php foreach ($types as $row): ?>
                            <?php
                                // เลือก "ทั่วไป" เป็นค่าเริ่มต้น
                                $is_general = (($row['type_name'] ?? '') === 'ทั่วไป');
                                $sel        = ($is_general && ! $type_selected) ? 'selected' : '';
                                if ($sel) {$type_selected = true;}
                            ?>
                            <option value="<?php echo htmlspecialchars($row['type_id'] ?? ''); ?>"
                                    data-name="<?php echo htmlspecialchars($row['type_name'] ?? ''); ?>" <?php echo $sel; ?>>
                                <?php echo htmlspecialchars($row['type_name'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="course_group" class="form-label fw-medium">หมวดหมู่ <span class="text-danger">*</span></label>
                    <select class="form-select" id="course_group" name="course_group">
                        <option value="">กรุณาเลือกหมวดหมู่</option>
                        <?php foreach ($groups as $row): ?>
                            <option value="<?php echo htmlspecialchars($row['group_id'] ?? ''); ?>">
                                <?php echo htmlspecialchars($row['group_name'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="course_instructor" class="form-label fw-medium">ผู้บรรยาย/ผู้สอน <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="course_instructor" name="course_instructor" placeholder="กรอกผู้บรรยาย/ผู้สอน">
                </div>
            </div>

            <div class="mb-3">
                <label for="course_overview" class="form-label fw-medium">รายละเอียดโดยย่อ <span class="text-danger">*</span></label>
                <textarea class="form-control" id="course_overview" name="course_overview" rows="3" placeholder="คำอธิบายสั้นๆ"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">รายละเอียดแบบเต็ม <span class="text-danger">*</span></label>
                <textarea id="editor_course_detail"></textarea>
                <input type="hidden" name="course_detail" id="course_detail">
            </div>

            <div class="row g-3 mb-3">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="col-md-3">
                        <label for="course_approval_date_<?php echo $i; ?>" class="form-label fw-medium">วันที่อนุมัติหลักสูตร (ไตรมาส <?php echo $i; ?>)</label>
                        <input type="text" class="form-control course-approval-date" id="course_approval_date_<?php echo $i; ?>" name="course_approval_date_<?php echo $i; ?>" placeholder="วัน/เดือน/ปี" autocomplete="off">
                    </div>
                <?php endfor; ?>
            </div>

            <div class="mb-4">
                <label for="course_demo_link" class="form-label fw-medium">ลิงก์ตัวอย่าง (Demo)</label>
                <input type="text" class="form-control" id="course_demo_link" name="course_demo_link" placeholder="https://...">
            </div>

            <!-- ===== รหัสหลักสูตรทุกไตรมาส ===== -->
            <h4 class="mb-1 fw-bold">รหัสหลักสูตรทุกไตรมาส</h4>
            <div class="row g-3 mb-4">
                <?php for ($i = 1; $i <= 4; $i++): $q = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                    <div class="col-md-3">
                        <div class="code-builder" data-name="course_code_cpd_<?php echo $i; ?>" data-quarter="<?php echo $q; ?>">
                            <label class="form-label fw-medium mb-1">รหัสหลักสูตร CPD (ไตรมาส <?php echo $i; ?>)</label>
                            <div class="cb-row d-flex flex-wrap align-items-center gap-1"></div>
                            <div class="cb-toggles d-flex justify-content-end gap-3 mt-1 small text-secondary">
                                <label class="m-0 user-select-none"><input type="checkbox" class="cb-year"> ปี</label>
                                <label class="m-0 user-select-none"><input type="checkbox" class="cb-quarter"> ไตรมาส</label>
                            </div>
                            <input type="hidden" name="course_code_cpd_<?php echo $i; ?>" value="<?php echo $cv('course_code_cpd_' . $i); ?>">
                        </div>
                    </div>
                <?php endfor; ?>
                <?php for ($i = 1; $i <= 4; $i++): $q = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                    <div class="col-md-3">
                        <div class="code-builder" data-name="course_code_cpa_<?php echo $i; ?>" data-quarter="<?php echo $q; ?>">
                            <label class="form-label fw-medium mb-1">รหัสหลักสูตร CPA (ไตรมาส <?php echo $i; ?>)</label>
                            <div class="cb-row d-flex flex-wrap align-items-center gap-1"></div>
                            <div class="cb-toggles d-flex justify-content-end gap-3 mt-1 small text-secondary">
                                <label class="m-0 user-select-none"><input type="checkbox" class="cb-year"> ปี</label>
                                <label class="m-0 user-select-none"><input type="checkbox" class="cb-quarter"> ไตรมาส</label>
                            </div>
                            <input type="hidden" name="course_code_cpa_<?php echo $i; ?>" value="<?php echo $cv('course_code_cpa_' . $i); ?>">
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- ===== การทำข้อสอบ ===== -->
            <h4 class="mb-3 fw-bold">การทำข้อสอบ</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label fw-medium">ระยะเวลาทำข้อสอบ (นาที) <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="course_exam_time" placeholder="0"></div>
                <div class="col-md-3"><label class="form-label fw-medium">คะแนนขั้นต่ำในการผ่านข้อสอบ <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="course_minimum_score" placeholder="0"></div>
                <div class="col-md-3"><label class="form-label fw-medium">จำนวนข้อสอบที่ต้องทำ <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="course_number_exam" placeholder="0"></div>
                <div class="col-md-3"><label class="form-label fw-medium">จำนวนครั้งที่ทำข้อสอบได้ <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="course_number_time" placeholder="0"></div>
            </div>

            <h4 class="mb-3 fw-bold">การอนุมัติใบรับรองอัตโนมัติ</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-medium">อนุมัติใบรับรองอัตโนมัติ <span class="text-danger">*</span></label>
                    <select class="form-select" name="course_auto_approve">
                        <option value="">-- กรุณาเลือก --</option>
                        <option value="0" <?php echo $cv('approve_certificate_auto') === '0' ? 'selected' : ''; ?>>ปิดการใช้งาน</option>
                        <option value="1" <?php echo $cv('approve_certificate_auto') === '1' ? 'selected' : ''; ?>>เปิดใช้งาน</option>
                    </select>
                    <div class="form-text mt-1 text-secondary">เมื่อเปิดใช้งาน ระบบจะอนุมัติใบรับรองให้ผู้สอบผ่านโดยอัตโนมัติ</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">วิธีการออกใบรับรอง <span class="text-danger">*</span></label>
                    <select class="form-select" name="course_approve_type">
                        <option value="">-- กรุณาเลือก --</option>
                        <option value="0" <?php echo $cv('approver_certificate_type') === '0' ? 'selected' : ''; ?>>ทันที</option>
                        <option value="1" <?php echo $cv('approver_certificate_type') === '1' ? 'selected' : ''; ?>>ตามกำหนดเวลา</option>
                    </select>
                    <div class="form-text mt-1 text-secondary">ทันที : ออกใบรับรองทันทีเมื่อสอบผ่าน / ตามกำหนดเวลา : ออกใบรับรองทุก 5 นาที</div>
                </div>
            </div>

            <!-- ===== ราคาและข้อมูลที่จำเป็นอื่น ๆ ===== -->
            <h4 class="mb-3 fw-bold">ราคาและข้อมูลที่จำเป็นอื่น ๆ</h4>

            <h6 class="fw-bold text-secondary mb-2">ผู้ทำ</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label fw-medium">ชั่วโมง CPD บัญชี <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_cpd_hour" placeholder="0.00"></div>
                <div class="col-md-4"><label class="form-label fw-medium">ชั่วโมง CPD บัญชี (จรรยาบรรณ) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_cpd_ethics" placeholder="0.00"></div>
                <div class="col-md-4"><label class="form-label fw-medium">ชั่วโมง CPD (อื่น ๆ) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_cpd_other" placeholder="0.00"></div>
            </div>

            <h6 class="fw-bold text-secondary mb-2">ผู้สอบ</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label fw-medium">ชั่วโมง CPA บัญชี <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_cpa_hour" placeholder="0.00"></div>
                <div class="col-md-4"><label class="form-label fw-medium">ชั่วโมง CPA บัญชี (จรรยาบรรณ) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_cpa_ethics" placeholder="0.00"></div>
                <div class="col-md-4"><label class="form-label fw-medium">ชั่วโมง CPA (อื่น ๆ) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_cpa_other" placeholder="0.00"></div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label fw-medium">ราคาปกติ <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_price" placeholder="0.00"></div>
                <div class="col-md-3"><label class="form-label fw-medium">ราคาโปรโมชั่น</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="course_promotion" placeholder="0.00"></div>
                <div class="col-md-3"><label class="form-label fw-medium">ระยะเวลาอบรม (วัน) <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="course_period" placeholder="0"></div>
                
            </div>
            

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-medium d-block">แสดงคอร์สในหน้าแรก <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_display" id="course_display_1" value="1">
                        <label class="form-check-label" for="course_display_1">แสดง</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_display" id="course_display_0" value="0" checked>
                        <label class="form-check-label" for="course_display_0">ไม่แสดง</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium d-block">สถานะคอร์สเรียน <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_status" id="course_status_1" value="1" checked>
                        <label class="form-check-label" for="course_status_1">เปิดใช้งาน</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_status" id="course_status_0" value="0">
                        <label class="form-check-label" for="course_status_0">ปิดใช้งาน</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium d-block">การข้ามวีดีโอ <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_skip" id="course_skip_1" value="1" checked>
                        <label class="form-check-label" for="course_skip_1">เปิดใช้งาน</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_skip" id="course_skip_0" value="0">
                        <label class="form-check-label" for="course_skip_0">ปิดใช้งาน</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium d-block">คำถามระหว่างรับชม <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_question" id="course_question_1" value="1" checked>
                        <label class="form-check-label" for="course_question_1">เปิดใช้งาน</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_question" id="course_question_0" value="0">
                        <label class="form-check-label" for="course_question_0">ปิดใช้งาน</label>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-medium d-block">การยืนยัน OTP <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_otp" id="course_otp_1" value="1" checked>
                        <label class="form-check-label" for="course_otp_1">เปิดใช้งาน</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="course_otp" id="course_otp_0" value="0">
                        <label class="form-check-label" for="course_otp_0">ปิดใช้งาน</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">จำนวนคำถาม/OTP ระหว่างรับชม</label>
                    <input type="number" min="0" class="form-control" name="course_question_limit" placeholder="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">ระยะเวลาในการตอบ (วินาที)</label>
                    <input type="number" min="0" class="form-control" name="course_question_time" placeholder="0">
                </div>
            </div>

            <div class="border-top pt-3">
                <button type="button" class="btn btn-primary w-100 py-2 BtnSaveCourse" onclick="SaveCourse()">
                    <?php echo $mode === 'edit' ? 'บันทึกการแก้ไข' : 'เพิ่มคอร์สเรียนใหม่'; ?>
                </button>
            </div>
        </form>
<?php if ($mode === 'edit'): ?>
</div>
<?php else: ?>
    </div>
</div>
<?php endif; ?>

<script>
    // ข้อมูลโหมดแก้ไข (โหมดเพิ่ม = null → prefill ทั้งหมดถูกข้าม ทำงานเหมือนเดิม)
    var FORM_MODE = '<?php echo $mode; ?>';
    var COURSE = <?php echo $course ? json_encode($course, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : 'null'; ?>;

    // เริ่มต้น dropdown + rich text editor (รันหลังฟอร์มถูกแทรกเข้า DOM)
    (function () {
        if ($.fn.select2) {
            $('#course_type').select2({ width: '100%', placeholder: 'เลือกประเภท' });
            $('#course_group').select2({ width: '100%', placeholder: 'กรุณาเลือกหมวดหมู่' });
        }

        var fpApprovalDates = [];
        if (typeof flatpickr !== 'undefined') {
            fpApprovalDates = flatpickr(".course-approval-date", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: true
            });
        }

        // เปิด/ปิดช่อง "วันที่อนุมัติหลักสูตร" ตามประเภท: ทั่วไป = ปิด (disabled สีเทา), อื่นๆ (เช่น เก็บชั่วโมงเรียน) = เปิดให้กรอก
        function toggleApprovalDates() {
            var selectedOpt = $('#course_type option:selected');
            var name = (selectedOpt.data('name') || selectedOpt.text() || '').toString().trim();
            var enable = (name !== '' && name !== 'ทั่วไป');
            $('.course-approval-date').each(function () {
                var fp = this._flatpickr;
                if (fp) {
                    fp.altInput.disabled = !enable;
                    fp.input.disabled = !enable;
                } else {
                    $(this).prop('disabled', !enable);
                }
            });
        }
        $('#course_type').on('change', toggleApprovalDates);
        toggleApprovalDates();

        // พรีวิวรูปหน้าปก — แสดงเมื่อเลือกไฟล์ใหม่ (ใช้ทั้งโหมดเพิ่มและแก้ไข)
        function showCoverPreview(src) {
            if (src) { $('#coverPreview').attr('src', src); $('#coverPreviewWrap').show(); }
            else { $('#coverPreviewWrap').hide(); }
        }
        $('#course_cover_image').on('change', function () {
            var f = this.files && this.files[0];
            if (f) { var r = new FileReader(); r.onload = function (e) { showCoverPreview(e.target.result); }; r.readAsDataURL(f); }
        });
        if (typeof tinymce !== 'undefined') {
            if (tinymce.get('editor_course_detail')) { tinymce.get('editor_course_detail').remove(); }
            tinymce.init({
                selector: '#editor_course_detail',
                height: 280,
                menubar: false,
                elementpath: false,
                plugins: 'lists link',
                toolbar: 'blocks | bold italic underline | bullist numlist | alignleft aligncenter alignright | link | removeformat',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                placeholder: 'กรอกรายละเอียดคอร์สเรียน...',
                setup: function (editor) {
                    editor.on('init', function () {
                        if (typeof COURSE !== 'undefined' && COURSE && COURSE.course_detail) {
                            editor.setContent(COURSE.course_detail);
                        }
                    });
                }
            });
        }

        // ===== ตัวสร้างรหัสหลักสูตร (year ddl + quarter + textbox) =====
        function initCodeBuilder(root) {
            const hidden     = root.querySelector('input[type=hidden]');
            const row        = root.querySelector('.cb-row');
            const cbYear     = root.querySelector('.cb-year');
            const cbQuarter  = root.querySelector('.cb-quarter');
            const quarter    = root.dataset.quarter || '01';

            const ce = new Date().getFullYear();   // ค.ศ.
            const be = ce + 543;                    // พ.ศ.
            const yearOpts = [
                { v: String(be),            t: String(be) },
                { v: String(be).slice(-2),  t: String(be).slice(-2) },
                { v: String(ce),            t: String(ce) },
                { v: String(ce).slice(-2),  t: String(ce).slice(-2) }
            ];

            // segments: [{type:'text'|'year'|'quarter', value}]
            // โหมดแก้ไข: parse ค่าเดิม เช่น "[2569]10-06-330-002-[01]-E" กลับเป็น segments
            // กฎ inverse ของ recompute(): [content] ที่ตรง data-quarter = ไตรมาส, นอกนั้น = ปี
            function parseStored(str) {
                if (!str) { return [{ type: 'text', value: '' }]; }
                const parts = str.split(/(\[[^\]]*\])/).filter(function (s) { return s !== ''; });
                const out = [];
                parts.forEach(function (p) {
                    const m = p.match(/^\[([^\]]*)\]$/);
                    if (m) {
                        out.push({ type: (m[1] === quarter ? 'quarter' : 'year'), value: m[1] });
                    } else {
                        out.push({ type: 'text', value: p });
                    }
                });
                if (!out.length || out[out.length - 1].type !== 'text') { out.push({ type: 'text', value: '' }); }
                return out;
            }
            let segs = parseStored(hidden.value);
            // ตั้งสถานะ checkbox ให้ตรงกับค่าที่ parse ได้
            cbYear.checked    = segs.some(function (s) { return s.type === 'year'; });
            cbQuarter.checked = segs.some(function (s) { return s.type === 'quarter'; });
            let active = { seg: null, pos: 0 };   // ช่อง text + ตำแหน่ง cursor ล่าสุด

            function syncFromDom() {
                Array.from(row.children).forEach(function (node, i) {
                    if (segs[i] && (segs[i].type === 'text' || segs[i].type === 'year')) {
                        segs[i].value = node.value;
                    }
                });
            }
            function recompute() {
                // ปี/ไตรมาส คร่อมด้วย [] เพื่อให้หน้าแก้ไข parse แยกกลับเป็น array ได้ เช่น [2569]10-06-330-002-[01]-E
                hidden.value = segs.map(function (s) {
                    if (s.type === 'year' || s.type === 'quarter') { return '[' + (s.value || '') + ']'; }
                    return s.value || '';
                }).join('');
            }
            // วัดความกว้างของข้อความใน input แล้วตั้ง width ให้พอดี (ใช้ span ซ่อนวัด)
            function cbFitWidth(el) {
                let m = document.getElementById('cbMeasureSpan');
                if (!m) {
                    m = document.createElement('span');
                    m.id = 'cbMeasureSpan';
                    m.style.cssText = 'position:absolute;left:-9999px;top:0;white-space:pre;visibility:hidden;';
                    document.body.appendChild(m);
                }
                const cs = getComputedStyle(el);
                m.style.font = cs.font;
                m.style.letterSpacing = cs.letterSpacing;
                m.textContent = el.value || '';
                el.style.width = (m.offsetWidth + 2) + 'px';
            }
            function render() {
                row.innerHTML = '';
                segs.forEach(function (s, i) {
                    let el;
                    if (s.type === 'year') {
                        el = document.createElement('select');
                        el.className = 'cb-seg-year';
                        if (!s.value) s.value = yearOpts[0].v;
                        // ปีเดิมที่ไม่อยู่ใน options ปัจจุบัน (เช่นคอร์สเก่า) → เพิ่มเข้าไปให้แสดงได้
                        if (!yearOpts.some(function (o) { return o.v === s.value; })) {
                            const op0 = document.createElement('option');
                            op0.value = s.value; op0.textContent = s.value; op0.selected = true;
                            el.appendChild(op0);
                        }
                        yearOpts.forEach(function (o) {
                            const op = document.createElement('option');
                            op.value = o.v; op.textContent = o.t;
                            if (o.v === s.value) op.selected = true;
                            el.appendChild(op);
                        });
                        el.addEventListener('change', function () { s.value = el.value; recompute(); });
                    } else if (s.type === 'quarter') {
                        el = document.createElement('input');
                        el.type = 'text';
                        el.className = 'cb-seg-quarter form-control form-control-sm';
                        el.value = s.value; el.disabled = true;
                    } else {
                        el = document.createElement('input');
                        el.type = 'text';
                        el.className = 'cb-seg-text';
                        el.value = s.value;
                        // ช่อง text ตัวสุดท้าย = พื้นที่พิมพ์ต่อ → ยืดเต็มเสมอ (คลิก/พิมพ์ได้แม้ว่าง); ตัวอื่นกว้างพอดีเนื้อหา
                        const isLast = (i === segs.length - 1);
                        if (isLast) { el.classList.add('cb-seg-last'); }
                        const track = function () { active.seg = s; active.pos = el.selectionStart || 0; };
                        el.addEventListener('input', function () { s.value = el.value; if (!isLast) { cbFitWidth(el); } track(); recompute(); });
                        el.addEventListener('focus', track);
                        el.addEventListener('click', track);
                        el.addEventListener('keyup', track);
                    }
                    row.appendChild(el);
                });
                // วัดความกว้างจริงของช่อง text (ยกเว้นตัวสุดท้ายที่ยืดเต็ม) ให้พอดีเนื้อหา — ปี/ไตรมาสจึงมาติดท้ายพอดี
                Array.prototype.forEach.call(row.querySelectorAll('.cb-seg-text:not(.cb-seg-last)'), cbFitWidth);
                recompute();
            }

            // แทรก segment ใหม่ (ปี/ไตรมาส) ตรงตำแหน่ง cursor ของช่องที่กำลังพิมพ์
            function insertAtCursor(newSeg) {
                syncFromDom();
                let idx = active.seg ? segs.indexOf(active.seg) : -1;
                if (idx < 0 || segs[idx].type !== 'text') {
                    for (let k = segs.length - 1; k >= 0; k--) { if (segs[k].type === 'text') { idx = k; break; } }
                }
                if (idx < 0) { segs.push(newSeg, { type: 'text', value: '' }); render(); return; }
                const seg = segs[idx];
                const val = seg.value || '';
                let pos = (active.seg === seg) ? active.pos : val.length;
                pos = Math.max(0, Math.min(pos, val.length));
                segs.splice(idx, 1, { type: 'text', value: val.slice(0, pos) }, newSeg, { type: 'text', value: val.slice(pos) });
                render();
                const after = row.children[idx + 2];
                if (after && after.focus) { active.seg = segs[idx + 2]; active.pos = 0; after.focus(); if (after.setSelectionRange) after.setSelectionRange(0, 0); }
            }

            // ลบ segment (ปี/ไตรมาส) แล้วรวมข้อความซ้าย-ขวาเข้าด้วยกัน
            function removeSeg(type) {
                syncFromDom();
                const i = segs.findIndex(function (s) { return s.type === type; });
                if (i < 0) { render(); return; }
                const before = (i - 1 >= 0 && segs[i - 1].type === 'text') ? segs[i - 1] : null;
                const after = (segs[i + 1] && segs[i + 1].type === 'text') ? segs[i + 1] : null;
                if (before && after) {
                    before.value = (before.value || '') + (after.value || '');
                    segs.splice(i, 2);
                } else {
                    segs.splice(i, 1);
                }
                render();
            }

            cbYear.addEventListener('change', function () {
                if (this.checked) { insertAtCursor({ type: 'year', value: yearOpts[0].v }); }
                else { removeSeg('year'); }
            });
            cbQuarter.addEventListener('change', function () {
                if (this.checked) { insertAtCursor({ type: 'quarter', value: quarter }); }
                else { removeSeg('quarter'); }
            });

            render();

            // API ให้ช่องอื่นดึง/เซ็ต segments (ใช้ตอนกดปุ่มคัดลอก)
            root._cb = {
                getSegs: function () {
                    syncFromDom();
                    return segs.map(function (s) { return { type: s.type, value: s.value }; });
                },
                setSegs: function (newSegs) {
                    // ก็อป segments มา แต่ "ไตรมาส" ใช้ค่าของช่องปลายทางเอง (รันเป็น 02/03/04 อัตโนมัติ) ; ปีก็อปเหมือนเดิม
                    segs = newSegs.map(function (s) {
                        return { type: s.type, value: (s.type === 'quarter') ? quarter : s.value };
                    });
                    if (!segs.length || segs[segs.length - 1].type !== 'text') { segs.push({ type: 'text', value: '' }); }
                    cbYear.checked    = segs.some(function (s) { return s.type === 'year'; });
                    cbQuarter.checked = segs.some(function (s) { return s.type === 'quarter'; });
                    render();
                }
            };

            // ปุ่มคัดลอก/ล้าง: แสดงเฉพาะช่องไตรมาสแรก (data-quarter=01) -> เติมหรือล้างช่อง Q2-4
            if (quarter === '01') {
                const actionBtn = document.createElement('button');
                actionBtn.type = 'button';
                actionBtn.className = 'btn btn-sm btn-outline-primary py-0 px-2 ms-2';
                actionBtn.innerHTML = '<span class="material-symbols-outlined align-middle" style="font-size:15px;">content_copy</span> คัดลอก';

            // ฟังก์ชันสำหรับอัปเดตหน้าตาปุ่ม
            function updateBtnVisual(isAllFilled) {
            // ตั้งค่าพื้นฐานที่ต้องการให้เหมือนกันทุกสถานะ
                actionBtn.style.minWidth = '90px'; // กำหนดความกว้างเท่ากันที่นี่
                actionBtn.style.display = 'inline-flex';
                actionBtn.style.alignItems = 'center';
                actionBtn.style.justifyContent = 'center';

            if (isAllFilled) {
                actionBtn.className = 'btn btn-sm btn-outline-danger py-0 px-2 ms-2';
                actionBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:15px; margin-right: 5px;">delete_sweep</span> ล้าง';
            } else {
                actionBtn.className = 'btn btn-sm btn-outline-primary py-0 px-2 ms-2';
                actionBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:15px; margin-right: 5px;">content_copy</span> คัดลอก';
                }
            }

                // ฟังก์ชันตรวจสอบว่าช่อง Q2-4 มีข้อมูลครบทุกช่องหรือไม่
                function checkAllFilled() {
                    const prefix = root.dataset.name.slice(0, -1);
                    let allFilled = true;
                    [2, 3, 4].forEach(function (q) {
                        const t = document.querySelector('.code-builder[data-name="' + prefix + q + '"]');
                        if (t) {
                            const h = t.querySelector('input[type=hidden]');
                            if (!h || h.value.trim() === '') {
                                allFilled = false;
                            }
                        }
                    });
                    return allFilled;
                }

                actionBtn.addEventListener('click', function () {
                    const prefix = root.dataset.name.slice(0, -1);
                    const allFilled = checkAllFilled();

                    if (!allFilled) {
                        // โหมดคัดลอก: มีบางช่องยังว่างอยู่ -> ให้คัดลอก Q1 ไปทับ Q2-4
                        const srcSegs = root._cb.getSegs();
                        [2, 3, 4].forEach(function (q) {
                            const t = document.querySelector('.code-builder[data-name="' + prefix + q + '"]');
                            if (t && t._cb) { t._cb.setSegs(srcSegs); }
                        });
                        updateBtnVisual(true); // เปลี่ยนปุ่มเป็นล้าง
                    } else {
                        // โหมดล้าง: มีข้อมูลครบทุกช่องแล้ว -> ให้ล้าง Q2-4
                        const emptySegs = [{ type: 'text', value: '' }];
                        [2, 3, 4].forEach(function (q) {
                            const t = document.querySelector('.code-builder[data-name="' + prefix + q + '"]');
                            if (t && t._cb) { t._cb.setSegs(emptySegs); }
                        });
                        updateBtnVisual(false); // เปลี่ยนปุ่มเป็นคัดลอก
                    }
                });

                // ตรวจสอบสถานะปุ่มตอนโหลดหน้า (เช่น ในโหมดแก้ไข)
                setTimeout(function() {
                    updateBtnVisual(checkAllFilled());
                }, 150);

                root.querySelector('.cb-toggles').appendChild(actionBtn);
            }
        }
        document.querySelectorAll('.code-builder').forEach(initCodeBuilder);

        // ===== โหมดแก้ไข: เติมข้อมูลเดิมลงฟอร์ม =====
        if (FORM_MODE === 'edit' && COURSE) {
            ['course_name', 'course_instructor', 'course_overview', 'course_demo_link',
             'course_exam_time', 'course_minimum_score', 'course_number_exam', 'course_number_time',
             'course_cpd_hour', 'course_cpd_ethics', 'course_cpd_other',
             'course_cpa_hour', 'course_cpa_ethics', 'course_cpa_other',
             'course_price', 'course_promotion', 'course_period'
            ].forEach(function (n) {
                if (COURSE[n] !== null && COURSE[n] !== undefined) { $('[name="' + n + '"]').val(COURSE[n]); }
            });

            // แมพค่าฟิลด์คำถาม/เวลาที่ไม่มีคำนำหน้า course_ จากฐานข้อมูลลงฟอร์มอินพุต
            if (COURSE.question_limit !== null && COURSE.question_limit !== undefined) {
                $('[name="course_question_limit"]').val(COURSE.question_limit);
            }
            if (COURSE.question_time !== null && COURSE.question_time !== undefined) {
                $('[name="course_question_time"]').val(COURSE.question_time);
            }

            // select (+ refresh select2) — trigger change ทำให้ toggleApprovalDates อัปเดตด้วย
            $('#course_type').val(COURSE.course_type || '').trigger('change');
            $('#course_group').val(COURSE.course_group || '').trigger('change');
            // radio flags
            ['course_display', 'course_status', 'course_skip', 'course_otp', 'course_question'].forEach(function (n) {
                if (COURSE[n] !== null && COURSE[n] !== undefined) {
                    $('[name="' + n + '"][value="' + COURSE[n] + '"]').prop('checked', true);
                }
            });
            // วันที่อนุมัติ — ตั้งหลัง trigger change (จะถูกเปิดถ้าประเภทไม่ใช่ "ทั่วไป")
            for (var qd = 1; qd <= 4; qd++) {
                var dn = 'course_approval_date_' + qd;
                var val = COURSE[dn];
                if (val && val !== '0000-00-00') {
                    var cleanVal = val.toString().trim().split(' ')[0];
                    var el = document.getElementById(dn);
                    if (el && el._flatpickr) {
                        el._flatpickr.setDate(cleanVal, true);
                    } else if (el) {
                        $(el).val(cleanVal);
                    }
                }
            }
            // รายละเอียดคอร์ส (TinyMCE) — เซ็ตค่าตอน editor init เสร็จแล้ว (ดู setup ด้านบน)
            // พรีวิวรูปหน้าปกเดิม
            if (COURSE.course_cover_image) { showCoverPreview(/^https?:\/\//.test(COURSE.course_cover_image) ? COURSE.course_cover_image : '../' + COURSE.course_cover_image); }
        }
    })();

    function SaveCourse() {
        // ดึงเนื้อหาจาก TinyMCE ใส่ hidden input
        var edCourseDetail = (typeof tinymce !== 'undefined') ? tinymce.get('editor_course_detail') : null;
        if (edCourseDetail) {
            let html = edCourseDetail.getContent();
            if (edCourseDetail.getContent({ format: 'text' }).trim() === '') { html = ''; }
            $('#course_detail').val(html);
        }

        // ===== ตรวจช่องบังคับให้ครบ (โชว์ทุกช่องที่ขาดพร้อมกัน + เลื่อนไปช่องแรก + ขอบแดง) =====
        const courseWarn = function (msg) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + msg + '</span>', icon: "warning", showConfirmButton: false, timer: 2000, timerProgressBar: true });
        };

        const numLabels = {
            course_exam_time: 'ระยะเวลาทำข้อสอบ', course_minimum_score: 'คะแนนขั้นต่ำในการผ่านข้อสอบ',
            course_number_exam: 'จำนวนข้อสอบที่ต้องทำ', course_number_time: 'จำนวนครั้งที่ทำข้อสอบได้',
            course_cpd_hour: 'ชั่วโมง CPD บัญชี', course_cpd_ethics: 'ชั่วโมง CPD บัญชี (จรรยาบรรณ)', course_cpd_other: 'ชั่วโมง CPD (อื่น ๆ)',
            course_cpa_hour: 'ชั่วโมง CPA บัญชี', course_cpa_ethics: 'ชั่วโมง CPA บัญชี (จรรยาบรรณ)', course_cpa_other: 'ชั่วโมง CPA (อื่น ๆ)',
            course_price: 'ราคาปกติ', course_period: 'ระยะเวลาอบรม (วัน)'
        };
        const rules = [
            { sel: '#course_cover_image', label: 'รูปหน้าปก', type: 'file', when: function () { return FORM_MODE !== 'edit'; } },
            { sel: '#course_name',          label: 'ชื่อคอร์สเรียน' },
            { sel: '#course_type',          label: 'ประเภท', type: 'select2' },
            { sel: '#course_group',         label: 'หมวดหมู่', type: 'select2' },
            { sel: '#course_instructor',    label: 'ผู้บรรยาย/ผู้สอน' },
            { sel: '#course_overview',      label: 'รายละเอียดโดยย่อ' },
            { sel: '#editor_course_detail', label: 'รายละเอียดแบบเต็ม', type: 'tinymce', editorId: 'editor_course_detail' }
        ].concat(Object.keys(numLabels).map(function (k) {
            return { sel: '[name="' + k + '"]', label: numLabels[k], type: 'number' };
        }));
        if (!ValidateRequired(rules)) { return; }

        // ตัวเลขห้ามติดลบ + โปรโมชั่นไม่เกินราคาปกติ (ตรวจเพิ่มหลังกรอกครบ)
        for (let k in numLabels) {
            const v = Number(($('[name="' + k + '"]').val() || '').trim());
            if (!isFinite(v) || v < 0) { courseWarn(numLabels[k] + ' ต้องเป็นตัวเลขไม่ติดลบ'); return; }
        }
        const promoRaw = ($('[name="course_promotion"]').val() || '').trim();
        if (promoRaw !== '') {
            const promo = Number(promoRaw);
            const price = Number($('[name="course_price"]').val() || 0);
            if (!isFinite(promo) || promo < 0) { courseWarn('ราคาโปรโมชั่นต้องเป็นตัวเลขไม่ติดลบ'); return; }
            if (promo > price) { courseWarn('ราคาโปรโมชั่นต้องไม่เกินราคาปกติ'); return; }
        }

        const isEdit = (FORM_MODE === 'edit');
        const formData = new FormData($('#formAddCourse')[0]);
        formData.append('request_state', 'list_course');
        formData.append('request_function', isEdit ? 'update_course' : 'add_course');

        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSaveCourse'); },
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, allowOutsideClick: false, timer: 1500, timerProgressBar: true })
                        .then(() => { if (!isEdit) { window.location.href = "course.php"; } });
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, timer: 2500, timerProgressBar: true });
                }
            },
            complete: function () { HideLoadingButton('.BtnSaveCourse'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
</script>
