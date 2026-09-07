<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>CPDTH - Login</title>

    <link rel="icon" type="image/png" href="assets/images/am-group-logo.png">
    <link rel="apple-touch-icon" href="assets/images/am-group-logo.png">
    <link rel="stylesheet" href="template/assets/css/font.css">
    <link rel="stylesheet" href="template/assets/css/sidebar-menu.css">
    <link rel="stylesheet" href="template/assets/css/simplebar.css">
    <link rel="stylesheet" href="template/assets/css/apexcharts.css">
    <link rel="stylesheet" href="template/assets/css/prism.css">
    <link rel="stylesheet" href="template/assets/css/rangeslider.css">
    <link rel="stylesheet" href="template/assets/css/google-icon.css">
    <link rel="stylesheet" href="template/assets/css/remixicon.css">
    <link rel="stylesheet" href="template/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="template/assets/css/fullcalendar.main.css">
    <link rel="stylesheet" href="template/assets/css/jsvectormap.min.css">
    <link rel="stylesheet" href="template/assets/css/lightpick.css">
    <link rel="stylesheet" href="template/assets/css/style.css">
    <link rel="stylesheet" href="template/assets/css/toastr.min.css">

    <link rel="stylesheet"
        href="template/assets/css/custom.css?ver=<?php echo @filemtime(__DIR__ . '/template/assets/css/custom.css') ?: time(); ?>">
    <link rel="stylesheet" href="template/assets/css/web.css">
    <link rel="stylesheet"
        href="template/assets/css/ui.css?ver=<?php echo @filemtime(__DIR__ . '/template/assets/css/ui.css') ?: time(); ?>">
