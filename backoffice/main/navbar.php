<?php
    $breadcrumbs = $breadcrumbs ?? [];
    $page_title  = ! empty($breadcrumbs) ? (end($breadcrumbs)['label'] ?? '') : '';
?>
<!-- Start Header / Navbar Area -->
<header class="header-area bg-white mb-4 p-0 shadow-sm border-0 d-flex justify-content-between align-items-center flex-wrap" id="header-area" style="border-bottom: 1px solid #eef0f3 !important; min-height: 56px;">
    <div class="d-flex align-items-center gap-3 ps-3 py-2">
        <!-- Mobile Toggle Button (Visible only on screen < 1200px) -->
        <a href="javascript:void(0);" onclick="toggleMobileSidebar(event)" class="d-xl-none text-dark d-flex align-items-center text-decoration-none p-2 rounded-3 bg-light" id="mobileMenuToggle" title="เปิดเมนู">
            <span class="material-symbols-outlined fs-22">menu</span>
        </a>
    </div>

    <!-- Right Side: Action Icons (Chat, Notification) + Highlighted User Profile Card -->
    <div class="d-flex align-items-center ms-auto">
        <!-- Action Icons (Chat Bell) -->
        <div class="d-flex align-items-center gap-3 me-3 px-2">

            <!-- Notification Dropdown -->
            <div class="dropdown">
                 <a href="#" class="btn-header-action text-decoration-none d-flex align-items-center justify-content-center position-relative" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="การแจ้งเตือน" id="bellIconBtn">
                    <span class="material-symbols-outlined text-success">notifications_active</span>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notificationBadge" style="font-size: 0.65rem;">
                        0
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown mt-2">
                    <div class="notification-header bg-primary">
                        <h6 class="text-white m-0 fw-semibold">การแจ้งเตือน</h6>
                        <button class="btn-no-new" id="markAllReadBtn">ทำเครื่องหมายว่าอ่านแล้ว</button>
                    </div>

                    <div class="notification-body" id="notificationList">
                        <!-- สำหรับแสดงข้อความแจ้งเตือนที่เข้ามาใหม่ -->
                        <div class="text-center py-4 text-muted">
                            <div class="fs-14">ยังไม่มีการแจ้งเตือนใหม่</div>
                        </div>
                    </div>

                    <div class="notification-footer">
                        <a href="notifications.php" class="btn-read-all">
                            อ่านทั้งหมด
                            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Highlighted User Profile Card Block with Dropdown -->
        <div class="dropdown position-relative">
            <button class="user-profile-card d-flex align-items-center gap-2 px-3 py-2 border-0 text-decoration-none dropdown-toggle-user" type="button" id="userProfileDropdown" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false">
                <div class="d-flex flex-column text-start me-1" style="max-width: 200px;">
                    <span class="fw-semibold text-dark text-truncate ShowUserFullname fs-14" style="line-height: 1.3;"></span>
                    <span class="small text-muted text-truncate ShowUserRole fs-12" style="line-height: 1.3;"></span>
                </div>
                <span class="material-symbols-outlined dropdown-chevron-icon fs-20 text-secondary ms-1">expand_more</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end profile-dropdown-menu border-0 shadow-lg" aria-labelledby="userProfileDropdown" onclick="event.stopPropagation()">
                <div class="dropdown-item-toggle d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <!-- <div class="toggle-icon-wrapper d-flex align-items-center justify-content-center me-2">
                            <span class="material-symbols-outlined fs-18 text-primary">notifications</span>
                        </div> -->
                        <span class="fs-13 mb-0" style="line-height: 1.2;">อนุญาตการแจ้งเตือน</span>
                    </div>
                    <label class="custom-nav-switch mb-0 ms-3">
                        <input type="checkbox" id="navNotificationToggle" onchange="toggleNotificationPermission(this)">
                        <span class="custom-nav-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Logout Link -->
            <a href="logout" class="nav-logout-icon text-decoration-none d-flex align-items-center justify-content-center ms-1" title="ออกจากระบบ">
                <span class="material-symbols-outlined fs-20 text-danger">logout</span>
            </a>
    </div>
