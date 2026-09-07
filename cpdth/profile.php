<?php
    $pageTitle = 'ข้อมูลส่วนตัวผู้ใช้';
    include 'components/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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

    /* ==================================
       2. Layout หลัก (Grid System สำหรับ Desktop)
       ================================== */
    .profile-wrapper {
        max-width: 1200px;
        margin: 40px auto 60px auto;
        padding: 0 20px;
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
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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

    .bg-placeholder-gray { background-color: #eeeeee; }
    .bg-placeholder-pink { background-color: #faf5f5; }

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
    .btn-enter-course i { font-size: 1.1rem; }
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
    .bg-success-badge { background-color: #22c55e; }
    .bg-warning-badge { background-color: #f59e0b; }
    .bg-orange-badge { background-color: #f97316; }

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
        border-radius: 50%;
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
        content: "\F282"; /* ไอคอนลูกศรลงจาก Bootstrap Icons */
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
        .course-img-box, .my-course-img {
            width: 100%;
            height: auto;
            aspect-ratio: 16/9;
        }
        .my-course-info { width: 100%; }
        .btn-enter-course { align-self: flex-start; margin-top: 15px; }
    }

    @media (max-width: 768px) {
        .profile-wrapper {
            margin: 20px auto 40px;
            padding: 0 15px;
        }
        .content-card { padding: 25px 20px; }
        .tab-title { font-size: 1.15rem; margin-bottom: 25px; }

        .info-row { flex-direction: column; gap: 4px; margin-bottom: 16px; }
        .info-label { flex: auto; font-size: 0.85rem; color: #94a3b8; }
        .info-value { font-size: 1rem; font-weight: 400; }

        .verify-box { flex-direction: column; text-align: center; padding: 30px 20px; }
        .verify-icon { margin-bottom: 10px; width: 70px; }

        .my-course-card { padding: 12px; gap: 15px; }
        .my-course-title { font-size: 1rem; margin-bottom: 8px; }
        .my-course-meta { font-size: 0.85rem; margin-bottom: 4px; }
        .text-expired { font-size: 0.95rem; margin-top: 10px; }

        .pw-actions { flex-direction: column-reverse; gap: 10px; }
        .btn-save, .btn-cancel { width: 100%; text-align: center; }

        /* Responsive Tables (Card Stack) */
        .custom-table {
            min-width: 100%;
        }
        .custom-table thead {
            display: none; /* ซ่อนหัวตาราง */
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
            display: none; /* ซ่อน Label ในคอลัมน์ปุ่มกด */
        }
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
                <a class="dropdown-item text-danger" href="#">ออกจากระบบ</a>
            </div>
        </div>

        <div class="col-md-4 col-lg-3 d-none d-md-block">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <div class="sidebar-name">นายแก้ไข ทดสอบ</div>
                    <div class="sidebar-email">cpdth12345@am-amaudit.com</div>
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
                    <a class="nav-link logout-link" href="#">
                        <i class="bi bi-box-arrow-right"></i> <span class="nav-link-text">ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9 col-12">
            <div class="content-card">
                <div class="tab-content" id="mainTabContent">

                    <div class="tab-pane show active" id="tab-info">
                        <h3 class="tab-title"><i class="bi bi-person-vcard-fill"></i> ข้อมูลผู้ใช้</h3>

                        <div class="info-row">
                            <div class="info-label">ชื่อ-นามสกุล :</div>
                            <div class="info-value"><span>นายแก้ไข ทดสอบ</span></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">เบอร์โทรศัพท์ :</div>
                            <div class="info-value">
                                <span>0876741016</span>
                                <a href="#" class="edit-link">เปลี่ยน</a>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">เลขที่ผู้ทำบัญชี :</div>
                            <div class="info-value"><span>1269900111121</span></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">เลขประจำตัวประชาชน :</div>
                            <div class="info-value"><span>1269900111121</span></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">อีเมล :</div>
                            <div class="info-value"><span>cpdth12345@am-amaudit.com</span></div>
                        </div>

                        <hr>

                        <h3 class="tab-title"><i class="bi bi-shield-check"></i> การยืนยันตัวตน</h3>
                        <div class="verify-box">
                            <svg class="verify-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                                <rect x="16" y="12" width="32" height="40" rx="4" fill="#3b82f6" />
                                <rect x="24" y="8" width="16" height="8" rx="2" fill="#93c5fd" />
                                <line x1="24" y1="28" x2="40" y2="28" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
                                <line x1="24" y1="36" x2="40" y2="36" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
                                <circle cx="44" cy="44" r="14" fill="#22c55e" />
                                <polyline points="38 44 42 48 50 38" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div>
                                <div class="verify-title">ยืนยันตัวตนสำเร็จ</div>
                                <p class="verify-text">
                                    คุณสามารถเริ่มเรียนคอร์สเรียนและดำเนินการสอบได้ทันที<br>
                                    กรณีต้องการแก้ไขบัตรประชาชนหรือรูปถ่ายปัจจุบันกรุณาติดต่อ<br>
                                    Admin Line: @cpdth (มี @ ข้างหน้า)
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tab-address">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <h3 class="tab-title m-0"><i class="bi bi-geo-alt-fill"></i> ที่อยู่ออกใบกำกับภาษี</h3>
                            <button class="btn-add-address">+ เพิ่มที่อยู่</button>
                        </div>

                        <div class="address-card">
                            <div class="address-default">ค่าเริ่มต้น</div>
                            <div class="address-edit"><i class="bi bi-pencil-fill"></i></div>
                            <p class="address-text">
                                <strong>สุรพงษ์</strong><br>
                                เลขประจำตัวประชาชน : 1269900111121<br>
                                เบอร์โทรศัพท์ : 0876741016<br>
                                189/211 ตำบลราชาเทวะ อำเภอบางพลี จังหวัดสมุทรปราการ 10540
                            </p>
                        </div>

                        <!-- เพิ่ม Pagination สำหรับที่อยู่ออกใบกำกับภาษี -->
                        <div class="d-flex justify-content-center mt-4 pb-3">
                            <ul class="pagination">
                                <li class="page-item disabled"><a class="page-link" href="#" onclick="return false;"><i class="bi bi-chevron-left"></i></a></li>
                                <li class="page-item active"><a class="page-link" href="#" onclick="return false;">1</a></li>
                                <li class="page-item disabled"><a class="page-link" href="#" onclick="return false;"><i class="bi bi-chevron-right"></i></a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="tab-pane" id="tab-course">
                        <h3 class="tab-title"><i class="bi bi-play-btn-fill"></i> คอร์สเรียนของฉัน</h3>

                        <div class="my-course-card">
                            <div class="course-img-box bg-placeholder-gray"></div>
                            <div class="my-course-info">
                                <div class="my-course-title">ทดสอบ</div>
                                <div class="my-course-meta">โดย 1</div>
                                <div class="my-course-meta">CPD บัญชี 1 จรรยาบรรณ 1 อื่นๆ 1</div>
                                <div class="my-course-meta">CPA บัญชี 1 จรรยาบรรณ 1 อื่นๆ 1</div>
                                <div class="my-course-meta">วันที่อบรม 3 มี.ค. 2025 16:58 ถึง 4 มี.ค. 2025 23:59</div>
                                <div class="text-expired">คอร์สเรียนหมดอายุ</div>
                            </div>
                        </div>

                        <div class="my-course-card">
                            <div class="course-img-box bg-placeholder-gray"></div>
                            <div class="my-course-info">
                                <div class="my-course-title">ทดสอบ (รายการที่ 2)</div>
                                <div class="my-course-meta">โดย 1</div>
                                <div class="my-course-meta">CPD บัญชี 1 จรรยาบรรณ 1 อื่นๆ 1</div>
                                <div class="my-course-meta">CPA บัญชี 1 จรรยาบรรณ 1 อื่นๆ 1</div>
                                <div class="my-course-meta">วันที่อบรม 5 มี.ค. 2025 10:00 ถึง 6 มี.ค. 2025 23:59</div>
                                <div class="text-expired">คอร์สเรียนหมดอายุ</div>
                            </div>
                        </div>

                        <div class="my-course-card">
                            <div class="course-img-box bg-placeholder-pink">
                                <div class="course-img-text-main">คอร์ส<br>สำหรับปี 2567<br>ไม่สามารถซื้อได้แล้ว</div>
                                <div class="course-img-text-danger">ไม่สามารถสอบได้แล้ว</div>
                            </div>
                            <div class="my-course-info">
                                <div class="my-course-title title-strike">มาตรฐานการรายงานทางการเงิน ฉบับที่ 15 รายได้จากสัญญาที่ทำกับลูกค้า (ปี 67)</div>
                                <div class="my-course-meta">โดย สุรพงษ์ ลักษณานุกูล</div>
                                <div class="my-course-meta">CPD บัญชี 6.30 จรรยาบรรณ 0.00 อื่นๆ 0.00</div>
                                <div class="my-course-meta">CPA บัญชี 6.00 จรรยาบรรณ 0.30 อื่นๆ 0.00</div>
                                <div class="my-course-meta">วันที่อบรม 3 ม.ค. 2024 18:10 ถึง 2 เม.ย. 2024 23:59</div>
                                <div class="my-course-meta">ระยะเวลาอบรม 89 วัน</div>
                                <div class="text-expired">คอร์สเรียนหมดอายุ</div>
                            </div>
                        </div>

                        <div class="my-course-card">
                            <div class="course-img-box bg-placeholder-pink">
                                <div class="course-img-text-main">คอร์ส<br>สำหรับปี 2567<br>ไม่สามารถซื้อได้แล้ว</div>
                                <div class="course-img-text-danger">ไม่สามารถสอบได้แล้ว</div>
                            </div>
                            <div class="my-course-info">
                                <div class="my-course-title title-strike">อัปเดตภาษีอากรและแนวทางปฏิบัติที่สำคัญ (ปี 67)</div>
                                <div class="my-course-meta">โดย สุรพงษ์ ลักษณานุกูล</div>
                                <div class="my-course-meta">CPD บัญชี 0.00 จรรยาบรรณ 0.00 อื่นๆ 6.00</div>
                                <div class="my-course-meta">CPA บัญชี 0.00 จรรยาบรรณ 0.00 อื่นๆ 6.00</div>
                                <div class="my-course-meta">วันที่อบรม 10 ม.ค. 2024 09:00 ถึง 10 เม.ย. 2024 23:59</div>
                                <div class="my-course-meta">ระยะเวลาอบรม 90 วัน</div>
                                <div class="text-expired">คอร์สเรียนหมดอายุ</div>
                            </div>
                        </div>

                        <div class="my-course-card">
                            <img src="https://via.placeholder.com/600x320/0a2240/ffffff?text=Course+1" alt="Course" class="my-course-img">
                            <div class="my-course-info">
                                <div class="my-course-title">จับมือแก้ไขกรรมการเพิ่ม/ลด และแก้ไขอำนาจกรรมการ e-Registration</div>
                                <div class="my-course-meta">โดย สุรพงษ์</div>
                                <div class="my-course-meta">ไม่มีวันหมดอายุ</div>
                                <button class="btn-enter-course"><i class="bi bi-mortarboard-fill"></i> เข้าสู่บทเรียน</button>
                            </div>
                        </div>

                        <div class="my-course-card">
                            <img src="https://via.placeholder.com/600x320/0a2240/ffffff?text=Course+2" alt="Course" class="my-course-img">
                            <div class="my-course-info">
                                <div class="my-course-title">จับมือแก้ไขที่อยู่บริษัท e-Registration</div>
                                <div class="my-course-meta">โดย สุรพงษ์</div>
                                <div class="my-course-meta">ไม่มีวันหมดอายุ</div>
                                <button class="btn-enter-course"><i class="bi bi-mortarboard-fill"></i> เข้าสู่บทเรียน</button>
                            </div>
                        </div>

                        <div class="my-course-card">
                            <img src="https://via.placeholder.com/600x320/0a2240/ffffff?text=Course+3" alt="Course" class="my-course-img">
                            <div class="my-course-info">
                                <div class="my-course-title">จัดมือจดบริษัทผ่าน e Registration</div>
                                <div class="my-course-meta">โดย สุรพงษ์</div>
                                <div class="my-course-meta">ไม่มีวันหมดอายุ</div>
                                <button class="btn-enter-course"><i class="bi bi-mortarboard-fill"></i> เข้าสู่บทเรียน</button>
                            </div>
                        </div>

                    </div>

                    <div class="tab-pane" id="tab-history">
                        <h3 class="tab-title"><i class="bi bi-clock-history"></i> ประวัติการชำระเงิน</h3>
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>หมายเลขคำสั่งซื้อ</th>
                                        <th>ราคา</th>
                                        <th>วันที่ทำรายการ</th>
                                        <th class="text-center">สถานะ</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="#">1</td>
                                        <td data-label="หมายเลขคำสั่งซื้อ">VAT2601001</td>
                                        <td data-label="ราคา" class="text-end">698</td>
                                        <td data-label="วันที่ทำรายการ">05/01/2026 13:05</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-success-badge">ดำเนินการแล้ว</span></td>
                                        <td data-label=""><button class="btn-action"><i class="bi bi-three-dots"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td data-label="#">2</td>
                                        <td data-label="หมายเลขคำสั่งซื้อ">ไม่มีข้อมูล</td>
                                        <td data-label="ราคา" class="text-end">1</td>
                                        <td data-label="วันที่ทำรายการ">31/12/2025 20:18</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-orange-badge">รอชำระเงิน</span></td>
                                        <td data-label=""><button class="btn-action"><i class="bi bi-three-dots"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td data-label="#">3</td>
                                        <td data-label="หมายเลขคำสั่งซื้อ">ไม่มีข้อมูล</td>
                                        <td data-label="ราคา" class="text-end">399</td>
                                        <td data-label="วันที่ทำรายการ">27/11/2025 17:00</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-warning-badge">หมดอายุแล้ว</span></td>
                                        <td data-label=""><button class="btn-action"><i class="bi bi-three-dots"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td data-label="#">4</td>
                                        <td data-label="หมายเลขคำสั่งซื้อ">VAT2511133</td>
                                        <td data-label="ราคา" class="text-end">1</td>
                                        <td data-label="วันที่ทำรายการ">04/11/2025 21:15</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-success-badge">ดำเนินการแล้ว</span></td>
                                        <td data-label=""><button class="btn-action"><i class="bi bi-three-dots"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td data-label="#">5</td>
                                        <td data-label="หมายเลขคำสั่งซื้อ">ไม่มีข้อมูล</td>
                                        <td data-label="ราคา" class="text-end">399</td>
                                        <td data-label="วันที่ทำรายการ">25/10/2025 22:00</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-warning-badge">หมดอายุแล้ว</span></td>
                                        <td data-label=""><button class="btn-action"><i class="bi bi-three-dots"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <ul class="pagination">
                            <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link border-0 text-muted" href="#">...</a></li>
                            <li class="page-item"><a class="page-link" href="#">5</a></li>
                            <li class="page-item"><a class="page-link" href="#">6</a></li>
                            <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </div>

                    <div class="tab-pane" id="tab-tax">
                        <h3 class="tab-title"><i class="bi bi-receipt"></i> ใบกำกับภาษี</h3>
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>เลขที่เอกสาร</th>
                                        <th class="text-center">ชื่อ</th>
                                        <th>วันที่ในเอกสาร</th>
                                        <th class="text-center">สถานะ</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="เลขที่เอกสาร">ET26010000003</td>
                                        <td data-label="ชื่อ">สุรพงษ์</td>
                                        <td data-label="วันที่ในเอกสาร">05/01/2569</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-success-badge">ออกใบกำกับภาษีแล้ว</span></td>
                                        <td data-label="" class="text-center"><button class="btn-download">ดาวน์โหลด</button></td>
                                    </tr>
                                    <tr>
                                        <td data-label="เลขที่เอกสาร">ET25110000132</td>
                                        <td data-label="ชื่อ">สุรพงษ์</td>
                                        <td data-label="วันที่ในเอกสาร">04/11/2568</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-success-badge">ออกใบกำกับภาษีแล้ว</span></td>
                                        <td data-label="" class="text-center"><button class="btn-download">ดาวน์โหลด</button></td>
                                    </tr>
                                    <tr>
                                        <td data-label="เลขที่เอกสาร">ET25100000374</td>
                                        <td data-label="ชื่อ">สุรพงษ์</td>
                                        <td data-label="วันที่ในเอกสาร">21/10/2568</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-success-badge">ออกใบกำกับภาษีแล้ว</span></td>
                                        <td data-label="" class="text-center"><button class="btn-download">ดาวน์โหลด</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <ul class="pagination">
                            <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </div>

                    <div class="tab-pane" id="tab-cert">
                        <h3 class="tab-title"><i class="bi bi-file-earmark-check-fill"></i> ใบรับรองการสอบ</h3>
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th class="text-center">หมายเลขใบรับรอง</th>
                                        <th>ชื่อคอร์สเรียน</th>
                                        <th class="text-center">วิทยากร</th>
                                        <th class="text-center">คะแนนสอบเฉลี่ย</th>
                                        <th class="text-center">สถานะ</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="#">1</td>
                                        <td data-label="หมายเลขใบรับรอง">25100295</td>
                                        <td data-label="ชื่อคอร์สเรียน">วิเคราะห์ธุรกิจผ่านงบการเงินและอัตราส่วน (ปี 68)</td>
                                        <td data-label="วิทยากร">สุรพงษ์ ลักษณานุกูล</td>
                                        <td data-label="คะแนนสอบเฉลี่ย">88.57 %</td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-badge bg-success-badge">อนุมัติ</span></td>
                                        <td data-label="" class="text-center"><button class="btn-download">ดาวน์โหลดใบรับรอง</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <ul class="pagination">
                            <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </div>

                    <div class="tab-pane" id="tab-password">
                        <h3 class="tab-title"><i class="bi bi-lock-fill"></i> แก้ไขรหัสผ่าน</h3>

                        <form action="#" method="POST">
                            <div class="form-group-pw">
                                <label>รหัสผ่านใหม่</label>
                                <input type="password" class="form-control-pw" placeholder="**********">
                                <i class="bi bi-lock-fill pw-icon"></i>
                            </div>

                            <div class="form-group-pw">
                                <label>ยืนยันรหัสผ่าน</label>
                                <input type="password" class="form-control-pw" placeholder="**********">
                                <i class="bi bi-lock-fill pw-icon"></i>
                            </div>

                            <div class="pw-actions">
                                <button type="button" class="btn-cancel">ยกเลิก</button>
                                <button type="submit" class="btn-save">บันทึก</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</main>

<script>
    // สคริปต์เสริมสำหรับควบคุม Tab และ Dropdown รับประกันการทำงาน 100%
    document.addEventListener('DOMContentLoaded', function() {
        const mobileBtn = document.getElementById('customMobileBtn');
        const mobileMenu = document.getElementById('customMobileMenu');

        // 1. เปิด/ปิด Dropdown มือถือ
        mobileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            mobileMenu.classList.toggle('show');
            this.classList.toggle('open');
        });

        // 2. ปิด Dropdown เมื่อคลิกพื้นที่อื่น
        document.addEventListener('click', function(e) {
            if (!mobileBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.remove('show');
                mobileBtn.classList.remove('open');
            }
        });

        // 3. ฟังก์ชันสลับ Tab (รองรับทั้งมือถือและคอมพิวเตอร์)
        function switchTab(targetId, newText) {
            // ซ่อน Tab เก่าทั้งหมด
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            // เอาแถบ Active ออกจากเมนูทุกตัว
            document.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
                link.classList.remove('active');
            });

            // แสดง Tab ใหม่
            const targetPane = document.querySelector(targetId);
            if(targetPane) targetPane.classList.add('show', 'active');

            // อัปเดตเมนู Desktop ให้ตรงกัน
            const desktopLink = document.querySelector(`.nav-link[data-target="${targetId}"]`);
            if(desktopLink) desktopLink.classList.add('active');

            // อัปเดตเมนู Mobile ให้ตรงกัน และเปลี่ยนชื่อปุ่ม
            const mobileLink = document.querySelector(`.dropdown-item[data-target="${targetId}"]`);
            if(mobileLink) mobileLink.classList.add('active');
            if(newText) mobileBtn.innerText = newText;
        }

        // จัดการเมื่อคลิกเมนูจากมือถือ
        document.querySelectorAll('.custom-dropdown-menu .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if(this.classList.contains('text-danger')) return; // ถ้าเป็นปุ่มออกจากระบบ ให้ข้ามไป
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
            item.addEventListener('click', function(e) {
                if(this.classList.contains('logout-link')) return; // ถ้าเป็นปุ่มออกจากระบบ ให้ข้ามไป
                e.preventDefault();

                const targetId = this.getAttribute('data-target');
                const menuText = this.querySelector('.nav-link-text').innerText;

                switchTab(targetId, menuText);
            });
        });
    });
</script>

<?php include 'components/footer.php'; ?>