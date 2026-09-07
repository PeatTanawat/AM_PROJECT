<?php
$pageTitle = 'ติดต่อ';
include 'components/header.php';
?>

<style>
    .page-header {
        background-color: #f4f5f7;
        padding: 80px 20px; 
        text-align: center;
    }

    .page-title {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    /* 📱 Responsive (หัวข้อ) */
    @media (max-width: 768px) {
        .page-header {
            padding: 60px 20px !important;
        }
        .page-title {
            font-size: 1.8rem !important;
        }
    }

    .bg-contact { padding: 60px 20px; }
    .contact-container { max-width: 1000px; margin: 0 auto; }

    .contact-row {
        background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 2px;
        padding: 40px 0; margin-bottom: 30px; display: flex; justify-content: space-between;
    }

    .contact-item { flex: 1; text-align: center; padding: 0 20px; border-right: 1px solid #e2e8f0; }
    .contact-item:last-child { border-right: none; }
    .contact-icon { margin-bottom: 20px; display: flex; justify-content: center; align-items: center; height: 60px; }
    .contact-label { font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 10px; }
    .contact-detail { font-size: 0.95rem; color: #4b5563; line-height: 1.6; }

    @media (max-width: 768px) {
        .contact-row { flex-direction: column; padding: 20px 0; }
        .contact-item { border-right: none; border-bottom: 1px solid #e2e8f0; padding: 30px 20px; }
        .contact-item:last-child { border-bottom: none; }
    }
</style>

<div class="page-header">
    <h1 class="page-title">ติดต่อเรา</h1>
</div>

<main class="bg-contact">
    <div class="contact-container">

        <div class="contact-row">
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="48" height="48" viewBox="0 0 384 512" fill="#e53935" xmlns="http://www.w3.org/2000/svg">
                        <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/>
                    </svg>
                </div>
                <div class="contact-label">ที่อยู่</div>
                <div class="contact-detail">เลขที่ 16 ซอยลาดกระบัง 14/1 ถนนลาดกระบัง แขวง ลาดกระบัง เขตลาดกระบัง กรุงเทพมหานคร 10520</div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="52" height="52" viewBox="0 0 512 512" fill="#00b8cc" xmlns="http://www.w3.org/2000/svg">
                        <path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/>
                    </svg>
                </div>
                <div class="contact-label">เบอร์โทรติดต่อ</div>
                <div class="contact-detail">083-429-6854</div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="52" height="52" viewBox="0 0 512 512" fill="#ff9c00" xmlns="http://www.w3.org/2000/svg">
                        <path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/>
                    </svg>
                </div>
                <div class="contact-label">อีเมล</div>
                <div class="contact-detail">cpdth@am-amaudit.com</div>
            </div>
        </div>

        <div class="contact-row">
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="54" height="54" viewBox="0 0 448 512" fill="#3b5998" xmlns="http://www.w3.org/2000/svg">
                        <path d="M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h137.25V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.27c-30.81 0-40.42 19.12-40.42 38.73V256h68.78l-11 71.69h-57.78V480H400a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48z"/>
                    </svg>
                </div>
                <div class="contact-label">Facebook</div>
                <div class="contact-detail">เก็บชั่วโมง CPD CPA e-Learning</div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">
                    <img src="assets/images/logo/line-logo.jpg" alt="Line Logo" width="54" height="54" style="object-fit: contain;">
                </div>
                <div class="contact-label">Line ID</div>
                <div class="contact-detail">@cpdth (มี@ข้างหน้า)</div>
            </div>
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="54" height="54" viewBox="0 0 448 512" fill="#3b5998" xmlns="http://www.w3.org/2000/svg">
                        <path d="M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h137.25V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.27c-30.81 0-40.42 19.12-40.42 38.73V256h68.78l-11 71.69h-57.78V480H400a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48z"/>
                    </svg>
                </div>
                <div class="contact-label">Facebook</div>
                <div class="contact-detail">หนังสือพิมพ์เชิญประชุมผู้ถือหุ้น</div>
            </div>
        </div>

    </div>
</main>

<?php include 'components/footer.php'; ?>