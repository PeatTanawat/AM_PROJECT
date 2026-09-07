<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key         = isset($_GET['key']) ? trim($_GET['key']) : '';
    $user_id     = \App\Utility\Cipher::decrypt($key);
    $breadcrumbs = [
    ['label' => 'ผู้ใช้/ลูกค้าทั้งหมด', 'url' => 'user'],
    ['label' => 'ผู้ใช้/ลูกค้า #' . ($user_id !== '' ? $user_id : '')],
    ];
?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">

            <!-- การ์ดที่ 1: รายละเอียด + ฟอร์มแก้ไข -->
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="mb-0">รายละเอียดผู้ใช้/ลูกค้า</h4>
                            <span id="userVerifyStatus"></span>
                            <span id="userAccountStatus"></span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="user"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><span
                                    class="material-symbols-outlined" style="font-size:18px;"
                                    aria-hidden="true">arrow_back</span> กลับ</a>
                            <button type="button" class="btn btn-info text-white"
                                onclick="LoginAsUser('<?php echo $user_id; ?>');">ล็อกอินเข้าเว็บไซต์</button>
                            <button type="button" id="btnVerify" class="btn btn-warning text-white"
                                onclick="OpenVerifyModal();">ดู/แก้ไขเอกสารยืนยันตัวตน</button>
                            <button type="button" class="btn btn-danger text-white"
                                onclick="DeleteUser('<?php echo $user_id; ?>');">ลบบัญชีผู้ใช้</button>
                        </div>
                    </div>

                    <form id="FormEditUser" autocomplete="off">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                <select class="form-select" name="user_prefix">
                                    <option value="">- เลือก -</option>
                                    <option value="1">นาย</option>
                                    <option value="2">นาง</option>
                                    <option value="3">นางสาว</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="user_firstname" value="">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="user_lastname" value="">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="user_email" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="user_phone" value=""
                                    oninput="if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    maxlength="10">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="user_citizen_id" value=""
                                    oninput="if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    maxlength="13">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">เลขที่ผู้ทำบัญชี</label>
                                <input type="number" class="form-control" name="user_cpd_no" value=""
                                    oninput="if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    maxlength="15">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">เลขที่ผู้สอบบัญชี</label>
                                <input type="number" class="form-control" name="user_cpa_no" value=""
                                    oninput="if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    maxlength="15">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">สถานะการใช้งาน</label>
                                <select class="form-select" name="user_status">
                                    <option value="1">ใช้งาน</option>
                                    <option value="0">ไม่ใช้งาน</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">รหัสผ่าน (กรอกหากต้องการเปลี่ยน)</label>
                                <input type="password" class="form-control" name="user_password" value=""
                                    autocomplete="new-password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ยืนยันรหัสผ่าน (กรอกหากต้องการเปลี่ยน)</label>
                                <input type="password" class="form-control" name="user_password_confirm" value=""
                                    autocomplete="new-password">
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">ยืนยันการแก้ไขข้อมูล</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <!-- การ์ดที่ 2: แท็บข้อมูลที่เกี่ยวข้อง -->
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">

                    <ul class="nav nav-tabs app-tabs mb-3" id="userTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-order"
                                type="button" role="tab">คำสั่งซื้อ</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-enroll" type="button"
                                role="tab">สิทธิ์เข้าคอร์สเรียน</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exam" type="button"
                                role="tab">ประวัติการสอบ/ใบรับรอง</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-verify" type="button"
                                role="tab">ประวัติการยืนยันตัวตน</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- แท็บ: คำสั่งซื้อ -->
                        <div class="tab-pane fade show active" id="tab-order" role="tabpanel">
                            <div class="default-table-area">
                                <div class="table-responsive">
                                    <table class="table align-middle w-100 user-tab-table" id="TableOrder">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 80px;">ลำดับ</th>
                                                <th>หมายเลขคำสั่งซื้อ</th>
                                                <th>คอร์สเรียน</th>
                                                <th class="text-end">ยอดรวม</th>
                                                <th class="text-center">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- แท็บ: สิทธิ์เข้าคอร์สเรียน -->
                        <div class="tab-pane fade" id="tab-enroll" role="tabpanel">
                            <div class="default-table-area">
                                <div class="table-responsive">
                                    <table class="table align-middle w-100 user-tab-table" id="TableEnroll">
                                        <thead>
                                            <tr>
                                                <th class="text-left text-nowrap">ลำดับ</th>
                                                <th class="text-left text-nowrap">SKU</th>
                                                <th class="text-left text-nowrap">คอร์สเรียน</th>
                                                <th class="text-left text-nowrap">การสอบ</th>
                                                <th class="text-center text-nowrap">สถานะการสอบ</th>
                                                <th class="text-center text-nowrap">สถานะ</th>
                                                <th class="text-center text-nowrap">เริ่มใช้งานเมื่อ</th>
                                                <th class="text-center text-nowrap">วันหมดอายุ</th>
                                                <th class="text-center text-nowrap"></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- แท็บ: ประวัติการสอบ/ใบรับรอง -->
                        <div class="tab-pane fade" id="tab-exam" role="tabpanel">
                            <div class="default-table-area">
                                <div class="table-responsive">
                                    <table class="table align-middle w-100 user-tab-table" id="TableExam">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 60px;">#</th>
                                                <th>คอร์สเรียน</th>
                                                <th class="text-center">คะแนนที่ได้</th>
                                                <th class="text-center">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- แท็บ: ประวัติการยืนยันตัวตน -->
                        <div class="tab-pane fade" id="tab-verify" role="tabpanel">
                            <div class="default-table-area">
                                <div class="table-responsive">
                                    <table class="table align-middle w-100 user-tab-table" id="TableVerify">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 60px;">#</th>
                                                <th>ผู้ดำเนินการ</th>
                                                <th class="text-center">สถานะ</th>
                                                <th>คำอธิบาย</th>
                                                <th>วันและเวลาที่ดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <?php include "footer.php"; ?>

    </div>
