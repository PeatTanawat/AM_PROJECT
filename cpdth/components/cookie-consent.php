<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
<style>
    .material-symbols-outlined {
      font-variation-settings:
      'FILL' 0,
      'wght' 400,
      'GRAD' 0,
      'opsz' 24
    }

    /* Floating Cookie Icon */
    .cookie-floating-icon {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: 50px;
        height: 50px;
        background-color: #f1f5f9;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        z-index: 9998;
        transition: transform 0.2s ease, background-color 0.2s ease;
    }
    .cookie-floating-icon:hover {
        transform: scale(1.1);
        background-color: #e2e8f0;
    }
    .cookie-floating-icon svg {
        width: 26px;
        height: 26px;
    }

    /* Initial Cookie Banner */
    .cookie-consent-banner {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: calc(100% - 40px);
        max-width: 450px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        padding: 20px;
        z-index: 9999;
        display: none;
        border: 1px solid #e2e8f0;
    }
    .cookie-banner-title {
        font-weight: 700;
        font-size: 1.15rem;
        color: #1e293b;
        margin-bottom: 8px;
    }
    .cookie-banner-text {
        font-size: 0.85rem;
        color: #475569;
        line-height: 1.5;
        margin-bottom: 0;
    }
    .cookie-banner-link {
        color: #3b5998;
        text-decoration: none;
    }
    .cookie-banner-link:hover {
        text-decoration: underline;
    }
    .cookie-btn-outline {
        background-color: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .cookie-btn-outline:hover {
        background-color: #e2e8f0;
    }
    .cookie-btn-primary {
        background-color: #3b5998;
        border: none;
        color: #ffffff;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .cookie-btn-primary:hover {
        background-color: #2d4373;
        color: #ffffff;
    }

    /* Modal Styling */
    #cookieSettingsModal {
        z-index: 100000 !important;
    }
    /* Hide scrollbar for modal */
    #cookieSettingsModal::-webkit-scrollbar {
        display: none;
    }
    #cookieSettingsModal {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .modal-backdrop {
        z-index: 99999 !important;
    }
    .cookie-modal-box {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .cookie-modal-box-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .cookie-modal-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        margin: 0;
    }
    .cookie-modal-status {
        font-size: 0.85rem;
        color: #3b5998;
        font-weight: 600;
    }
    .cookie-modal-desc {
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 10px;
        line-height: 1.5;
    }
    .cookie-details-btn {
        font-size: 0.85rem;
        color: #3b5998;
        background: none;
        border: none;
        padding: 0;
        text-decoration: underline;
        cursor: pointer;
    }
    .cookie-details-table {
        width: 100%;
        font-size: 0.85rem;
        color: #475569;
        margin-top: 0;
    }
    .cookie-details-table th, .cookie-details-table td {
        padding: 8px 0;
        border-bottom: 1px solid transparent; /* Hide border inside since we use HR */
    }
    .cookie-details-table th:first-child {
        padding-left: 1.5rem;
        width: 30%;
        font-weight: 600;
        color: #1e293b;
        text-align: left;
    }
    .cookie-details-table td:last-child {
        padding-right: 1.5rem;
    }
</style>

<!-- 1. Floating Cookie Icon -->
<div id="cookie-floating-icon" class="cookie-floating-icon" onclick="openCookieBanner()">
    <span class="material-symbols-outlined" style="color: #3b5998; font-size: 26px;">cookie</span>
</div>

<!-- 2. Cookie Banner (Initial View) -->
<div id="cookie-consent-banner" class="cookie-consent-banner">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="closeCookieBanner()" aria-label="Close"></button>
    <div class="d-flex align-items-start mt-2">
        <div class="me-3 mt-1">
            <span class="material-symbols-outlined" style="color: #3b5998; font-size: 55px;">cookie</span>
        </div>
        <div>
            <h5 class="cookie-banner-title">เว็บไซต์นี้ใช้คุกกี้</h5>
            <p class="cookie-banner-text">
                เราใช้คุกกี้เพื่อเพิ่มประสิทธิภาพและประสบการณ์ที่ดีในการใช้เว็บไซต์ ท่านสามารถศึกษารายละเอียดการใช้คุกกี้ได้ที่
                <a href="<?php echo $base_url ?? '' ?>privacy-policy.php" class="cookie-banner-link" target="_blank">"เงื่อนไขบริการและนโยบายความเป็นส่วนตัว"</a>
            </p>
        </div>
    </div>
    <div id="cookie-banner-buttons" class="mt-4 d-flex gap-2 w-100">
        <button class="btn cookie-btn-outline flex-fill" onclick="openCookieSettingsModal()">ตั้งค่าคุกกี้</button>
        <button class="btn cookie-btn-primary flex-fill" onclick="acceptAllCookies()">ยอมรับทั้งหมด</button>
    </div>
</div>

