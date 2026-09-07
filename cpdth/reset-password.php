<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token   = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid   = false;
$expired = false;
$message = '';

if (empty($token)) {
    $message = 'ไม่พบโทเค็นสำหรับการรีเซ็ตรหัสผ่าน';
} else {
    try {
        $db_instance = new Connection();
        $pdo_connect = $db_instance->getPdo();

        if ($pdo_connect) {
            $sql_check  = "SELECT user_id, reset_token_expire FROM tbl_user WHERE reset_token = :token AND delete_at IS NULL LIMIT 1";
            $stmt_check = $pdo_connect->prepare($sql_check);
            $stmt_check->execute([':token' => $token]);
            $row_user = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $stmt_check->closeCursor();

            if ($row_user) {
                $now    = new DateTime();
                $expiry = new DateTime($row_user['reset_token_expire']);
                if ($now > $expiry) {
                    $expired = true;
                    $message = 'ลิงก์รีเซ็ตรหัสผ่านนี้หมดอายุแล้ว กรุณาทำรายการลืมรหัสผ่านใหม่อีกครั้ง';
                } else {
                    $valid = true;
                }
            } else {
                $message = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือถูกใช้งานไปแล้ว';
            }
        } else {
            $message = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้';
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่ารหัสผ่านใหม่ - CPDTH</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #eef2ff 0%, #e8f0fe 50%, #f0f4ff 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-wrapper {
            width: 100%;
            padding: 30px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-card {
            background: #ffffff;
            width: 100%;
            max-width: 460px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(68, 97, 157, 0.15), 0 4px 16px rgba(0,0,0,0.06);
            padding: 50px 44px 44px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .reset-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #44619d, #6b8fd6, #44619d);
        }

        /* Icon */
        .icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            margin-bottom: 22px;
        }

        .icon-container.success-icon {
            background: linear-gradient(135deg, #e8f0fe, #d0def7);
            box-shadow: 0 8px 24px rgba(68, 97, 157, 0.2);
        }

        .icon-container.error-icon {
            background: linear-gradient(135deg, #fdecea, #fdd0cc);
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.15);
        }

        .icon-container i { font-size: 2.4rem; }
        .icon-container.success-icon i { color: #44619d; }
        .icon-container.error-icon i { color: #dc3545; }

        /* Title & desc */
        .reset-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .reset-desc {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.65;
            margin-bottom: 30px;
        }

        /* Form */
        .form-group-pw {
            text-align: left;
            margin-bottom: 20px;
            position: relative;
        }

        .form-group-pw label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 7px;
        }

        .form-control-pw {
            width: 100%;
            background-color: #f3f4f6;
            border: 2px solid transparent;
            border-radius: 50px;
            padding: 13px 48px 13px 20px;
            font-size: 0.93rem;
            font-family: 'Prompt', sans-serif;
            color: #374151;
            outline: none;
            transition: all 0.25s ease;
        }

        .form-control-pw::placeholder { color: #9ca3af; }

        .form-control-pw:focus {
            background-color: #fff;
            border-color: #44619d;
            box-shadow: 0 0 0 4px rgba(68, 97, 157, 0.1);
        }

        .form-control-pw.is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff8f8 !important;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 39px;
            cursor: pointer;
            color: #9ca3af;
            font-size: 1.05rem;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: #44619d; }

        .invalid-feedback-custom {
            display: none;
            font-size: 0.8rem;
            color: #dc3545;
            margin-top: 5px;
            padding-left: 14px;
        }

        .pw-hint {
            font-size: 0.78rem;
            color: #9ca3af;
            padding-left: 14px;
            margin-top: 5px;
        }

        /* Submit button */
        .btn-reset-submit {
            width: 100%;
            background: linear-gradient(135deg, #44619d, #2d4373);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Prompt', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(68, 97, 157, 0.35);
            margin-top: 8px;
            letter-spacing: 0.3px;
        }

        .btn-reset-submit:hover {
            background: linear-gradient(135deg, #3a5490, #243761);
            box-shadow: 0 6px 24px rgba(68, 97, 157, 0.45);
            transform: translateY(-1px);
        }

        .btn-reset-submit:active { transform: scale(0.98); }

        /* Error state buttons */
        .btn-try-again {
            display: inline-block;
            background: linear-gradient(135deg, #44619d, #2d4373);
            color: #fff;
            padding: 12px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            font-family: 'Prompt', sans-serif;
            box-shadow: 0 4px 16px rgba(68, 97, 157, 0.3);
            transition: all 0.3s ease;
        }

        .btn-try-again:hover {
            color: #fff;
            background: linear-gradient(135deg, #3a5490, #243761);
            transform: translateY(-1px);
        }

        .btn-back-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 0.88rem;
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.2s;
        }

        .btn-back-link:hover {
            color: #44619d;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .reset-card { padding: 40px 24px 36px; }
            .reset-title { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

<main class="reset-wrapper">
    <div class="reset-card">

        <?php if ($valid): ?>
            <!-- ✅ Token ถูกต้อง แสดงฟอร์มตั้งรหัสผ่านใหม่ -->
            <div class="icon-container success-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="reset-title">ตั้งค่ารหัสผ่านใหม่</div>
            <div class="reset-desc">กรุณากรอกรหัสผ่านใหม่ที่ต้องการ รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร</div>

            <form id="reset-password-form">
                <input type="hidden" name="token" id="reset-token" value="<?php echo htmlspecialchars($token) ?>">

                <div class="form-group-pw">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" id="new_password" class="form-control-pw" placeholder="รหัสผ่านใหม่">
                    <i class="bi bi-eye-slash-fill toggle-password" onclick="togglePw('new_password', this)"></i>
                    <div class="invalid-feedback-custom"><i class="bi bi-exclamation-circle"></i> <span></span></div>
                    <div class="pw-hint"><i class="bi bi-info-circle"></i> ความยาวอย่างน้อย 6 ตัวอักษร</div>
                </div>

                <div class="form-group-pw">
                    <label>ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-pw" placeholder="ยืนยันรหัสผ่านใหม่">
                    <i class="bi bi-eye-slash-fill toggle-password" onclick="togglePw('confirm_password', this)"></i>
                    <div class="invalid-feedback-custom"><i class="bi bi-exclamation-circle"></i> <span></span></div>
                </div>

                <button type="submit" class="btn-reset-submit">
                    <i class="bi bi-check-circle-fill"></i> ตั้งรหัสผ่านใหม่
                </button>
            </form>

        <?php else: ?>
            <!-- ❌ Token ไม่ถูกต้องหรือหมดอายุ -->
            <div class="icon-container error-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="reset-title"><?php echo $expired ? 'ลิงก์หมดอายุแล้ว' : 'ลิงก์ไม่ถูกต้อง' ?></div>
            <div class="reset-desc"><?php echo htmlspecialchars($message) ?></div>

            <a href="forgot-password" class="btn-try-again">
                <i class="bi bi-<?php echo $expired ? 'arrow-repeat' : 'envelope-fill' ?>"></i>
                <?php echo $expired ? 'ขอลิงก์ใหม่' : 'กลับหน้าลืมรหัสผ่าน' ?>
            </a>
            <br>
            <a href="login" class="btn-back-link"><i class="bi bi-arrow-left"></i> กลับหน้าเข้าสู่ระบบ</a>
        <?php endif; ?>

    </div>
</main>

<?php if ($valid): ?>
<script>
$(document).ready(function () {
    $('#reset-password-form').on('submit', function (e) {
        e.preventDefault();

        const token            = $('#reset-token').val();
        const new_password     = $('#new_password').val();
        const confirm_password = $('#confirm_password').val();
        let isValid = true;

        $('.invalid-feedback-custom').hide();
        $('.form-control-pw').removeClass('is-invalid');

        const showError = (id, msg) => {
            const el = $('#' + id);
            el.addClass('is-invalid');
            el.siblings('.invalid-feedback-custom').find('span').text(msg);
            el.siblings('.invalid-feedback-custom').show();
            isValid = false;
        };

        if (!new_password) {
            showError('new_password', 'กรุณากรอกรหัสผ่านใหม่');
        } else if (new_password.length < 6) {
            showError('new_password', 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        }

        if (!confirm_password) {
            showError('confirm_password', 'กรุณายืนยันรหัสผ่านใหม่');
        } else if (new_password !== confirm_password) {
            showError('confirm_password', 'รหัสผ่านไม่ตรงกัน กรุณากรอกใหม่อีกครั้ง');
        }

        if (!isValid) return;

        $.ajax({
            beforeSend: function () {
                Swal.fire({
                    title: 'กำลังบันทึก...',
                    html: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            },
            type: 'POST',
            url: 'core.php',
            data: {
                request_state:    'password',
                request_function: 'reset_password',
                token:            token,
                new_password:     new_password,
                confirm_password: confirm_password
            },
            dataType: 'json',
            success: function (response) {
                Swal.close();
                if (response.result == 1 || response.status == 1) {
                    Swal.fire({
                        title: 'รีเซ็ตรหัสผ่านสำเร็จ!',
                        html: '<p>รหัสผ่านของคุณถูกเปลี่ยนแล้ว<br>คุณสามารถเข้าสู่ระบบด้วยรหัสผ่านใหม่ได้ทันที</p>',
                        icon: 'success',
                        confirmButtonText: 'ไปหน้าเข้าสู่ระบบ',
                        confirmButtonColor: '#44619d',
                        allowOutsideClick: false,
                    }).then(() => {
                        window.location.href = 'login';
                    });
                } else {
                    Swal.fire('แจ้งเตือน', response.message || response.msg, 'warning');
                }
            },
            error: function () {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง', 'error');
            }
        });
    });
});

function togglePw(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
    }
}
</script>
<?php endif; ?>

</body>
</html>
