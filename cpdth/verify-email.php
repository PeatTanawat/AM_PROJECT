<?php
$pageTitle = 'ยืนยันอีเมลของคุณ';
include 'components/header.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$mailLink = '';

if (!empty($email)) {
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $domain = strtolower($parts[1]);
        if ($domain === 'gmail.com') {
            $mailLink = 'https://mail.google.com';
        } elseif (in_array($domain, ['hotmail.com', 'outlook.com', 'live.com', 'msn.com'])) {
            $mailLink = 'https://outlook.live.com';
        } elseif ($domain === 'yahoo.com' || $domain === 'ymail.com') {
            $mailLink = 'https://mail.yahoo.com';
        } elseif (str_ends_with($domain, '.co.th') || str_ends_with($domain, '.in.th') || str_ends_with($domain, '.ac.th')) {
            // โดเมนเฉพาะไทย หรือบริษัท/สถาบันทั่วไป 
            $mailLink = 'https://' . $domain;
        } else {
            $mailLink = 'https://' . $domain;
        }
    }
}
?>


<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8fafc;
        margin: 0;
        padding: 0;
    }

    .verify-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 350px);
        padding: 150px 20px 80px;
    }

    .verify-card {
        background-color: #ffffff;
        width: 100%;
        max-width: 500px;
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
        padding: 50px 40px;
        text-align: center;
        transition: transform 0.3s ease;
    }

    .verify-card:hover {
        transform: translateY(-5px);
    }

    /* SVG Envelope Animation styling */
    .envelope-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .envelope-icon {
        width: 120px;
        height: 120px;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .verify-title {
        font-size: 1.6rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
    }

    .verify-desc {
        font-size: 0.95rem;
        color: #6b7280;
        line-height: 1.7;
        margin-bottom: 35px;
    }

    .verify-email-text {
        font-weight: 600;
        color: #3b5998;
        text-decoration: underline;
        word-break: break-all;
    }
    
    .verify-email-link {
        font-weight: 600;
        color: #3b5998;
        text-decoration: underline;
        word-break: break-all;
        transition: color 0.2s;
    }
    
    .verify-email-link:hover {
        color: #1d4ed8;
    }

    .btn-resend {
        background: none;
        border: none;
        color: #44619d;
        font-weight: 600;
        font-size: 0.95rem;
        font-family: 'Prompt', sans-serif;
        cursor: pointer;
        transition: color 0.2s, transform 0.1s;
        text-decoration: none;
        display: inline-block;
        padding: 5px 10px;
    }

    .btn-resend:hover {
        color: #2d4373;
        text-decoration: underline;
    }

    .btn-resend:active {
        transform: scale(0.97);
    }

    @media (max-width: 576px) {
        .verify-card {
            padding: 40px 20px;
        }
        .verify-title {
            font-size: 1.4rem;
        }
        .verify-desc {
            font-size: 0.9rem;
        }
    }
</style>

<main class="verify-wrapper">
    <div class="verify-card">
        
        <!-- Premium Yellow Envelope Graphic using inline SVG -->
        <div class="envelope-container">
            <svg class="envelope-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Shadow -->
                <ellipse cx="50" cy="85" rx="35" ry="6" fill="#E2E8F0" />
                
                <!-- Main Envelope Body (Yellow Back) -->
                <path d="M10 35C10 32.2386 12.2386 30 15 30H85C87.7614 30 90 32.2386 90 35V75C90 77.7614 87.7614 80 85 80H15C12.2386 80 10 77.7614 10 75V35Z" fill="#FBBF24" />
                
                <!-- Inner Letter (White Document) -->
                <rect x="22" y="15" width="56" height="45" rx="4" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="2" />
                <!-- Lines on Letter -->
                <rect x="30" y="25" width="20" height="4" rx="2" fill="#38BDF8" />
                <rect x="30" y="34" width="40" height="3" rx="1.5" fill="#94A3B8" />
                <rect x="30" y="42" width="40" height="3" rx="1.5" fill="#94A3B8" />
                <rect x="30" y="50" width="25" height="3" rx="1.5" fill="#94A3B8" />
                
                <!-- Envelope Cover Open / Top Flap -->
                <path d="M10 35L47.0503 62.7877C48.8055 64.1041 51.1945 64.1041 52.9497 62.7877L90 35" stroke="#F59E0B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M10 79.5L42.5 52.5" stroke="#D97706" stroke-width="3" stroke-linecap="round" />
                <path d="M90 79.5L57.5 52.5" stroke="#D97706" stroke-width="3" stroke-linecap="round" />
            </svg>
        </div>

        <div class="verify-title">ยืนยันอีเมลของคุณ</div>
        
        <div class="verify-desc">
            เราได้ส่งอีเมลไปที่ 
            <?php if (!empty($mailLink)): ?>
                <a href="<?php echo htmlspecialchars($mailLink) ?>" target="_blank" class="verify-email-link" title="เปิดหน้าเว็บเมลเพื่อตรวจกล่องจดหมายของคุณ">
                    <?php echo htmlspecialchars($email) ?> <i class="bi bi-box-arrow-up-right" style="font-size: 0.8rem;"></i>
                </a>
            <?php else: ?>
                <span class="verify-email-text"><?php echo htmlspecialchars($email) ?></span>
            <?php endif; ?>
            เพื่อยืนยันความถูกต้องของที่อยู่อีเมลของคุณ กรุณากดตามลิงก์ที่ให้ไว้ในอีเมล เพื่อดำเนินการยืนยันอีเมล
        </div>

        <div>
            <button class="btn-resend" onclick="ResendVerification('<?php echo htmlspecialchars(addslashes($email)) ?>')">
                ส่งอีเมลยืนยันอีกครั้ง
            </button>
        </div>

    </div>
</main>

<script>
$(document).ready(function() {
    const email = '<?php echo htmlspecialchars(addslashes($email)) ?>';
    
    if (email) {
        // เริ่มต้นการดึงสถานะ (Polling) ทุกๆ 3 วินาที
        const checkInterval = setInterval(function() {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "register",
                    request_function: "check_status",
                    email: email
                },
                dataType: "json",
                success: function(response) {
                    if (response.result == 1) {
                        clearInterval(checkInterval); // หยุดส่งคำขอเมื่อยืนยันสำเร็จแล้ว
                        
                        const token = response.data.access_token;
                        localStorage.setItem('access_token', token);
                        document.cookie = "access_token=" + token + "; path=/; max-age=25200; SameSite=Lax";

                        Swal.fire({
                            title: "ยืนยันตัวตนสำเร็จ",
                            html: '<span class="fw-bold text-success">ระบบยืนยันตัวตนเรียบร้อย กำลังเข้าสู่ระบบอัตโนมัติ...</span>',
                            icon: "success",
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didClose: () => {
                                window.location.replace("index");
                            }
                        });
                    }
                }
            });
        }, 3000);
    }
});

function ResendVerification(email) {
    if (!email) {
        Swal.fire({
            title: "แจ้งเตือน",
            text: "ไม่พบข้อมูลอีเมลสำหรับส่งรหัสยืนยัน",
            icon: "warning"
        });
        return;
    }

    $.ajax({
        beforeSend: function() {
            Swal.fire({
                title: 'กำลังส่งอีเมลอีกครั้ง...',
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
            request_function: "resend_verification",
            email: email
        },
        dataType: "json",
        success: function(response) {
            if (response.result == 1) {
                Swal.fire({
                    title: "สำเร็จ",
                    html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                    icon: "success",
                    timer: 2500,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                    icon: "warning"
                });
            }
        },
        error: function() {
            Swal.fire({
                title: "ผิดพลาด",
                text: "เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์",
                icon: "error"
            });
        }
    });
}
</script>

<?php include 'components/footer.php'; ?>
