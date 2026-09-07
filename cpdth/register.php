<?php
    $pageTitle = 'ลงทะเบียนสมาชิกใหม่';
    include 'components/header.php';
?>


<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #ffffff;
        margin: 0;
        padding: 100px 20px 80px;
    }

    .register-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        min-height: calc(100vh - 350px);
    }

    .register-card {
        background-color: #ffffff;
        width: 100%;
        max-width: 550px;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        padding: 40px 45px;
    }

    .register-title {
        font-size: 1.6rem;
        font-weight: 600;
        color: #1f2937;
        text-align: center;
        margin-bottom: 30px;
    }

    .section-header {
        font-size: 1rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-header i {
        font-size: 1.2rem;
    }

    .section-divider {
        margin-top: 30px;
    }

    .input-group-custom {
        margin-bottom: 15px;
    }

    .form-control-custom,
    .form-select-custom {
        width: 100%;
        background-color: #f3f4f6;
        border: 2px solid transparent;
        border-radius: 50px;
        padding: 12px 20px;
        font-size: 0.95rem;
        font-family: 'Prompt', sans-serif;
        color: #374151;
        outline: none;
        transition: all 0.3s ease;
        box-sizing: border-box;
        appearance: none;
    }

    .form-select-custom {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 12px 12px;
        cursor: pointer;
    }

    .form-control-custom::placeholder {
        color: #9ca3af;
    }

    .form-control-custom:focus,
    .form-select-custom:focus {
        background-color: #ffffff;
        border-color: #3b5998;
        box-shadow: 0 0 0 4px rgba(59, 89, 152, 0.1);
    }

    hr {
        border-color: #e2e8f0;
        margin: 25px 0;
    }

    .checkbox-group {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .checkbox-group input[type="checkbox"] {
        margin-top: 4px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .checkbox-label {
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.6;
    }

    .checkbox-label a {
        color: #3b5998;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .checkbox-label a:hover {
        color: #2d4373;
        text-decoration: underline;
    }

    .btn-register {
        width: 100%;
        background-color: #44619d;
        color: #ffffff;
        border: none;
        border-radius: 50px;
        padding: 14px 20px;
        font-size: 1.05rem;
        font-weight: 600;
        font-family: 'Prompt', sans-serif;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.1s;
    }

    .btn-register:hover {
        background-color: #2d4373;
    }

    .btn-register:active {
        transform: scale(0.98);
    }

    .divider-text {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 25px 0;
        color: #9ca3af;
        font-size: 0.9rem;
    }

    .divider-text::before,
    .divider-text::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #e5e7eb;
    }

    .divider-text:not(:empty)::before {
        margin-right: 15px;
    }

    .divider-text:not(:empty)::after {
        margin-left: 15px;
    }

    .btn-line {
        width: 100%;
        background-color: #00C300; 
        color: #ffffff;
        border: none;
        border-radius: 50px;
        padding: 14px 20px;
        font-size: 1.05rem;
        font-weight: 500;
        font-family: 'Prompt', sans-serif;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.1s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
    }

    .btn-line:hover {
        background-color: #00a000;
        color: #ffffff;
    }

    .btn-line:active {
        transform: scale(0.98);
    }

    .btn-line i {
        font-size: 1.3rem;
    }

    .auth-link-text {
        text-align: center;
        margin-top: 25px;
        font-size: 0.95rem;
        color: #64748b;
    }

    .auth-link-text a {
        color: #3b5998;
        font-weight: 600;
        text-decoration: none;
    }

    .auth-link-text a:hover {
        text-decoration: underline;
    }

    @media (max-width: 576px) {
        .register-card {
            padding: 30px 20px;
        }
        .register-title {
            font-size: 1.4rem;
        }
        .form-control-custom,
        .form-select-custom {
            padding: 10px 18px;
            font-size: 0.9rem;
        }
        .checkbox-label {
            font-size: 0.85rem;
        }
    }

    .form-control-custom.is-invalid,
    .form-select-custom.is-invalid {
        border-color: #dc3545 !important;
        background-color: #fff8f8;
    }

    .position-relative {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        z-index: 10;
        transition: color 0.2s ease;
    }

    .toggle-password:hover {
        color: #212529;
    }
</style>

<main class="register-wrapper">
    <div class="register-card">
        
        <div class="register-title">ลงทะเบียนสมาชิกใหม่</div>

        <form action="#" method="POST" autocomplete="off">
            
            <div class="section-header">
                <i class="bi bi-person-vcard-fill"></i> ข้อมูลสมาชิก
            </div>

            <div class="input-group-custom">
                <select name="prefix" class="form-select-custom" autocomplete="off" required>
                    <option value="" disabled selected>คำนำหน้า</option>
                    <option value="1">นาย</option>
                    <option value="2">นาง</option>
                    <option value="3">นางสาว</option>
                </select>
            </div>

            <div class="input-group-custom">
                <input type="text" name="user_firstname" class="form-control-custom" placeholder="ชื่อ" autocomplete="off" required>
            </div>

            <div class="input-group-custom">
                <input type="text" name="user_lastname" class="form-control-custom" placeholder="นามสกุล" autocomplete="off" required>
            </div>

            <div class="input-group-custom">
                <input type="text" name="user_citizen_id" class="form-control-custom" placeholder="เลขบัตรประจำตัวประชาชน" autocomplete="off" required maxlength="13">
            </div>

            <div class="input-group-custom">
                <input type="number" name="user_cpd_no" class="form-control-custom" oninput="if(this.value.length > 13) this.value = this.value.slice(0, 13);" placeholder="เลขที่ผู้ทำบัญชี" autocomplete="off">
            </div>

            <div class="input-group-custom">
                <input type="number" name="user_cpa_no" class="form-control-custom" oninput="if(this.value.length > 13) this.value = this.value.slice(0, 13);" placeholder="เลขที่ผู้สอบบัญชี" autocomplete="off">
            </div>

            <div class="input-group-custom">
                <input type="tel" name="user_phone" class="form-control-custom" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '');" placeholder="เบอร์โทรศัพท์" autocomplete="off" required>
            </div>


            <div class="section-header section-divider">
                <i class="bi bi-lock-fill"></i> ข้อมูลบัญชีผู้ใช้
            </div>

            <div class="input-group-custom">
                <input type="email" name="user_email" class="form-control-custom" placeholder="อีเมล" autocomplete="off" required>
            </div>

            <div class="input-group-custom position-relative">
                <input type="password" id="user_password" name="user_password" class="form-control-custom pe-5" placeholder="รหัสผ่าน" autocomplete="new-password" required>
                <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword"></i>
            </div>

            <div class="input-group-custom position-relative">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control-custom pe-5" placeholder="ยืนยันรหัสผ่าน" autocomplete="new-password" required>
                <i class="bi bi-eye-slash-fill toggle-password" id="toggleConfirmPassword"></i>
            </div>

            <hr>

            <div class="checkbox-group">
                <input type="checkbox" id="accept_terms" name="accept_terms" required>
                <label for="accept_terms" class="checkbox-label">
                    กรุณายอมรับ <a href="privacy-policy">เงื่อนไขการใช้บริการ และนโยบายความเป็นส่วนตัว</a>
                </label>
            </div>

            <!-- ปุ่มยืนยันการสมัคร -->
            <button type="button" class="btn-register" onclick="Register()">ยืนยันการสมัครสมาชิก</button>

            <!-- เส้นคั่น -->
            <!-- <div class="divider-text">หรือ</div> -->

            <!-- ปุ่มสมัครสมาชิกด้วย LINE -->
            <!-- <a href="#" class="btn-line">
                <i class="bi bi-line"></i> สมัครสมาชิกด้วย LINE
            </a> -->

            <!-- ลิงก์ไปหน้าเข้าสู่ระบบ -->
            <div class="auth-link-text">
                มีบัญชีผู้ใช้อยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a>
            </div>

        </form>

    </div>
</main>

<?php include 'components/footer.php'; ?>



<script>
$(document).ready(function() {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#user_password');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });

    const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
    const confirmPassword = document.querySelector('#confirm_password');

    toggleConfirmPassword.addEventListener('click', function (e) {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
});

function Register() {
    // ล้างกรอบแดง (is-invalid) และข้อความแจ้งเตือนเก่าทั้งหมดก่อนตรวจสอบใหม่
    $(".form-control-custom, .form-select-custom, #accept_terms").removeClass("is-invalid");
    $(".error-text").remove();

    const prefix = $("select[name='prefix']").val();
    const user_firstname = $("input[name='user_firstname']").val().trim();
    const user_lastname = $("input[name='user_lastname']").val().trim();
    const user_citizen_id = $("input[name='user_citizen_id']").val().trim();
    const user_cpd_no = $("input[name='user_cpd_no']").val().trim();
    const user_cpa_no = $("input[name='user_cpa_no']").val().trim();
    const user_phone = $("input[name='user_phone']").val().trim();
    const user_email = $("input[name='user_email']").val().trim();
    const user_password = $("input[name='user_password']").val();
    const confirm_password = $("input[name='confirm_password']").val();
    const accept_terms = $("#accept_terms").is(":checked");

    let hasError = false;

    function showError(selector, message) {
        if ($(selector).hasClass("is-invalid")) return;
        $(selector).addClass("is-invalid");
        
        const $el = $(selector);
        if ($el.attr('type') === 'checkbox') {
            $el.closest('.checkbox-group').append('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem; width: 100%;">' + message + '</div>');
        } else if ($el.closest('.input-group-custom').length > 0) {
            $el.closest('.input-group-custom').append('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem; padding-left: 15px;">' + message + '</div>');
        } else {
            $el.after('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
        }
        hasError = true;
    }

    // 1. ตรวจสอบฟิลด์ที่จำเป็นทั้งหมด
    if (!prefix) showError("select[name='prefix']", "กรุณาเลือกคำนำหน้า");
    if (!user_firstname) showError("input[name='user_firstname']", "กรุณากรอกชื่อ");
    if (!user_lastname) showError("input[name='user_lastname']", "กรุณากรอกนามสกุล");
    if (!user_citizen_id) showError("input[name='user_citizen_id']", "กรุณากรอกเลขบัตรประจำตัวประชาชน");
    if (!user_phone) showError("input[name='user_phone']", "กรุณากรอกเบอร์โทรศัพท์");
    if (!user_email) showError("input[name='user_email']", "กรุณากรอกอีเมล");
    if (!user_password) showError("input[name='user_password']", "กรุณากรอกรหัสผ่าน");
    if (!confirm_password) showError("input[name='confirm_password']", "กรุณายืนยันรหัสผ่าน");

    // ตรวจสอบ CPD หรือ CPA ต้องมีอย่างน้อย 1 อย่าง
    if (!user_cpd_no && !user_cpa_no) {
        showError("input[name='user_cpd_no']", "กรุณากรอกเลขที่ผู้ทำบัญชี หรือ เลขที่ผู้สอบบัญชี อย่างใดอย่างหนึ่ง");
        showError("input[name='user_cpa_no']", "กรุณากรอกเลขที่ผู้ทำบัญชี หรือ เลขที่ผู้สอบบัญชี อย่างใดอย่างหนึ่ง");
    }

    // 2. ตรวจสอบรูปแบบชื่อและนามสกุล (บังคับภาษาไทยเท่านั้น)
    const nameRegex = /^[ก-๙\s]+$/;
    if (user_firstname && !nameRegex.test(user_firstname)) {
        showError("input[name='user_firstname']", "ชื่อต้องเป็นภาษาไทยเท่านั้น");
    }
    if (user_lastname && !nameRegex.test(user_lastname)) {
        showError("input[name='user_lastname']", "นามสกุลต้องเป็นภาษาไทยเท่านั้น");
    }

    // 3. ตรวจสอบความยาวเลขบัตรประจำตัวประชาชน 13 หลัก และต้องเป็นตัวเลขเท่านั้น
    const citizenRegex = /^\d{13}$/;
    if (user_citizen_id && !citizenRegex.test(user_citizen_id)) {
        showError("input[name='user_citizen_id']", "กรุณากรอกเลขบัตรประจำตัวประชาชนให้ครบ 13 หลัก");
    }

    // 4. ตรวจสอบความยาวเบอร์โทรศัพท์ (9-10 หลัก)
    const phoneRegex = /^\d{9,10}$/;
    if (user_phone && !phoneRegex.test(user_phone)) {
        showError("input[name='user_phone']", "เบอร์โทรศัพท์ต้องมีความยาว 9-10 หลัก");
    }

    // 5. ตรวจสอบรูปแบบอีเมล
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (user_email && !emailRegex.test(user_email)) {
        showError("input[name='user_email']", "รูปแบบอีเมลไม่ถูกต้อง");
    }

    // 6. ตรวจสอบความยาวรหัสผ่าน (ขั้นต่ำ 6 ตัวอักษร)
    if (user_password && user_password.length < 6) {
        showError("input[name='user_password']", "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
    }

    // 7. ตรวจสอบรหัสผ่านและยืนยันรหัสผ่านว่าตรงกันไหม
    if (user_password && confirm_password && user_password !== confirm_password) {
        showError("input[name='confirm_password']", "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน");
    }

    // 8. ตรวจสอบการยอมรับข้อตกลงการใช้บริการ
    if (!accept_terms) {
        showError("#accept_terms", "กรุณายอมรับเงื่อนไขการใช้บริการ และนโยบายความเป็นส่วนตัว");
    }

    if (hasError) {
        return false;
    }

    $.ajax({
        beforeSend: function() {
            Swal.fire({
                title: 'กำลังประมวลผล...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        type: "POST",
        url: "core.php",
        data: {
            request_state: "register",
            request_function: "register",
            prefix: prefix,
            user_firstname: user_firstname,
            user_lastname: user_lastname,
            user_citizen_id: user_citizen_id,
            user_cpd_no: user_cpd_no,
            user_cpa_no: user_cpa_no,
            user_phone: user_phone,
            user_email: user_email,
            user_password: user_password,
        },
        dataType: "json",
        success: function (response) {
            if (response.result == 1) {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                    icon: "success",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didClose: () => {
                        const email = response.data && response.data.email ? response.data.email : "";
                        window.location.replace("verify-email.php?email=" + encodeURIComponent(email));
                    }
                });
            } else {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                    icon: "warning",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000,
                    timerProgressBar: true,
                });
            }
        },
        error: function(jqXHR, exception) {
            let msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            Swal.fire({
                title: "แจ้งเตือน",
                html: "พบปัญหาการบันทึก กรุณาติดต่อผู้ดูแลระบบ<br>" + msg,
                icon: "error",
                showConfirmButton: true,
            });
        }
    });
}

$(document).ready(function() {
    $("input[name='user_firstname'], input[name='user_lastname']").on("input", function() {
        this.value = this.value.replace(/[^ก-๙\s]/g, "");
    });
});
</script>
