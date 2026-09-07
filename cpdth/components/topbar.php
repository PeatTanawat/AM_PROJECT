<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Utility\Auth;
use App\Database\Connection;

$currentUser = Auth::getUser();
$cartCount = 0;
if ($currentUser) {
    $db = (new Connection())->getPdo();
    $stmt = $db->prepare("SELECT COUNT(item_id) as count 
                             FROM tbl_cart_item ci 
                             JOIN tbl_cart c ON ci.cart_id = c.cart_id 
                             WHERE c.user_id = :user_id");
    $stmt->execute([':user_id' => $currentUser->user_id]);
    $cartCount = (int) $stmt->fetchColumn();
}
?>
<div class="topbar">
    <div class="container-fluid px-5">
        <div class="d-flex justify-content-between align-items-center">
            <div class="topbar-contact d-flex gap-4">
                <span><i class="bi bi-telephone-fill me-1"></i>083-429-6854</span>
                <span><i class="bi bi-envelope-fill me-1"></i>cpdth@am-amaudit.com</span>
            </div>
            <div class="topbar-user d-flex align-items-center gap-3">
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <a class="text-white dropdown-toggle text-decoration-none d-inline-flex align-items-center gap-1"
                            href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span><?php echo htmlspecialchars($currentUser->user_firstname . ' ' . $currentUser->user_lastname) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item py-2" href="profile-menu"><i class="bi bi-person text-secondary"
                                        style="width: 20px; display: inline-block; text-align: center; margin-right: 6px;"></i>
                                    ข้อมูลผู้ใช้</a></li>
                            <li><a class="dropdown-item py-2" href="profile-menu?tab=course"><i
                                        class="bi bi-journal-text text-secondary"
                                        style="width: 20px; display: inline-block; text-align: center; margin-right: 6px;"></i>
                                    คอร์สของฉัน</a></li>
                            <li><a class="dropdown-item py-2" href="profile-menu?tab=history"><i
                                        class="bi bi-credit-card text-secondary"
                                        style="width: 20px; display: inline-block; text-align: center; margin-right: 6px;"></i>
                                    ประวัติการชำระเงิน</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 text-danger" href="logout"><i class="bi bi-box-arrow-right"
                                        style="width: 20px; display: inline-block; text-align: center; margin-right: 6px;"></i>
                                    ออกจากระบบ</a></li>
                        </ul>
                    </div>
                    <?php if (basename($_SERVER['PHP_SELF']) != 'payment.php'): ?>
                        <div class="dropdown d-inline-block position-relative align-middle me-2">
                            <a href="#" class="topbar-cart-link text-white dropdown-toggle text-decoration-none" role="button"
                                id="cartDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                <img src="<?php echo $base_url ?? '' ?>assets/images/icons/cart_icon.png"
                                    class="topbar-cart-icon"
                                    style="width: 1.55rem; height: 1.55rem; object-fit: contain; vertical-align: middle;"
                                    alt="Cart">
                                <span class="topbar-cart-badge" id="cartCount"
                                    style="<?php echo $cartCount > 0 ? '' : 'display: none;'; ?>"><?php echo $cartCount; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-0 cart-dropdown-menu"
                                aria-labelledby="cartDropdown"
                                style="width: 340px; border-radius: 8px; overflow: hidden; background: #fff; z-index: 1080;">
                                <!-- Header -->
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark" style="font-size: 0.95rem;">ตะกร้าสินค้า</span>
                                    <span class="badge bg-primary-subtle text-primary" id="dropdownCartCount"
                                        style="font-size: 0.75rem; font-weight: 600; padding: 4px 8px; border-radius: 4px;"><?php echo $cartCount; ?>
                                        รายการ</span>
                                </div>

                                <!-- Items list -->
                                <div id="cartDropdownItems" style="max-height: 250px; overflow-y: auto;">
                                    <div class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                                        <div style="font-size: 0.85rem;">กำลังโหลดข้อมูล...</div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="p-3 bg-light border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-secondary" style="font-size: 0.9rem;">ยอดรวม:</span>
                                        <span class="fw-bold fs-5" id="cartDropdownTotal" style="color: #f97316;">0 ฿</span>
                                    </div>
                                    <a href="payment.php" class="btn btn-primary w-100 fw-semibold py-2"
                                        style="background-color: #3e6dba; border-color: #3e6dba; font-size: 0.9rem;">ชำระเงิน</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login" class="topbar-auth-link">เข้าสู่ระบบ</a>
                    <span class="topbar-divider">|</span>
                    <a href="register" class="topbar-auth-link">ลงทะเบียน</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .topbar-cart-link::after {
        display: none !important;
    }

    .cart-item-row:hover {
        background-color: #f8fafc;
    }

    .topbar-cart-icon {
        filter: invert(62%) sepia(87%) saturate(415%) hue-rotate(357deg) brightness(98%) contrast(90%);
    }

    .cart-dropdown-menu {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<script>
    $(document).ready(function () {
        // When the cart dropdown is shown
        $('#cartDropdown').on('show.bs.dropdown', function () {
            loadDropdownCart();
        });

        // Make sure click on trash icon doesn't navigate away, and calls delete item
        $(document).on('click', '.btn-delete-cart-item', function (e) {
            e.preventDefault();
            e.stopPropagation();
            let itemId = $(this).data('id');
            removeDropdownCartItem(itemId);
        });
    });

    function loadDropdownCart() {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';
        let container = $('#cartDropdownItems');

        container.html(`
        <div class="text-center py-4 text-muted">
            <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
            <div style="font-size: 0.85rem;">กำลังโหลดข้อมูล...</div>
        </div>
    `);

        $.ajax({
            type: "POST",
            url: coreUrl,
            data: {
                request_state: "cart",
                request_function: "get"
            },
            dataType: "json",
            success: function (response) {
                let isSuccess = (response.result == 1 || response.status == 1);
                if (isSuccess && response.data) {
                    renderDropdownCart(response.data);
                } else {
                    container.html('<div class="text-center py-4 text-muted" style="font-size: 0.85rem;">ไม่มีสินค้าในตะกร้า</div>');
                    $('#cartDropdownTotal').text('0 ฿');
                    $('#cartCount').hide();
                    $('#dropdownCartCount').text('0 รายการ');
                }
            },
            error: function () {
                container.html('<div class="text-center py-4 text-danger" style="font-size: 0.85rem;">ไม่สามารถโหลดข้อมูลได้</div>');
            }
        });
    }

    function renderDropdownCart(data) {
        let container = $('#cartDropdownItems');
        let items = data.items || [];

        // Update badges & totals
        $('#cartDropdownTotal').text(data.total_fmt + ' ฿');
        $('#dropdownCartCount').text(items.length + ' รายการ');

        let badge = $('#cartCount');
        badge.text(items.length);
        if (items.length > 0) {
            badge.show();
        } else {
            badge.hide();
        }

        if (items.length === 0) {
            container.html('<div class="text-center py-4 text-muted" style="font-size: 0.85rem;">ไม่มีสินค้าในตะกร้า</div>');
            return;
        }

        let backofficePath = window.location.pathname.includes('/pages/') ? '../../backoffice/' : '../backoffice/';
        let html = '';

        items.forEach(function (item) {
            let imgUrl = '';
            if (item.course_cover_image) {
                if (item.course_cover_image.startsWith('http://') || item.course_cover_image.startsWith('https://') || item.course_cover_image.startsWith('data:')) {
                    imgUrl = item.course_cover_image;
                } else {
                    let clean = item.course_cover_image.replace(/^(\.\.\/)+/, '').replace(/^backoffice\//, '').replace(/^upload\//, '').replace(/^course\//, '');
                    imgUrl = `${backofficePath}upload/course/${clean}`;
                }
            }
            let imgHtml = imgUrl ?
                `<img src="${imgUrl}" alt="Cover" class="rounded" style="width: 50px; height: 50px; object-fit: cover; flex-shrink: 0;" onerror="this.style.display='none';">` :
                `<div class="rounded bg-light text-muted d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 10px; flex-shrink: 0; color: #94a3b8;">No Image</div>`;

            html += `
            <div class="d-flex align-items-center gap-3 p-3 border-bottom cart-item-row" style="transition: background-color 0.2s;">
                ${imgHtml}
                <div class="flex-grow-1" style="min-width: 0;">
                    <div class="fw-medium text-dark text-truncate" style="font-size: 0.85rem; font-weight: 500;" title="${item.course_name}">${item.course_name}</div>
                    <div class="fw-bold mt-1" style="font-size: 0.9rem; color: #f97316;">${item.course_price_fmt} ฿</div>
                </div>
                <button class="btn btn-link text-danger p-0 border-0 btn-delete-cart-item" data-id="${item.item_id}" title="ลบรายการ" style="box-shadow: none;">
                    <i class="bi bi-trash3" style="font-size: 1.1rem; color: #ef4444;"></i>
                </button>
            </div>
        `;
        });
        container.html(html);
    }

    function removeDropdownCartItem(itemId) {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';

        $.ajax({
            type: "POST",
            url: coreUrl,
            data: {
                request_state: "cart",
                request_function: "remove",
                item_id: itemId
            },
            dataType: "json",
            success: function (response) {
                let isSuccess = (response.result == 1 || response.status == 1);
                if (isSuccess && response.data) {
                    renderDropdownCart(response.data);
                } else {
                    Swal.fire('แจ้งเตือน', response.msg || 'ไม่สามารถลบรายการได้', 'warning');
                }
            },
            error: function () {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    }
</script>