</div>

<!-- Modal: ตรวจสอบเอกสารยืนยันตัวตน -->
<div class="modal fade" id="VerifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content app-card">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันตัวตนผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="FormVerify" autocomplete="off">
                    <input type="hidden" name="user_id" id="verify_user_id" value="">

                    <div class="mb-3">
                        <label class="form-label d-block">สถานะการยืนยันตัวตนปัจจุบัน</label>
                        <span id="verifyModalStatus"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ประเภทเอกสาร</label>
                        <input type="text" class="form-control bg-light" value="ยืนยันด้วยบัตรประชาชน" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="verify_citizen_id">หมายเลข</label>
                        <input type="text" class="form-control bg-light" id="verify_citizen_id" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="verify_expiry">วันหมดอายุของเอกสาร</label>
                        <input type="text" class="form-control bg-white" name="id_card_expiry_date" id="verify_expiry" placeholder="วว/ดด/ปปปป">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="verify_file_id_card">รูปเอกสาร</label>
                        <input type="file" class="form-control" name="file_id_card" id="verify_file_id_card" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปเอกสารปัจจุบัน</label>
                        <div>
                            <img id="verify_id_image" src="" alt="รูปเอกสาร" class="img-fluid w-100"
                                style="object-fit: contain; border-radius: var(--radius-md); border: 1px solid var(--border);">
                            <div id="verify_id_image_empty" class="text-muted small d-none">ไม่มีรูปเอกสาร</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="verify_file_current_photo">รูปหน้ายืนยัน</label>
                        <input type="file" class="form-control" name="file_current_photo" id="verify_file_current_photo" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปหน้ายืนยันปัจจุบัน</label>
                        <div>
                            <img id="verify_photo" src="" alt="รูปหน้ายืนยัน" class="img-fluid w-100"
                                style="object-fit: contain; border-radius: var(--radius-md); border: 1px solid var(--border);">
                            <div id="verify_photo_empty" class="text-muted small d-none">ไม่มีรูปหน้ายืนยัน</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label d-block">ผลการตรวจสอบเอกสาร <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="approver_citizen" id="result_approve"
                                value="2">
                            <label class="form-check-label" for="result_approve">อนุมัติ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="approver_citizen" id="result_reject"
                                value="1">
                            <label class="form-check-label" for="result_reject">ไม่อนุมัติ</label>
                        </div>
                    </div>

                    <!-- หมายเหตุ: บังคับกรอกเมื่อเลือก "ไม่อนุมัติ" (เหตุผลการปฏิเสธ) -->
                    <div class="mb-2 d-none" id="remark_wrap">
                        <label class="form-label" for="verify_remark">หมายเหตุ (เหตุผลที่ไม่อนุมัติ) <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="remark" id="verify_remark" rows="2"
                            placeholder="ระบุเหตุผลการปฏิเสธ"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100"
                    onclick="SubmitVerify();">ยืนยันข้อมูลการยืนยันตัวตน</button>
            </div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>

