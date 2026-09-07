<?php $now_page            = str_replace('.php', '', basename($_SERVER['PHP_SELF'])); ?>
<?php $course_pages        = ['course', 'course_fromadd', 'course_edit', 'course_category', 'course_type', 'lesson_manage', 'lesson_preview']; ?>
<?php $remaining_pages     = ['course_remaining']; ?>
<?php $order_pages         = ['order', 'order_detail']; ?>
<?php $order_pending_pages = ['order_pending']; ?>
<?php $certificate_pages   = ['course_certificate']; ?>
<?php $etax_pages          = ['etax', 'etax_view', 'etax_edit']; ?>
<?php $etax_link_pages     = ['etax_link', 'etax_link_fromadd', 'etax_link_view']; ?>
<?php $report_pages        = ['report']; ?>
<?php $user_pages          = ['user', 'user_edit']; ?>
<?php $history_pages       = ['verify_history']; ?>
<?php $verify_pages        = ['verify_request']; ?>
<?php $bank_pages          = ['bank_setting']; ?>
<?php $address_pages       = ['address_setting']; ?>
<?php $chat_pages          = ['chat']; ?>
<?php $coupon_pages        = ['coupon', 'coupon_fromadd', 'coupon_edit']; ?>
<?php $setting_pages       = ['website_setting']; ?>
<?php $review_pages        = ['reviews']; ?>
<?php $banner_pages        = ['banner', 'banner_fromadd', 'banner_edit']; ?>
<?php $admin_pages         = ['admin', 'admin_fromadd', 'admin_edit']; ?>
<?php
    // จำนวนคำขอยืนยันตัวตนที่รอตรวจ (identity_verified = '1') สำหรับ badge ข้างเมนู
    $verify_pending = 0;
    try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $pdo_sidebar = (new \App\Database\Connection())->getPdo();
    if ($pdo_sidebar) {
        $stmt_sidebar = $pdo_sidebar->query(
            "SELECT COUNT(*) FROM tbl_user WHERE delete_at IS NULL AND identity_verified = '1'"
        );
        $verify_pending = (int) $stmt_sidebar->fetchColumn();
        $stmt_sidebar->closeCursor();
    }
    } catch (\Throwable $e) {
    $verify_pending = 0;
    }

    // จำนวนคำสั่งซื้อรอยืนยันการโอนเงิน (payment_status='0' AND payment_method='2') สำหรับ badge ข้างเมนู
    $pending_transfer_orders = 0;
    try {
    if (! empty($pdo_sidebar)) {
        $stmt_pending = $pdo_sidebar->query(
            "SELECT COUNT(*) FROM tbl_orders WHERE payment_status = '0' AND payment_method = '2'"
        );
        $pending_transfer_orders = (int) $stmt_pending->fetchColumn();
        $stmt_pending->closeCursor();
    }
    } catch (\Throwable $e) {
    $pending_transfer_orders = 0;
    }

    // จำนวนข้อความจากผู้เรียนที่ยังไม่อ่าน (sender_type='1' AND is_read='0') สำหรับ badge ข้างเมนู
    $chat_unread_msgs = 0;
    try {
    if (! empty($pdo_sidebar)) {
        $stmt_chat = $pdo_sidebar->query(
            "SELECT COUNT(*) FROM tbl_chat_messages
                 WHERE delete_at IS NULL AND sender_type = '1' AND is_read = '0'"
        );
        $chat_unread_msgs = (int) $stmt_chat->fetchColumn();
        $stmt_chat->closeCursor();
    }
    } catch (\Throwable $e) {
    $chat_unread_msgs = 0;
    }

    // จำนวนผู้สอบผ่านที่รอออกใบรับรอง (exam_result) สำหรับ badge ข้างเมนู
    $exam_result = 0;
    try {
    if (! empty($pdo_sidebar)) {
        $stmt_exam = $pdo_sidebar->query(
            "SELECT COUNT(*) FROM tbl_exam_attempt a
                 LEFT JOIN tbl_course_enrollment b ON a.attempt_enroll_id = b.enroll_id
                 WHERE a.attempt_pass = '1' AND b.enroll_is_completed = '0'"
        );
        $exam_result = (int) $stmt_exam->fetchColumn();
        $stmt_exam->closeCursor();
    }
    } catch (\Throwable $e) {
    $exam_result = 0;
    }
?>

