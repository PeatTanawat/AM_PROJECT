<?php
    $pageTitle = 'หมวดหมู่คอร์สเรียน';
    include 'components/header.php';
?>


<style>
    body {
        font-family: 'Kanit', sans-serif;
        background-color: #ffffff;
        color: rgba(0,0,0,.87);
    }

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

    @media (max-width: 768px) {
        .page-header { padding: 60px 20px !important; }
        .page-title { font-size: 1.8rem !important; }
    }

    .layout-container { display: flex; max-width: 1185px; margin: 40px auto; gap: 30px; padding: 12px; align-items: flex-start; width: 100%; box-sizing: border-box; }
    .sidebar { width: 280px; min-width: 280px; flex-shrink: 0; }
    .search-box { position: relative; margin-bottom: 20px; }
    .search-input { width: 100%; padding: 12px 40px 12px 20px; border-radius: 30px; border: 1px solid #e2e8f0; background-color: #f8fafc; font-family: 'Kanit', sans-serif; font-size: 0.95rem; outline: none; box-sizing: border-box; }
    .search-input::placeholder { color: #94a3b8; }
    .search-input:focus { border-color: #3b5998; }
    .search-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; }

    .category-widget { border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; overflow: hidden; width: 100%; }
    .category-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; font-weight: 600; color: #3b5998; border-bottom: 1px solid #e2e8f0; cursor: pointer; user-select: none; }
    .category-header svg { transition: transform 0.3s ease; }
    .category-widget.collapsed .category-header svg { transform: rotate(180deg); }
    .category-list { list-style: none; padding: 0; margin: 0; max-height: 500px; overflow: hidden; transition: max-height 0.3s ease-in-out; }
    .category-widget.collapsed .category-list { max-height: 0; }
    .category-list li { padding: 15px 20px; font-size: 0.9rem; color: #1f2937; cursor: pointer; transition: background-color 0.2s, color 0.2s; border-bottom: 1px solid #f8fafc; }
    .category-list li:last-child { border-bottom: none; }
    .category-list li:hover { background-color: #f8fafc; }
    .category-list li.active { background-color: #e6edf5; color: #2d4373; font-weight: 500; }

    .main-content { flex: 1; min-width: 0; }
    .course-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: stretch; }

    .course-card { background: #fff; display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 8px; height: 100%; transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); }
    .course-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }

    .card-header { width: 100%; height: 200px; overflow: hidden; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; }
    .card-header img { width: 100%; height: 100%; object-fit: cover; }

    .card-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
    .course-title { font-weight: 600; font-size: 0.95rem; line-height: 1.5; color: #1f2937; margin-bottom: 15px; }
    .course-details { font-size: 0.8rem; color: #64748b; margin-bottom: 15px; line-height: 1.8; flex-grow: 1; }
    .course-details strong { color: #475569; }
    .course-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: stretch; justify-items: center; }

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
        max-width: 350px;
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
        margin: 0 auto!important;
        border-radius: 16px!important;
        background-color: #fff;
        color: rgba(0,0,0,.87);
        width: 100%;
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
    .no-data {
        text-align: center;
        padding: 40px;
        color: #64748b;
        font-size: 1.1rem;
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
        gap: 8px;
    }
    .new-design .price-box {
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
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
        display: flex;
        align-items: baseline;
        gap: 4px;
    }
    .new-design .price-current span {
        font-size: 1.2rem;
    }
    .new-design .btn-add-cart-new {
        background: #3b5998;
        color: #fff;
        border: none;
        border-radius: 24px;
        padding: 8px 16px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
        flex-shrink: 0;
        white-space: nowrap;
    }
    .new-design .btn-add-cart-new:hover {
        background: #2d4373;
        color: #fff;
    }

    .btn-add-cart { display: flex; justify-content: center; align-items: center; gap: 8px; background-color: #3b5998; color: #fff; border: none; padding: 12px; border-radius: 4px; width: 100%; font-family: 'Kanit', sans-serif; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
    .btn-add-cart:hover { background-color: #2d4373; }

    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 50px; }
    .page-btn { display: flex; justify-content: center; align-items: center; width: 35px; height: 35px; border: 1px solid #e2e8f0; border-radius: 4px; background: #fff; color: #4b5563; text-decoration: none; font-size: 0.9rem; }
    .page-btn.active { background-color: #3b5998; color: #fff; border-color: #3b5998; }

    @media (max-width: 992px) {
        .layout-container { flex-direction: column; }
        .sidebar { width: 100%; }
        .course-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .course-grid { grid-template-columns: 1fr; }
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="page-header" style="position: relative;">
    <h1 class="page-title">คอร์สเรียนทั้งหมด</h1>
</div>
<div id="backgroundLoader" style="display: none; position: fixed; bottom: 20px; right: 20px; align-items: center; justify-content: center; z-index: 9999; background: #fff; padding: 10px; border-radius: 50%; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="width: 24px; height: 24px; border: 3px solid rgba(59, 89, 152, 0.2); border-top-color: #3b5998; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
</div>

<main class="layout-container" id="listView">

    <aside class="sidebar">
        <div class="search-box">
            <input type="text" class="search-input" placeholder="ค้นหาคอร์สเรียน">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>

        <div class="category-widget" id="categoryWidget">
            <div class="category-header" id="categoryToggleBtn">
                หมวดหมู่
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
            </div>
            <ul class="category-list">
            </ul>
        </div>
    </aside>

    <section class="main-content">
        <div class="course-grid">
        </div>

        <div class="pagination">
            <a href="#" class="page-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </a>
            <a href="#" class="page-btn active">1</a>
            <a href="#" class="page-btn">2</a>
            <a href="#" class="page-btn">3</a>
            <a href="#" class="page-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
    </section>
</main>

<main class="layout-container" id="detailView" style="display: none; align-items: flex-start; gap: 30px;">
    <!-- Main content (left) -->
    <section class="detail-content" style="flex: 1; min-width: 0; background: #fff; padding: 30px;">
        <h2 id="detailTitle" style="color:#3b5998; font-size: 1.4rem; font-weight: 600; margin-bottom: 5px;"></h2>
        <div id="detailInstructor" style="color:#64748b; font-size: 0.9rem; margin-bottom: 20px;"></div>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
        <div id="detailHtml" class="course-html-content" style="color: #475569; font-size: 0.95rem; line-height: 1.8;"></div>
    </section>

    <!-- Sidebar (right) -->
    <aside class="detail-sidebar" style="width: 350px; min-width: 350px; flex-shrink: 0;">
        <div class="detail-card" style="border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div id="detailCover" style="margin-bottom: 20px; text-align: center; border-radius: 8px; overflow: hidden; background: #f8f9fa;"></div>
            <div style="font-size: 0.85rem; color: #94a3b8; text-align: right; margin-bottom: 5px;">หมวดหมู่</div>
            <div id="detailGroup" style="font-size: 0.95rem; color: #475569; text-align: right; margin-bottom: 15px;"></div>

            <div id="detailTitleRight" style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin-bottom: 25px; line-height: 1.4;"></div>

            <div style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 5px;">รหัสหลักสูตร</div>
            <div id="detailCode" style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px; line-height: 1.8;"></div>

            <div style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 5px;">การนับชั่วโมง</div>
            <div id="detailHours" style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px; line-height: 1.8;"></div>

            <div style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 5px;">ระยะเวลาการอบรม</div>
            <div id="detailPeriod" style="font-size: 0.85rem; color: #64748b; margin-bottom: 30px;"></div>

            <div id="detailPriceContainer" style="text-align: center; margin-bottom: 20px;"></div>

            <button class="btn-add-cart" type="button" onclick="addCart()" style="width: 100%; padding: 14px; font-size: 1.05rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                เพิ่มเข้าตะกร้า
            </button>
        </div>
    </aside>
</main>



<script>
    const groupList = document.querySelector('.category-list');
    const courseGrid = document.querySelector('.course-grid');
    const paginationContainer = document.querySelector('.pagination');
    let currentGroupId = new URLSearchParams(window.location.search).get('group_id') || 0;
    let currentPage = new URLSearchParams(window.location.search).get('page') || 1;
    let currentCourseKey = new URLSearchParams(window.location.search).get('key') || '';

    let lastLoadedParams = "";
    let lastLoadedCourseKey = "";

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('categoryToggleBtn');
        const widget = document.getElementById('categoryWidget');

        toggleBtn.addEventListener('click', function() {
            widget.classList.toggle('collapsed');
        });

        checkView();

        // Register popstate listener after initial load to prevent duplicate trigger on some browsers
        // 500ms is a safe threshold to ensure initial AJAX calls have already started
        setTimeout(function() {
            window.addEventListener('popstate', function() {
                currentGroupId = new URLSearchParams(window.location.search).get('group_id') || 0;
                currentPage = new URLSearchParams(window.location.search).get('page') || 1;
                currentCourseKey = new URLSearchParams(window.location.search).get('key') || '';
                // Reset guards so navigation always reloads data correctly
                lastLoadedParams = "";
                lastLoadedCourseKey = "";
                checkView();
            });
        }, 500);
    });

    function checkView() {
        if (currentCourseKey) {
            document.getElementById('listView').style.display = 'none';
            document.getElementById('detailView').style.display = 'flex';
            document.querySelector('.page-header').style.display = 'none';
            loadCourseDetail();
        } else {
            document.getElementById('listView').style.display = 'flex';
            document.getElementById('detailView').style.display = 'none';
            document.querySelector('.page-header').style.display = 'block';
            document.querySelector('.page-title').innerText = 'คอร์สเรียนทั้งหมด';
            $('#backgroundLoader').hide();
            loadCourses();
        }
    }

    function goBackToList() {
        currentCourseKey = '';
        updateUrl();
        checkView();
    }

    function goToDetail(key) {
        currentCourseKey = key;
        const cardEl = document.querySelector(`.course-card[data-key="${key}"]`);
        if (cardEl) {
            try {
                const courseData = JSON.parse(cardEl.getAttribute('data-course'));
                if (courseData) {
                    sessionStorage.setItem('course_detail_' + key, JSON.stringify(courseData));
                }
            } catch(e) {
                console.error("Error caching course data:", e);
            }
        }
        const url = new URL(window.location);
        url.searchParams.set('key', key);
        window.history.pushState({}, '', url);
        checkView();
    }

    function renderDetailHtml(courseData) {
        $.ajax({
            type: "POST",
            url: "view/category/course_detail.php",
            data: JSON.stringify(courseData),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function(responseHtml) {
                document.getElementById('detailView').innerHTML = responseHtml;
            }
        });
    }

    function fetchDetailData(key, showLoader) {
        $.ajax({
            beforeSend: function() {
                if (showLoader) {
                    Swal.fire({
                        title: 'กำลังโหลดข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                } else {
                    $('#backgroundLoader').css('display', 'inline-flex');
                }
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "course",
                request_function: "detail",
                id: key
            },
            dataType: "json",
            success: function (response) {
                if (showLoader) {
                    Swal.close();
                } else {
                    $('#backgroundLoader').hide();
                }
                let isSuccess = (response.result == 1 || response.status == 1);
                if (isSuccess) {
                    let c = response.data;
                    try {
                        sessionStorage.setItem('course_detail_' + key, JSON.stringify(c));
                    } catch(e) {
                        console.error("Error writing cache:", e);
                    }
                    renderDetailHtml(c);
                } else {
                    if (showLoader) {
                        Swal.fire('แจ้งเตือน', response.msg || 'ไม่สามารถโหลดข้อมูลได้', 'warning').then(() => {
                            currentCourseKey = '';
                            updateUrl();
                            checkView();
                        });
                    } else {
                        $('#backgroundLoader').hide();
                    }
                }
            },
            error: function(err) {
                if (showLoader) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                } else {
                    $('#backgroundLoader').hide();
                }
            }
        });
    }

    function loadCourseDetail() {
        if (lastLoadedCourseKey === currentCourseKey) {
            return;
        }
        lastLoadedCourseKey = currentCourseKey;

        const cacheKey = 'course_detail_' + currentCourseKey;
        let cachedData = null;
        try {
            const cachedStr = sessionStorage.getItem(cacheKey);
            if (cachedStr) {
                cachedData = JSON.parse(cachedStr);
            }
        } catch(e) {
            console.error("Error reading cache:", e);
        }

        if (cachedData) {
            renderDetailHtml(cachedData);
            fetchDetailData(currentCourseKey, false);
        } else {
            fetchDetailData(currentCourseKey, true);
        }
    }

    function loadCourses() {
        const currentParams = `${currentGroupId}_${currentPage}`;
        if (lastLoadedParams === currentParams) {
            return;
        }
        lastLoadedParams = currentParams;

        const cacheKey = 'courses_list_' + currentParams;
        let cachedData = null;
        try {
            const cachedStr = sessionStorage.getItem(cacheKey);
            if (cachedStr) {
                cachedData = JSON.parse(cachedStr);
            }
        } catch(e) {
            console.error("Error reading cache:", e);
        }

        if (cachedData) {
            renderGroups(cachedData.groups);
            renderCourses(cachedData.courses);
            renderPagination(cachedData.pagination);
            fetchCoursesData(currentGroupId, currentPage, cacheKey, false);
        } else {
            fetchCoursesData(currentGroupId, currentPage, cacheKey, true);
        }
    }

    function fetchCoursesData(groupId, page, cacheKey, showLoader) {
        $.ajax({
            beforeSend: function() {
                if (showLoader) {
                    courseGrid.innerHTML = '<p style="text-align:center; grid-column: 1 / -1; padding: 50px;">กำลังโหลดข้อมูล...</p>';
                }
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "course",
                request_function: "list",
                group_id: groupId,
                page: page
            },
            dataType: "json",
            success: function (response) {
                let isSuccess = (response.result == 1 || response.status == 1);

                if (isSuccess) {
                    try {
                        sessionStorage.setItem(cacheKey, JSON.stringify(response.data));
                    } catch(e) {
                        console.error("Error writing cache:", e);
                    }
                    renderGroups(response.data.groups);
                    renderCourses(response.data.courses);
                    renderPagination(response.data.pagination);
                } else if (showLoader) {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">'+(response.msg || 'ไม่สามารถโหลดข้อมูลได้')+'</span>',
                        icon: "warning",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            },
            error: function(jqXHR, exception) {
                if (!showLoader) return;
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

                courseGrid.innerHTML = '<p style="text-align:center; grid-column: 1 / -1; padding: 50px; color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';

                Swal.fire({
                    title: "แจ้งเตือน",
                    html: "พบปัญหาการดึงข้อมูล กรุณาติดต่อผู้ดูแลระบบ<br>"+ msg,
                    icon: "error",
                    showConfirmButton: true,
                });
            }
        });
    }


    function renderGroups(groups) {
        let html = `<li class="${currentGroupId == 0 ? 'active' : ''}" data-id="0">หมวดหมู่ทั้งหมด</li>`;
        groups.forEach(g => {
            html += `<li class="${currentGroupId == g.group_id ? 'active' : ''}" data-id="${g.group_id}">${g.group_name}</li>`;
        });
        groupList.innerHTML = html;

        $(groupList).find('li').on('click', function() {
            currentGroupId = $(this).attr('data-id');
            currentPage = 1; // Reset to page 1 when category changes
            currentCourseKey = ''; // Clear course detail view
            updateUrl();
            checkView();
        });
    }

    function updateUrl() {
        const url = new URL(window.location);
        if (currentGroupId == 0) {
            url.searchParams.delete('group_id');
        } else {
            url.searchParams.set('group_id', currentGroupId);
        }
        if (currentPage == 1) {
            url.searchParams.delete('page');
        } else {
            url.searchParams.set('page', currentPage);
        }
        if (!currentCourseKey) {
            url.searchParams.delete('key');
        } else {
            url.searchParams.set('key', currentCourseKey);
        }
        window.history.pushState({}, '', url);
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            $(paginationContainer).hide();
            return;
        }

        $(paginationContainer).show();
        let html = '';
        let current = parseInt(pagination.current_page);
        let total = parseInt(pagination.total_pages);

        // Prev button
        if (current > 1) {
            html += `<a href="#" class="page-btn" data-page="${current - 1}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                     </a>`;
        } else {
            html += `<a href="#" class="page-btn" style="opacity:0.5; pointer-events:none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                     </a>`;
        }

        // Page numbers
        for (let i = 1; i <= total; i++) {
            html += `<a href="#" class="page-btn ${i === current ? 'active' : ''}" data-page="${i}">${i}</a>`;
        }

        // Next button
        if (current < total) {
            html += `<a href="#" class="page-btn" data-page="${current + 1}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                     </a>`;
        } else {
            html += `<a href="#" class="page-btn" style="opacity:0.5; pointer-events:none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                     </a>`;
        }

        paginationContainer.innerHTML = html;

        $(paginationContainer).find('.page-btn[data-page]').on('click', function(e) {
            e.preventDefault();
            currentPage = $(this).attr('data-page');
            updateUrl();
            checkView();
        });
    }

    function renderCourses(courses) {
        $.ajax({
            type: "POST",
            url: "view/category/course_list.php",
            data: JSON.stringify({ courses: courses }),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function(responseHtml) {
                courseGrid.innerHTML = responseHtml;
            },
            error: function() {
                courseGrid.innerHTML = '<p style="text-align:center; grid-column: 1 / -1; padding: 50px; color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>';
            }
        });
    }

   
</script>

<?php include 'components/footer.php'; ?>