</header>
<!-- End Header / Navbar Area -->

<style>
.header-area {
    margin-top: -1.5rem;
    margin-left: -12px;
    margin-right: -12px;
    padding-left: 12px !important;
    padding-right: 12px !important;
    position: relative;
}

@media (min-width: 1200px) {
    .header-area {
        margin-left: -24px;
        margin-right: -24px;
    }
}
.header-area a,
.header-area a:hover,
.header-area a:focus {
    text-decoration: none !important;
}

/* Action Icons (Chat & Bell) */
.btn-header-action {
    color: #64748b !important;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    transition: all 0.2s ease-in-out;
}
.btn-header-action:hover {
    color: #1e293b !important;
    background-color: #f1f5f9;
}

/* Highlighted User Profile Card & Dropdown Toggle */
.user-profile-card {
    background-color: #f0f3fa !important;
    min-height: 48px;
    padding-top: 6px !important;
    padding-bottom: 6px !important;
    border-radius: 0 !important;
    margin: 0 !important;
    transition: background-color 0.2s ease, box-shadow 0.2s ease !important;
    cursor: pointer;
}

.user-profile-card:hover,
.user-profile-card:focus,
.dropdown.show .user-profile-card {
    background-color: #e2e8f0 !important;
}

.dropdown-chevron-icon {
    transition: transform 0.25s ease !important;
}

.dropdown.show .dropdown-chevron-icon {
    transform: rotate(180deg);
}

@keyframes navFadeInSlideDown {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-dropdown-menu {
    min-width: 0px;
    padding: 8px !important;
    border-radius: 0 !important;
    background-color: #ffffff;
    border: 1px solid #eef0f3 !important;
    box-shadow: 0 12px 32px -4px rgba(15, 23, 42, 0.12), 0 4px 12px -2px rgba(15, 23, 42, 0.06) !important;
    position: absolute !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
    margin: 0 !important;
    margin-top: 0 !important;
}

.profile-dropdown-menu.show,
.dropdown.show .profile-dropdown-menu {
    display: block !important;
    animation: navFadeInSlideDown 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
}

.dropdown-item-toggle {
    background-color: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 0 !important;
    padding: 10px 12px;
    transition: all 0.2s ease;
}

.dropdown-item-toggle:hover {
    background-color: #f0f4f9;
    border-color: #e2e8f0;
}

.toggle-icon-wrapper {
    width: 34px;
    height: 34px;
    background-color: #eff6ff;
    border-radius: 0 !important;
    color: #2563eb;
    flex-shrink: 0;
}

/* Custom Modern Soft Toggle Switch */
.custom-nav-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.custom-nav-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.custom-nav-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 24px;
}

.custom-nav-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: #ffffff;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.custom-nav-switch input:checked + .custom-nav-slider {
    background-color: #2563eb;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.custom-nav-switch input:checked + .custom-nav-slider:before {
    transform: translateX(20px);
}