<style>
    /* ซ่อนเมนูทั้งหมดเป็นค่าเริ่มต้นเพื่อป้องกันเมนูกระพริบตอนสลับหน้า */
    .sidebar-area .menu-item,
    .sidebar-area .menu-title {
        display: none;
    }

    /* ปรับโลโก้ให้ชิดและกระทัดรัด */
    .sidebar-area .logo {
        padding: 10px 14px !important;
        margin-bottom: 2px !important;
    }

    /* ปรับระยะห่างของหัวข้อหมวดหมู่ ให้ชิดกันยิ่งขึ้น */
    .sidebar-area .menu-title,
    .sidebar-area .menu-title:not(:first-child),
    .layout-menu .menu-title,
    .layout-menu .menu-title:not(:first-child) {
        margin-top: 2px !important;
        margin-bottom: 0px !important;
        padding: 0 14px !important;
        line-height: 1 !important;
    }
    .sidebar-area .menu-title .menu-title-text,
    .layout-menu .menu-title .menu-title-text {
        font-size: 0.65rem !important; /* ปรับขนาดฟอนต์หัวข้อหมวดหมู่ให้เล็กลง */
        letter-spacing: 0.03em !important;
    }

    /* บีบระยะห่างและขนาดฟอนต์ของเมนูทุกตัวใน sidebar */
    .sidebar-area .menu-vertical .menu-item,
    .sidebar-area .menu-item {
        margin: 0 !important;
        padding: 0 !important;
    }

    .sidebar-area .menu-vertical .menu-item .menu-link,
    .sidebar-area .menu-link,
    .layout-menu .menu-item .menu-link {
        margin: 1px 8px !important; /* บีบ margin รอบเมนู */
        padding: 4px 10px !important; /* บีบ padding บน-ล่าง จาก 9px เหลือ 4px */
        min-height: unset !important;
        height: auto !important;
        white-space: nowrap !important;
    }

    /* ปรับขนาดตัวหนังสือเมนู */
    .sidebar-area .menu-vertical .menu-item .menu-link .title,
    .sidebar-area .menu-link .title,
    .layout-menu .menu-item .menu-link .title {
        font-size: 13px !important; /* ปรับขนาดฟอนต์เมนูให้เล็กลงกว่าเดิม (0.73rem ≈ 11.5px) */
        line-height: 1.2 !important;
        overflow: hidden;
        text-overflow: ellipsis;
        flex-grow: 1;
    }

    /* ปรับขนาดไอคอนเมนู */
    .sidebar-area .menu-vertical .menu-item .menu-link .menu-icon,
    .sidebar-area .menu-link .menu-icon,
    .layout-menu .menu-item .menu-link .menu-icon {
        font-size: 0.95rem !important; /* ปรับไอคอนให้เล็กลงสมดุลกับฟอนต์ */
        margin-right: 6px !important;
    }

    /* ปรับตัว Badge แจ้งเตือน */
    .sidebar-area .menu-link .badge,
    .layout-menu .menu-item .menu-link .badge {
        font-size: 9px !important;
        padding: 2px 6px !important;
        min-width: 18px !important;
        flex-shrink: 0;
        margin-left: 0.4rem !important;
    }

    /* ซ่อน Scrollbar ของ sidebar */
    .layout-menu, .menu-inner {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    .layout-menu::-webkit-scrollbar, .menu-inner::-webkit-scrollbar {
        display: none; /* Chrome, Safari and Opera */
    }
    /* ซ่อน simplebar ถ้ามีการเรียกใช้ */
    .simplebar-scrollbar::before,
    .simplebar-track {
        display: none !important;
    }
    .simplebar-content {
        padding: 0 !important;
    }
</style>

<div class="sidebar-area" id="sidebar-area">

    <div class="logo position-relative">
        <div class="d-block text-decoration-none position-relative" style="cursor: default;">
            <img src="../assets/images/am-group-logo.png" alt="AM GROUP" style="height:40px; width:auto;">
            <span class="logo-text fw-bold text-dark ps-4 ">CPDTH</span>
        </div>
    </div>

    <aside id="layout-menu" class="layout-menu menu-vertical menu active" data-simplebar>
        <ul class="menu-inner">

            <li class="menu-title small text-uppercase" style="display: none;">
                <span class="menu-title-text">หน้าหลัก</span>
            </li>

            <li class="menu-item <?php echo $now_page == 'home' ? 'open' : '' ?>" style="display: none;">
                <a href="home" class="menu-link <?php echo $now_page == 'home' ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">home</span>
                    <span class="title">หน้าแรก</span>
                </a>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">คอร์สเรียน</span>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $course_pages) ? 'open' : '' ?>">
                <a href="course" class="menu-link <?php echo in_array($now_page, $course_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">school</span>
                    <span class="title">คอร์สเรียน</span>
                </a>
            </li>
           <li class="menu-item <?php echo in_array($now_page, $remaining_pages) ? 'open' : '' ?>">
                <a href="course_remaining" class="menu-link <?php echo in_array($now_page, $remaining_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">inventory</span>
                    <span class="title">คอร์สเรียนคงเหลือ</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $order_pages) ? 'open' : '' ?>">
                <a href="order" class="menu-link <?php echo in_array($now_page, $order_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">receipt_long</span>
                    <span class="title">คำสั่งซื้อคอร์สเรียน</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $chat_pages) ? 'open' : '' ?>">
                <a href="chat" class="menu-link <?php echo in_array($now_page, $chat_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">forum</span>
                    <span class="title">ตอบคำถามผู้เรียน</span>
                    <span class="badge rounded-pill bg-danger ms-auto" id="sidebarChatBadge" style="<?php echo $chat_unread_msgs > 0 ? '' : 'display:none;'; ?>"><?php echo $chat_unread_msgs > 99 ? '99+' : $chat_unread_msgs; ?></span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $order_pending_pages) ? 'open' : '' ?>">
                <a href="order_pending" class="menu-link <?php echo in_array($now_page, $order_pending_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">pending_actions</span>
                    <span class="title">คำสั่งซื้อรอยืนยัน</span>
                    <span class="badge rounded-pill bg-danger ms-auto" id="sidebarOrderBadge" style="<?php echo $pending_transfer_orders > 0 ? '' : 'display:none;'; ?>"><?php echo $pending_transfer_orders > 99 ? '99+' : $pending_transfer_orders; ?></span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $certificate_pages) ? 'open' : '' ?>">
                <a href="course_certificate" class="menu-link <?php echo in_array($now_page, $certificate_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">workspace_premium</span>
                    <span class="title">ใบรับรองผลการสอบ</span>
                    <span class="badge rounded-pill bg-danger ms-auto" id="sidebarExamResultBadge" style="<?php echo $exam_result > 0 ? '' : 'display:none;'; ?>"><?php echo $exam_result > 99 ? '99+' : $exam_result; ?></span>
                </a>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">เอกสารทางบัญชี</span>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $etax_pages) ? 'open' : '' ?>">
                <a href="etax" class="menu-link <?php echo in_array($now_page, $etax_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">description</span>
                    <span class="title">ใบกำกับภาษี (E-Tax)</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $etax_link_pages) ? 'open' : '' ?>">
                <a href="etax_link" class="menu-link <?php echo in_array($now_page, $etax_link_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">link</span>
                    <span class="title">ลิงก์ออกใบกำกับภาษี</span>
                </a>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">จัดการผู้ใช้</span>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $user_pages) ? 'open' : '' ?>">
                <a href="user" class="menu-link <?php echo in_array($now_page, $user_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">group</span>
                    <span class="title">ผู้ใช้/ลูกค้า</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $history_pages) ? 'open' : '' ?>">
                <a href="verify_history" class="menu-link <?php echo in_array($now_page, $history_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">history</span>
                    <span class="title">ประวัติการยืนยันตัวตน</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $verify_pages) ? 'open' : '' ?>">
                <a href="verify_request" class="menu-link <?php echo in_array($now_page, $verify_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">how_to_reg</span>
                    <span class="title">ยืนยันตัวตนผู้ใช้งาน</span>
                    <span class="badge rounded-pill bg-danger ms-auto" id="sidebarVerifyBadge" style="<?php echo $verify_pending > 0 ? '' : 'display:none;'; ?>"><?php echo $verify_pending > 99 ? '99+' : $verify_pending; ?></span>
                </a>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">อื่น ๆ</span>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $report_pages) ? 'open' : '' ?>">
                <a href="report" class="menu-link <?php echo in_array($now_page, $report_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">summarize</span>
                    <span class="title">รายงาน/เอกสาร</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $coupon_pages) ? 'open' : '' ?>">
                <a href="coupon" class="menu-link <?php echo in_array($now_page, $coupon_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">sell</span>
                    <span class="title">คูปองส่วนลด</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $setting_pages) ? 'open' : '' ?>">
                <a href="website_setting" class="menu-link <?php echo in_array($now_page, $setting_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">settings</span>
                    <span class="title">ตั้งค่าเว็บไซต์</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $bank_pages) ? 'open' : '' ?>">
                <a href="bank_setting" class="menu-link <?php echo in_array($now_page, $bank_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">account_balance</span>
                    <span class="title">ตั้งค่าธนาคาร</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $address_pages) ? 'open' : '' ?>">
                <a href="address_setting" class="menu-link <?php echo in_array($now_page, $address_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">location_on</span>
                    <span class="title">ตั้งค่าที่อยู่</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $review_pages) ? 'open' : '' ?>">
                <a href="reviews" class="menu-link <?php echo in_array($now_page, $review_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">reviews</span>
                    <span class="title">รีวิวจากลูกค้า</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $banner_pages) ? 'open' : '' ?>">
                <a href="banner" class="menu-link <?php echo in_array($now_page, $banner_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">image</span>
                    <span class="title">แบนเนอร์</span>
                </a>
            </li>

            <li class="menu-item <?php echo in_array($now_page, $admin_pages) ? 'open' : '' ?>">
                <a href="admin" class="menu-link <?php echo in_array($now_page, $admin_pages) ? 'active' : '' ?>">
                    <span class="material-symbols-outlined menu-icon">manage_accounts</span>
                    <span class="title">ผู้ดูแลระบบ</span>
                </a>
            </li>

        </ul>
    </aside>

    <!-- โปรไฟล์ผู้ใช้ + ออกจากระบบ (ตรึงไว้ล่างสุดของ sidebar) -->
    <!-- <div class="sidebar-user d-flex align-items-center gap-2 p-3"> -->
        <!-- <img class="rounded-circle ShowUserAvatar flex-shrink-0" src="../template/assets/images/administrator.jpg" alt="admin" style="width:42px;height:42px;object-fit:cover;"> -->
        <!-- <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold text-truncate ShowUserFullname"></div>
            <div class="small text-secondary text-truncate ShowUserRole"></div>
        </div> -->
        <!-- ปุ่มเปิดรับการแจ้งเตือน (สำหรับมือถือ) -->
        <!-- <a href="javascript:void(0);" onclick="enablePushNotification()" class="logout-btn text-success flex-shrink-0" title="เปิดรับการแจ้งเตือน">
            <span class="material-symbols-outlined">notification_add</span>
        </a>
        <a href="logout" class="logout-btn text-danger flex-shrink-0" title="ออกจากระบบ">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div> -->

