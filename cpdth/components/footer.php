<footer class="footer">
    <div class="footer-main">
        <div class="container">
            <div class="row py-4 g-4">

                 <!-- Column 1: Logo + Company Info -->
                <div class="col-lg-3 col-md-6">
                    <a href="<?php echo $base_url ?? '' ?>index.php" class="d-inline-block mb-3">
                        <img src="<?php echo $base_url ?? '' ?>assets/images/logo/am-group-logo.png" alt="AM GROUP" height="52">
                    </a>
                    <div id="footerAboutUs" class="footer-desc">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </div>
                </div>

                <!-- Column 2: Social Media -->
                <div class="col-lg-3 col-md-6">
                    <ul class="list-unstyled footer-social">
                        <li id="footerSocialFacebook" style="display:none;">
                            <a href="#" id="footerLinkFacebook" class="footer-social-link" target="_blank">
                                <i class="bi bi-facebook"></i> Facebook
                            </a>
                        </li>
                        <li id="footerSocialX" style="display:none;">
                            <a href="#" id="footerLinkX" class="footer-social-link" target="_blank">
                                <i class="bi bi-twitter-x"></i> X (Twitter)
                            </a>
                        </li>
                        <li id="footerSocialLine" style="display:none;">
                            <a href="#" id="footerLinkLine" class="footer-social-link" target="_blank">
                                <i class="bi bi-line"></i> Line
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Links -->
                <div class="col-lg-3 col-md-6">
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?php echo $base_url ?? '' ?>lecturer.php">วิทยากร</a></li>
                        <li><a href="<?php echo $base_url ?? '' ?>user-guide.php">คู่มือการใช้งาน</a></li>
                        <li><a href="<?php echo $base_url ?? '' ?>contact.php">ติดต่อเรา</a></li>
                        <li><a href="<?php echo $base_url ?? '' ?>privacy-policy.php">เงื่อนไขบริการและนโยบายความเป็นส่วนตัว</a></li>
                        <li><a href="<?php echo $base_url ?? '' ?>refund-policy.php">เงื่อนไขการคืนเงิน</a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact Info -->
                <div class="col-lg-3 col-md-6">
                    <div id="footerContactUs" class="footer-contact-info" style="color: #cbd5e1; font-size: 0.95rem;">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer Bottom Bar -->
    <div class="footer-bottom">
        <div class="container text-center">
            <span><?php echo date('Y') ?> &mdash; <strong>CPDTH</strong></span>
        </div>
    </div>
</footer>

<?php include 'cookie-consent.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
<!-- Custom JS -->
<script src="<?php echo $base_url ?? '' ?>assets/js/main.js?v=1.0.2" defer></script>

<script>
window.addEventListener('load', function() {
    $.LoadingOverlay("hide");
    function renderFooter(s) {
        // About Us
        var fAbout = document.getElementById('footerAboutUs');
        if (fAbout) fAbout.innerHTML = s.about_us ? s.about_us : '';

        // Contact Us
        var fContact = document.getElementById('footerContactUs');
        if (fContact) fContact.innerHTML = s.contact_us ? s.contact_us : '';

        // Socials
        if (s.facebook_link) {
            var liFb = document.getElementById('footerSocialFacebook');
            if (liFb) { liFb.style.display = 'block'; document.getElementById('footerLinkFacebook').href = s.facebook_link; }
        }
        if (s.x_link) {
            var liX = document.getElementById('footerSocialX');
            if (liX) { liX.style.display = 'block'; document.getElementById('footerLinkX').href = s.x_link; }
        }
        if (s.line_link) {
            var liLine = document.getElementById('footerSocialLine');
            if (liLine) { liLine.style.display = 'block'; document.getElementById('footerLinkLine').href = s.line_link; }
        }
    }

    function clearFooterLoaders() {
        var fAbout = document.getElementById('footerAboutUs');
        if (fAbout) fAbout.innerHTML = '';
        var fContact = document.getElementById('footerContactUs');
        if (fContact) fContact.innerHTML = '';
    }

    // ดึงข้อมูลจากแคช sessionStorage ก่อน
    var cachedSetting = sessionStorage.getItem('website_settings');
    if (cachedSetting) {
        try {
            renderFooter(JSON.parse(cachedSetting));
        } catch (e) {
            console.error("Error parsing cached settings:", e);
        }
    }

    fetch('<?php echo $base_url ?? '' ?>core.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'request_state=setting&request_function=get_setting'
    })
    .then(function(res) { return res.json(); })
    .then(function(resp) {
        if (resp.result === 1 && resp.data && resp.data.setting) {
            var s = resp.data.setting;
            sessionStorage.setItem('website_settings', JSON.stringify(s));
            renderFooter(s);
        } else {
            if (!cachedSetting) clearFooterLoaders();
        }
    })
    .catch(function(err) {
        console.warn('Footer setting error:', err);
        if (!cachedSetting) clearFooterLoaders();
    });

});

// Remove inert when modal is about to show, enabling tab focus
$(document).on('show.bs.modal', '.modal', function () {
    this.removeAttribute('inert');
});

// Add inert back when modal is hidden, blocking tab focus
$(document).on('hidden.bs.modal', '.modal', function () {
    this.setAttribute('inert', '');
});
</script>
</body>
</html>