.custom-nav-switch input:focus + .custom-nav-slider {
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.nav-logout-icon {
    color: #94a3b8 !important;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    transition: all 0.2s ease-in-out;
}
.nav-logout-icon:hover {
    color: #ef4444 !important;
    background-color: #fee2e2;
}

/* Notification Dropdown Custom Styles */
.notification-dropdown {
    width: 320px;
    padding: 0;
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    overflow: hidden;
}

@media (max-width: 575.98px) {
    .header-area .dropdown {
        position: static !important;
    }
    .notification-dropdown {
        position: absolute !important;
        left: 12px !important;
        right: 12px !important;
        width: auto !important;
        max-width: none !important;
        transform: none !important;
        top: 100% !important;
        margin-top: 4px !important;
        border-radius: 12px !important;
        z-index: 1050 !important;
    }
    .profile-dropdown-menu {
        position: absolute !important;
        left: 12px !important;
        right: 12px !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        top: 100% !important;
        margin-top: 4px !important;
        border-radius: 0 !important;
        z-index: 1050 !important;
    }
}
.notification-header {
    color: white;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.notification-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: 16px;
}
.btn-no-new {
    background-color: #f8f9fa;
    color: #333;
    border: none;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}
.notification-body {
    max-height: 350px;
    overflow-y: auto;
}
.notification-item {
    display: flex;
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.2s;
}
.notification-item:hover {
    background-color: #f8fafc;
}
.notification-item:last-child {
    border-bottom: none;
}
.notification-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-right: 12px;
}
.notification-icon-wrapper .material-symbols-outlined {
    font-size: 20px;
}
.notification-content {
    flex-grow: 1;
}
.notification-title {
    font-weight: 600;
    color: #334155;
    font-size: 14px;
    margin-bottom: 4px;
    line-height: 1.3;
}
.notification-desc {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 6px;
    line-height: 1.4;
}
.notification-time {
    color: #94a3b8;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.notification-time .material-symbols-outlined {
    font-size: 12px;
}
.notification-footer {
    padding: 12px;
    text-align: center;
    background-color: #ffffff;
    border-top: 1px solid #f1f5f9;
}
.btn-read-all {
    background-color: #e2e8f0;
    color: #3b5082;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: background-color 0.2s;
}
.btn-read-all:hover {
    background-color: #cbd5e1;
    color: #3b5082;
}

@media (max-width: 1199px) {
    .sidebar-area {
        position: fixed !important;
        top: 0 !important;
        left: -400px !important;
        height: 100vh !important;
        width: 270px !important;
        z-index: 1045 !important;
        transition: all 0.3s ease-in-out !important;
        background-color: #fff !important;
        transform: none !important;
        opacity: 1 !important;
        visibility: visible !important;
        display: block !important;
    }
    .sidebar-area.mobile-show {
        left: 0 !important;
        box-shadow: 0 0 15px rgba(0,0,0,0.1) !important;
    }
    .sidebar-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
    }
    .sidebar-backdrop.mobile-show {
        display: block;
    }
}
</style>

<!-- Backdrop -->
<div class="sidebar-backdrop" id="mobileSidebarBackdrop" onclick="toggleMobileSidebar(event)"></div>