</div>

<!-- Web Push Notification Script -->
<script>
    // ค้นหาตำแหน่งของคำว่า backoffice ใน URL แล้วตัดเอาแค่ส่วนนั้น
    <?php
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $boPos = strpos($scriptPath, '/backoffice');
        if ($boPos !== false) {
            $base = substr($scriptPath, 0, $boPos + 11);
        } else {
            $base = rtrim(dirname(dirname($scriptPath)), '/\\');
        }
    ?>
    const baseUrl = "<?php echo $base; ?>";

    function urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function subscribeUserToPush() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                const registration = await navigator.serviceWorker.register(baseUrl + '/sw.js');
                console.log('Service Worker registered at ' + baseUrl + '/sw.js');
                await navigator.serviceWorker.ready;

                const response = await fetch(baseUrl + '/get_vapid.php');
                const data = await response.json();
                
                if (!data.publicKey) {
                    alert('ไม่พบ VAPID Public Key ในระบบ กรุณาตรวจสอบไฟล์ .env');
                    return false;
                }

                const applicationServerKey = urlB64ToUint8Array(data.publicKey);
                
                let permission = Notification.permission;
                if (permission === 'default') {
                    permission = await Notification.requestPermission();
                }

                if (permission === 'granted') {
                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: applicationServerKey
                    });

                    const token = document.cookie.split('; ').find(row => row.startsWith('bo_access_token='));
                    const tokenVal = token ? token.split('=')[1] : '';

                    const saveRes = await fetch(baseUrl + '/save_subscription.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + tokenVal
                        },
                        body: JSON.stringify(subscription)
                    });
                    
                    const saveJson = await saveRes.json();
                    if (saveJson.result === 1) {
                        console.log('User is subscribed and saved on backend.');
                        return true;
                    } else {
                        alert('ไม่สามารถบันทึกข้อมูลแจ้งเตือนได้: ' + saveJson.msg);
                        return false;
                    }
                } else {
                    alert('คุณไม่อนุญาตให้แสดงการแจ้งเตือน (Permission Denied) โปรดไปตั้งค่าเบราว์เซอร์เพื่ออนุญาต');
                    return false;
                }
            } catch (error) {
                console.error('Error during Service Worker registration or subscription:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
                return false;
            }
        } else {
            alert('เบราว์เซอร์ของคุณไม่รองรับ Push Notification');
            return false;
        }
    }

    async function enablePushNotification() {
        const success = await subscribeUserToPush();
        if (success) {
            alert('เปิดรับการแจ้งเตือนสำเร็จแล้ว! (คุณจะได้รับการแจ้งเตือนบนอุปกรณ์นี้)');
        }
    }

    window.addEventListener('load', () => {
        if (Notification.permission === 'granted') {
            subscribeUserToPush();
        }
    });
</script>