<!-- 3. Cookie Settings Modal -->
<div class="modal fade" id="cookieSettingsModal" tabindex="-1" aria-labelledby="cookieSettingsModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header border-0 bg-light py-3">
        <h5 class="modal-title fw-bold text-dark" id="cookieSettingsModalLabel">การตั้งค่าความเป็นส่วนตัว</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeCookieSettingsModal()"></button>
      </div>
      <div class="modal-body p-0">

        <!-- View 1: Overview -->
        <div id="cookieOverviewView" class="p-4">
            <div class="cookie-modal-box">
                <div class="cookie-modal-box-header">
                    <h6 class="cookie-modal-title">คุกกี้พื้นฐานที่จำเป็น</h6>
                    <span class="cookie-modal-status">เปิดใช้งานตลอดเวลา</span>
                </div>
                <p class="cookie-modal-desc">
                    คุกกี้พื้นฐานที่จำเป็น เพื่อช่วยให้การทำงานหลักของเว็บไซต์ใช้งานได้ รวมถึงการเข้าถึงพื้นที่ที่ปลอดภัยต่างๆ ของเว็บไซต์ หากไม่มีคุกกี้นี้เว็บไซต์จะไม่สามารถทำงานได้อย่างเหมาะสม และจะใช้งานได้โดยการตั้งค่าเริ่มต้น โดยไม่สามารถปิดการใช้งานได้
                </p>
                <button class="cookie-details-btn" onclick="toggleCookieDetails()">รายละเอียดคุกกี้</button>
            </div>
        </div>

        <!-- View 2: Details (Initially Hidden) -->
        <div id="cookieDetailsView" style="display: none; padding-bottom: 1rem;">
            <table class="cookie-details-table">
                <tr>
                    <th>ชื่อ</th>
                    <td>auth._token.local</td>
                </tr>
                <tr>
                    <th>โฮสต์</th>
                    <td>cpdth.com</td>
                </tr>
                <tr>
                    <th>ระยะเวลา</th>
                    <td>เซสชัน</td>
                </tr>
                <tr>
                    <th>หมวดหมู่</th>
                    <td>คุกกี้พื้นฐานที่จำเป็น</td>
                </tr>
                <tr>
                    <th>คำอธิบาย</th>
                    <td>โทเคนการตรวจสอบสิทธิ์</td>
                </tr>
            </table>
            <hr class="m-0" style="border-color: #e2e8f0;">
            <table class="cookie-details-table">
                <tr>
                    <th>ชื่อ</th>
                    <td>auth._token_expiration.local</td>
                </tr>
                <tr>
                    <th>โฮสต์</th>
                    <td>cpdth.com</td>
                </tr>
                <tr>
                    <th>ระยะเวลา</th>
                    <td>เซสชัน</td>
                </tr>
                <tr>
                    <th>หมวดหมู่</th>
                    <td>คุกกี้พื้นฐานที่จำเป็น</td>
                </tr>
                <tr>
                    <th>คำอธิบาย</th>
                    <td>เวลาหมดอายุของโทเคนการตรวจสอบสิทธิ์</td>
                </tr>
            </table>
            <hr class="m-0" style="border-color: #e2e8f0;">
            <table class="cookie-details-table">
                <tr>
                    <th>ชื่อ</th>
                    <td>auth.redirect</td>
                </tr>
                <tr>
                    <th>โฮสต์</th>
                    <td>cpdth.com</td>
                </tr>
                <tr>
                    <th>ระยะเวลา</th>
                    <td>เซสชัน</td>
                </tr>
                <tr>
                    <th>หมวดหมู่</th>
                    <td>คุกกี้พื้นฐานที่จำเป็น</td>
                </tr>
                <tr>
                    <th>คำอธิบาย</th>
                    <td>รับรองความถูกต้องเปลี่ยนเส้นทาง</td>
                </tr>
            </table>
            <hr class="m-0" style="border-color: #e2e8f0;">
            <div style="background-color: #f1f5f9; padding-top: 10px; padding-bottom: 10px;">
                <table class="cookie-details-table">
                    <tr>
                        <th>ชื่อ</th>
                        <td>auth.strategy</td>
                    </tr>
                    <tr>
                        <th>โฮสต์</th>
                        <td>cpdth.com</td>
                    </tr>
                    <tr>
                        <th>ระยะเวลา</th>
                        <td>เซสชัน</td>
                    </tr>
                    <tr>
                        <th>หมวดหมู่</th>
                        <td>คุกกี้พื้นฐานที่จำเป็น</td>
                    </tr>
                    <tr>
                        <th>คำอธิบาย</th>
                        <td>รูปแบบการรับรองความถูกต้อง</td>
                    </tr>
                </table>
            </div>
        </div>

      </div>
      <div class="modal-footer border-0 pt-0 pb-4 px-4 justify-content-end bg-white">
        <button type="button" class="btn cookie-btn-primary px-4 py-2" onclick="acceptAllCookies()">ยอมรับทั้งหมด</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const consentGiven = document.cookie.indexOf('cpdth_cookie_consent=true') !== -1;

        if (!consentGiven) {
            // Show banner if no consent
            document.getElementById('cookie-consent-banner').style.display = 'block';
            document.getElementById('cookie-floating-icon').style.display = 'none';
        } else {
            // Show floating icon if already consented
            document.getElementById('cookie-consent-banner').style.display = 'none';
            document.getElementById('cookie-floating-icon').style.display = 'flex';
            // Hide the buttons in the banner
            const buttons = document.getElementById('cookie-banner-buttons');
            if(buttons) {
                buttons.classList.remove('d-flex');
                buttons.classList.add('d-none');
            }
        }

        // บังคับฟีซหน้าจอ (ล็อค Scrollbar) แบบ 100% เมื่อเปิด Modal
        const cookieModalEl = document.getElementById('cookieSettingsModal');
        if (cookieModalEl) {
            cookieModalEl.addEventListener('show.bs.modal', function () {
                document.documentElement.style.setProperty('overflow', 'hidden', 'important');
                document.body.style.setProperty('overflow', 'hidden', 'important');
            });
            cookieModalEl.addEventListener('hidden.bs.modal', function () {
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            });
        }
    });