<script>
    function toggleMobileSidebar(e) {
        if(e) e.preventDefault();
        var sidebar = document.getElementById('sidebar-area');
        var backdrop = document.getElementById('mobileSidebarBackdrop');
        if (sidebar) sidebar.classList.toggle('mobile-show');
        if (backdrop) backdrop.classList.toggle('mobile-show');
    }

    // --- Notification System Logic ---
    function fetchNotifications() {
        fetch('core/notification/GetNotifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update Badge
                    const badge = document.getElementById('notificationBadge');
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }

                    // Update List
                    const list = document.getElementById('notificationList');
                    list.innerHTML = ''; // Clear current

                    if (data.notifications.length === 0) {
                        list.innerHTML = `
                            <div class="text-center py-4 text-muted">
                                <div class="fs-14">ยังไม่มีการแจ้งเตือนใหม่</div>
                            </div>
                        `;
                    } else {
                        data.notifications.forEach(noti => {
                            let icon = 'notifications';
                            let iconColorClass = 'text-primary';
                            let bgColorClass = 'bg-primary bg-opacity-10';

                            if (noti.type === 'user_verify') {
                                icon = 'how_to_reg';
                                iconColorClass = 'text-warning';
                                bgColorClass = 'bg-warning bg-opacity-10';
                            } else if (noti.type === 'course_approval') {
                                icon = 'school';
                                iconColorClass = 'text-info';
                                bgColorClass = 'bg-info bg-opacity-10';
                            } else if (noti.type === 'new_order') {
                                icon = 'shopping_cart';
                                iconColorClass = 'text-success';
                                bgColorClass = 'bg-success bg-opacity-10';
                            }

                            // Calculate relative time (simplified)
                            const date = new Date(noti.created_at);
                            const now = new Date();
                            const diffMs = now - date;
                            const diffMins = Math.round(diffMs / 60000);
                            let timeStr = diffMins + ' นาทีที่แล้ว';
                            if (diffMins > 60) {
                                const diffHrs = Math.round(diffMins / 60);
                                timeStr = diffHrs + ' ชม. ที่แล้ว';
                                if (diffHrs > 24) {
                                    timeStr = Math.round(diffHrs / 24) + ' วันที่แล้ว';
                                }
                            }

                            const isUnreadClass = noti.is_read == 0 ? 'bg-light' : '';

                            list.innerHTML += `
                                <a href="${noti.link_url || '#'}" class="notification-item ${isUnreadClass}" onclick="markAsRead(${noti.noti_id}, this)">
                                    <div class="notification-icon-wrapper ${bgColorClass} ${iconColorClass}">
                                        <span class="material-symbols-outlined">${icon}</span>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title ${noti.is_read == 0 ? 'text-dark' : 'text-muted'}">${noti.title}</div>
                                        <div class="notification-desc">${noti.message}</div>
                                        <div class="notification-time">
                                            <span class="material-symbols-outlined">schedule</span>
                                            ${timeStr}
                                        </div>
                                    </div>
                                </a>
                            `;
                        });
                    }
                }
            })
            .catch(err => console.error("Error fetching notifications:", err));
    }

    function markAsRead(notiId, element) {
        fetch('core/notification/MarkAsRead.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'noti_id=' + notiId
        }).then(() => {
            if (element) {
                element.classList.remove('bg-light');
                const title = element.querySelector('.notification-title');
                if (title) {
                    title.classList.remove('text-dark');
                    title.classList.add('text-muted');
                }
                fetchNotifications(); // Refresh count
            }
        });
    }

    // Mark all as read
    document.getElementById('markAllReadBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        markAsRead('all', null);
        setTimeout(fetchNotifications, 500);
    });

    // Initial fetch
    fetchNotifications();
    // Poll every 60 seconds
    setInterval(fetchNotifications, 60000);

    // --- Notification Permission Toggle Logic ---
    function updateNavNotificationToggleState() {
        const toggle = document.getElementById('navNotificationToggle');
        if (!toggle) return;

        const isUserDisabled = localStorage.getItem('cpdth_push_user_disabled') === 'true';
        const hasPermission = (typeof Notification !== 'undefined') && Notification.permission === 'granted';

        toggle.checked = hasPermission && !isUserDisabled;
    }

    async function toggleNotificationPermission(toggleElement) {
        if (toggleElement.checked) {
            localStorage.removeItem('cpdth_push_user_disabled');
            if (typeof Notification !== 'undefined' && Notification.permission === 'denied') {
                alert('คุณได้บล็อกการแจ้งเตือนในเบราว์เซอร์ไว้ กรุณาเปิดสิทธิ์การแจ้งเตือนในการตั้งค่าเบราว์เซอร์');
                toggleElement.checked = false;
                return;
            }

            if (typeof subscribeUserToPush === 'function') {
                const success = await subscribeUserToPush();
                if (!success) {
                    toggleElement.checked = false;
                }
            } else if (typeof Notification !== 'undefined') {
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') {
                    toggleElement.checked = false;
                }
            }
        } else {
            localStorage.setItem('cpdth_push_user_disabled', 'true');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateNavNotificationToggleState();
        
        const dropdownBtn = document.getElementById('userProfileDropdown');
        if (dropdownBtn) {
            dropdownBtn.addEventListener('show.bs.dropdown', () => {
                updateNavNotificationToggleState();
            });
        }
    });
</script>
