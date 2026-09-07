<?php
    require_once __DIR__ . '/vendor/autoload.php';
    use App\Utility\Auth;

    if (Auth::getUser()) {
    header("Location: index");
    exit();
    }

    $pageTitle = 'เข้าสู่ระบบ';
    include 'components/header.php';
?>


<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #ffffff;
        margin: 0;
        /* padding: 10; */
    }

    .topbar {
        background-color: #4065AC !important;
        padding: 18px 0 !important;
    }

    .login-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 350px);
        padding: 150px 20px 80px;
    }

    .login-card {
        background-color: #ffffff;
        width: 100%;
        max-width: 480px;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        padding: 50px 40px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 35px;
    }

    .login-logo img {
        height: 80px;
        object-fit: contain;
    }

    .login-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 25px;
        text-align: left;
    }

    .input-group-custom {
        position: relative;
        margin-bottom: 20px;
    }

    .input-group-custom i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 1.2rem;
    }

    .form-control-custom {
        width: 100%;
        background-color: #f3f4f6;
        border: 2px solid transparent;
        border-radius: 50px;
        padding: 14px 20px 14px 55px;
        font-size: 0.95rem;
        font-family: 'Prompt', sans-serif;
        color: #374151;
        outline: none;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-control-custom::placeholder {
        color: #6b7280;
    }

    .form-control-custom:focus {
        background-color: #ffffff;
        border-color: #3b5998;
        box-shadow: 0 0 0 4px rgba(59, 89, 152, 0.1);
    }

    .forgot-password {
        text-align: right;
        margin-bottom: 30px;
    }

    .forgot-password a {
        color: #3b5998;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.2s;
    }

    .forgot-password a:hover {
        color: #2d4373;
        text-decoration: underline;
    }

    .btn-login {
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

    .btn-login:hover {
        background-color: #2d4373;
    }

    .btn-login:active {
        transform: scale(0.98);
    }

    /* --- สไตล์สำหรับปุ่ม LINE และเส้นคั่น --- */
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
        background-color: #00C300; /* สีเขียวของ LINE */
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

    .input-group-custom {
    position: relative;
    display: flex;
    align-items: center;
    }

    /* ไอคอนดวงตาด้านขวา */
    .input-group-custom i.toggle-password {
        position: absolute;
        right: 15px;
        left: auto;
        cursor: pointer;
        color: #6c757d; /* สีไอคอนจางๆ */
        z-index: 10;
        transition: color 0.2s ease;
    }

    .input-group-custom i.toggle-password:hover {
        color: #212529; /* สีเข้มขึ้นเมื่อ hover */
    }

    /* ป้องกันไม่ให้ข้อความรหัสผ่านที่พิมพ์บังไอคอนดวงตา */
    .form-control-custom {
        padding-right: 45px !important;
    }

    @media (max-width: 576px) {
        .login-card {
            padding: 40px 25px;
        }
        .login-logo img {
            height: 65px;
        }
        .login-title {
            font-size: 1.25rem;
        }
    }
</style>

<main class="login-wrapper">
    <div class="login-card">

        <!-- โลโก้ -->
        <div class="login-logo">
            <img src="assets/images/logo/am-group-logo.png" alt="AM GROUP">
        </div>

        <div class="login-title">เข้าสู่ระบบ</div>

        <form action="#" method="POST">

            <!-- ช่องกรอกอีเมล -->
            <div class="input-group-custom">
                <i class="bi bi-person-fill"></i>
                <input type="email" id="user_email" name="user_email" class="form-control-custom" placeholder="อีเมล" required>
            </div>

            <!-- ช่องกรอกรหัสผ่าน -->
           <div class="input-group-custom position-relative">
                <i class="bi bi-lock-fill icon-left"></i>
                <input type="password" id="user_password" name="user_password" class="form-control-custom pe-5" placeholder="รหัสผ่าน" required>
                <!-- ปุ่มไอคอนดวงตาด้านขวา -->
                <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword"></i>
            </div>

            <!-- ลืมรหัสผ่าน -->
            <div class="forgot-password">
                <a href="forgot-password.php">ลืมรหัสผ่าน?</a>
            </div>

            <!-- ปุ่ม Submit -->
            <button type="button" class="btn-login" onclick="Login()">เข้าสู่ระบบ</button>

            <!-- เส้นคั่น -->
            <div class="divider-text">หรือ</div>

            <!-- ปุ่มเข้าสู่ระบบด้วย LINE -->
            <a href="line" class="btn-line">
                <i class="bi bi-line"></i> เข้าสู่ระบบด้วย LINE
            </a>

            <!-- ลิงก์ไปหน้าสมัครสมาชิก -->
            <div class="auth-link-text">
                ยังไม่มีบัญชีผู้ใช้? <a href="register.php">สมัครสมาชิก</a>
            </div>

        </form>

    </div>
</main>

<?php include 'components/footer.php'; ?>

<!-- นำเข้า jQuery และ SweetAlert2 CDN ก่อนแท็กปิด body -->

<script>
document.getElementById('user_password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        Login();
    }
});

document.getElementById('user_email').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        Login();
    }
});

const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#user_password');

togglePassword.addEventListener('click', function (e) {
    // toggle the type attribute
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    // toggle the eye / eye slash icon
    this.classList.toggle('bi-eye-fill');
    this.classList.toggle('bi-eye-slash-fill');
});

function Login() {
    const user_email = $("#user_email").val();
    const user_password = $("#user_password").val();

    if (user_email == "" || user_password == "") {
        Swal.fire({
            title: "แจ้งเตือน",
            html: '<span class="fw-bold text-danger">กรุณากรอก Email และ Password</span>',
            icon: "warning",
            showConfirmButton: false,
            allowOutsideClick: false,
            timer: 2000,
            timerProgressBar: true,
        });
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
            request_state: "login",
            request_function: "login",
            username: user_email,
            password: user_password,
        },
        dataType: "json",
        success: function (response) {
            console.log("LOGIN SUCCESS RESPONSE:", response);
            if (response.result == 1) {
                const token = response.data.access_token;

                // บันทึก JWT ลงใน localStorage และ Cookie
                localStorage.setItem('access_token', token);
                document.cookie = "access_token=" + token + "; path=/; max-age=25200; SameSite=Lax";

                if (response.data.id_card_expired) {
                    localStorage.setItem('show_id_card_expired_toast', '1');
                } else {
                    localStorage.removeItem('show_id_card_expired_toast');
                }

                if (response.data.expiring_courses && response.data.expiring_courses.length > 0) {
                    localStorage.setItem('show_course_expired_toast', JSON.stringify(response.data.expiring_courses));
                } else {
                    localStorage.removeItem('show_course_expired_toast');
                }

                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-success">'+response.msg+'</span>',
                    icon: "success",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didClose: () => {
                        window.location.replace("index");
                    }
                });
            } else {
                if (response.data && response.data.unverified) {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-warning">' + response.msg + '</span>',
                        icon: "warning",
                        showConfirmButton: true,
                        confirmButtonText: "ไปยังหน้ายืนยันอีเมล",
                        showCancelButton: true,
                        cancelButtonText: "ปิด",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "verify-email.php?email=" + encodeURIComponent(response.data.email);
                        }
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">'+response.msg+'</span>',
                        icon: "warning",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            }
        },
        error: function(jqXHR, exception) {
            console.error("LOGIN ERROR:", jqXHR.responseText, exception);
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
                html: "พบปัญหาการบันทึก กรุณาติดต่อผู้ดูแลระบบ<br>"+ msg,
                icon: "error",
                showConfirmButton: true,
            });
        }
    });
}
</script>