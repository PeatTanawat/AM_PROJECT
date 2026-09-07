<?php
use App\Utility\Auth;

require_once __DIR__ . '/vendor/autoload.php';

$currentUser = Auth::getUser();
if (!$currentUser) {
    header("Location: index");
    exit;
}

$pageTitle = 'ข้อมูลส่วนตัวผู้ใช้';
include 'components/header.php';
?>


<style>
    /* ==================================
       1. ตัวแปรสีและฟอนต์ (Variables & Base)
       ================================== */
    :root {
        --primary-blue: #4a66ac;
        --primary-hover: #3b538e;
        --bg-color: #f0f2f5;
        --text-main: #1f2937;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    body {
        font-family: 'Prompt', sans-serif;
        background-color: var(--bg-color);
    }

    /* สไตล์เบลอพื้นหลังและล็อคการสกรอลล์เมื่อเปิด Modal */
    /* ลบโค้ด lock scroll เองออก เพื่อใช้ของ Bootstrap ที่มีการจัดการ padding-right ป้องกันจอกระตุก */

    .modal {
        backdrop-filter: blur(5px) !important;
        -webkit-backdrop-filter: blur(5px) !important;
        background-color: rgba(15, 23, 42, 0.45) !important;
        z-index: 99999 !important;
    }

    .modal-backdrop {
        display: none !important;
    }

    /* ==================================
       2. Layout หลัก (Grid System สำหรับ Desktop)
       ================================== */
    .profile-wrapper {
        max-width: 1200px;
        margin: 40px auto 60px auto;
        padding: 0 20px;
        min-height: 900px;
    }

    @media (min-width: 768px) {
        .profile-wrapper .row {
            display: grid !important;
            grid-template-columns: 280px 850px !important;
            gap: 2rem !important;
            align-items: flex-start;
        }

        .profile-wrapper .row>div {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            min-width: 0 !important;
        }
    }

    /* ==================================
       3. ส่วน Sidebar (เมนูด้านซ้ายสีน้ำเงิน)
       ================================== */
    .sidebar-card {
        background-color: var(--primary-blue);
        border-radius: 12px;
        overflow: hidden;
        color: #ffffff;
        padding-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .sidebar-header {
        padding: 30px 24px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 12px;
    }

    .sidebar-name {
        font-size: 1.15rem;
        font-weight: 600;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sidebar-email {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.8);
        word-break: break-all;
    }

    .nav-pills .nav-link {
        color: #ffffff;
        font-family: 'Prompt', sans-serif;
        font-size: 0.95rem;
        font-weight: 400;
        height: 54px !important;
        padding: 0 24px;
        border-radius: 0;
        display: flex;
        align-items: center;
        text-align: left;
        transition: background-color 0.2s;
        white-space: nowrap !important;
        cursor: pointer;
    }

    .nav-pills .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-pills .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
        font-weight: 500;
    }

    .nav-pills .nav-link i {
        font-size: 1.2rem;
        width: 32px;
        flex-shrink: 0;
    }

    .nav-link-text {
        white-space: nowrap !important;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        width: 100%;
    }

    .logout-link {
        margin-top: 15px;
        color: #ffffff !important;
    }

    /* ==================================
       4. ส่วน Content (เนื้อหาด้านขวาสีขาว)
       ================================== */
    .content-card {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        padding: 40px;
    }

    .tab-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .tab-title i {
        color: var(--text-muted);
        font-size: 1.4rem;
    }

    hr {
        border-color: var(--border-color);
        margin: 30px 0;
    }

    /* --- ข้อมูลผู้ใช้ --- */
    .info-row {
        display: flex;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .info-label {
        flex: 0 0 200px;
        color: var(--text-muted);
        font-weight: 400;
    }

    .info-value {
        flex: 1;
        color: var(--text-main);
        font-weight: 500;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .edit-link {
        font-size: 0.85rem;
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 500;
    }

    .verify-box {
        background-color: #f8fafc;
        border-radius: 12px;
        padding: 25px 30px;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .verify-icon {
        width: 65px;
        height: auto;
        flex-shrink: 0;
    }

    .verify-title {
        color: #22c55e;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .verify-text {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.6;
        margin: 0;
    }

    /* --- ที่อยู่ --- */
    .address-card {
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 25px;
        position: relative;
    }

    .address-default {
        position: absolute;
        top: 25px;
        right: 25px;
        color: #10b981;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .address-edit {
        position: absolute;
        bottom: 25px;
        right: 25px;
        color: #f59e0b;
        font-size: 1.1rem;
        cursor: pointer;
    }

    .address-text {
        font-size: 0.95rem;
        color: #4b5563;
        line-height: 1.8;
        margin: 0;
    }

    .btn-add-address {
        background-color: var(--primary-blue);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-size: 0.9rem;
        font-weight: 400;
    }

    /* --- คอร์สเรียนของฉัน --- */
    .my-course-card {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 15px;
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        background: #fff;
        transition: box-shadow 0.2s;
    }

    .my-course-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    /* สำหรับคอร์สเดิมที่มีรูปภาพจริง */
    .my-course-img {
        width: 260px;
        height: 140px;
        border-radius: 4px;
        object-fit: cover;
        flex-shrink: 0;
    }

    /* สำหรับคอร์สใหม่ที่ใช้ CSS แทนรูป */
    .course-img-box {
        width: 260px;
        height: 140px;
        flex-shrink: 0;
        border-radius: 4px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 15px;
    }

    .bg-placeholder-gray {
        background-color: #eeeeee;
    }

    .bg-placeholder-pink {
        background-color: #faf5f5;
    }

    .course-img-text-main {
        color: #1f2937;
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 8px;
    }

    .course-img-text-danger {
        color: #ef4444;
        font-size: 1rem;
        font-weight: 700;
    }

    .my-course-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .my-course-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 12px;
        line-height: 1.4;
        word-break: break-word;
    }

    .title-strike {
        text-decoration: line-through;
        color: #6b7280;
    }

    .my-course-meta {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 6px;
        line-height: 1.4;
        word-break: break-word;
    }

    .text-expired {
        color: #ef4444;
        font-size: 1rem;
        font-weight: 500;
        align-self: flex-end;
        margin-top: auto;
        padding-top: 10px;
    }

    .btn-enter-course {
        background-color: var(--primary-blue);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 8px 24px;
        font-size: 0.9rem;
        font-weight: 400;
        align-self: flex-end;
        margin-top: auto;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-enter-course i {
        font-size: 1.1rem;
    }

    .btn-enter-course:hover {
        background-color: var(--primary-hover);
        color: #fff;
    }

    /* --- Tables (ประวัติ/ภาษี/ใบรับรอง) --- */
    .table-container {
        width: 100%;
        /* ลบ overflow-x ออกเพื่อใช้แบบ Card บนมือถือ */
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table th {
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 500;
        padding: 15px 10px;
        border-bottom: 2px solid #f1f5f9;
        text-align: left;
        white-space: nowrap;
    }

    .custom-table td {
        font-size: 0.95rem;
        color: var(--text-main);
        font-weight: 400;
        padding: 15px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .status-badge {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #fff;
        display: inline-block;
        white-space: nowrap;

    }

    .bg-success-badge {
        background-color: #22c55e;
    }

    .bg-warning-badge {
        background-color: #f59e0b;
    }

    .bg-orange-badge {
        background-color: #f97316;
    }

    .btn-action {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--text-main);
        cursor: pointer;
        padding: 0;
    }

    .btn-download {
        background-color: var(--primary-blue);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 16px;
        font-size: 0.85rem;
        font-weight: 400;
        white-space: nowrap;
    }

    /* --- Pagination --- */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 30px;
        list-style: none;
        padding: 0;
    }

    .page-item .page-link {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px; /* เปลี่ยนจาก 50% เป็น 6px เพื่อให้เป็นสี่เหลี่ยมขอบมน */
        border: 1px solid var(--border-color);
        color: #4b5563;
        font-size: 0.9rem;
        text-decoration: none;
        background-color: transparent;
    }

    .page-item.active .page-link {
        background-color: var(--primary-blue);
        color: #fff;
        border-color: var(--primary-blue);
    }

    /* --- แก้ไขรหัสผ่าน --- */
    .form-group-pw {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group-pw label {
        display: block;
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .form-control-pw {
        width: 100%;
        background-color: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 12px 40px 12px 16px;
        font-size: 0.95rem;
        color: #333;
        outline: none;
    }

    .form-control-pw:focus {
        border-color: var(--primary-blue);
        background-color: #fff;
    }

    .pw-icon {
        position: absolute;
        right: 16px;
        bottom: 12px;
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    .pw-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-cancel {
        background: transparent;
        color: var(--primary-blue);
        border: none;
        font-weight: 500;
        font-size: 0.95rem;
        padding: 10px 20px;
    }

    .btn-save {
        background-color: var(--primary-blue);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 400;
        font-size: 0.95rem;
        padding: 10px 30px;
    }

    /* ==================================
       5. Mobile Dropdown Nav (ปรับปรุงใหม่ด้วย JavaScript เสริม)
       ================================== */
    .custom-mobile-dropdown {
        position: relative;
    }

    .mobile-nav-btn {
        background-color: var(--primary-blue);
        color: white;
        border-radius: 8px;
        padding: 14px 20px;
        font-size: 1rem;
        font-weight: 500;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: none;
        width: 100%;
        text-align: left;
        box-shadow: 0 4px 10px rgba(74, 102, 172, 0.2);
    }

    .mobile-nav-btn::after {
        content: "\F282";
        /* ไอคอนลูกศรลงจาก Bootstrap Icons */
        font-family: "bootstrap-icons";
        font-size: 0.9rem;
        font-weight: bold;
        transition: transform 0.3s;
    }

    .mobile-nav-btn.open::after {
        transform: rotate(180deg);
    }

    .custom-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        padding: 10px 0;
        z-index: 1000;
        margin-top: 5px;
    }

    .custom-dropdown-menu.show {
        display: block;
    }

    .custom-dropdown-menu .dropdown-item {
        padding: 12px 20px;
        font-size: 0.95rem;
        color: var(--text-main);
        font-weight: 400;
        display: block;
        text-decoration: none;
    }

    .custom-dropdown-menu .dropdown-item:active,
    .custom-dropdown-menu .dropdown-item.active {
        background-color: #f1f5f9;
        color: var(--primary-blue);
        font-weight: 500;
    }

    /* Tab Content - จำเป็นต้องมีสำหรับสลับหน้า (ในกรณี JS ตัวหลักไม่ทำงาน) */
    .tab-pane {
        display: none;
        opacity: 0;
        transition: opacity 0.15s linear;
    }

    .tab-pane.show {
        display: block;
    }

    .tab-pane.active {
        opacity: 1;
    }

    /* ==================================
       6. Responsive (การปรับขนาดหน้าจอ)
       ================================== */
    @media (max-width: 992px) {
        .my-course-card {
            flex-direction: column;
            align-items: center;
        }

        .course-img-box,
        .my-course-img {
            width: 100%;
            height: auto;
            aspect-ratio: 16/9;
        }

        .my-course-info {
            width: 100%;
        }

        .btn-enter-course {
            align-self: flex-start;
            margin-top: 15px;
        }
    }

    @media (max-width: 768px) {
        .profile-wrapper {
            margin: 20px auto 40px;
            padding: 0 15px;
        }

        .content-card {
            padding: 25px 20px;
        }

        .tab-title {
            font-size: 1.15rem;
            margin-bottom: 25px;
        }

        .info-row {
            flex-direction: column;
            gap: 4px;
            margin-bottom: 16px;
        }

        .info-label {
            flex: auto;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 400;
        }

        .verify-box {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }

        .verify-icon {
            margin-bottom: 10px;
            width: 70px;
        }

        .my-course-card {
            padding: 12px;
            gap: 15px;
        }

        .my-course-title {
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .my-course-meta {
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .text-expired {
            font-size: 0.95rem;
            margin-top: 10px;
        }

        .pw-actions {
            flex-direction: column-reverse;
            gap: 10px;
        }

        .btn-save,
        .btn-cancel {
            width: 100%;
            text-align: center;
        }

        /* Responsive Tables (Card Stack) */
        .custom-table {
            min-width: 100%;
        }

        .custom-table thead {
            display: none;
            /* ซ่อนหัวตาราง */
        }

        .custom-table tr {
            display: block;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fff;
        }

        .custom-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: right;
            border-bottom: 1px solid #f8fafc;
            padding: 12px 5px;
        }

        .custom-table td::before {
            content: attr(data-label);
            font-weight: 500;
            color: var(--text-muted);
            text-align: left;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .custom-table td:last-child {
            border-bottom: none;
            justify-content: flex-end;
        }

        .custom-table td:last-child::before {
            display: none;
            /* ซ่อน Label ในคอลัมน์ปุ่มกด */
        }
    }
    /* Prevent image download */
    .my-course-img {
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

<main class="profile-wrapper">
    <div class="row">

        <div class="col-12 d-md-none mb-3 custom-mobile-dropdown">
            <button class="mobile-nav-btn" id="customMobileBtn">ข้อมูลผู้ใช้</button>
            <div class="custom-dropdown-menu" id="customMobileMenu">
                <a class="dropdown-item active" href="#" data-target="#tab-info">ข้อมูลผู้ใช้</a>
                <a class="dropdown-item" href="#" data-target="#tab-address">ที่อยู่</a>
                <a class="dropdown-item" href="#" data-target="#tab-course">คอร์สเรียนของฉัน</a>
                <a class="dropdown-item" href="#" data-target="#tab-history">ประวัติการชำระเงิน</a>
                <a class="dropdown-item" href="#" data-target="#tab-tax">ใบกำกับภาษี (E-Tax)</a>
                <a class="dropdown-item" href="#" data-target="#tab-cert">ใบรับรองการสอบ</a>
                <a class="dropdown-item" href="#" data-target="#tab-password">แก้ไขรหัสผ่าน</a>
                <hr class="dropdown-divider">
                <a class="dropdown-item text-danger" href="logout">ออกจากระบบ</a>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 d-none d-md-block">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <div class="sidebar-name"><?php echo htmlspecialchars($currentUser->user_firstname . ' ' . $currentUser->user_lastname); ?></div>
                    <div class="sidebar-email"><?php echo htmlspecialchars($currentUser->user_email); ?></div>
                </div>

                <div class="nav flex-column nav-pills" id="desktop-nav-pills">
                    <div class="nav-link active" data-target="#tab-info">
                        <i class="bi bi-person-vcard"></i> <span class="nav-link-text">ข้อมูลผู้ใช้</span>
                    </div>
                    <div class="nav-link" data-target="#tab-address">
                        <i class="bi bi-geo-alt-fill"></i> <span class="nav-link-text">ที่อยู่</span>
                    </div>
                    <div class="nav-link" data-target="#tab-course">
                        <i class="bi bi-play-btn-fill"></i> <span class="nav-link-text">คอร์สเรียนของฉัน</span>
                    </div>
                    <div class="nav-link" data-target="#tab-history">
                        <i class="bi bi-clock-history"></i> <span class="nav-link-text">ประวัติการชำระเงิน</span>
                    </div>
                    <div class="nav-link" data-target="#tab-tax">
                        <i class="bi bi-receipt"></i> <span class="nav-link-text">ใบกำกับภาษี (E-Tax)</span>
                    </div>
                    <div class="nav-link" data-target="#tab-cert">
                        <i class="bi bi-file-earmark-check-fill"></i> <span class="nav-link-text">ใบรับรองการสอบ</span>
                    </div>
                    <div class="nav-link" data-target="#tab-password">
                        <i class="bi bi-lock-fill"></i> <span class="nav-link-text">แก้ไขรหัสผ่าน</span>
                    </div>
                    <a class="nav-link logout-link" href="logout">
                        <i class="bi bi-box-arrow-right"></i> <span class="nav-link-text">ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>

        <div id="profile-content-area"></div>

    </div>
</main>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true" inert>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content animated fadeIn" id="LoadingMyModal">
            <div id="showModal"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-hidden="true" inert>
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content animated fadeIn" id="LoadingMyModal">
            <div id="showModal2"></div>
        </div>
    </div>
</div>

<script>
    // ล็อคไม่ให้ฉากหลัง (body/html) scroll เมื่อเปิด #myModal หรือ #myModal2 เฉพาะในหน้า profile-menu
    function lockProfileModalScroll() {
        document.body.style.setProperty('overflow-y', 'hidden', 'important');
        document.body.style.setProperty('overflow', 'hidden', 'important');
        document.documentElement.style.setProperty('overflow-y', 'hidden', 'important');
        document.documentElement.style.setProperty('overflow', 'hidden', 'important');
    }

    function unlockProfileModalScroll() {
        if ($('.modal.show').length === 0) {
            document.body.style.removeProperty('overflow-y');
            document.body.style.removeProperty('overflow');
            document.documentElement.style.removeProperty('overflow-y');
            document.documentElement.style.removeProperty('overflow');
        }
    }

    $(document).on('show.bs.modal shown.bs.modal', '#myModal, #myModal2', function () {
        lockProfileModalScroll();
        setTimeout(lockProfileModalScroll, 100);
        setTimeout(lockProfileModalScroll, 300);
        setTimeout(lockProfileModalScroll, 500);
    });

    $(document).on('hidden.bs.modal', '#myModal, #myModal2', function () {
        unlockProfileModalScroll();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const mobileBtn = document.getElementById('customMobileBtn');
        const mobileMenu = document.getElementById('customMobileMenu');

        // 1. แผนผังเส้นทางดึงหน้าวิว (View Mapping)
        const viewMap = {
            '#tab-info': 'view/profile-menu/profile.php',
            '#tab-address': 'view/address/address.php',
            '#tab-course': 'view/my_course/my_course.php',
            '#tab-history': 'view/payment/payment_history.php',
            '#tab-tax': 'view/tax_invoice/tax_Invoice.php',
            '#tab-cert': 'view/certificate/certificate.php',
            '#tab-password': 'view/edit_password/edit_password.php'
        };

        // ฟังก์ชันข้อมูลโปรไฟล์
        function loaddataprofile() {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "profile",
                    request_function: "get_profile"
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        const payload = response.data;
                        const user = payload.user;
                        window.currentUser = user; // เก็บไว้ใช้ดึงข้อมูลเดิมไปใส่ในหน้าต่างแก้ไข
                        if (user.user_firstname && user.user_lastname) {
                            $(".sidebar-name").text(user.user_firstname + " " + user.user_lastname);
                        }
                        if (user.user_email) {
                            $(".sidebar-email").text(user.user_email);
                        }

                        RenderProfile(payload);
                    }
                }
            });
        }

        function RenderProfile(userData) {
            $.ajax({
                type: "GET",
                url: "view/profile/profile.php",
                dataType: "html",
                success: function (responseHtml) {
                    $("#profile-content-area").html(responseHtml);

                    const pane = document.querySelector('#tab-info');
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                }
            });
        }

        window.Getmodal_identity_verified = function () {
            $.ajax({
                beforeSend: function () {
                    Swal.fire({
                        title: 'กำลังโหลด...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                type: "POST",
                url: "view/profile/modal_identity_verified.php",
                dataType: "html",
                success: function (response) {
                    Swal.close();
                    $("#showModal").html(response);
                    $("#myModal").modal("show");
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถโหลดหน้าต่างยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        };

        window.Getmodal_editverifly = function () {
            $.ajax({
                beforeSend: function () {
                    Swal.fire({
                        title: 'กำลังโหลด...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                type: "GET",
                url: "view/profile/modal_editverifly.php",
                dataType: "html",
                success: function (response) {
                    Swal.close();
                    $("#showModal").html(response);
                    $("#myModal").modal("show");
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถโหลดหน้าต่างยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        };




        window.saveIdentityVerify = function () {
            var citizenId = $.trim($("#citizen_id").val());
            var expireDate = $.trim($("#expire_date").val());
            var fileCitizen = $("#file_citizen")[0].files[0];
            var fileAvatar = $("#file_avatar")[0].files[0];

            let hasError = false;
            $("#formIdentityVerify .is-invalid").removeClass("is-invalid");
            $("#formIdentityVerify .error-text").remove();

            function showInlineError(selector, message) {
                $(selector).addClass("is-invalid");
                const $el = $(selector);
                if ($el.closest('.position-relative').length > 0) {
                    $el.closest('.position-relative').append('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
                } else if ($el.parent('.flex-grow-1').length > 0) {
                    $el.parent('.flex-grow-1').append('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
                } else {
                    $el.after('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
                }
                hasError = true;
            }

            // 1. บังคับใส่ข้อมูล
            if (!citizenId) showInlineError("#citizen_id", "กรุณากรอกเลขประจำตัวประชาชน");
            if (!expireDate) {
                showInlineError("#expire_date", "กรุณาเลือกวันหมดอายุ");
            } else {
                var todayStr = new Date().toISOString().split('T')[0];
                if (expireDate < todayStr) {
                    showInlineError("#expire_date", "บัตรประจำตัวประชาชนหมดอายุแล้ว กรุณาระบุวันหมดอายุใหม่ที่ยังไม่หมดอายุ");
                }
            }

            var hasOldCitizen1 = $("#preview_citizen").attr("data-original-src") || $("#preview_citizen").attr("src");
            var hasOldAvatar1 = $("#preview_avatar").attr("data-original-src") || $("#preview_avatar").attr("src");

            if (!fileCitizen && !hasOldCitizen1) showInlineError("#label_file_citizen", "กรุณาอัพโหลดภาพถ่ายบัตรประชาชน");
            if (!fileAvatar && !hasOldAvatar1) showInlineError("#label_file_avatar", "กรุณาอัพโหลดภาพถ่ายปัจจุบัน");

            // 2. เช็คประเภทไฟล์รูปภาพ (ไม่รับ PDF หรือไฟล์อื่นๆ)
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];

            if (fileCitizen && $.inArray(fileCitizen.type, allowedTypes) === -1) {
                showInlineError("#label_file_citizen", "ภาพถ่ายบัตรประชาชนต้องเป็นไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)");
            }
            if (fileAvatar && $.inArray(fileAvatar.type, allowedTypes) === -1) {
                showInlineError("#label_file_avatar", "ภาพถ่ายปัจจุบันต้องเป็นไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)");
            }

            if (hasError) return;

            // 3. เตรียม FormData สำหรับส่งไฟล์
            var formData = new FormData($("#formIdentityVerify")[0]);
            formData.append("request_state", "profile");
            formData.append("request_function", "save_identity_verify");

            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "core.php",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "JSON",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "บันทึกสำเร็จ",
                            text: "ข้อมูลการยืนยันตัวตนของคุณได้รับการบันทึกเรียบร้อยแล้ว",
                            icon: "success"
                        });
                        $("#myModal").modal("hide");
                        loaddataprofile();
                    } else {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: response.message || response.msg,
                            icon: "error"
                        });
                    }
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถบันทึกข้อมูลการยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        };
        window.editIdentityVerify = function () {
            var citizenId = $.trim($("#citizen_id").val());
            var expireDate = $.trim($("#expire_date").val());
            var fileCitizen = $("#file_citizen")[0].files[0];
            var fileAvatar = $("#file_avatar")[0].files[0];

            let hasError = false;
            $("#formIdentityVerify .is-invalid").removeClass("is-invalid");
            $("#formIdentityVerify .error-text").remove();

            function showInlineError(selector, message) {
                $(selector).addClass("is-invalid");
                const $el = $(selector);
                if ($el.closest('.position-relative').length > 0) {
                    $el.closest('.position-relative').append('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
                } else if ($el.parent('.flex-grow-1').length > 0) {
                    $el.parent('.flex-grow-1').append('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
                } else {
                    $el.after('<div class="text-danger fw-bold mt-1 error-text" style="font-size: 0.85rem;">' + message + '</div>');
                }
                hasError = true;
            }

            // 1. บังคับใส่ข้อมูล
            if (!citizenId) showInlineError("#citizen_id", "กรุณากรอกเลขประจำตัวประชาชน");
            if (!expireDate) {
                showInlineError("#expire_date", "กรุณาเลือกวันหมดอายุ");
            } else {
                var todayStr = new Date().toISOString().split('T')[0];
                if (expireDate < todayStr) {
                    showInlineError("#expire_date", "บัตรประจำตัวประชาชนหมดอายุแล้ว กรุณาระบุวันหมดอายุใหม่ที่ยังไม่หมดอายุ");
                }
            }

            var hasOldCitizen2 = $("#preview_citizen").attr("data-original-src") || $("#preview_citizen").attr("src");
            var hasOldAvatar2 = $("#preview_avatar").attr("data-original-src") || $("#preview_avatar").attr("src");

            if (!fileCitizen && !hasOldCitizen2) showInlineError("#label_file_citizen", "กรุณาอัพโหลดภาพถ่ายบัตรประชาชน");
            if (!fileAvatar && !hasOldAvatar2) showInlineError("#label_file_avatar", "กรุณาอัพโหลดภาพถ่ายปัจจุบัน");

            // 2. เช็คประเภทไฟล์รูปภาพ (ไม่รับ PDF หรือไฟล์อื่นๆ)
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];

            if (fileCitizen && $.inArray(fileCitizen.type, allowedTypes) === -1) {
                showInlineError("#label_file_citizen", "ภาพถ่ายบัตรประชาชนต้องเป็นไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)");
            }
            if (fileAvatar && $.inArray(fileAvatar.type, allowedTypes) === -1) {
                showInlineError("#label_file_avatar", "ภาพถ่ายปัจจุบันต้องเป็นไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif, webp)");
            }

            if (hasError) return;

            // 3. เตรียม FormData สำหรับส่งไฟล์
            var formData = new FormData($("#formIdentityVerify")[0]);
            formData.append("request_state", "profile");
            formData.append("request_function", "edit_identity_verify");

            $("#myModal").modal("hide");
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "core.php",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "JSON",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "บันทึกสำเร็จ",
                            text: "ข้อมูลการยืนยันตัวตนของคุณได้รับการบันทึกเรียบร้อยแล้ว",
                            icon: "success"
                        });
                        $("#myModal").modal("hide");
                        loaddataprofile();
                    } else {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: response.message || response.msg,
                            icon: "error"
                        });
                    }
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถบันทึกข้อมูลการยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        };

        window.Getmodal_change_phone = function () {
            $.ajax({
                beforeSend: function () {
                    Swal.fire({
                        title: 'กำลังโหลด...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                type: "POST",
                url: "view/profile/modal_change_phone.php",
                dataType: "html",
                success: function (response) {
                    Swal.close();
                    $("#showModal2").html(response);
                    $("#myModal2").modal("show");
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถโหลดหน้าต่างยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        };

        // ฟังก์ชั่น ที่อยู่
        window.loaddataaddress = function(page = 1) {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "address",
                    request_function: "get_address",
                    page: page,
                    limit: 2
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        const payload = response.data;
                        Renderaddress(payload);
                    }
                }
            });
        }
        function loaddataaddress(page = 1) {
            window.loaddataaddress(page);
        }

        function Renderaddress(addressData) {
            $.ajax({
                type: "POST",
                url: "view/address/address.php",
                data: JSON.stringify(addressData),
                contentType: "application/json; charset=utf-8",
                processData: false,
                dataType: "html",
                success: function (responseHtml) {
                    $("#profile-content-area").html(responseHtml);

                    const pane = document.querySelector('#tab-address');
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                }
            });
        }

        window.Getmodal_Addaddress = function () {
            $.ajax({
                beforeSend: function () {
                    Swal.fire({
                        title: 'กำลังโหลด...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                type: "POST",
                url: "view/address/modal_add_address.php",
                dataType: "html",
                success: function (response) {
                    Swal.close();
                    $("#showModal").html(response);
                    $("#myModal").modal("show");
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถโหลดหน้าต่างยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        }

        window.saveAddress = function () {
            // Validation
            let isValid = true;
            $(".invalid-feedback-custom").hide();
            $("input, select").removeClass("is-invalid");
            // สำหรับ select2
            $(".select2-selection").removeClass("is-invalid");

            const showError = (name) => {
                let el = $("input[name='"+name+"'], select[name='"+name+"']");
                el.addClass("is-invalid");
                el.next(".invalid-feedback-custom").show();
                if (el.hasClass("select2-hidden-accessible")) {
                    el.next(".select2-container").find(".select2-selection").addClass("is-invalid");
                    el.next(".select2-container").next(".invalid-feedback-custom").show();
                }
                isValid = false;
            };

            const addr_type = $("input[name='addr_type']:checked").val();
            
            if (addr_type === 'individual' || addr_type === 'typeIndividual') {
                if (!$("input[name='indiv_name']").val()) showError('indiv_name');
                if (!$("input[name='indiv_tax_id']").val() || $("input[name='indiv_tax_id']").val().length !== 13) showError('indiv_tax_id');
            } else {
                if (!$("input[name='corp_name']").val()) showError('corp_name');
                if (!$("input[name='corp_tax_id']").val() || $("input[name='corp_tax_id']").val().length !== 13) showError('corp_tax_id');
                
                if (!$("input[name='corp_branch_code']").val()) showError('corp_branch_code');
                if (!$("input[name='corp_is_headoffice']").is(':checked')) {
                    if (!$("input[name='corp_branch_name']").val()) showError('corp_branch_name');
                }
            }
            
            if (!$("input[name='addr_phone']").val() || $("input[name='addr_phone']").val().length !== 10) showError('addr_phone');
            if (!$("input[name='addr_detail']").val()) showError('addr_detail');
            if (!$("select[name='addr_province']").val()) showError('addr_province');
            if (!$("select[name='addr_district']").val()) showError('addr_district');
            if (!$("select[name='addr_subdistrict']").val()) showError('addr_subdistrict');
            if (!$("select[name='addr_zipcode']").val()) showError('addr_zipcode');

            if (!isValid) return;

            // 3. เตรียม FormData สำหรับส่งไฟล์
            var formData = new FormData($("#formAddAddress")[0]);
            formData.append("request_state", "address");
            formData.append("request_function", "save_address");

            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "core.php",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "JSON",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "บันทึกสำเร็จ",
                            text: "บันทึกข้อมูลที่อยู่ออกใบกำกับภาษีเรียบร้อยแล้ว",
                            icon: "success"
                        });
                        $("#myModal").modal("hide");
                        loaddataaddress();
                    } else {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: response.message || response.msg,
                            icon: "error"
                        });
                    }
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถบันทึกข้อมูลที่อยู่ได้",
                        icon: "error"
                    });
                }
            });
        };

        window.deleteAddress = function (addr_id) {
            Swal.fire({
                title: 'ยืนยันการลบที่อยู่?',
                text: "คุณต้องการลบที่อยู่ออกใบกำกับภาษีนี้ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        beforeSend: function () {
                            Swal.fire({
                                title: 'กำลังลบ...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        type: "POST",
                        url: "core.php",
                        data: {
                            request_state: "address",
                            request_function: "delete_address",
                            addr_id: addr_id
                        },
                        dataType: "JSON",
                        success: function (response) {
                            if (response.result == 1) {
                                Swal.fire({
                                    title: "ลบที่อยู่สำเร็จ",
                                    text: response.msg || "ระบบได้ทำการลบที่อยู่เรียบร้อยแล้ว",
                                    icon: "success"
                                });
                                loaddataaddress();
                            } else {
                                Swal.fire({
                                    title: "เกิดข้อผิดพลาด",
                                    text: response.msg || "ไม่สามารถลบที่อยู่ได้",
                                    icon: "error"
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                title: "เกิดข้อผิดพลาด",
                                text: "ระบบขัดข้อง ไม่สามารถดำเนินการได้",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        };

        window.setDefaultAddress = function (addr_id) {
            $.ajax({
                beforeSend: function () {
                    Swal.fire({
                        title: 'กำลังบันทึก...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "address",
                    request_function: "set_default_address",
                    addr_id: addr_id
                },
                dataType: "JSON",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "ตั้งค่าเริ่มต้นสำเร็จ",
                            text: response.msg || "ระบบตั้งที่อยู่นี้เป็นค่าเริ่มต้นเรียบร้อยแล้ว",
                            icon: "success"
                        });
                        loaddataaddress();
                    } else {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: response.msg || "ไม่สามารถตั้งค่าที่อยู่เริ่มต้นได้",
                            icon: "error"
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ระบบขัดข้อง ไม่สามารถดำเนินการได้",
                        icon: "error"
                    });
                }
            });
        };

        window.Getmodal_Editaddress = function (addr_id) {
            $.ajax({
                beforeSend: function () {
                    Swal.fire({
                        title: 'กำลังโหลด...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                type: "POST",
                data: JSON.stringify({ addr_id: addr_id }),
                contentType: "application/json; charset=utf-8",
                processData: false,
                dataType: "html",
                url: "view/address/modal_edit_address.php",
                success: function (response) {
                    Swal.close();
                    $("#showModal").html(response);
                    $("#myModal").modal("show");
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถโหลดหน้าต่างยืนยันตัวตนได้",
                        icon: "error"
                    });
                }
            });
        }
        // my course
        window.GetMyCourse = function () {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "my_course",
                    request_function: "get_my_course"
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        const payload = response.data;
                        RenderMyCourse(payload);
                    }
                }
            });
        }

        function RenderMyCourse(courseData) {
            $.ajax({
                type: "POST",
                url: "view/my_course/my_course.php",
                data: JSON.stringify(courseData),
                contentType: "application/json; charset=utf-8",
                processData: false,
                dataType: "html",
                success: function (responseHtml) {
                    $("#profile-content-area").html(responseHtml);
                    const pane = document.querySelector('#tab-course');
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                },
                error: function () {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ระบบขัดข้อง ไม่สามารถดำเนินการได้",
                        icon: "error"
                    });
                }
            });
        }

        window.GetTaxInvoice = function (page = 1) {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "tax_invoice",
                    request_function: "get_etax_list",
                    page: page,
                    limit: 5
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        const payload = response.data;
                        RenderTaxInvoice(payload);
                    }
                }
            });
        }

        function RenderTaxInvoice(taxData) {
            $.ajax({
                type: "POST",
                url: "view/tax_invoice/tax_Invoice.php",
                data: JSON.stringify(taxData),
                contentType: "application/json; charset=utf-8",
                processData: false,
                dataType: "html",
                success: function (responseHtml) {
                    $("#profile-content-area").html(responseHtml);
                    const pane = document.querySelector('#tab-tax');
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                },
                error: function () {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ระบบขัดข้อง ไม่สามารถดำเนินการได้",
                        icon: "error"
                    });
                }
            });
        }

        window.loaddatacertificate = function(page = 1) {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "certificate",
                    request_function: "get_certificates",
                    page: page,
                    limit: 5
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        RenderCertificate(response.data);
                    } else {
                        Swal.fire("เกิดข้อผิดพลาด", response.msg || response.message, "error");
                        RenderCertificate({});
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถดึงข้อมูลใบรับรองได้", "error");
                }
            });
        }

        function RenderCertificate(certData) {
            $.ajax({
                type: "POST",
                url: "view/certificate/certificate.php",
                data: JSON.stringify(certData),
                contentType: "application/json; charset=utf-8",
                processData: false,
                dataType: "html",
                success: function (responseHtml) {
                    $("#profile-content-area").html(responseHtml);
                    const pane = document.querySelector('#tab-cert');
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                },
                error: function () {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ระบบขัดข้อง ไม่สามารถดำเนินการได้",
                        icon: "error"
                    });
                }
            });
        }


        window.cheangephone = window.Getmodal_change_phone;

        window.saveChangePhone = function () {
            var phone = $.trim($("#phone").val());


            // 1. บังคับใส่ข้อมูล
            if (!phone || phone == '-') {
                Swal.fire("แจ้งเตือน", "กรุณากรอกหมายเลขโทรศัพท์", "warning");
                return;
            }
            if (!/^[0-9]{10}$/.test(phone)) {
                Swal.fire("แจ้งเตือน", "กรุณากรอกหมายเลขโทรศัพท์ 10 หลักให้ถูกต้อง", "warning");
                return;
            }

            // 3. เตรียม FormData สำหรับส่งไฟล์
            var formData = new FormData($("#formChangePhone")[0]);
            formData.append("request_state", "profile");
            formData.append("request_function", "save_change_phone");

            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "core.php",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "JSON",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "บันทึกสำเร็จ",
                            text: "เปลี่ยนหมายเลขโทรศัพท์เรียบร้อยแล้ว",
                            icon: "success"
                        });
                        $("#myModal2").modal("hide");
                        loaddataprofile();
                    } else {
                        Swal.fire({
                            title: "เกิดข้อผิดพลาด",
                            text: response.message || response.msg,
                            icon: "error"
                        });
                    }
                },
                error: function (jqXHR, exception) {
                    Swal.fire({
                        title: "เกิดข้อผิดพลาด",
                        text: "ไม่สามารถบันทึกข้อมูลการเปลี่ยนหมายเลขโทรศัพท์ได้",
                        icon: "error"
                    });
                }
            });
        };


        // ฟังก์ชันที่อยุ่


        // 2. เปิด/ปิด Dropdown มือถือ
        mobileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            mobileMenu.classList.toggle('show');
            this.classList.toggle('open');
        });

        // 3. ปิด Dropdown เมื่อคลิกพื้นที่อื่น
        document.addEventListener('click', function (e) {
            if (!mobileBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.remove('show');
                mobileBtn.classList.remove('open');
            }
        });

        // 4. ฟังก์ชันสลับ Tab และดึงเนื้อหาจาก View มาแสดงผลแบบ Dynamic
        function switchTab(targetId, newText) {
            const viewPath = viewMap[targetId];
            if (!viewPath) return;

            // แสดงหน้าจอสำหรับโหลดข้อมูลชั่วคราว
            $("#profile-content-area").html(
                '<div class="col-12" style="width: 100%; max-width: 100%;">' +
                '  <div class="content-card text-center py-5">' +
                '    <div class="spinner-border text-primary" role="status"></div>' +
                '    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>' +
                '  </div>' +
                '</div>'
            );

            if (targetId === '#tab-info') {
                // สำหรับข้อมูลผู้ใช้ ให้เรียกใช้งาน loaddataprofile เพื่อส่ง JSON ไปเรนเดอร์
                loaddataprofile();
            } else if (targetId === '#tab-address') {
                // สำหรับที่อยู่ ให้เรียกใช้งาน loaddataaddress เพื่อส่ง JSON ไปเรนเดอร์
                loaddataaddress();
            } else if (targetId === '#tab-course') {
                // ฟังก์ชันสำหรับเช็คสิทธิ์ก่อนเข้าคอร์สเรียน
                const checkAndLoadCourse = (user) => {
                    // ตรวจสอบเงื่อนไข email_status = 1 และ approver_citizen = 2
                    if (user.email_status == 1 && user.approver_citizen == 2) {
                        GetMyCourse();
                    } else {
                        // แสดงข้อความแจ้งเตือนในรูปแบบหน้าคอร์สเปล่าๆ แทนไอคอนโหลด ก่อนแสดง Swal
                        $("#profile-content-area").html(
                            '<div class="col-12" style="width: 100%; max-width: 100%;">' +
                            '  <div class="content-card">' +
                            '    <div class="tab-content" id="mainTabContent">' +
                            '      <div class="tab-pane show active" id="tab-course">' +
                            '        <h3 class="tab-title"><i class="bi bi-play-btn-fill"></i> คอร์สเรียนของฉัน</h3>' +
                            '        <div class="text-center py-5 text-muted">' +
                            '          <i class="fa-solid fa-lock text-warning" style="font-size: 3rem;"></i>' +
                            '          <h5 class="mt-3 text-warning">กรุณายืนยันตัวตนให้สมบูรณ์ก่อนเข้าสู่บทเรียน</h5>' +
                            '          <p class="mb-0">ยืนยันอีเมลและรอการอนุมัติบัตรประชาชนเพื่อเข้าถึงข้อมูลคอร์สเรียนของคุณ</p>' +
                            '        </div>' +
                            '      </div>' +
                            '    </div>' +
                            '  </div>' +
                            '</div>'
                        );

                        Swal.fire({
                            title: 'แจ้งเตือน',
                            text: 'กรุณายืนยันตัวตนให้สมบูรณ์ก่อนเข้าสู่บทเรียน',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#4a66ac',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'ไปยืนยันตัวตน',
                            cancelButtonText: 'ยกเลิก',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // ถ้ากดยืนยัน กลับไปที่หน้าข้อมูลผู้ใช้
                                switchTab('#tab-info', 'ข้อมูลผู้ใช้');
                                
                                // ควบคุมการเปลี่ยน Active Tab ของเมนูให้เป็นข้อมูลผู้ใช้ด้วย
                                document.querySelectorAll('.desktop-menu .nav-link, .mobile-dropdown .dropdown-item').forEach(link => {
                                    link.classList.remove('active');
                                    if(link.getAttribute('href') === '#tab-info') {
                                        link.classList.add('active');
                                    }
                                });
                                // window.Getmodal_identity_verified();
                            }
                            // ถ้ากดยกเลิก ไม่ต้องทำอะไรเพิ่มเพราะตารางเปล่าๆ ถูกโหลดไว้ด้านหลังแล้ว
                        });
                    }
                };

                // ตรวจสอบว่ามีข้อมูล user หรือยัง (กรณีเข้ามาหน้าแรกด้วย ?tab=course จะยังไม่มีข้อมูล)
                if (window.currentUser) {
                    checkAndLoadCourse(window.currentUser);
                } else {
                    $.ajax({
                        type: "POST",
                        url: "core.php",
                        data: { request_state: "profile", request_function: "get_profile" },
                        dataType: "json",
                        success: function (response) {
                            if (response.result == 1) {
                                window.currentUser = response.data.user;
                                checkAndLoadCourse(window.currentUser);
                            } else {
                                GetMyCourse();
                            }
                        },
                        error: function() {
                            GetMyCourse();
                        }
                    });
                }
            } else if (targetId === '#tab-tax') {
                GetTaxInvoice();
            } else if (targetId === '#tab-cert') {
                loaddatacertificate();
            } else {
                // โหลดโค้ด HTML/PHP ของแท็บอื่น ๆ เข้ามาใส่ในพื้นที่แสดงผลแบบปกติ
                $("#profile-content-area").load(viewPath, function () {
                    const pane = document.querySelector(targetId);
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                });
            }

            // เอาแถบ Active ออกจากเมนูทุกตัว
            document.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
                link.classList.remove('active');
            });

            // อัปเดตเมนู Desktop ให้ตรงกัน
            const desktopLink = document.querySelector(`.nav-link[data-target="${targetId}"]`);
            if (desktopLink) desktopLink.classList.add('active');

            // อัปเดตเมนู Mobile ให้ตรงกัน และเปลี่ยนชื่อปุ่ม
            const mobileLink = document.querySelector(`.dropdown-item[data-target="${targetId}"]`);
            if (mobileLink) mobileLink.classList.add('active');
            if (newText) mobileBtn.innerText = newText;
        }

        // ตรวจสอบ URL Parameters เพื่อเปิดแท็บที่ต้องการ
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('tab');

        if (initialTab === 'course' || initialTab === 'my_course') {
            switchTab('#tab-course', 'คอร์สเรียนของฉัน');
        } else if (initialTab === 'address') {
            switchTab('#tab-address', 'ที่อยู่');
        } else if (initialTab === 'history' || initialTab === 'payment_history') {
            switchTab('#tab-history', 'ประวัติการชำระเงิน');
        } else if (initialTab === 'tax') {
            switchTab('#tab-tax', 'ใบกำกับภาษี (E-Tax)');
        } else if (initialTab === 'cert') {
            switchTab('#tab-cert', 'ใบรับรองการสอบ');
        } else if (initialTab === 'password') {
            switchTab('#tab-password', 'แก้ไขรหัสผ่าน');
        } else {
            // เริ่มต้น: โหลดหน้า ข้อมูลผู้ใช้ (profile.php) ก่อนเป็นค่าเริ่มต้น
            switchTab('#tab-info', 'ข้อมูลผู้ใช้');
        }

        // จัดการเมื่อคลิกเมนูจากมือถือ
        document.querySelectorAll('.custom-dropdown-menu .dropdown-item').forEach(item => {
            item.addEventListener('click', function (e) {
                if (this.classList.contains('text-danger')) return; // ถ้าเป็นปุ่มออกจากระบบ ให้ข้ามไป
                e.preventDefault();

                const targetId = this.getAttribute('data-target');
                const menuText = this.innerText;

                switchTab(targetId, menuText);

                // ปิด Dropdown
                mobileMenu.classList.remove('show');
                mobileBtn.classList.remove('open');
            });
        });

        // จัดการเมื่อคลิกเมนูจาก Desktop
        document.querySelectorAll('#desktop-nav-pills .nav-link').forEach(item => {
            item.addEventListener('click', function (e) {
                if (this.classList.contains('logout-link')) return; // ถ้าเป็นปุ่มออกจากระบบ ให้ข้ามไป
                e.preventDefault();

                const targetId = this.getAttribute('data-target');
                const menuText = this.querySelector('.nav-link-text').innerText;

                switchTab(targetId, menuText);
            });
        });
    });
</script>

<?php include 'components/footer.php'; ?>