<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utility\Auth;

if (Auth::getUser()) {
    header('Location: index');
    exit();
}

$pageTitle = 'เข้าสู่ระบบ';
include 'components/header.php';
?>

<!-- ดึงสไตล์ของหน้า login มาใช้เพื่อให้หน้า line.php มีพื้นหลังเป็นหน้าล็อกอินเหมือนกัน -->
<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #ffffff;
        margin: 0;
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
        display: flex;
        align-items: center;
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

    .divider-text:not(:empty)::before { margin-right: 15px; }
    .divider-text:not(:empty)::after { margin-left: 15px; }

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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
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

    /* สไตล์สำหรับ Overlay/Loader ของหน้าจัดการ LIFF */
    .loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .spinner {
        border: 6px solid #f3f3f3;
        border-top: 6px solid #44619d;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin-bottom: 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<!-- แสดงหน้าล็อกอินจำลองเป็นพื้นหลังระหว่างที่ LIFF กำลังโหลดหรือแจ้งเตือน -->
<main class="login-wrapper" style="position: relative;">
    
    <!-- Loader ระหว่างประมวลผล LIFF -->
    <div class="loader-overlay" id="liffLoader">
        <div class="spinner"></div>
        <div style="font-weight: 500; color: #374151;">กำลังตรวจสอบสิทธิ์การเข้าใช้งาน LINE...</div>
    </div>

    <div class="login-card" style="filter: blur(1px); pointer-events: none; opacity: 0.7;">
        <div class="login-logo">
            <img src="assets/images/logo/am-group-logo.png" alt="AM GROUP">
        </div>
        <div class="login-title">เข้าสู่ระบบ</div>
        <form>
            <div class="input-group-custom">
                <i class="bi bi-person-fill"></i>
                <input type="email" class="form-control-custom" placeholder="อีเมล" disabled>
            </div>
            <div class="input-group-custom">
                <i class="bi bi-lock-fill"></i>
                <input type="password" class="form-control-custom" placeholder="รหัสผ่าน" disabled>
            </div>
            <button type="button" class="btn-login" disabled>เข้าสู่ระบบ</button>
            <div class="divider-text">หรือ</div>
            <a href="javascript:void(0)" class="btn-line">
                <i class="bi bi-line"></i> เข้าสู่ระบบด้วย LINE
            </a>
            <div class="auth-link-text">
                ยังไม่มีบัญชีผู้ใช้? <a href="javascript:void(0)">สมัครสมาชิก</a>
            </div>
        </form>
    </div>
</main>

<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get("id");

    if (id) localStorage.setItem("id", id);

    liff.init({
        liffId: "2011178285-krDl5as1"
    }, () => {
        if (liff.isLoggedIn()) {
            CheckLogin();
        } else {
            liff.login();
        }
    }, err => {
        console.error(err.code, err.message);
        $("#liffLoader").hide();
    });

    function CheckLogin() {
        let userId = null;
        let displayName = "";
        let pictureUrl = "";
        let statusMessage = "";

        try {
            const decodedToken = liff.getDecodedIDToken();
            if (decodedToken && decodedToken.sub) {
                userId = decodedToken.sub;
                displayName = decodedToken.name || "";
                pictureUrl = decodedToken.picture || "";
            }
        } catch (e) {
            console.error("Failed to get decoded ID token:", e);
        }

        if (!userId) {
            const context = liff.getContext();
            if (context && context.userId) {
                userId = context.userId;
            }
        }

        if (userId) {
            sendCheckLogin(userId, displayName, pictureUrl, statusMessage);
        } else {
            liff.getProfile().then(profile => {
                sendCheckLogin(profile.userId, profile.displayName, profile.pictureUrl, profile.statusMessage);
            }).catch(err => {
                console.error("getProfile failed:", err);
                $("#liffLoader").hide();
                Swal.fire("แจ้งเตือน", "ไม่สามารถดึงข้อมูลสิทธิ์ LINE profile ได้", "error");
            });
        }
    }

    function sendCheckLogin(userId, displayName, pictureUrl, statusMessage) {
        $.post("core.php", {
            "request_state": "line_auth",
            "request_function": "check_login",
            "userId": userId,
            "id": id
        }, function (data) {
            if (data.result == 1) {
                // เก็บ JWT Token
                const token = data.data.access_token;
                localStorage.setItem('access_token', token);
                document.cookie = "access_token=" + token + "; path=/; max-age=25200; SameSite=Lax";
                window.location.replace("index");
            } else {
                // ซ่อน Loader เพื่อให้เห็นหน้าล็อกอินเป็นพื้นหลังชัดเจน
                $("#liffLoader").hide();
                localStorage.setItem("line_token", userId);
                
                Swal.fire({
                    title: "ไม่พบบัญชีผู้ใช้งาน",
                    text: "บัญชี LINE นี้ยังไม่ได้เชื่อมต่อกับระบบ กรุณาลงทะเบียนสมัครสมาชิกก่อนใช้งาน",
                    icon: "warning",
                    confirmButtonText: "ไปหน้าสมัครสมาชิก",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.replace("register");
                    }
                });
            }
        }, "json");
    }
</script>

<?php include 'components/footer.php'; ?>
</html>