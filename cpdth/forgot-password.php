<?php
$pageTitle = 'ลืมรหัสผ่าน';
include 'components/header.php';
?>


<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #ffffff; /* พื้นหลังสีเทาอ่อนเพื่อให้กล่องโดดเด่น */
        margin: 0;
        padding: 100px 20px 80px;
    }

    /* จัดกล่องให้อยู่กึ่งกลางหน้าจอ */
    .forgot-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 350px);
        padding: 60px 20px;
    }

    /* สไตล์กล่องหลัก */
    .forgot-card {
        background-color: #ffffff;
        width: 100%;
        max-width: 520px; /* ขยายความกว้างให้พอดีกับข้อความอธิบาย */
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); /* เงาฟุ้งๆ */
        padding: 50px 40px;
        text-align: center;
    }

    /* ไอคอนกุญแจด้านบน (จำลองให้คล้ายรูปภาพมากที่สุด) */
    .icon-container {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 120px;
        height: 120px;
        margin-bottom: 25px;
    }

    /* วงกลมลูกศรหมุนรอบนอก */
    .icon-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 6px solid #475569;
        border-radius: 50%;
        border-right-color: transparent;
        border-bottom-color: transparent;
        transform: rotate(45deg);
    }

    .icon-circle::after {
        content: '';
        position: absolute;
        bottom: 5px;
        right: 15px;
        width: 0;
        height: 0;
        border-left: 12px solid transparent;
        border-right: 12px solid transparent;
        border-top: 16px solid #475569;
        transform: rotate(-45deg);
    }

    /* ตัวแม่กุญแจด้านใน */
    .icon-lock {
        font-size: 3rem;
        color: #f59e0b; /* สีเหลืองส้ม */
        z-index: 2;
    }

    /* หัวข้อและคำอธิบาย */
    .forgot-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
    }

    .forgot-desc {
        font-size: 0.95rem;
        color: #4b5563;
        margin-bottom: 35px;
        line-height: 1.5;
    }

    /* ช่องกรอกข้อมูล (Input) */
    .input-group-custom {
        position: relative;
        margin-bottom: 25px;
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

    /* ปุ่มรีเซ็ตรหัสผ่าน */
    .btn-reset {
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

    .btn-reset:hover {
        background-color: #2d4373;
    }

    .btn-reset:active {
        transform: scale(0.98);
    }

    /* ย้อนกลับไปหน้าล็อกอิน (ตัวเลือกเสริมเพื่อให้ใช้งานง่ายขึ้น) */
    .back-to-login {
        margin-top: 20px;
        font-size: 0.9rem;
    }

    .back-to-login a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s;
    }

    .back-to-login a:hover {
        color: #3b5998;
        text-decoration: underline;
    }

    /* 📱 Responsive สำหรับมือถือ */
    @media (max-width: 576px) {
        .forgot-card {
            padding: 40px 25px;
        }
        .icon-container {
            width: 100px;
            height: 100px;
        }
        .icon-circle {
            border-width: 5px;
        }
        .icon-lock {
            font-size: 2.5rem;
        }
        .forgot-title {
            font-size: 1.3rem;
        }
        .forgot-desc {
            font-size: 0.85rem;
        }
    }
</style>

<main class="forgot-wrapper">
    <div class="forgot-card">
        
        <div class="icon-container">
            <img src="assets/images/logo/reset-password.png" alt="Forgot Password Icon" style="width: 100%; max-height: 100%;">
        </div>

        <div class="forgot-title">รีเซ็ตรหัสผ่าน</div>
        <div class="forgot-desc">
            กรุณากรอกที่อยู่อีเมลของคุณ แล้วเราจะส่งลิงก์เพื่อรีเซ็ตรหัสผ่านให้ทางอีเมล
        </div>

        <form id="forgot-password-form">
            
            <div class="input-group-custom">
                <i class="bi bi-envelope-fill"></i>
                <input type="email" name="email" id="forgot-email" class="form-control-custom" placeholder="อีเมล" required>
            </div>

            <button type="submit" class="btn-reset">รีเซ็ตรหัสผ่าน</button>

            <div class="back-to-login">
                <a href="login.php"><i class="bi bi-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ</a>
            </div>

        </form>

    </div>
</main>

<script>
$(document).ready(function() {
    $('#forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const email = $('#forgot-email').val();
        
        if (!email) {
            Swal.fire('แจ้งเตือน', 'กรุณากรอกอีเมลของคุณ', 'warning');
            return;
        }

        $.ajax({
            beforeSend: function() {
                Swal.fire({
                    title: 'กำลังดำเนินการ...',
                    html: 'ระบบกำลังส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณ',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "login",
                request_function: "forgot_password",
                email: email
            },
            dataType: "json",
            success: function(response) {
                Swal.close();
                if (response.result == 1 || response.status == 1) {
                    Swal.fire('สำเร็จ', response.message || response.msg, 'success').then(() => {
                        $('#forgot-password-form')[0].reset();
                    });
                } else {
                    Swal.fire('แจ้งเตือน', response.message || response.msg, 'warning');
                }
            },
            error: function(err) {
                console.error(err);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ หรือเกิดข้อผิดพลาดในการส่งอีเมล', 'error');
            }
        });
    });
});
</script>


<?php include 'components/footer.php'; ?>