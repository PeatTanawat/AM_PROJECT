<?php
// รายละเอียดและแก้ไขข้อมูลใบรับรองผลการสอบ (Snapshot Page)
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
$key = isset($_GET['key']) ? trim($_GET['key']) : '';
$enroll_id = \App\Utility\Cipher::decrypt($key);
if (empty($enroll_id) && isset($_GET['enroll_id'])) {
    $enroll_id = (int) $_GET['enroll_id'];
}
$breadcrumbs = [
    ['label' => 'ใบรับรองผลการสอบ', 'url' => 'course_certificate'],
    ['label' => 'รายละเอียดใบรับรองผลการสอบ'],
];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <base href="../../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>รายละเอียดใบรับรองผลการสอบ - CPDTH</title>
    <?php include dirname(__DIR__, 2) . "/header.php"; ?>
    <style>
        .detail-table {
            border: 1px solid #cbd5e1 !important;
            overflow: hidden;
        }

        .detail-table td {
            padding: 9px 14px;
            vertical-align: middle;
            border: 1px solid #e2e8f0 !important;
        }

        .detail-table tr:nth-child(odd) td {
            background-color: #f8fafc;
        }

        .detail-table tr:nth-child(even) td {
            background-color: #ffffff;
        }

        .detail-table .lbl {
            width: 38%;
            color: #1e293b;
            font-weight: 700;
            text-align: left;
            border-right: 1px solid #e2e8f0 !important;
        }

        .detail-table .val {
            color: #334155;
            font-weight: 500;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px !important;
            padding-bottom: 12px !important;
            text-align: start;
            margin: 0 !important;
        }

        .form-card h4 {
            border-bottom: 0 !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="main-content d-flex flex-column">

            <?php include dirname(__DIR__, 2) . "/navbar.php"; ?>

            <div class="px-2">
                <div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
                    <div
                        class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="mb-0 fw-bold">รายละเอียดใบรับรองผลการสอบ : <span id="v_title_certno">-</span>
                            </h2>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="course_certificate"
                                class="btn btn-outline-secondary d-inline-flex align-items-center me-2">
                                <span class="material-symbols-outlined me-1" style="font-size:18px;"
                                    aria-hidden="true">arrow_back</span> กลับไปหน้ารายการ
                            </a>
                            <button type="button" class="btn btn-info text-white fw-medium px-3"
                                onclick="DownloadCert()">
                                ดาวน์โหลดใบรับรอง
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-4">

                        <!-- 1. ข้อมูลผู้สอบและผลการสอบ -->
                        <div class="mb-4">
                            <div class="section-title">ข้อมูลผู้สอบและผลการสอบ</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <table class="table table-bordered detail-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="lbl">ผู้สอบ</td>
                                                <td class="val" id="v_fullname">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">เลขที่บัตรประชาชน</td>
                                                <td class="val" id="v_citizen">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">เลขที่ผู้ทำบัญชี</td>
                                                <td class="val" id="v_cpd">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">เลขที่ผู้สอบบัญชี</td>
                                                <td class="val" id="v_cpa">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">หมายเลขโทรศัพท์</td>
                                                <td class="val" id="v_phone">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">อีเมล</td>
                                                <td class="val" id="v_email">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered detail-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="lbl">คอร์สเรียน</td>
                                                <td class="val" id="v_course">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">หมายเลขใบรับรอง</td>
                                                <td class="val" id="v_certno">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">จำนวนข้อ / จำนวนข้อที่ทำ</td>
                                                <td class="val" id="v_num">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">คะแนนขั้นต่ำ</td>
                                                <td class="val" id="v_min">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">คะแนนที่ได้รับ</td>
                                                <td class="val" id="v_score">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">คิดเป็นเปอร์เซ็นต์</td>
                                                <td class="val" id="v_percent">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 2. ข้อมูลใบรับรอง -->
                        <div class="mb-4">
                            <div class="section-title">ข้อมูลใบรับรอง</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <table class="table table-bordered detail-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="lbl">หมายเลขใบรับรอง</td>
                                                <td class="val" id="v_certno2">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">สถานะการอนุมัติ</td>
                                                <td class="val" id="v_status">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">วันที่อนุมัติผลสอบ</td>
                                                <td class="val" id="v_traindate">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">ไตรมาสของหลักสูตร</td>
                                                <td class="val" id="v_quarter">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered detail-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="lbl">รหัสหลักสูตร CPD</td>
                                                <td class="val" id="v_code_cpd">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">รหัสหลักสูตร CPA</td>
                                                <td class="val" id="v_code_cpa">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">วันที่อนุมัติหลักสูตร</td>
                                                <td class="val" id="v_approvaldate">-</td>
                                            </tr>
                                            <tr>
                                                <td class="lbl">วันที่ส่งใบรับรองให้ผู้เรียน</td>
                                                <td class="val" id="v_sentdate">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 3. แก้ไขข้อมูลใบรับรอง -->
                        <div class="mb-2">
                            <div class="section-title">แก้ไขข้อมูลใบรับรอง</div>
                            <form id="formEditCertificate" autocomplete="off">
                                <input type="hidden" name="enroll_id" id="ed_enroll_id"
                                    value="<?php echo (int) $enroll_id; ?>">
                                <input type="hidden" name="cert_no" id="ed_cert_no">
                                <input type="hidden" name="user_firstname" id="ed_user_firstname">
                                <input type="hidden" name="user_lastname" id="ed_user_lastname">
                                <input type="hidden" name="user_citizen_id" id="ed_user_citizen_id">
                                <input type="hidden" name="user_license_no" id="ed_user_license_no">
                                <input type="hidden" name="user_licensecpa_no" id="ed_user_licensecpa_no">
                                <input type="hidden" name="course_name" id="ed_course_name">
                                <input type="hidden" name="course_instructor" id="ed_course_instructor">
                                <input type="hidden" name="issued_at" id="ed_issued_at">
                                <input type="hidden" name="hours_account" id="ed_hours_account">
                                <input type="hidden" name="hours_ethics" id="ed_hours_ethics">
                                <input type="hidden" name="hours_other" id="ed_hours_other">
                                <input type="hidden" name="exam_score" id="ed_exam_score">
                                <input type="hidden" name="exam_total" id="ed_exam_total">
                                <input type="hidden" name="score_percent" id="ed_score_percent">

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="ed_course_code_cpd"
                                            class="form-label fw-medium text-dark">รหัสหลักสูตร CPD</label>
                                        <select class="form-select" id="ed_course_code_cpd" name="course_code_cpd"
                                            onchange="OnCodeCpdChange(this.value)">
                                            <option value="">โปรดระบุ</option>
                                        </select>
                                        <div id="box_cpd_manual" class="d-none mt-2">
                                            <input type="text" class="form-control" id="ed_course_code_cpd_manual"
                                                name="course_code_cpd_manual" placeholder="ระบุรหัสหลักสูตร CPD เอง">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="ed_course_code_cpa"
                                            class="form-label fw-medium text-dark">รหัสหลักสูตร CPA</label>
                                        <select class="form-select" id="ed_course_code_cpa" name="course_code_cpa"
                                            onchange="OnCodeCpaChange(this.value)">
                                            <option value="">โปรดระบุ</option>
                                        </select>
                                        <div id="box_cpa_manual" class="d-none mt-2">
                                            <input type="text" class="form-control" id="ed_course_code_cpa_manual"
                                                name="course_code_cpa_manual" placeholder="ระบุรหัสหลักสูตร CPA เอง">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="ed_course_approval_date"
                                            class="form-label fw-medium text-dark">วันที่อนุมัติหลักสูตร</label>
                                        <input type="text" class="form-control" id="ed_course_approval_date"
                                            name="course_approval_date" placeholder="เลือกวันที่อนุมัติหลักสูตร"
                                            readonly>
                                    </div>
                                </div>

                                <div class="alert alert-warning text-center py-2 border-0 mt-3 mb-3 fw-medium text-dark"
                                    style="background-color: #fff3cd;">
                                    กรุณาตรวจสอบข้อมูลก่อนยืนยันการทำรายการ
                                </div>

                                <button type="button" class="btn btn-primary w-100 py-2 fs-6 fw-semibold BtnSaveCert"
                                    style="background-color: #6366f1; border-color: #6366f1;"
                                    onclick="SaveCertificateData()">
                                    ยืนยันการแก้ไขข้อมูลใบรับรอง
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <?php include dirname(__DIR__, 2) . "/footer.php"; ?>
        </div>
    </div>

    <?php include dirname(__DIR__, 2) . "/script.php"; ?>

    <script>
        var enrollId = <?php echo (int) $enroll_id; ?>;
        var enrollKey = <?php echo json_encode($key); ?>;
        var approvalDatesMap = {};
        var fpApprovalDate = null;

        $(document).ready(function () {
            if (typeof flatpickr !== 'undefined') {
                fpApprovalDate = flatpickr("#ed_course_approval_date", {
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y",
                    allowInput: true,
                    locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.th ? flatpickr.l10ns.th : "default"
                });
            }

            if (enrollId > 0) {
                LoadCertificateData();
            } else {
                Swal.fire({ title: "แจ้งเตือน", text: "ไม่พบข้อมูลรายการที่ต้องการดู/แก้ไข", icon: "error" })
                    .then(function () { window.location.href = "course_certificate"; });
            }
        });

        function setApprovalDate(val) {
            if (fpApprovalDate) {
                fpApprovalDate.setDate(val || '', true);
            } else {
                $("#ed_course_approval_date").val(val || '');
            }
        }

        function LoadCertificateData() {
            $.ajax({
                beforeSend: function () { ShowLoadingOverlay(".form-card"); },
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_certificate",
                    request_function: "get_certificate",
                    enroll_id: enrollId
                },
                dataType: "json",
                success: function (r) {
                    HideLoadingOverlay(".form-card");
                    if (r.result == 1 && r.data) {
                        var d = r.data;
                        renderCertificatePage(d);
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถดึงข้อมูลได้') + '</span>', icon: "error" });
                    }
                },
                complete: function () { HideLoadingOverlay(".form-card"); },
                error: function (j, e) { HideLoadingOverlay(".form-card"); ShowErrorAjax(j, e); }
            });
        }

        function renderCertificatePage(d) {
            var dash = function (v) { return (v === null || v === undefined || trimStr(v) === '') ? '-' : v; };
            function trimStr(s) { return String(s || '').trim(); }

            $("#v_title_certno").text(dash(d.cert_no));
            $("#v_fullname").text(dash(d.fullname));
            $("#v_citizen").text(dash(d.citizen_id));
            $("#v_cpd").text(dash(d.cpd_no));
            $("#v_cpa").text(dash(d.cpa_no));
            $("#v_phone").text(dash(d.phone));
            $("#v_email").text(dash(d.email));

            $("#v_course").text(dash(d.course_name));
            $("#v_certno").text(dash(d.cert_no));
            $("#v_num").text((d.num_exam || 0) + " ข้อ / " + (d.num_exam || 0) + " ข้อ");
            $("#v_min").text((d.min_score || 0) + " คะแนน");
            $("#v_score").text((d.score === null ? "-" : d.score) + " คะแนน");
            $("#v_percent").text((d.percent === null ? "-" : d.percent) + " %");

            $("#v_certno2").text(dash(d.cert_no));
            var statusBadge = (d.cert_approved == 1)
                ? '<span class="badge bg-success">อนุมัติ</span>'
                : '<span class="badge bg-secondary">รออนุมัติ</span>';
            $("#v_status").html(statusBadge);

            $("#v_traindate").text(dash(d.train_date));
            $("#v_quarter").text(dash(d.quarter_text));

            $("#v_code_cpd").text(dash(d.course_code_cpd || d.course_code_cpd_curr || d.course_code));
            $("#v_code_cpa").text(dash(d.course_code_cpa || d.course_code_cpa_curr || d.course_code));
            $("#v_approvaldate").text(dash(d.approval_date));
            $("#v_sentdate").text(dash(d.train_date));

            // เติม Hidden fields สำหรับบันทึก snapshot
            $("#ed_cert_no").val(d.cert_no || '');
            $("#ed_user_firstname").val(d.user_firstname || '');
            $("#ed_user_lastname").val(d.user_lastname || '');
            $("#ed_user_citizen_id").val(d.citizen_id || '');
            $("#ed_user_license_no").val(d.cpd_no || '');
            $("#ed_user_licensecpa_no").val(d.cpa_no || '');
            $("#ed_course_name").val(d.course_name || '');
            $("#ed_course_instructor").val(d.instructor || '');
            $("#ed_issued_at").val(d.issued_at_raw || '');
            $("#ed_hours_account").val(d.hours_account !== undefined ? d.hours_account : 0);
            $("#ed_hours_ethics").val(d.hours_ethics !== undefined ? d.hours_ethics : 0);
            $("#ed_hours_other").val(d.hours_other !== undefined ? d.hours_other : 0);
            $("#ed_exam_score").val(d.score !== null ? d.score : '');
            $("#ed_exam_total").val(d.num_exam !== null ? d.num_exam : '');
            $("#ed_score_percent").val(d.percent !== null ? d.percent : '');

            setApprovalDate(d.approval_date_raw || '');

            approvalDatesMap = d.approval_dates_map || {};

            // เติม Options ใน Select Dropdown CPD
            var currentCpdVal = d.course_code_cpd || d.course_code_cpd_curr || '';
            var cpdInOptions = false;
            var cpdHtml = '<option value="">โปรดระบุ</option>';
            if (d.cpd_options && d.cpd_options.length > 0) {
                d.cpd_options.forEach(function (opt) {
                    var isSel = (currentCpdVal === opt.code);
                    if (isSel) cpdInOptions = true;
                    cpdHtml += '<option value="' + opt.code + '" data-q="' + opt.q + '" ' + (isSel ? 'selected' : '') + '>' + opt.label + '</option>';
                });
            }
            var isCpdManual = currentCpdVal !== '' && !cpdInOptions;
            cpdHtml += '<option value="manual" ' + (isCpdManual ? 'selected' : '') + '>ระบุเอง (กรอกด้วยตนเอง)</option>';
            $("#ed_course_code_cpd").html(cpdHtml);
            if (isCpdManual) {
                $("#box_cpd_manual").removeClass('d-none');
                $("#ed_course_code_cpd_manual").val(currentCpdVal);
            } else {
                $("#box_cpd_manual").addClass('d-none');
                $("#ed_course_code_cpd_manual").val('');
            }

            // เติม Options ใน Select Dropdown CPA
            var currentCpaVal = d.course_code_cpa || d.course_code_cpa_curr || '';
            var cpaInOptions = false;
            var cpaHtml = '<option value="">โปรดระบุ</option>';
            if (d.cpa_options && d.cpa_options.length > 0) {
                d.cpa_options.forEach(function (opt) {
                    var isSel = (currentCpaVal === opt.code);
                    if (isSel) cpaInOptions = true;
                    cpaHtml += '<option value="' + opt.code + '" data-q="' + opt.q + '" ' + (isSel ? 'selected' : '') + '>' + opt.label + '</option>';
                });
            }
            var isCpaManual = currentCpaVal !== '' && !cpaInOptions;
            cpaHtml += '<option value="manual" ' + (isCpaManual ? 'selected' : '') + '>ระบุเอง (กรอกด้วยตนเอง)</option>';
            $("#ed_course_code_cpa").html(cpaHtml);
            if (isCpaManual) {
                $("#box_cpa_manual").removeClass('d-none');
                $("#ed_course_code_cpa_manual").val(currentCpaVal);
            } else {
                $("#box_cpa_manual").addClass('d-none');
                $("#ed_course_code_cpa_manual").val('');
            }
        }

        function OnCodeCpdChange(val) {
            if (val === 'manual') {
                $("#box_cpd_manual").removeClass('d-none');
            } else {
                $("#box_cpd_manual").addClass('d-none');
                var selectedOpt = $("#ed_course_code_cpd option:selected");
                var q = selectedOpt.data('q');
                if (q && approvalDatesMap[q]) {
                    setApprovalDate(approvalDatesMap[q]);
                }
            }
        }

        function OnCodeCpaChange(val) {
            if (val === 'manual') {
                $("#box_cpa_manual").removeClass('d-none');
            } else {
                $("#box_cpa_manual").addClass('d-none');
                var selectedOpt = $("#ed_course_code_cpa option:selected");
                var q = selectedOpt.data('q');
                if (q && approvalDatesMap[q]) {
                    setApprovalDate(approvalDatesMap[q]);
                }
            }
        }

        function DownloadCert() {
            if (!enrollKey) { return; }
            window.open("pdf_preview.php?type=certificate&key=" + enrollKey + "&cert_type=cpd", "_blank");
        }

        function SaveCertificateData() {
            Swal.fire({
                title: "ยืนยันการบันทึก",
                // html: '<span class="text-secondary">ยืนยันแก้ไขข้อมูลใบรับรองรายการนี้? ข้อมูลจะถูกปรับปรุงใน Snapshot ใบรับรองโดยตรง</span>',
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "ยืนยัน",
                cancelButtonText: "ยกเลิก"
            }).then(function (res) {
                if (!res.isConfirmed) { return; }

                ShowLoadingButton('.BtnSaveCert');
                var formData = $('#formEditCertificate').serializeArray();
                formData.push({ name: 'request_state', value: 'list_certificate' });
                formData.push({ name: 'request_function', value: 'update_certificate' });

                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: formData,
                    dataType: "json",
                    success: function (r) {
                        HideLoadingButton('.BtnSaveCert');
                        if (r.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + r.msg + '</span>',
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function () {
                                LoadCertificateData();
                            });
                        } else {
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + r.msg + '</span>', icon: "error" });
                        }
                    },
                    error: function (j, e) {
                        HideLoadingButton('.BtnSaveCert');
                        ShowErrorAjax(j, e);
                    }
                });
            });
        }
    </script>

</body>

</html>