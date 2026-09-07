<?php $breadcrumbs = [['label' => 'รายงาน/เอกสาร']]; ?>
<?php include "header.php"; ?>

<style>
    /* ===== หน้ารายงาน/เอกสาร: แบ่งซ้าย/ขวา 30/70 ===== */
    .rp-step-label { font-size: .8rem; font-weight: 600; letter-spacing: .02em; color: var(--brand-500); text-transform: uppercase; }
    @media (min-width: 992px) {
        .rp-col-left  { flex: 0 0 30%; max-width: 30%; }
        .rp-col-right { flex: 0 0 70%; max-width: 70%; border-left: 1px solid var(--border); }
    }
    #rpFilterPanel { animation: rpFade .2s ease; }
    @keyframes rpFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    /* หัวข้อ panel ระบุเงื่อนไข */
    .rp-panel-head { display: flex; align-items: center; gap: .6rem; margin-bottom: 1.25rem; padding-bottom: .9rem; border-bottom: 1px solid var(--border); }
    .rp-panel-head .rp-head-icon {
        flex-shrink: 0; width: 42px; height: 42px; border-radius: .6rem; display: inline-flex;
        align-items: center; justify-content: center; background: var(--brand-soft); color: var(--brand-500);
    }
    .rp-panel-head .rp-head-icon .material-symbols-outlined { font-size: 24px; }
    /* ช่องกรอกข้อมูล: พื้นหลังขาว
       (web.css ตั้ง .form-control{background:var(--bs-body-bg)!important} = เทา #F6F7F9
        ต้องใช้ !important + specificity สูงกว่าถึงจะชนะ) */
    .rp-col-right .form-control,
    .rp-col-right .form-select,
    .rp-col-right .form-control:focus,
    .rp-col-right .form-select:focus { background-color: #fff !important; }
    .rp-col-right .form-label { color: var(--text); }
    /* แถบปุ่มดาวน์โหลด */
    .rp-actions { border-top: 1px solid var(--border); margin-top: 1.5rem; padding-top: 1.25rem; }
    .rp-empty { color: var(--text-muted); text-align: center; padding: 2.5rem 1rem; }
    .rp-empty .material-symbols-outlined { font-size: 46px; opacity: .5; }
    /* รายการประเภทเอกสารฝั่งซ้าย */
    .rp-list-label { font-size: .74rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .02em; margin: .25rem 0 .45rem; }
    .rp-type-item {
        display: flex; align-items: center; gap: .55rem; width: 100%; text-align: left;
        background: #fff; border: 1px solid var(--border); border-radius: .6rem;
        padding: .6rem .75rem; margin-bottom: .4rem; cursor: pointer; color: var(--text);
        transition: border-color .12s ease, background .12s ease, box-shadow .12s ease;
    }
    .rp-type-item:hover { border-color: var(--brand-400); background: var(--brand-soft); }
    .rp-type-item.active { background: var(--brand-500); border-color: var(--brand-500); color: #fff; box-shadow: 0 4px 12px rgba(96,93,255,.22); }
    .rp-item-icon { font-size: 20px; color: var(--brand-500); flex-shrink: 0; }
    .rp-type-item.active .rp-item-icon { color: #fff; }
    .rp-item-title { flex-grow: 1; line-height: 1.25; font-size: .9rem; }

    /* Select2 overrides */
    .select2-container .select2-selection--single {
        height: 36px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px !important; padding-left: 0.75rem !important; color: var(--bs-body-color) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #6c757d !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px !important; }
    /* กำหนดความสูงของรายการเลือกใน Select2 ให้มองเห็นได้ประมาณ 10 รายการแรกเมื่อคลิกเปิด (และเลื่อนดูเพิ่มได้) */
    .select2-container--default .select2-results__options {
        max-height: 300px !important;
        overflow-y: auto !important;
    }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">
        <?php include "navbar.php"; ?>
        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white p-4">
                    <h2 class="mb-1">รายงาน/เอกสาร</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">

                        <!-- ===== ซ้าย (30%): ประเภทเอกสาร (รายการ) ===== -->
                        <div class="col-12 rp-col-left">
                            <div class="rp-step-label mb-2">ประเภทเอกสาร</div>
                            <div id="rpTypeList"></div>
                        </div>

                        <!-- ===== ขวา (70%): ระบุเงื่อนไข ===== -->
                        <div class="col-12 rp-col-right">
                            <div class="rp-step-label mb-3">ระบุเงื่อนไข</div>

                            <!-- ว่าง: ยังไม่เลือกประเภท -->
                            <div id="rpEmpty" class="rp-empty">
                                <div><span class="material-symbols-outlined" aria-hidden="true">description</span></div>
                                <div class="mt-2">เลือกประเภทเอกสารทางซ้ายเพื่อระบุเงื่อนไข</div>
                            </div>

                            <!-- ตัวกรอง + ดาวน์โหลด -->
                            <div id="rpFilterPanel" style="display:none;">
                                <div class="rp-panel-head">
                                    <span class="rp-head-icon"><span class="material-symbols-outlined" id="rpPanelIcon" aria-hidden="true">description</span></span>
                                    <div>
                                        <div class="fw-semibold" id="rpPanelTitle"></div>
                                        <div class="text-secondary small" id="rpPanelDesc"></div>
                                    </div>
                                </div>

                                <form id="ReportForm" autocomplete="off">
                                    <div class="row g-3" id="ReportFields"></div>

                                    <!-- ข้อความสำหรับประเภทที่ยัง mock (toggle ด้วย d-none เพราะ d-flex เป็น !important) -->
                                    <div class="alert alert-info d-flex align-items-start gap-2 mt-3 d-none" id="rpMockNote">
                                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                                        <span id="rpMockText"></span>
                                    </div>

                                    <div class="rp-actions text-end" id="ReportSubmitWrap" style="display:none;">
                                        <button type="submit" class="btn btn-primary px-4 py-2 BtnDownloadReport">
                                            <span class="material-symbols-outlined align-middle me-1" style="font-size:20px;" aria-hidden="true">download</span>ดาวน์โหลด
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
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
    // ====== ข้อมูลประเภทเอกสาร (เมตา + ฟิลด์ฟิลเตอร์) ======
    // mock=true => ยังไม่สร้างไฟล์จริง (ต้องส่งอีเมล) แสดงข้อความแทนปุ่มดาวน์โหลด
    var TYPES = [
        {
            key: "cpa_attendance", group: "report", icon: "fact_check", fileLabel: "Excel",
            title: "รายชื่อผู้เข้าอบรม (ผู้สอบบัญชี CPA)",
            desc: "รายชื่อผู้สอบบัญชีที่เข้าอบรมในคอร์สที่เลือก พร้อมชั่วโมงและคะแนน",
            fields: [
                { id: "course_id", label: "คอร์สเรียน", kind: "course", req: true },
                { id: "from", label: "จากวันที่ (วันที่อนุมัติ)", kind: "date", req: true },
                { id: "to", label: "ถึงวันที่ (วันที่อนุมัติ)", kind: "date", req: true },
                { id: "agency_code", label: "รหัสหน่วยงาน", kind: "text", req: true, val: "06-330" },
                { id: "agency_name", label: "ชื่อหน่วยงาน", kind: "text", req: true, val: "บริษัท เอ เอ็ม ซีพีดี จำกัด" },
                { id: "seminar_place", label: "สถานที่อบรม/สัมมนา", kind: "text", req: true, val: "e-Learning" }
            ]
        },
        {
            key: "cpd_attendance", group: "report", icon: "fact_check", fileLabel: "Excel",
            title: "รายชื่อผู้เข้าอบรม (ผู้ทำบัญชี CPD)",
            desc: "รายชื่อผู้ทำบัญชีที่เข้าอบรมในคอร์สที่เลือก พร้อมชั่วโมงและคะแนน",
            fields: [
                { id: "course_id", label: "คอร์สเรียน", kind: "course", req: true },
                { id: "from", label: "จากวันที่ (วันที่อนุมัติ)", kind: "date", req: true },
                { id: "to", label: "ถึงวันที่ (วันที่อนุมัติ)", kind: "date", req: true },
                { id: "agency_code", label: "รหัสหน่วยงาน", kind: "text", req: true, val: "06-330" },
                { id: "agency_name", label: "ชื่อหน่วยงาน", kind: "text", req: true, val: "บริษัท เอ เอ็ม ซีพีดี จำกัด" },
                { id: "seminar_place", label: "สถานที่อบรม/สัมมนา", kind: "text", req: true, val: "e-Learning" }
            ]
        },
        {
            key: "course_attendance_registration", group: "report", icon: "assignment", fileLabel: "Excel",
            title: "ฟอร์มใบลงทะเบียนอบรม",
            desc: "ใบลงทะเบียน/เซ็นชื่อเข้าอบรม สำหรับผู้มีสิทธิ์เข้าเรียนในคอร์ส",
            fields: [
                { id: "course_id", label: "คอร์สเรียน", kind: "course", req: true },
                { id: "from", label: "จากวันที่ (มีสิทธิ์เข้าเรียน/สั่งซื้อ)", kind: "date" },
                { id: "to", label: "ถึงวันที่ (มีสิทธิ์เข้าเรียน/สั่งซื้อ)", kind: "date" },
                { id: "seminar_date", label: "วันที่อบรม/สัมมนา", kind: "date", req: true },
                { id: "seminar_time", label: "เวลาที่อบรม/สัมมนา", kind: "text", req: true, val: "09:00-16:00" },
                { id: "seminar_place", label: "สถานที่อบรม/สัมมนา", kind: "text", req: true, val: "e-Learning" }
            ]
        },
        {
            key: "exam_result", group: "report", icon: "grading", fileLabel: "PDF",
            title: "สรุปใบรับรอง/ผลสอบ",
            desc: "สรุปใบรับรองและผลสอบที่ออกให้ผู้ทำบัญชี/ผู้สอบบัญชี",
            fields: [
                { id: "course_id", label: "คอร์สเรียน", kind: "course", col: "col-md-6" },
                { id: "license_type", label: "ประเภทใบรับรอง", kind: "select", options: [
                    ["both", "CPD และ CPA"],
                    ["cpd", "CPD ผู้ทำบัญชี"],
                    ["cpa", "CPA ผู้สอบบัญชี"]
                ], val: "both", col: "col-md-6" },
                { id: "from", label: "จากวันที่ (วันที่อนุมัติ)", kind: "date" },
                { id: "to", label: "ถึงวันที่ (วันที่อนุมัติ)", kind: "date" }
            ]
        },
        {
            key: "user_list", group: "report", icon: "group", fileLabel: "Excel",
            title: "รายงานการสมัครสมาชิก",
            desc: "รายชื่อสมาชิกที่สมัครเข้าระบบ พร้อมยอดใช้จ่ายรวม",
            fields: [
                { id: "from", label: "จากวันที่ (สมัครสมาชิก)", kind: "date" },
                { id: "to", label: "ถึงวันที่ (สมัครสมาชิก)", kind: "date" },
                { id: "email", label: "อีเมล", kind: "text" }
            ]
        },
        // {
        //     key: "order_receipt", group: "document", icon: "receipt_long", fileLabel: "อีเมล", mock: true,
        //     title: "ใบเสร็จรับเงิน/ใบกำกับภาษี",
        //     desc: "ส่งใบเสร็จ/ใบกำกับภาษีให้ลูกค้าทางอีเมล",
        //     fields: [
        //         { id: "from", label: "จากวันที่ (สั่งซื้อ)", kind: "date" },
        //         { id: "to", label: "ถึงวันที่ (สั่งซื้อ)", kind: "date" },
        //         { id: "email", label: "อีเมล", kind: "text" },
        //         { id: "file_type", label: "ประเภทไฟล์", kind: "select", options: [["", "---กรุณาเลือก---"], ["excel", "Excel"], ["pdf", "PDF"]] },
        //         { id: "type", label: "ประเภทเอกสาร", kind: "select", options: [["", "---กรุณาเลือก---"], ["original", "ต้นฉบับ"], ["copy", "สำเนา"]] }
        //     ]
        // },
        // {
        //     key: "course_certificate", group: "document", icon: "workspace_premium", fileLabel: "อีเมล", mock: true,
        //     title: "ใบรับรอง",
        //     desc: "ส่งใบรับรองผลการเรียนให้ผู้เรียนทางอีเมล",
        //     fields: [
        //         { id: "from", label: "จากวันที่ (สั่งซื้อ)", kind: "date" },
        //         { id: "to", label: "ถึงวันที่ (สั่งซื้อ)", kind: "date" },
        //         { id: "course_id", label: "คอร์สเรียน", kind: "course" },
        //         { id: "download_type", label: "ประเภท", kind: "select", options: [["", "---กรุณาเลือก---"], ["all", "ดาวน์โหลดทั้งหมด"], ["cpd", "เฉพาะผู้ทำ (CPD)"], ["cpa", "เฉพาะผู้สอบ (CPA)"]] }
        //     ]
        // }
    ];

    var TYPE_MAP = {};
    TYPES.forEach(function (t) { TYPE_MAP[t.key] = t; });

    var COURSES = null;        // cache รายชื่อคอร์ส
    var selectedType = null;   // key ที่เลือกอยู่

    function esc(s) { return (typeof EscapeHTML === "function") ? EscapeHTML(s) : String(s); }

    // ---- ตัวเลือกประเภท (รายการ list แยกกลุ่ม) ----
    function renderList() {
        var groups = [
            { label: "รายงาน (ไฟล์ Excel)", key: "report" },
            // { label: "เอกสารส่งทางอีเมล", key: "document" }
        ];
        var html = '';
        groups.forEach(function (g) {
            var items = TYPES.filter(function (t) { return t.group === g.key; }).map(function (t) {
                var badge = t.mock ? '<span class="badge bg-warning text-dark">เร็ว ๆ นี้</span>' : '';
                return '<button type="button" class="rp-type-item" data-type="' + t.key + '">' +
                    '<span class="material-symbols-outlined rp-item-icon">' + t.icon + '</span>' +
                    '<span class="rp-item-title">' + esc(t.title) + '</span>' + badge +
                    '</button>';
            }).join("");
            html += '<div class="rp-list-label">' + esc(g.label) + '</div>' + items;
        });
        $("#rpTypeList").html(html);
    }

    // ---- ความกว้างของแต่ละช่อง (Bootstrap col) ให้พอดีกับชนิดข้อมูล ----
    // - คอร์ส/สถานที่ = เต็มแถว (ข้อความยาว)
    // - จากวันที่/ถึงวันที่ = ครึ่งแถว -> คู่กันแถวเดียว (เลือกช่วงง่าย)
    // - รหัสหน่วยงาน = สั้น (1/3) คู่กับชื่อหน่วยงาน (2/3)
    function fieldCol(f) {
        if (f.col) { return f.col; }
        if (f.kind === "course") { return "col-12"; }
        if (f.id === "seminar_place") { return "col-12"; }
        if (f.id === "agency_code") { return "col-md-4"; }
        if (f.id === "agency_name") { return "col-md-8"; }
        return "col-md-6"; // date (from/to), email, select ฯลฯ = ครึ่งแถว
    }

    // ---- ฟิลด์ฟิลเตอร์ ----
    function buildField(f) {
        var star = f.req ? ' <span class="text-danger">*</span>' : '';
        var col = fieldCol(f);
        var val = f.val ? f.val : "";
        var inner;
        if (f.kind === "course") {
            inner = '<select class="form-select rp-field" id="rp_' + f.id + '" data-id="' + f.id + '"><option value="">---กรุณาเลือก---</option></select>';
        } else if (f.kind === "select") {
            var opts = (f.options || []).map(function (o) { return '<option value="' + o[0] + '">' + esc(o[1]) + '</option>'; }).join("");
            inner = '<select class="form-select rp-field" id="rp_' + f.id + '" data-id="' + f.id + '">' + opts + '</select>';
        } else if (f.kind === "date") {
            inner = '<input type="text" class="form-control rp-field rp-date" id="rp_' + f.id + '" data-id="' + f.id + '" placeholder="วัน/เดือน/ปี" autocomplete="off">';
        } else {
            inner = '<input type="text" class="form-control rp-field" id="rp_' + f.id + '" data-id="' + f.id + '" value="' + esc(val) + '">';
        }
        return '<div class="' + col + '"><label class="form-label fw-medium">' + esc(f.label) + star + '</label>' + inner + '</div>';
    }

    function fillCourseSelects() {
        if (!COURSES) { return; }
        $(".rp-field[data-id='course_id']").each(function () {
            var $s = $(this);
            if ($s.find("option").length > 1) {
                if ($.fn.select2) { $s.select2({ width: '100%' }); }
                return;
            }
            COURSES.forEach(function (c) { $s.append('<option value="' + c.id + '">' + esc(c.name) + '</option>'); });
            if ($.fn.select2) { $s.select2({ width: '100%' }); }
        });
    }


    function loadCourses(cb) {
        if (COURSES) { cb && cb(); return; }
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "report", request_function: "get_courses" },
            dataType: "json",
            success: function (res) { COURSES = (res.result == 1 && res.data) ? (res.data.courses || []) : []; cb && cb(); },
            error: function (j, e) { COURSES = []; ShowErrorAjax(j, e); }
        });
    }

    // ---- เลือกประเภท -> เรนเดอร์ panel ----
    function selectType(key) {
        var t = TYPE_MAP[key];
        if (!t) { selectedType = null; $("#rpFilterPanel").hide(); $("#rpEmpty").show(); return; }
        selectedType = key;
        $("#rpEmpty").hide();

        $(".rp-type-item").removeClass("active");
        $(".rp-type-item[data-type='" + key + "']").addClass("active");

        $("#rpPanelIcon").text(t.icon);
        $("#rpPanelTitle").text(t.title);
        $("#rpPanelDesc").text(t.desc);

        $("#ReportFields").html(t.fields.map(buildField).join(""));
        if (typeof InitThaiDatepicker === "function") {
            InitThaiDatepicker(".rp-date");
        } else if (typeof flatpickr !== "undefined") {
            flatpickr(".rp-date", { dateFormat: "d/m/Y", allowInput: true });
        }
        if (t.fields.some(function (f) { return f.kind === "course"; })) { loadCourses(fillCourseSelects); }

        if (t.mock) {
            $("#rpMockText").text("ประเภทนี้จะส่งเอกสารให้ทางอีเมล — อยู่ระหว่างเชื่อมต่อระบบส่งอีเมล (ยังไม่เปิดใช้งาน)");
            $("#rpMockNote").removeClass("d-none");
            $("#ReportSubmitWrap").hide();
        } else {
            $("#rpMockNote").addClass("d-none");
            $("#ReportSubmitWrap").show();
        }

        $("#rpFilterPanel").show();
        // เลื่อนให้เห็น panel (เฉพาะจอเล็ก)
        if (window.innerWidth < 992) {
            $("#rpFilterPanel")[0].scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    function downloadDirectly(key) {
        var t = TYPE_MAP[key];
        if (!t) return;

        var body = new URLSearchParams({ request_state: "report", request_function: "export_report", report_type: key });
        if (t.fields) {
            t.fields.forEach(function (f) { body.append(f.id, ""); });
        }

        Swal.fire({ title: "กำลังสร้างรายงาน...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        var filename = "";
        fetch("core.php", {
            method: "POST",
            headers: { "Authorization": "Bearer " + (localStorage.getItem("bo_access_token") || "") },
            body: body
        }).then(function (res) {
            var ct = res.headers.get("Content-Type") || "";
            if (ct.indexOf("application/json") !== -1) {
                return res.json().then(function (j) { throw new Error(j.msg || "ดาวน์โหลดไม่สำเร็จ"); });
            }
            var cd = res.headers.get("Content-Disposition");
            if (cd && cd.indexOf("filename") !== -1) {
                var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                var matches = filenameRegex.exec(cd);
                if (matches != null && matches[1]) { 
                    filename = matches[1].replace(/['"]/g, '');
                    if (filename.indexOf("UTF-8''") === 0) {
                        filename = filename.substring(7);
                    }
                    filename = decodeURIComponent(filename);
                }
            }
            return res.blob();
        }).then(function (blob) {
            Swal.close();
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            var ext = (t.fileLabel && t.fileLabel.toLowerCase() === "pdf") ? ".pdf" : ".xlsx";
            a.href = url; a.download = filename || (key + ext);
            document.body.appendChild(a); a.click(); a.remove();
            URL.revokeObjectURL(url);
        }).catch(function (err) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + esc(err.message) + '</span>', icon: "error", showConfirmButton: true });
        });
    }

    $(document).ready(function () {
        renderList();
        $(document).on("click", ".rp-type-item", function () {
            var key = $(this).data("type");
            selectType(key);
        });
    });

    // ---- ดาวน์โหลด ----
    $("#ReportForm").on("submit", function (e) {
        e.preventDefault();
        var t = TYPE_MAP[selectedType];
        if (!t || t.mock) { return; }

        // ตรวจฟิลด์ที่จำเป็น
        var missing = false;
        t.fields.forEach(function (f) {
            if (f.req && !String($("#rp_" + f.id).val() || "").trim()) { missing = true; }
        });
        if (missing) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบ</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }

        // รวมค่าฟิลด์
        var body = new URLSearchParams({ request_state: "report", request_function: "export_report", report_type: selectedType });
        t.fields.forEach(function (f) { body.append(f.id, String($("#rp_" + f.id).val() || "")); });

        Swal.fire({ title: "กำลังสร้างรายงาน...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        var filename = "";
        fetch("core.php", {
            method: "POST",
            headers: { "Authorization": "Bearer " + (localStorage.getItem("bo_access_token") || "") },
            body: body
        }).then(function (res) {
            var ct = res.headers.get("Content-Type") || "";
            if (ct.indexOf("application/json") !== -1) {
                return res.json().then(function (j) { throw new Error(j.msg || "ดาวน์โหลดไม่สำเร็จ"); });
            }
            var cd = res.headers.get("Content-Disposition");
            if (cd && cd.indexOf("filename") !== -1) {
                var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                var matches = filenameRegex.exec(cd);
                if (matches != null && matches[1]) { 
                    filename = matches[1].replace(/['"]/g, '');
                    if (filename.indexOf("UTF-8''") === 0) {
                        filename = filename.substring(7);
                    }
                    filename = decodeURIComponent(filename);
                }
            }
            return res.blob();
        }).then(function (blob) {
            Swal.close();
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            var ext = (t.fileLabel && t.fileLabel.toLowerCase() === "pdf") ? ".pdf" : ".xlsx";
            a.href = url; a.download = filename || (selectedType + ext);
            document.body.appendChild(a); a.click(); a.remove();
            URL.revokeObjectURL(url);
        }).catch(function (err) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + esc(err.message) + '</span>', icon: "error", showConfirmButton: true });
        });
    });
</script>
