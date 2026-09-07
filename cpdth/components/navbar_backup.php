<?php
    // เช็คชื่อไฟล์ปัจจุบัน เพื่อให้แถบตัวหนังสือเปลี่ยนเป็นสีน้ำเงินตามหน้าที่เปิดอยู่
    $current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid px-5 navbar-inner">

        <a class="navbar-brand ps-0 me-0" href="/cpdth/">
<img src="assets/images/logo/am-group-logo.png" alt="AM GROUP" height="52">
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#navSidebar" aria-controls="navSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto gap-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'index.php') ? 'active' : '' ?>" href="https://bigsara-demo.com/am/cpdth/index.php">หน้าแรก</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo($current_page == 'categories.php') ? 'active' : '' ?>" href="categories.php" data-bs-toggle="dropdown" data-bs-display="static" onclick="window.location.href='categories.php'">หมวดหมู่</a>
                    <ul class="dropdown-menu">
                        <!-- แก้ไขจาก . เป็น หมวดหมู่ทั้งหมด และแก้คลาสให้เป็น dropdown-item -->
                        <li><a class="dropdown-item" href="categories.php?category=all">หมวดหมู่ทั้งหมด</a></li>
                        <li><a class="dropdown-item" href="categories.php?category=1">นับชั่วโมงจรรยาบรรณ (ผู้ทำ ผู้สอบ)</a></li>
                        <li><a class="dropdown-item" href="categories.php?category=2">นับชั่วโมง CPD - อื่นๆ (ผู้ทำ ผู้สอบ)</a></li>
                        <li><a class="dropdown-item" href="categories.php?category=3">นับชั่วโมง CPD - บัญชี (ผู้ทำ ผู้สอบ)</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'lecturer.php') ? 'active' : '' ?>" href="lecturer.php">วิทยากร</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'user-guide.php') ? 'active' : '' ?>" href="user-guide.php">คู่มือการใช้งาน</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'contact.php') ? 'active' : '' ?>" href="contact.php">ติดต่อเรา</a>
                </li>
            </ul>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-start nav-sidebar" tabindex="-1" id="navSidebar">
    <div class="offcanvas-header">
        <a href="/cpdth/">
            <img src="/cpdth/assets/images/logo/am-group-logo.png" alt="AM GROUP" height="44">
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="list-unstyled sidebar-nav mb-0">
            <li>
                <a class="sidebar-link <?php echo($current_page == 'index.php' || $current_page == '') ? 'active' : '' ?>" href="/cpdth/">หน้าแรก</a>
            </li>
            <li>
                <a class="sidebar-link d-flex justify-content-between align-items-center <?php echo($current_page == 'categories.php') ? 'active' : '' ?>"
                    href="categories.php" data-bs-toggle="collapse" data-bs-target="#sidebarCategory" aria-expanded="false">
                    หมวดหมู่
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse" id="sidebarCategory">
                    <ul class="list-unstyled sidebar-sub mb-0">
                        <!-- แก้ไขจาก . เป็น หมวดหมู่ทั้งหมด สำหรับ Sidebar -->
                        <li><a class="sidebar-sub-link" href="categories.php?category=all">หมวดหมู่ทั้งหมด</a></li>
                        <li><a class="sidebar-sub-link" href="categories.php?category=1">นับชั่วโมงจรรยาบรรณ (ผู้ทำ ผู้สอบ)</a></li>
                        <li><a class="sidebar-sub-link" href="categories.php?category=2">นับชั่วโมง CPD - อื่นๆ (ผู้ทำ ผู้สอบ)</a></li>
                        <li><a class="sidebar-sub-link" href="categories.php?category=3">นับชั่วโมง CPD - บัญชี (ผู้ทำ ผู้สอบ)</a></li>
                    </ul>
                </div>
            </li>
            <li><a class="sidebar-link <?php echo($current_page == 'lecturer.php') ? 'active' : '' ?>" href="lecturer.php">วิทยากร</a></li>
            <li><a class="sidebar-link <?php echo($current_page == 'user-guide.php') ? 'active' : '' ?>" href="user-guide.php">คู่มือการใช้งาน</a></li>
            <li><a class="sidebar-link <?php echo($current_page == 'contact.php') ? 'active' : '' ?>" href="contact.php">ติดต่อเรา</a></li>
        </ul>
    </div>
</div>