</head>
<style>
    .login-page .main-content {
        min-height: 100vh !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .login-shell {
        width: 100% !important;
        max-width: 480px !important;
        margin: 0 auto !important;
    }

    .login-card {
        width: 100% !important;
        max-width: 480px !important;
        margin: 0 auto !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(16, 24, 40, 0.1) !important;
    }

    .login-form-wrap {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding-right: 60px;
        padding-left: 60px;
        padding-bottom: 20px;
        padding-top: 0px;
    }

    .login-brand {
        margin-bottom: 0px !important;
    }
</style>

<body class="boxed-size bg-white">
    <!-- Start Preloader Area -->
    <div class="preloader" id="preloader">
        <div class="preloader">
            <div class="waviy position-relative">
                <span class="d-inline-block">C</span>
                <span class="d-inline-block">P</span>
                <span class="d-inline-block">D</span>
                <span class="d-inline-block">T</span>
                <span class="d-inline-block">H</span>
            </div>
        </div>
    </div>
    <!-- End Preloader Area -->

    <!-- Start Main Content Area -->
    <div class="container login-page">
        <div class="main-content d-flex flex-column p-0 align-items-center justify-content-center">
            <div class="login-shell mx-auto">
                <div class="login-card mx-auto">
                    <!-- <div class="col-lg-6 d-none d-lg-block login-hero">
                            <img src="template/assets/images/login.jpg" alt="" aria-hidden="true">
                        </div> -->
                    <div class="col-lg-12">
                        <div class="login-form-wrap">
                            <div class="login-brand flex-column align-items-start">
                                <img src="assets/images/G_AM_logo-01.jpg" alt="CPDTH" class="align-self-center"
                                    style="width: auto; height: 180px; max-width: 100%; object-fit: cover;"
                                    onerror="this.style.display='none'">
                                <!-- <div>
                                        <h1 class="login-title">เข้าสู่ระบบ</h1>
                                        <p class="login-subtitle mb-0">กรุณาลงชื่อเข้าใช้เพื่อจัดการระบบ</p>
                                    </div> -->
                            </div>
                            <div id="loginAlert" class="login-alert" role="alert" style="display:none;"></div>
                            <form novalidate autocomplete="on">
                                <div class="mb-3">
                                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                    <input type="text" id="username" name="username" class="form-control"
                                        placeholder="อีเมล หรือ ชื่อผู้ใช้" autocomplete="username" autofocus>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">รหัสผ่าน</label>
                                    <div class="login-field">
                                        <input type="password" id="password" name="password" class="form-control"
                                            placeholder="รหัสผ่าน" autocomplete="current-password">
                                        <button type="button" class="login-eye" aria-label="แสดงหรือซ่อนรหัสผ่าน"
                                            aria-pressed="false"
                                            onclick="(function(b){var i=document.getElementById('password');var show=i.type==='password';i.type=show?'text':'password';b.setAttribute('aria-pressed',String(show));var g=b.querySelector('.material-symbols-outlined');if(g){g.textContent=show?'visibility_off':'visibility';}})(this)">
                                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary w-100 login-submit" onclick="Login()">
                                    <span class="login-submit-default"><span class="material-symbols-outlined"
                                            aria-hidden="true">login</span> เข้าสู่ระบบ</span>
                                    <span class="login-submit-loading"><span class="spinner-border spinner-border-sm"
                                            role="status" aria-hidden="true"></span> กำลังเข้าสู่ระบบ…</span>
                                </button>
                            </form>
                            <p class="login-foot">© CPDTH · ระบบจัดการหลังบ้าน</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Link Of JS File -->
    <script src="template/assets/js/jquery-3.1.1.min.js"></script>
    <script src="template/assets/js/bootstrap.bundle.min.js"></script>
    <script src="template/assets/js/sidebar-menu.js"></script>
    <script src="template/assets/js/dragdrop.js"></script>
    <script src="template/assets/js/rangeslider.min.js"></script>
    <script src="template/assets/js/data-table.js"></script>
    <script src="template/assets/js/prism.js"></script>
    <script src="template/assets/js/clipboard.min.js"></script>
    <script src="template/assets/js/feather.min.js"></script>
    <script src="template/assets/js/simplebar.min.js"></script>
    <script src="template/assets/js/apexcharts.min.js"></script>
    <script src="template/assets/js/echarts.js"></script>
    <script src="template/assets/js/swiper-bundle.min.js"></script>
    <script src="template/assets/js/fullcalendar.main.js"></script>
    <script src="template/assets/js/jsvectormap.min.js"></script>
    <script src="template/assets/js/world-merc.js"></script>
    <script src="template/assets/js/moment.min.js"></script>
    <script src="template/assets/js/lightpick.js"></script>
    <script src="template/assets/js/custom/custom.js"></script>
    <script src="template/assets/js/toastr.min.js"></script>
    <script src="template/assets/js/sweetalert2@11.js"></script>
    <script src="template/assets/js/loadingoverlay.js"></script>
    <script src="js/main.js"></script>

    <script>
        document.getElementById('password').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { Login(); }
        });
        document.getElementById('username').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { Login(); }
        });

        // ปุ่มเข้าสู่ระบบ: สลับสถานะกำลังโหลด (แทน overlay spin)
        function LoginBusy(on) {
            const btn = document.querySelector(".login-submit");
            if (!btn) { return; }
            btn.disabled = on;
            btn.classList.toggle("is-loading", on);
        }

        // แจ้ง error แบบ inline ใต้หัวฟอร์ม (แทน Swal)
        function LoginError(msg) {
            const box = document.getElementById("loginAlert");
            if (!box) { return; }
            box.textContent = msg || "";
            box.style.display = msg ? "flex" : "none";
        }

        function Login() {
            const username = $("#username").val().trim();
            const password = $("#password").val();

            LoginError("");
            if (username === "" || password === "") {
                LoginError("กรุณากรอกชื่อผู้ใช้และรหัสผ่าน");
                return false;
            }

            $.ajax({
                beforeSend: function () { LoginBusy(true); },
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "login",
                    request_function: "login",
                    username: username,
                    password: password,
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        localStorage.setItem("bo_access_token", response.data.access_token);
                        sessionStorage.setItem("cpdth_show_preloader", "1");
                        window.location.replace("main/index");
                    } else {
                        LoginBusy(false);
                        LoginError(response.msg || "เข้าสู่ระบบไม่สำเร็จ");
                    }
                },
                error: function (jqXHR, exception) {
                    LoginBusy(false);
                    LoginError("เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง");
                }
            });
        }
    </script>
</body>

</html>