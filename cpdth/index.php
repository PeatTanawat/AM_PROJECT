<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'หน้าหลัก | CPDTH';
$useSwiper = true;
include 'components/header.php';
?>

<!-- ============================================================
     Hero Section
     ============================================================ -->
<section class="hero-section">
    <div id="heroBannerCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner" id="heroBannerInner">
            <!-- placeholder แสดงก่อน banner โหลด -->
            <div class="carousel-item active" id="bannerPlaceholder">
                <div style="
                    width: 100%;
                    max-width: 2036px;
                    margin: 0 auto;
                    height: 500px;
                    background: linear-gradient(135deg, #1a3c6e 0%, #2563b0 50%, #1a3c6e 100%);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                ">
                    <div style="width:60px; height:60px; border:4px solid rgba(255,255,255,0.4); border-top-color:#fff; border-radius:50%; animation:spin .8s linear infinite;"></div>
                    <span style="color:rgba(255,255,255,0.7); font-size:14px;">กำลังโหลด...</span>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev hero-carousel-btn" type="button" data-bs-target="#heroBannerCarousel"
            data-bs-slide="prev" id="heroBannerPrev" style="display: none;">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="carousel-control-next hero-carousel-btn" type="button" data-bs-target="#heroBannerCarousel"
            data-bs-slide="next" id="heroBannerNext" style="display: none;">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</section>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* ล็อคขนาดรูปแบนเนอร์ไม่ให้ใหญ่เกินไปและไม่เพี้ยน (2036x500) */
.hero-banner-img {
    width: 100%;
    /* max-width: 2036px; */
    /* height: 500px; ป้องกันหน้ากระโดดตอนโหลดรูปเสร็จ */
    /* object-fit: cover; */
    object-position: center;
    display: block;
    margin: 0 auto;
}

/* Fix for swiper cutting off shadows and bottom of cards */
.promo-swiper {
    padding: 16px !important;
    margin: -16px !important;
}

/* New Course Card Design */
.new-design.course-card {
    font-feature-settings: normal;
    font-variation-settings: normal;
    -webkit-text-size-adjust: 100%;
    tab-size: 4;
    word-break: normal;
    font-size: 16px;
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
    -webkit-tap-highlight-color: rgba(0,0,0,0);
    font-family: "Kanit",sans-serif;
    line-height: 1;
    box-sizing: inherit;
    padding: 0;
    max-width: 100%;
    outline: none;
    text-decoration: none;
    word-wrap: break-word;
    position: relative;
    white-space: normal;
    box-shadow: 0 3px 1px -2px rgba(0,0,0,.2),0 2px 2px 0 rgba(0,0,0,.14),0 1px 5px 0 rgba(0,0,0,.12)!important;
    transition: .3s cubic-bezier(.25,.8,.5,1)!important;
    overflow: hidden!important;
    display: flex!important;
    flex-direction: column!important;
    margin: 8px auto!important;
    border-radius: 16px!important;
    background-color: #fff;
    color: rgba(0,0,0,.87);
    width: 350px;
    max-width: 100%;
    height: 100%;
    cursor: pointer;
    border: 1px solid rgb(240, 240, 240);
}
.new-design.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}
.new-design .course-card-img-wrap {
    width: 100%;
    position: relative;
    background: #f8fafc;
}
.new-design .course-thumb-img {
    width: 100%;
    height: auto;
    aspect-ratio: 16/9;
    object-fit: contain;
    background: #0f172a;
}
.new-design .course-card-body {
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.new-design .badge-tag {
    background: #e0f2fe;
    color: #0284c7;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 12px;
    border-radius: 20px;
    display: inline-block;
    align-self: flex-start;
    margin-bottom: 6px;
}
.new-design .course-title {
    font-size: 1rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 6px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.6em;
}
.new-design .course-instructor {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 0.8rem;
    margin-bottom: 8px;
}
.new-design .instructor-avatar {
    width: 24px;
    height: 24px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 1rem;
}
.new-design .hours-table {
    background: #f8fafc;
    border-radius: 12px;
    padding: 6px 12px;
    margin-bottom: 12px;
}
.new-design .ht-col {
    font-size: 0.8rem;
    padding: 4px;
    color: #334155;
    font-weight: 700;
}
.new-design .ht-header {
    border-bottom: 1px solid #e2e8f0;
}
.new-design .ht-header .ht-col {
    color: #64748b;
}
.new-design .cpd-text { color: #1d4ed8 !important; font-weight: 800; }
.new-design .cpa-text { color: #047857 !important; font-weight: 800; }
.new-design .ht-val {
    font-weight: 800;
    color: #0f172a;
}
.new-design .course-footer {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: auto;
}
.new-design .price-box {
    display: flex;
    flex-direction: column;
}
.new-design .price-original {
    font-size: 0.85rem;
    color: #94a3b8;
    text-decoration: line-through;
    line-height: 1;
    margin-bottom: 4px;
}
.new-design .price-current {
    font-size: 1.6rem;
    font-weight: 800;
    color: #ff5722;
    line-height: 1;
}
.new-design .price-current span {
    font-size: 1.2rem;
}
.new-design .btn-add-cart-new {
    background: #3b5998;
    color: #fff;
    border: none;
    border-radius: 24px;
    padding: 8px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.new-design .btn-add-cart-new:hover {
    background: #2d4373;
    color: #fff;
}
/* Prevent image download */
img {
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
    user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
    pointer-events: none;
}
</style>


<!-- ============================================================
     โปรโมชั่นวันนี้
     ============================================================ -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">โปรโมชั่นวันนี้</h2>
        <div class="promo-slider-wrap" id="promoSliderWrap">
            <!-- Swiper -->
            <div class="swiper promo-swiper" id="promoSwiper">
                <div class="swiper-wrapper" id="promoSwiperWrapper">
                    <!-- skeleton placeholders -->
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="swiper-slide promo-skeleton-slide">
                        <div class="course-card">
                            <div class="course-card-img-wrap">
                                <div class="course-thumb-placeholder skeleton-pulse"></div>
                            </div>
                            <div class="course-card-body">
                                <div class="skeleton-line" style="width:90%;height:14px;margin-bottom:8px;"></div>
                                <div class="skeleton-line" style="width:70%;height:12px;margin-bottom:4px;"></div>
                                <div class="skeleton-line" style="width:50%;height:12px;margin-bottom:12px;"></div>
                                <div class="skeleton-line" style="width:40%;height:20px;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <!-- Nav buttons -->
            <button class="promo-nav promo-nav-prev" aria-label="ก่อนหน้า"><i class="bi bi-chevron-left"></i></button>
            <button class="promo-nav promo-nav-next" aria-label="ถัดไป"><i class="bi bi-chevron-right"></i></button>
        </div>
        <!-- กรณีไม่มีโปรโมชั่น -->
        <div id="promoEmpty" style="display:none;text-align:center;padding:40px 0;color:#888;">
            <i class="bi bi-tag" style="font-size:2rem;"></i>
            <p class="mt-2">ยังไม่มีคอร์สโปรโมชั่นในขณะนี้</p>
        </div>
    </div>
</section>

<script>
    var promoSwiperInstance = null;
</script>

<!-- ============================================================
     Features
     ============================================================ -->
<section class="features-section py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <i class="bi bi-search feature-icon"></i>
                <h5 class="feature-title">อบรมได้ทุกที่ทุกเวลา</h5>
                <p class="feature-desc">สามารถเรียน 24 ชั่วโมง และ 7 วัน โดยแสดงผลตรงกับความต้องการ</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-display feature-icon"></i>
                <h5 class="feature-title">บรรยายโดยผู้เชี่ยวชาญ และมีประสบการณ์</h5>
                <p class="feature-desc">ทุกหลักสูตรบรรยายโดยผู้มีความรู้และมีประสบการณ์ตรงด้านวิชาชีพบัญชี</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-phone feature-icon"></i>
                <h5 class="feature-title">รองรับการอบรมผ่านอุปกรณ์ครบระบบ</h5>
                <p class="feature-desc">รองรับทั้ง PC และ Tablet อีกทั้งยังรองรับ 100% บน iOS และ Android ผ่าน Browser
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     Reviews Section
     ============================================================ -->
<?php include 'view/review/review_slide.php'; ?>



<?php if (!isset($currentUser) || !$currentUser): ?>
<!-- ============================================================
     CTA Banner
     ============================================================ -->

<section class="cta-banner-section py-2">
    <div class="container">
        <div class="cta-banner">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <p class="cta-banner-text"><i class="bi bi-mortarboard-fill me-2"></i>อบรม CPD CPA แบบ e-Learning</p>
                <a href="register.php" class="btn btn-cta">ลงทะเบียน</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     Course Info + How-to Video
     ============================================================ -->
<section class="py-5">
    <div class="container" id="settingContent1">
        <div class="row g-5 align-items-start">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">กำลังโหลดเนื้อหา...</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     ID Card Guide + Provider Info
     ============================================================ -->
<section class="py-5 bg-light">
    <div class="container" id="settingContent2">
        <div class="row g-5">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">กำลังโหลดเนื้อหา...</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     คอร์สเรียนแนะนำ
     ============================================================ -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">คอร์สเรียนแนะนำ</h2>
        <div class="row g-3" id="recommendedGrid">
            <!-- skeleton -->
            <?php for ($i = 0; $i < 8; $i++): ?>
            <div class="col-lg-3 col-md-6 col-sm-6 rec-skeleton-col">
                <div class="course-card">
                    <div class="course-card-img-wrap">
                        <div class="course-thumb-placeholder skeleton-pulse"></div>
                    </div>
                    <div class="course-card-body">
                        <div class="skeleton-line" style="width:90%;height:14px;margin-bottom:8px;"></div>
                        <div class="skeleton-line" style="width:70%;height:12px;margin-bottom:4px;"></div>
                        <div class="skeleton-line" style="width:50%;height:12px;margin-bottom:12px;"></div>
                        <div class="skeleton-line" style="width:40%;height:20px;"></div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <div id="recommendedEmpty" style="display:none;text-align:center;padding:40px 0;color:#888;">
            <i class="bi bi-book" style="font-size:2rem;"></i>
            <p class="mt-2">ยังไม่มีคอร์สแนะนำในขณะนี้</p>
        </div>
    </div>
</section>



<?php include 'components/footer.php'; ?>

<script>
/* =================================================================
   โหลด Course สำหรับหน้า Index จาก API
   ================================================================= */
(function () {
    window.indexCourseRegistry = window.indexCourseRegistry || {};

    window.goToCourseDetailFromIndex = function(encryptedId) {
        try {
            var courseData = window.indexCourseRegistry[encryptedId];
            if (courseData) {
                sessionStorage.setItem('course_detail_' + encryptedId, JSON.stringify(courseData));
            }
        } catch(e) {
            console.error("Error writing sessionStorage:", e);
        }
        window.location.href = 'categories.php?key=' + encodeURIComponent(encryptedId);
    };

    /**
     * สร้าง HTML ของ course-card
     * @param {Object} c   - ข้อมูล course จาก API
     * @param {string} wrapClass  - CSS class ของ wrapper ('' หรือ 'col-lg-3 col-md-6 col-sm-6')
     */
    function buildCourseCard(c, wrapClass) {
        if (c && c.encrypted_id) {
            window.indexCourseRegistry[c.encrypted_id] = c;
        }

        var img = c.course_cover_image 
            ? '<img src="' + c.course_cover_image + '" alt="' + escHtml(c.course_name) + '" class="course-thumb-img" onerror="this.style.display=\'none\';">' 
            : '<span style="color:#94a3b8;">No Image</span>';

        var oldPriceHtml = c.old_price_fmt
            ? '<div class="price-original">' + escHtml(c.old_price_fmt) + ' ฿</div>'
            : '<div class="price-original">&nbsp;</div>';

        var instructor = c.course_instructor
            ? '<div class="course-instructor"><div class="instructor-avatar"><i class="bi bi-person-fill"></i></div><span>' + escHtml(c.course_instructor) + '</span></div>'
            : '<div class="course-instructor"><div class="instructor-avatar"><i class="bi bi-person-fill"></i></div><span>ชื่อวิทยากร</span></div>';

        var cpdAccount = c.course_cpd_hour ? parseFloat(c.course_cpd_hour).toFixed(2) : '0.00';
        var cpdEthics = c.course_cpd_ethics ? parseFloat(c.course_cpd_ethics).toFixed(2) : '0.00';
        var cpdOther = c.course_cpd_other ? parseFloat(c.course_cpd_other).toFixed(2) : '0.00';
        
        var cpaAccount = c.course_cpa_hour ? parseFloat(c.course_cpa_hour).toFixed(2) : '0.00';
        var cpaEthics = c.course_cpa_ethics ? parseFloat(c.course_cpa_ethics).toFixed(2) : '0.00';
        var cpaOther = c.course_cpa_other ? parseFloat(c.course_cpa_other).toFixed(2) : '0.00';

        var cardHtml = '<div class="course-card new-design" onclick="goToCourseDetailFromIndex(\'' + c.encrypted_id + '\')">'
            + '<div class="course-card-img-wrap">'
            + img
            + '</div>'
            + '<div class="course-card-body">'
            + '<div class="badge-tag">เก็บชั่วโมง</div>'
            + '<h5 class="course-title">' + escHtml(c.course_name) + '</h5>'
            + instructor
            + '<div class="hours-table">'
            + '  <div class="row m-0 ht-header">'
            + '    <div class="col-6 ht-col">ประเภท</div>'
            + '    <div class="col-3 ht-col text-center cpd-text">CPD</div>'
            + '    <div class="col-3 ht-col text-center cpa-text">CPA</div>'
            + '  </div>'
            + '  <div class="row m-0 ht-row">'
            + '    <div class="col-6 ht-col">การบัญชี</div>'
            + '    <div class="col-3 ht-col text-center ht-val">' + cpdAccount + '</div>'
            + '    <div class="col-3 ht-col text-center ht-val">' + cpaAccount + '</div>'
            + '  </div>'
            + '  <div class="row m-0 ht-row">'
            + '    <div class="col-6 ht-col">จรรยาบรรณ</div>'
            + '    <div class="col-3 ht-col text-center ht-val">' + cpdEthics + '</div>'
            + '    <div class="col-3 ht-col text-center ht-val">' + cpaEthics + '</div>'
            + '  </div>'
            + '  <div class="row m-0 ht-row">'
            + '    <div class="col-6 ht-col">อื่นๆ</div>'
            + '    <div class="col-3 ht-col text-center ht-val">' + cpdOther + '</div>'
            + '    <div class="col-3 ht-col text-center ht-val">' + cpaOther + '</div>'
            + '  </div>'
            + '</div>'
            + '<div class="course-footer">'
            + '  <div class="price-box">'
            +      oldPriceHtml
            + '    <div class="price-current">' + escHtml(c.final_price_fmt) + ' <span>฿</span></div>'
            + '  </div>'
            + '  <button class="btn-add-cart-new" type="button" onclick="event.stopPropagation(); addCart(' + parseInt(c.course_id) + ')">'
            + '    <i class="bi bi-cart3"></i> เพิ่มเข้าตะกร้า'
            + '  </button>'
            + '</div>'
            + '</div>'
            + '</div>';

        if (wrapClass) {
            return '<div class="' + wrapClass + '">' + cardHtml + '</div>';
        }
        return cardHtml;
    }

    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ─── Init Promo Swiper ─── */
    function initPromoSwiper() {
        if (window.promoSwiperInstance) {
            window.promoSwiperInstance.destroy(true, true);
        }
        window.promoSwiperInstance = new Swiper('.promo-swiper', {
            slidesPerView: 1.15,
            spaceBetween: 12,
            grabCursor: true,
            navigation: {
                prevEl: '.promo-nav-prev',
                nextEl: '.promo-nav-next',
            },
            breakpoints: {
                576: { slidesPerView: 2, spaceBetween: 16 },
                768: { slidesPerView: 2.5, spaceBetween: 16 },
                992: { slidesPerView: 3, spaceBetween: 20 },
                1200: { slidesPerView: 4, spaceBetween: 20 },
            }
        });
    }

    /* ─── Fetch All Home Page Data ─── */
    window.addEventListener('load', function () {
        fetch('core.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_state=index&request_function=get_index_data'
        })
        .then(function (res) { return res.json(); })
        .then(function (resp) {
            /* ── 1. BANNERS ── */
            var inner      = document.getElementById('heroBannerInner');
            var carouselEl = document.getElementById('heroBannerCarousel');

            if (inner && carouselEl) {
                if (resp.result !== 1 || !resp.data || !resp.data.banners || resp.data.banners.length === 0) {
                    inner.innerHTML = [
                        '<div class="carousel-item active">',
                        '  <div style="width:100%; max-width:2036px; margin:0 auto; height:500px; background:linear-gradient(135deg,#1a3c6e,#2563b0);',
                        '       display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;">',
                        '    <i class="bi bi-image" style="font-size:56px;color:rgba(255,255,255,0.4);"></i>',
                        '    <p style="color:rgba(255,255,255,0.6);margin:0;font-size:15px;">ยังไม่มีแบนเนอร์ในระบบ</p>',
                        '  </div>',
                        '</div>'
                    ].join('');
                } else {
                    var banners = resp.data.banners;
                    var existingCarousel = bootstrap.Carousel.getInstance(carouselEl);
                    if (existingCarousel) existingCarousel.dispose();
                    inner.innerHTML = '';

                    banners.forEach(function (b, i) {
                        var item = document.createElement('div');
                        item.className = 'carousel-item' + (i === 0 ? ' active' : '');

                        var imgEl       = document.createElement('img');
                        imgEl.src       = b.banner_image;
                        imgEl.alt       = 'Banner ' + b.banner_order;
                        imgEl.className = 'hero-banner-img';

                        imgEl.onerror = function () {
                            item.innerHTML = [
                                '<div style="width:100%; max-width:2036px; margin:0 auto; height:500px; background:linear-gradient(135deg,#1a3c6e,#2563b0);',
                                '     display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;">',
                                '  <i class="bi bi-image-alt" style="font-size:48px;color:rgba(255,255,255,0.35);"></i>',
                                '  <span style="color:rgba(255,255,255,0.5);font-size:13px;">ไม่พบไฟล์รูปภาพ</span>',
                                '</div>'
                            ].join('');
                        };

                        if (b.has_link && b.banner_url) {
                            var aEl = document.createElement('a');
                            aEl.href = b.banner_url;
                            if (b.link_target === '_blank') {
                                aEl.target = '_blank';
                                aEl.rel = 'noopener noreferrer';
                            }
                            aEl.appendChild(imgEl);
                            item.appendChild(aEl);
                        } else {
                            item.appendChild(imgEl);
                        }

                        inner.appendChild(item);
                    });

                    new bootstrap.Carousel(carouselEl, { interval: 5000, ride: 'carousel' });
                    
                    if (banners.length > 1) {
                        var prevBtn = document.getElementById('heroBannerPrev');
                        var nextBtn = document.getElementById('heroBannerNext');
                        if(prevBtn) prevBtn.style.display = '';
                        if(nextBtn) nextBtn.style.display = '';
                    }
                }
            }

            /* ── 2. PROMO COURSES ── */
            var promoWrapper = document.getElementById('promoSwiperWrapper');
            var promoEmpty   = document.getElementById('promoEmpty');
            var promoWrap    = document.getElementById('promoSliderWrap');

            if (resp.result === 1 && resp.data && resp.data.promo && resp.data.promo.length > 0) {
                var promoHtml = '';
                resp.data.promo.forEach(function (c) {
                    promoHtml += '<div class="swiper-slide">' + buildCourseCard(c, '') + '</div>';
                });
                if (promoWrapper) promoWrapper.innerHTML = promoHtml;
                initPromoSwiper();
            } else {
                if (promoWrap) promoWrap.style.display = 'none';
                if (promoEmpty) promoEmpty.style.display = 'block';
            }

            /* ── 3. RECOMMENDED COURSES ── */
            var recGrid  = document.getElementById('recommendedGrid');
            var recEmpty = document.getElementById('recommendedEmpty');

            if (resp.result === 1 && resp.data && resp.data.recommended && resp.data.recommended.length > 0) {
                var recHtml = '';
                resp.data.recommended.forEach(function (c) {
                    recHtml += buildCourseCard(c, 'col-lg-3 col-md-6 col-sm-6');
                });
                if (recGrid) recGrid.innerHTML = recHtml;
            } else {
                if (recGrid) recGrid.style.display = 'none';
                if (recEmpty) recEmpty.style.display = 'block';
            }

            /* ── 4. WEBSITE SETTINGS ── */
            var c1 = document.getElementById('settingContent1');
            var c2 = document.getElementById('settingContent2');

            if (c1 && c2) {
                if (resp.result === 1 && resp.data && resp.data.setting) {
                    var s = resp.data.setting;
                    
                    // Section 1: Course Info (text_1) + YouTube (youtube_id)
                    var html1 = '<div class="row g-5 align-items-start">';
                    if (s.text_1 || s.youtube_id) {
                        html1 += '<div class="col-lg-6">';
                        html1 += s.text_1 ? s.text_1 : '<p class="text-muted">ยังไม่มีเนื้อหา</p>';
                        html1 += '</div>';
                        
                        html1 += '<div class="col-lg-6">';
                        if (s.youtube_id) {
                            html1 += '<div class="video-embed-wrap"><iframe src="https://www.youtube.com/embed/' + escHtml(s.youtube_id) + '" frameborder="0" allowfullscreen></iframe></div>';
                        } else {
                            html1 += '<div class="p-5 text-center bg-light border rounded text-muted">ยังไม่มีวิดีโอแนะนำ</div>';
                        }
                        html1 += '</div>';
                    } else {
                        html1 += '<div class="col-12 text-center text-muted py-4">ยังไม่มีเนื้อหา</div>';
                    }
                    html1 += '</div>';
                    c1.innerHTML = html1;

                    // Section 2: Image (image_path) + Provider Info (text_2)
                    var html2 = '<div class="row g-5">';
                    if (s.text_2 || s.image_path) {
                        html2 += '<div class="col-lg-6">';
                        if (s.image_path) {
                            html2 += '<img src="' + escHtml(s.image_path) + '" class="img-fluid rounded shadow-sm" alt="Website Image" onerror="this.style.display=\'none\';">';
                        } else {
                            html2 += '<div class="p-5 text-center bg-white border rounded text-muted">ยังไม่มีรูปภาพ</div>';
                        }
                        html2 += '</div>';

                        html2 += '<div class="col-lg-6">';
                        html2 += s.text_2 ? s.text_2 : '<p class="text-muted">ยังไม่มีเนื้อหา</p>';
                        html2 += '</div>';
                    } else {
                        html2 += '<div class="col-12 text-center text-muted py-4">ยังไม่มีเนื้อหา</div>';
                    }
                    html2 += '</div>';
                    c2.innerHTML = html2;
                } else {
                    c1.innerHTML = '<div class="text-center text-muted py-4">ยังไม่มีเนื้อหา</div>';
                    c2.innerHTML = '<div class="text-center text-muted py-4">ยังไม่มีเนื้อหา</div>';
                }
            }
        })
        .catch(function (err) {
            console.warn('Home page data load error:', err);
            initPromoSwiper();
            var inner = document.getElementById('heroBannerInner');
            if (inner) {
                inner.innerHTML = [
                    '<div class="carousel-item active">',
                    '  <div style="width:100%; max-width:2036px; margin:0 auto; height:500px; background:linear-gradient(135deg,#1a3c6e,#2563b0);',
                    '       display:flex;align-items:center;justify-content:center;">',
                    '    <i class="bi bi-wifi-off" style="font-size:56px;color:rgba(255,255,255,0.35);"></i>',
                    '  </div>',
                    '</div>'
                ].join('');
            }
            var c1 = document.getElementById('settingContent1');
            var c2 = document.getElementById('settingContent2');
            if (c1) c1.innerHTML = '<div class="text-center text-danger py-4">โหลดข้อมูลไม่สำเร็จ</div>';
            if (c2) c2.innerHTML = '<div class="text-center text-danger py-4">โหลดข้อมูลไม่สำเร็จ</div>';
        });
    });

    // ตรวจสอบสถานะวันหมดอายุของบัตรประชาชนเมื่อเข้าหน้าเว็บหลังจากล็อกอิน
    window.addEventListener('load', function() {
        if (localStorage.getItem('show_id_card_expired_toast') === '1') {
            localStorage.removeItem('show_id_card_expired_toast');
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'warning',
                title: 'บัตรประชาชนของคุณหมดอายุแล้ว กรุณาอัปเดตข้อมูล',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        }

        // ตรวจสอบสถานะวันหมดอายุของคอร์สเรียนเมื่อเข้าหน้าเว็บหลังจากล็อกอิน
        const courseExpiredData = localStorage.getItem('show_course_expired_toast');
        if (courseExpiredData) {
            localStorage.removeItem('show_course_expired_toast');
            try {
                const expiringCourses = JSON.parse(courseExpiredData);
                if (Array.isArray(expiringCourses) && expiringCourses.length > 0) {
                    const msg = 'คุณมีคอร์สเรียนใกล้หมดอายุ จำนวน ' + expiringCourses.length + ' คอร์ส';
                    Swal.fire({
                        toast: true,
                        position: 'top',
                        icon: 'warning',
                        title: msg,
                        showConfirmButton: false,
                        timer: 6000,
                        timerProgressBar: true
                    });
                }
            } catch (e) {
                console.error("Error parsing expiring courses toast", e);
            }
        }
    });

})();
</script>