<script>
    var USER_ID = "<?php echo $user_id; ?>";
    var AUTO_VERIFY = "<?php echo isset($_GET['verify']) ? '1' : '' ?>";
    var verifyModal = null;

    $(document).ready(function () {
        InitThaiDatepicker("#verify_expiry");
        verifyModal = new bootstrap.Modal(document.getElementById('VerifyModal'));

        // เปลี่ยน select สถานะการใช้งาน -> อัปเดต badge ที่หัวข้อให้ตรงกันทันที
        $(document).on('change', '[name="user_status"]', function () {
            $("#userAccountStatus").html(String($(this).val()) === '1'
                ? '<span class="badge bg-success">บัญชีใช้งาน</span>'
                : '<span class="badge bg-secondary">บัญชีถูกระงับ</span>');
        });

        // เลือก "ไม่อนุมัติ" -> โชว์ช่องหมายเหตุ (บังคับกรอก) ; "อนุมัติ" -> ซ่อน + ล้างค่า
        $('input[name="approver_citizen"]').on('change', function () {
            if ($(this).val() === '1') {
                $('#remark_wrap').removeClass('d-none');
            } else {
                $('#remark_wrap').addClass('d-none');
                $('#verify_remark').val('');
            }
        });

        // มาจากหน้า "คำขอยืนยันตัวตนผู้ใช้งาน" -> เปิด modal ตรวจเอกสารอัตโนมัติ
        if (USER_ID && AUTO_VERIFY === '1') {
            OpenVerifyModal();
        }

        if (USER_ID) {
            LoadUser();
        }
    });

    // โหลดข้อมูลผู้ใช้มาเติมในฟอร์ม + แท็บ
    function LoadUser() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#FormEditUser"); },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_user",
                request_function: "get_user",
                user_id: USER_ID
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    FillForm(response.data.user);
                    FillOrderTab(response.data.orders || []);
                    FillEnrollTab(response.data.enrollments || []);
                    FillExamTab(response.data.exams || []);
                    FillVerifyTab(response.data.verify_history || []);
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                }
            },
            complete: function () { HideLoadingOverlay("#FormEditUser"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function FillForm(u) {
        if (!u) return;
        var f = $("#FormEditUser");
        // ข้อมูลเก่า: user_prefix ว่าง แต่คำนำหน้าฝังอยู่ในชื่อ -> แยกออกมาใส่ dropdown
        // (แยก นางสาว ก่อน นาง เพราะ "นางสาว" ขึ้นต้นด้วย "นาง")
        var prefix = u.user_prefix ? String(u.user_prefix) : "";
        var firstname = u.user_firstname || "";
        if (!prefix) {
            var pmap = [["นางสาว", "3"], ["นาย", "1"], ["นาง", "2"]];
            for (var i = 0; i < pmap.length; i++) {
                if (firstname.indexOf(pmap[i][0]) === 0) {
                    prefix = pmap[i][1];
                    firstname = firstname.substring(pmap[i][0].length).trim();
                    break;
                }
            }
        }
        f.find('[name="user_prefix"]').val(prefix);
        f.find('[name="user_firstname"]').val(firstname);
        f.find('[name="user_lastname"]').val(u.user_lastname || "");
        f.find('[name="user_email"]').val(u.user_email || "");
        f.find('[name="user_phone"]').val(u.user_phone || "");
        f.find('[name="user_citizen_id"]').val(u.user_citizen_id || "");
        f.find('[name="user_cpd_no"]').val(u.user_cpd_no || "");
        f.find('[name="user_cpa_no"]').val(u.user_cpa_no || "");
        f.find('[name="user_password"]').val("");
        f.find('[name="user_password_confirm"]').val("");
        // สถานะการใช้งาน (1 = ใช้งาน, 0 = ไม่ใช้งาน)
        f.find('[name="user_status"]').val(String(u.user_status) === '0' ? '0' : '1');
        $("#userVerifyStatus").html(VerifyBadge(u.identity_verified));
        // ยืนยันแล้ว (2) -> ปุ่มเปลี่ยนเป็น "อัพเดทข้อมูล", ยังไม่ยืนยัน -> ตรวจสอบเอกสาร
        $("#btnVerify").text(String(u.identity_verified) === '2' ? 'ดู/ตรวจสอบเอกสารยืนยันตัวตนผู้ใช้' : 'ตรวจสอบเอกสารยืนยันตัวตนผู้ใช้');
        // สถานะบัญชี (1 = ใช้งาน, อื่น ๆ = ถูกระงับ)
        $("#userAccountStatus").html(String(u.user_status) === '1'
            ? '<span class="badge bg-success">บัญชีใช้งาน</span>'
            : '<span class="badge bg-secondary">บัญชีถูกระงับ</span>');
    }

    // ป้ายสถานะการยืนยันตัวตน (0=ยังไม่ยืนยัน 1=รอตรวจสอบ 2=ยืนยันแล้ว)
    function VerifyBadge(iv) {
        iv = String(iv || '0');
        if (iv === '2') { return '<span class="badge bg-success">ยืนยันตัวตนแล้ว</span>'; }
        if (iv === '1') { return '<span class="badge bg-warning text-white">รอตรวจสอบ</span>'; }
        return '<span class="badge bg-secondary">ยังไม่ยืนยันตัวตน</span>';
    }

    // เติมแท็บ "ประวัติการยืนยันตัวตน" จาก tbl_identity_verification_log
    function FillVerifyTab(rows) {
        var html = '';
        if (!rows || rows.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td></tr>';
        } else {
            rows.forEach(function (r, i) {
                var act = String(r.action_type || '');
                var badge = act === '1' ? '<span class="badge bg-success">อนุมัติยืนยันตัวตน</span>'
                    : act === '2' ? '<span class="badge bg-danger">ยกเลิกการยืนยัน</span>'
                        : '<span class="badge bg-secondary">-</span>';
                var remark = r.remark ? EscapeHTML(r.remark) : '<span class="text-muted">-</span>';
                html += '<tr>'
                    + '<td class="text-center">' + (i + 1) + '</td>'
                    + '<td>' + EscapeHTML(r.admin_name || '-') + '</td>'
                    + '<td class="text-center">' + badge + '</td>'
                    + '<td>' + remark + '</td>'
                    + '<td class="text-nowrap">' + EscapeHTML(FormatDateTime(r.created_at)) + '</td>'
                    + '</tr>';
            });
        }
        $("#TableVerify tbody").html(html);
    }

    function FormatDateTime(ts) {
        if (!ts) { return '-'; }
        var d = new Date(String(ts).replace(' ', 'T'));
        if (isNaN(d.getTime())) { return ts; }
        var p = function (n) { return ('0' + n).slice(-2); };
        return p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
    }

    // เติมแท็บ "คำสั่งซื้อ"
    function FillOrderTab(rows) {
        var html = '';
        if (!rows || rows.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td></tr>';
        } else {
            rows.forEach(function (r, i) {
                var statusHtml = '';
                if (String(r.payment_status) === '1') {
                    statusHtml = '<span class="badge bg-success">ชำระเงินแล้ว</span>';
                } else if (String(r.payment_status) === '2') {
                    statusHtml = '<span class="badge bg-danger">ยกเลิก</span>';
                } else {
                    statusHtml = '<span class="badge bg-warning text-white">รอชำระเงิน</span>';
                }

                var courses = r.courses && r.courses.length > 0 ? r.courses.map(EscapeHTML).join('<br>') : '<span class="text-muted">-</span>';
                var formattedPrice = Number(r.total_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                html += '<tr>'
                    + '<td class="text-center">' + (i + 1) + '</td>'
                    + '<td>' + EscapeHTML(r.order_code || '-') + '</td>'
                    + '<td>' + courses + '</td>'
                    + '<td class="text-end">' + formattedPrice + ' ฿</td>'
                    + '<td class="text-center">' + statusHtml + '</td>'
                    + '</tr>';
            });
        }
        $("#TableOrder tbody").html(html);
    }

    // เติมแท็บ "สิทธิ์เข้าคอร์สเรียน" (ตารางธรรมดา render ทุกแถวฝั่ง client)
    function FillEnrollTab(rows) {
        var html = '';
        if (!rows || rows.length === 0) {
            html = '<tr><td colspan="9" class="text-center text-muted">ไม่มีข้อมูล</td></tr>';
        } else {
            rows.forEach(function (r, i) {
                // 1. จัดการข้อมูลเริ่มใช้งานและหมดอายุ
                var startDate = FormatDateTime(r.enroll_date).split(' ')[0]; // เอาแค่วันที่
                var expireDate = FormatDateTime(r.enroll_expiry_date).split(' ')[0];
                if (!r.enroll_date) startDate = '-';
                if (!r.enroll_expiry_date) expireDate = '-';

                // 2. จัดการสถานะ (เทียบวันหมดอายุกับปัจจุบัน)
                var statusHtml = '-';
                if (String(r.enroll_access) === '0') {
                    statusHtml = '<span class="badge bg-secondary">ยกเลิกสิทธิ์การใช้งาน</span>';
                } else if (r.enroll_expiry_date) {
                    var exp = new Date(r.enroll_expiry_date.replace(' ', 'T'));
                    if (exp > new Date()) {
                        statusHtml = '<span class="badge bg-success">ใช้งานได้</span>';
                    } else {
                        statusHtml = '<span class="badge bg-danger">หมดอายุ</span>';
                    }
                }

                // 3. จัดการข้อมูลการสอบ
                var maxTime = r.course_number_time || 0;
                var examCountHtml = '<span class="text-muted">-</span>';
                var examStatusHtml = '<span class="text-muted">-</span>';
                var attempts = r.attempts || [];
                var isPassed = false;

                if (maxTime > 0) {
                    var maxRound = 0;
                    attempts.forEach(function(a) {
                        var round = parseInt(a.attempt_new || 0);
                        if (round > maxRound) maxRound = round;
                    });
                    
                    var countInRound = 0;
                    attempts.forEach(function(a) {
                        var round = parseInt(a.attempt_new || 0);
                        if (round === maxRound) {
                            countInRound++;
                            if (String(a.attempt_pass) === '1') {
                                isPassed = true;
                            }
                        }
                    });

                    // การสอบ
                    var roundText = maxRound > 0 ? '<br><small class="text-muted">(รอบปลดล็อค ' + maxRound + ')</small>' : '';
                    examCountHtml = 'สอบแล้ว ' + countInRound + ' ครั้ง / สูงสุด ' + maxTime + ' ครั้ง' + roundText;

                    // สถานะการสอบ
                    if (attempts.length === 0) {
                        examStatusHtml = '<span class="text-muted">ไม่มีประวัติการสอบ</span>';
                    } else if (isPassed) {
                        examStatusHtml = '<span class="badge bg-success">สอบผ่านแล้ว</span>';
                    } else {
                        if (countInRound >= maxTime) {
                            examStatusHtml = '<span class="badge bg-danger">สอบไม่ผ่าน</span>';
                        } else {
                            examStatusHtml = '<span class="badge bg-warning text-white">กำลังดำเนินการ</span>';
                        }
                    }
                } else {
                    examCountHtml = '<span class="badge bg-secondary">ไม่มีสอบ</span>';
                    examStatusHtml = '<span class="text-muted">-</span>';
                }

                // 4. ปุ่มจัดการ
                var actionHtml = '<div class="d-flex gap-1 justify-content-center flex-nowrap">';
                if (String(r.enroll_access) === '0') {
                    actionHtml += '<button type="button" class="btn btn-sm btn-warning text-white text-nowrap" onclick="GrantRight(' + r.enroll_id + ')" title="ให้สิทธิ์การใช้งาน">ให้สิทธิ์การใช้งาน</button>';
                } else {
                    if (attempts.length === 0) {
                        actionHtml += '<button type="button" class="btn btn-sm btn-danger text-white text-nowrap" onclick="CancelRight(' + r.enroll_id + ')" title="ยกเลิกสิทธิ์การใช้งาน">ยกเลิกสิทธิ์การใช้งาน</button>';
                    } else if (isPassed === true) {
                        actionHtml += '<button type="button" class="btn btn-sm btn-success text-white text-nowrap" onclick="IssueCertificate(' + r.enroll_id + ')" title="ออกใบรับรอง">ออกใบรับรอง</button>';
                    } else {
                        if (r.is_locked == 1) {
                            actionHtml += '<button type="button" class="btn btn-sm btn-primary text-nowrap" onclick="EditExamRight(' + r.enroll_id + ')" title="แก้ไขสิทธิ์การสอบ">แก้ไขสิทธิ์การสอบ</button>' +
                                          '<button type="button" class="btn btn-sm btn-warning text-white text-nowrap" onclick="ResetLearning(' + r.enroll_id + ')" title="รีเซ็ตการเรียน">รีเซ็ตการเรียน</button>';
                        }
                        actionHtml += '<button type="button" class="btn btn-sm btn-danger text-white text-nowrap" onclick="CancelRight(' + r.enroll_id + ')" title="ยกเลิกสิทธิ์การใช้งาน">ยกเลิกสิทธิ์การใช้งาน</button>';
                    }
                }
                actionHtml += '</div>';

                html += '<tr>'
                    + '<td class="text-left">' + (i + 1) + '</td>'
                    + '<td class="text-left">' + EscapeHTML(r.sku || '-') + '</td>'
                    + '<td class="text-left">' + EscapeHTML(r.course_name || 'ไม่มีข้อมูล') + '</td>'
                    + '<td class="text-left">' + examCountHtml + '</td>'
                    + '<td class="text-center">' + examStatusHtml + '</td>'
                    + '<td class="text-center">' + statusHtml + '</td>'
                    + '<td class="text-center">' + EscapeHTML(startDate) + '</td>'
                    + '<td class="text-center">' + EscapeHTML(expireDate) + '</td>'
                    + '<td>' + actionHtml + '</td>'
                    + '</tr>';
            });
        }
        $("#TableEnroll tbody").html(html);
    }

    // ฟังก์ชันจัดการสิทธิ์การเรียน
    function EditExamRight(enroll_id) {
        Swal.fire({
            title: "ยืนยันการแก้ไขสิทธิ์การสอบ?",
            text: "ระบบจะทำการปลดล็อกสิทธิ์การสอบให้กับผู้ใช้นี้",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยืนยัน",
            cancelButtonText: "ยกเลิก"
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_enrollment",
                        request_function: "unlock_enrollment",
                        enroll_id: enroll_id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didClose: function () {
                                    LoadUser();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "แจ้งเตือน",
                                html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                                icon: "error",
                                confirmButtonText: "ตกลง"
                            });
                        }
                    },
                    error: function (jqXHR, exception) {
                        ShowErrorAjax(jqXHR, exception);
                    }
                });
            }
        });
    }
    function ResetLearning(enroll_id) {
        Swal.fire({
            title: "ยืนยันการรีเซ็ตการเรียน?",
            text: "ระบบจะทำการปลดล็อกสิทธิ์การสอบและล้างข้อมูลความคืบหน้าการเรียนทั้งหมดของคอร์สนี้ ประวัติการสอบเดิมจะยังคงอยู่",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยืนยัน",
            cancelButtonText: "ยกเลิก"
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_enrollment",
                        request_function: "reset_learning",
                        enroll_id: enroll_id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didClose: function () {
                                    LoadUser();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "แจ้งเตือน",
                                html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                                icon: "error",
                                confirmButtonText: "ตกลง"
                            });
                        }
                    },
                    error: function (jqXHR, exception) {
                        ShowErrorAjax(jqXHR, exception);
                    }
                });
            }
        });
    }
    function CancelRight(enroll_id) {
        Swal.fire({
            title: "ยืนยันการยกเลิกสิทธิ์การใช้งาน?",
            text: "ระบบจะทำการยกเลิกสิทธิ์การใช้งานของคอร์สนี้",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยืนยัน",
            cancelButtonText: "ยกเลิก"
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_enrollment",
                        request_function: "cancel_enrollment",
                        enroll_id: enroll_id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didClose: function () {
                                    LoadUser();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "แจ้งเตือน",
                                html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                                icon: "error",
                                confirmButtonText: "ตกลง"
                            });
                        }
                    },
                    error: function (jqXHR, exception) {
                        ShowErrorAjax(jqXHR, exception);
                    }
                });
            }
        });
    }
    function GrantRight(enroll_id) {
        Swal.fire({
            title: "ยืนยันการให้สิทธิ์การใช้งาน?",
            text: "ระบบจะทำการให้สิทธิ์การใช้งานของคอร์สนี้กลับคืนมา",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยืนยัน",
            cancelButtonText: "ยกเลิก"
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_enrollment",
                        request_function: "grant_enrollment",
                        enroll_id: enroll_id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didClose: function () {
                                    LoadUser();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "แจ้งเตือน",
                                html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                                icon: "error",
                                confirmButtonText: "ตกลง"
                            });
                        }
                    },
                    error: function (jqXHR, exception) {
                        ShowErrorAjax(jqXHR, exception);
                    }
                });
            }
        });
    }
    function IssueCertificate(enroll_id) {
        // 1) พยายามอนุมัติ (หากยังไม่อนุมัติ)
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "list_certificate",
                    request_function: "approve_certificate", 
                    enroll_id: enroll_id },
            dataType: "json"
        }).done(function (r) {
            if (r.result != 1 && r.msg !== 'รายการนี้อนุมัติออกใบรับรองไปแล้ว') {
                Swal.fire({ title: "แจ้งเตือน", html: '<span class="text-danger">' + r.msg + '</span>', icon: "error" });
                return;
            }
            // 2) โหลดข้อมูลใบรับรองเพื่อแสดงตัวเลือกประเภทใบรับรองเหมือนในหน้า course_certificate.php
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "list_certificate", request_function: "get_certificate", enroll_id: enroll_id },
                dataType: "json",
                success: function (r2) {
                    if (r2.result != 1) {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="text-danger">' + (r2.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error" });
                        return;
                    }
                    var opts = {};
                    if (String(r2.data.cpd_no || '').trim() !== '') { opts.cpd = "ผู้ทำบัญชี"; }
                    if (String(r2.data.cpa_no || '').trim() !== '') { opts.cpa = "ผู้สอบบัญชี"; }
                    var keys = Object.keys(opts);

                    if (keys.length === 0) {
                        Swal.fire({ title: "ไม่สามารถออกใบรับรองได้", html: '<span class="text-secondary">ผู้ใช้นี้ยังไม่มีเลขที่ผู้ทำบัญชีหรือเลขที่ผู้สอบบัญชี</span>', icon: "warning" });
                        LoadUser();
                        return;
                    }

                    // เปิดหน้าใบรับรองทันที ไม่ต้องเลือกประเภทแล้ว
                    window.open("pdf_preview.php?type=certificate&key=" + r2.data.enroll_key, "_blank");
                    LoadUser();
                },
                error: function (j, e) { ShowErrorAjax(j, e); LoadUser(); }
            });
        }).fail(function (j, e) { ShowErrorAjax(j, e); });
    }



    // เติมแท็บ "ประวัติการสอบ/ใบรับรอง" (ตารางธรรมดา render ทุกแถวฝั่ง client)
    function FillExamTab(rows) {
        var html = '';
        if (!rows || rows.length === 0) {
            html = '<tr><td colspan="4" class="text-center text-muted">ไม่มีข้อมูล</td></tr>';
        } else {
            rows.forEach(function (r, i) {
                var status = (String(r.pass) === '1')
                    ? '<span class="badge bg-success">ผ่าน</span>'
                    : '<span class="badge bg-danger">ไม่ผ่าน</span>';
                html += '<tr>'
                    + '<td class="text-center">' + (i + 1) + '</td>'
                    + '<td>' + EscapeHTML(r.course_name || '-') + '</td>'
                    + '<td class="text-center">' + EscapeHTML(r.score != null ? String(r.score) : '-') + '</td>'
                    + '<td class="text-center">' + status + '</td>'
                    + '</tr>';
            });
        }
        $("#TableExam tbody").html(html);
    }

    // บันทึกการแก้ไขข้อมูล
    $(document).on('submit', '#FormEditUser', function (e) {
        e.preventDefault();

        // ===== ตรวจช่องบังคับให้ครบ =====
        if (!ValidateRequired([
            { sel: '[name="user_prefix"]', label: 'คำนำหน้า', type: 'select' },
            { sel: '[name="user_firstname"]', label: 'ชื่อ' },
            { sel: '[name="user_lastname"]', label: 'นามสกุล' },
            { sel: '[name="user_email"]', label: 'อีเมล' },
            { sel: '[name="user_phone"]', label: 'เบอร์โทรศัพท์' },
            { sel: '[name="user_citizen_id"]', label: 'เลขบัตรประชาชน' }
        ])) { return; }

        var pwd = $('[name="user_password"]').val();
        var pwd2 = $('[name="user_password_confirm"]').val();
        if (pwd !== pwd2) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน</span>', icon: "warning", confirmButtonText: "ตกลง" });
            return;
        }

        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#FormEditUser"); },
            type: "POST",
            url: "core.php",
            data: $(this).serialize() + "&request_state=list_user&request_function=update_user",
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    Swal.fire({
                        title: "สำเร็จ",
                        html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                        icon: "success", showConfirmButton: false,
                        timer: 1500, timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    });
                }
            },
            complete: function () { HideLoadingOverlay("#FormEditUser"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    });

    function DeleteUser(user_id) {
        Swal.fire({
            title: "ลบบัญชีผู้ใช้?",
            html: '<span class="text-secondary">ระบบจะทำการลบบัญชีผู้ใช้นี้</span>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ลบบัญชี",
            cancelButtonText: "ยกเลิก",
            confirmButtonColor: "#dc3545"
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                type: "POST",
                url: "core.php",
                data: { request_state: "list_user", request_function: "delete_user", user_id: user_id },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true, didClose: function () { window.location.href = "user"; } });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", confirmButtonText: "ตกลง" });
                    }
                },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
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
                    document.cookie = "access_token=" + token + "; path=/; max-age=25200";
                    try { localStorage.setItem("access_token", token); } catch (e) { }
                    window.open("../../cpdth/index.php", "_blank");
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
    }

    // ===== ตรวจสอบเอกสารยืนยันตัวตน (modal) =====

    // รูป KYC (DB เก็บเป็น "uploads/identity/...") เว็บฝั่งลูกค้า (โฟลเดอร์ cpdth) เป็นคนอัปโหลด
    // โปรเจกต์แอดมินกับ cpdth เป็นโฟลเดอร์พี่น้องกันทั้ง 2 environment:
    //   - local : intern/am/        + intern/cpdth/
    //   - server: public_html/am/backoffice/ + public_html/am/cpdth/
    // หน้าอยู่ใน main/ -> ถอยขึ้น 2 ชั้น (main -> โปรเจกต์ -> โฟลเดอร์แม่) แล้วเข้า cpdth/
    // แปลง path รูปที่เก็บใน DB ให้เป็น URL ที่เปิดได้ (ถ้าเป็น URL เต็มอยู่แล้วใช้ตามนั้น)
    function ImageSrc(path) {
        if (!path) return "";
        if (/^https?:\/\//i.test(path) || /^data:/i.test(path)) return path;
        var clean = String(path).replace(/^(\.\.\/)+/, '').replace(/^(am\/)?(backoffice\/)?/i, '');
        return '../' + clean;
    }

    // แปลงวันหมดอายุ Y-m-d -> dd/mm/yyyy (ค.ศ.)
    function FormatExpiry(d) {
        if (!d || d === "0000-00-00") return "-";
        var p = String(d).split("-");
        if (p.length !== 3) return d;
        return p[2] + "/" + p[1] + "/" + p[0];
    }

    function OpenVerifyModal() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#FormEditUser"); },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "verify_request",
                request_function: "get_verify",
                user_id: USER_ID
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    FillVerifyModal(response.data.verify);
                    verifyModal.show();
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    });
                }
            },
            complete: function () { HideLoadingOverlay("#FormEditUser"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // พรีวิวภาพเมื่อเลือกไฟล์ใหม่ใน modal
    $(document).on('change', '#verify_file_id_card', function () {
        if (this.files && this.files[0]) {
            var src = URL.createObjectURL(this.files[0]);
            $("#verify_id_image").attr("src", src).removeClass("d-none");
            $("#verify_id_image_empty").addClass("d-none");
        }
    });
    $(document).on('change', '#verify_file_current_photo', function () {
        if (this.files && this.files[0]) {
            var src = URL.createObjectURL(this.files[0]);
            $("#verify_photo").attr("src", src).removeClass("d-none");
            $("#verify_photo_empty").addClass("d-none");
        }
    });

    function FillVerifyModal(v) {
        if (!v) return;
        $("#verifyModalStatus").html(VerifyBadge(v.identity_verified));
        $("#verify_user_id").val(v.user_id || "");
        $("#verify_citizen_id").val(v.user_citizen_id || "-");
        
        var fp = document.querySelector("#verify_expiry")._flatpickr;
        var expiryDate = v.id_card_expiry_date && v.id_card_expiry_date !== "0000-00-00" ? v.id_card_expiry_date : null;
        if (fp) {
            fp.setDate(expiryDate);
        } else {
            $("#verify_expiry").val(expiryDate || "");
        }

        // รีเซ็ตไฟล์ที่เคยเลือกค้างไว้
        $("#verify_file_id_card").val("");
        $("#verify_file_current_photo").val("");

        // prefill ผลตรวจ/หมายเหตุเดิม (ถ้าเคยตัดสินแล้ว) เพื่อให้เห็นผลปัจจุบันตอนเปิดซ้ำ
        var prevResult = String(v.approver_citizen || '');
        $('input[name="approver_citizen"]').prop("checked", false);
        if (prevResult === '1' || prevResult === '2') {
            $('input[name="approver_citizen"][value="' + prevResult + '"]').prop("checked", true);
        }
        $("#verify_remark").val(v.remark || "");
        $("#remark_wrap").toggleClass("d-none", prevResult !== '1');   // ปฏิเสธ -> โชว์ช่องหมายเหตุ

        SetVerifyImage("#verify_id_image", "#verify_id_image_empty", v.id_card_image);
        SetVerifyImage("#verify_photo", "#verify_photo_empty", v.current_photo);
    }

    function SetVerifyImage(imgSel, emptySel, path) {
        var src = ImageSrc(path);
        if (src) {
            $(imgSel).attr("src", src).removeClass("d-none");
            $(emptySel).addClass("d-none");
        } else {
            $(imgSel).attr("src", "").addClass("d-none");
            $(emptySel).removeClass("d-none");
        }
    }

    function SubmitVerify() {
        var result = $('input[name="approver_citizen"]:checked').val();
        if (!result) {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณาเลือกผลการตรวจสอบเอกสาร</span>',
                icon: "warning",
                confirmButtonText: "ตกลง"
            });
            return;
        }

        // ถ้าเลือกอนุมัติ (2) ต้องเช็คว่ามีรูปเอกสารและรูปหน้ายืนยันหรือไม่ (เช็คจาก src หรือไฟล์ที่เลือกใหม่)
        if (result === '2') {
            var hasIdImage = $('#verify_id_image').attr('src') !== "" || $('#verify_file_id_card')[0].files.length > 0;
            var hasPhoto = $('#verify_photo').attr('src') !== "" || $('#verify_file_current_photo')[0].files.length > 0;
            
            if (!hasIdImage || !hasPhoto) {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">ไม่สามารถอนุมัติได้ เนื่องจากผู้ใช้ยังไม่ได้อัปโหลดรูปเอกสารหรือรูปหน้ายืนยัน</span>',
                    icon: "warning",
                    confirmButtonText: "ตกลง"
                });
                return;
            }
        }

        // ปฏิเสธ ต้องระบุหมายเหตุ (เหตุผล) เสมอ
        if (result === '1' && $('#verify_remark').val().trim() === '') {
            Swal.fire({ 
                title: "แจ้งเตือน", 
                html: '<span class="fw-bold text-danger">กรุณาระบุหมายเหตุการไม่อนุมัติ</span>', 
                icon: "warning", 
                confirmButtonText: "ตกลง"
             });
            $('#verify_remark').focus();
            return;
        }

        var formElement = document.getElementById("FormVerify");
        var formData = new FormData(formElement);
        formData.append("request_state", "verify_request");
        formData.append("request_function", "update_verify");
        formData.append("send_email", "1"); // แจ้ง backend ให้ส่งเมลแจ้งลูกค้าเสมอ

        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#FormVerify"); },
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    verifyModal.hide();
                    Swal.fire({
                        title: "สำเร็จ",
                        html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        didClose: function () { LoadUser(); }
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    });
                }
            },
            complete: function () { HideLoadingOverlay("#FormVerify"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
</script>
</body>
</html>