function acceptAllCookies() {
    document.cookie = "cpdth_cookie_consent=true; path=/;";

    document.getElementById('cookie-consent-banner').style.display = 'none';

    const buttons = document.getElementById('cookie-banner-buttons');
    if (buttons) {
        buttons.classList.remove('d-flex');
        buttons.classList.add('d-none');
    }

    const settingsModalEl = document.getElementById('cookieSettingsModal');
    if (settingsModalEl) {
        const settingsModal = bootstrap.Modal.getInstance(settingsModalEl);
        if (settingsModal) {
            settingsModal.hide();
        }
    }

    document.getElementById('cookie-floating-icon').style.display = 'flex';
}

    function closeCookieBanner() {
        document.getElementById('cookie-consent-banner').style.display = 'none';
        document.getElementById('cookie-floating-icon').style.display = 'flex';
    }

function openCookieBanner() {
    document.getElementById('cookie-consent-banner').style.display = 'block';
    document.getElementById('cookie-floating-icon').style.display = 'none';

    const consentGiven = document.cookie.indexOf('cpdth_cookie_consent=true') !== -1;

    const buttons = document.getElementById('cookie-banner-buttons');
    if (buttons) {
        if (consentGiven) {
            buttons.classList.remove('d-flex');
            buttons.classList.add('d-none');
        } else {
            buttons.classList.remove('d-none');
            buttons.classList.add('d-flex');
        }
    }
}

    function openCookieSettingsModal() {
        // Hide banner
        document.getElementById('cookie-consent-banner').style.display = 'none';

        // Reset View
        document.getElementById('cookieOverviewView').style.display = 'block';
        document.getElementById('cookieDetailsView').style.display = 'none';
        document.getElementById('cookieSettingsModalLabel').innerText = "การตั้งค่าความเป็นส่วนตัว";

        // Check if consented to hide footer
        const consentGiven = document.cookie.indexOf('cpdth_cookie_consent=true') !== -1;
        const footer = document.querySelector('#cookieSettingsModal .modal-footer');
        if(footer) footer.style.display = consentGiven ? 'none' : '';

        // Show Modal
        const settingsModalEl = document.getElementById('cookieSettingsModal');
        let settingsModal = bootstrap.Modal.getInstance(settingsModalEl);
        if (!settingsModal) {
            settingsModal = new bootstrap.Modal(settingsModalEl);
        }
        settingsModal.show();
    }

    function closeCookieSettingsModal() {
        // When modal is closed via X button, show floating icon
        document.getElementById('cookie-floating-icon').style.display = 'flex';
    }

    function toggleCookieDetails() {
        const overview = document.getElementById('cookieOverviewView');
        const details = document.getElementById('cookieDetailsView');
        const label = document.getElementById('cookieSettingsModalLabel');
        const footer = document.querySelector('#cookieSettingsModal .modal-footer');
        const consentGiven = document.cookie.indexOf('cpdth_cookie_consent=true') !== -1;
        
        if (overview.style.display === 'none') {
            overview.style.display = 'block';
            details.style.display = 'none';
            // Show footer only if not yet consented
            if(footer) footer.style.display = consentGiven ? 'none' : '';
            label.innerHTML = "การตั้งค่าความเป็นส่วนตัว";
        } else {
            overview.style.display = 'none';
            details.style.display = 'block';
            if(footer) footer.style.display = 'none';
            label.innerHTML = '<span style="cursor:pointer;" onclick="toggleCookieDetails()"><i class="bi bi-chevron-left"></i> สรุปความยินยอมทั้งหมด</span>';
        }
    }
</script>
