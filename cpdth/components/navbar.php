<?php
    // เช็คชื่อไฟล์ปัจจุบัน เพื่อให้แถบตัวหนังสือเปลี่ยนเป็นสีน้ำเงินตามหน้าที่เปิดอยู่
    $current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<style>
    .nav-link.active, .sidebar-link.active {
        pointer-events: none;
        cursor: default;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid px-5 navbar-inner">

        <a class="navbar-brand ps-0 me-0" href="<?php echo $base_url ?>index.php">
<img src="<?php echo $base_url ?>assets/images/logo/am-group-logo.png" alt="AM GROUP" height="52">
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#navSidebar" aria-controls="navSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto gap-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'index.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>index.php">หน้าแรก</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo($current_page == 'categories.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>categories.php" data-bs-toggle="dropdown" data-bs-display="static" onclick="window.location.href='<?php echo $base_url ?>categories.php'">หมวดหมู่</a>
                    <ul class="dropdown-menu" id="nav-categories-dropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_url ?>categories.php?group_id=0">หมวดหมู่ทั้งหมด</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'lecturer.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>lecturer.php">วิทยากร</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'user-guide.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>user-guide.php">คู่มือการใช้งาน</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo($current_page == 'contact.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>contact.php">ติดต่อเรา</a>
                </li>
            </ul>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-start nav-sidebar" tabindex="-1" id="navSidebar">
    <div class="offcanvas-header">
        <a href="<?php echo $base_url ?>index.php">
            <img src="<?php echo $base_url ?>assets/images/logo/am-group-logo.png" alt="AM GROUP" height="44">
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="list-unstyled sidebar-nav mb-0">
            <li>
                <a class="sidebar-link <?php echo($current_page == 'index.php' || $current_page == '') ? 'active' : '' ?>" href="<?php echo $base_url ?>index.php">หน้าแรก</a>
            </li>
            <li>
                <a class="sidebar-link d-flex justify-content-between align-items-center <?php echo($current_page == 'categories.php') ? 'active' : '' ?>"
                    href="<?php echo $base_url ?>categories.php" data-bs-toggle="collapse" data-bs-target="#sidebarCategory" aria-expanded="false">
                    หมวดหมู่
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse" id="sidebarCategory">
                    <ul class="list-unstyled sidebar-sub mb-0" id="sidebar-categories-dropdown">
                        <li><a class="sidebar-sub-link" href="<?php echo $base_url ?>categories.php?group_id=0">หมวดหมู่ทั้งหมด</a></li>
                    </ul>
                </div>
            </li>
            <li><a class="sidebar-link <?php echo($current_page == 'lecturer.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>lecturer.php">วิทยากร</a></li>
            <li><a class="sidebar-link <?php echo($current_page == 'user-guide.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>user-guide.php">คู่มือการใช้งาน</a></li>
            <li><a class="sidebar-link <?php echo($current_page == 'contact.php') ? 'active' : '' ?>" href="<?php echo $base_url ?>contact.php">ติดต่อเรา</a></li>
        </ul>
    </div>
</div>

<script>
$(document).ready(function() {
    let categoriesLoaded = false;

    function renderNavbarCategories(groups) {
        // desktop dropdown
        const dDropdown = $('#nav-categories-dropdown');
        dDropdown.find('li:not(:first)').remove();
        
        // mobile sidebar dropdown
        const mDropdown = $('#sidebar-categories-dropdown');
        mDropdown.find('li:not(:first)').remove();
        
        groups.forEach(function(g) {
            const url = `<?php echo $base_url ?>categories.php?group_id=${g.group_id}`;
            dDropdown.append(`<li><a class="dropdown-item" href="${url}">${g.group_name}</a></li>`);
            mDropdown.append(`<li><a class="sidebar-sub-link" href="${url}">${g.group_name}</a></li>`);
        });
    }

    function loadNavbarCategories() {
        // ลองดึงจาก Cache (sessionStorage) ก่อนเพื่อความเร็วสูงและไม่ต้องเห็นการโหลดหมุนๆ
        const cachedGroups = sessionStorage.getItem('navbar_categories');
        if (cachedGroups) {
            try {
                const groups = JSON.parse(cachedGroups);
                renderNavbarCategories(groups);
                return;
            } catch (e) {
                console.error("Error parsing cached categories:", e);
            }
        }

        $.ajax({
            type: "POST",
            url: "<?php echo $base_url ?>core.php",
            data: {
                request_state: "course",
                request_function: "list",
                group_id: 0,
                page: 1
            },
            dataType: "json",
            success: function(response) {
                let isSuccess = (response.result == 1 || response.status == 1);
                if (isSuccess && response.data && response.data.groups) {
                    const groups = response.data.groups;
                    // บันทึกเก็บใน Cache
                    sessionStorage.setItem('navbar_categories', JSON.stringify(groups));
                    renderNavbarCategories(groups);
                }
            }
        });
    }

    // ดึงข้อมูลหมวดหมู่ทั้งหมดทันทีที่หน้าเว็บโหลดเสร็จ
    loadNavbarCategories();
});


</script>