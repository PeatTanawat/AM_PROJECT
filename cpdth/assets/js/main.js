// ฟังก์ชันสำหรับอ่าน Cookie
function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

// ตรวจสอบและซิงค์ Token จาก Cookie ไปยัง localStorage (เช่นเมื่อกดลิงก์ verify อีเมลเข้ามา)
const cookieToken = getCookie('access_token');
if (cookieToken && !localStorage.getItem('access_token')) {
    localStorage.setItem('access_token', cookieToken);
}

// แนบ access token จาก localStorage ไปกับทุก request ของ jQuery (เหมือนโปรเจกต์ am)
$(document).ajaxSend(function(event, jqXHR, settings) {
    const token = localStorage.getItem("access_token");
    if (token) {
        jqXHR.setRequestHeader("Authorization", "Bearer " + token);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // Remove inert when modal is about to show, enabling tab focus
    $(document).on('show.bs.modal', '.modal', function () {
        this.removeAttribute('inert');
    });

    // Add inert back when modal is hidden, blocking tab focus
    $(document).on('hidden.bs.modal', '.modal', function () {
        this.setAttribute('inert', '');
    });
});
 function addCart(courseId, forceAdd = false) {
    let id = courseId || (typeof currentCourseId !== 'undefined' ? currentCourseId : null);
    if (!id) {
        Swal.fire('แจ้งเตือน', 'ไม่พบรหัสคอร์สเรียน', 'warning');
        return;
    }

    let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';

    let requestData = {
        request_state: "course",
        request_function: "add_cart",
        course_id: id
    };
    if (forceAdd) {
        requestData.force_add = 1;
    }

    $.ajax({
        type: "POST",
        url: coreUrl,
        data: requestData,
        dataType: "json",
        success: function (response) {
            if (response.result == 2 || response.status == 2) {
                Swal.fire({
                    title: 'ท่านมีคอร์สเรียนนี้อยู่แล้ว',
                    text: response.msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        addCart(id, true);
                    }
                });
                return;
            }

            let isSuccess = (response.result == 1 || response.status == 1);
            if (isSuccess) {
                Swal.fire({
                    title: 'สำเร็จ',
                    text: response.msg || 'เพิ่มลงตะกร้าสำเร็จ',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

                if (response.data && typeof response.data.cart_count !== 'undefined') {
                    let badge = $('#cartCount');
                    if (badge.length > 0) {
                        badge.text(response.data.cart_count);
                        if (response.data.cart_count > 0) {
                            badge.show();
                        } else {
                            badge.hide();
                        }
                    }
                }
            } else {
                if (response.msg === 'Unauthorized' || response.msg === 'Invalid token' || response.msg === 'Token expired' || response.msg === 'User revoked') {
                    let loginUrl = window.location.pathname.includes('/pages/') ? '../login' : 'login';
                    Swal.fire({
                        title: 'กรุณาเข้าสู่ระบบ',
                        text: 'กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงในตะกร้า',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'เข้าสู่ระบบ',
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = loginUrl;
                        }
                    });
                } else {
                    Swal.fire('แจ้งเตือน', response.msg || 'ไม่สามารถเพิ่มลงตะกร้าได้', 'warning');
                }
            }
        },
        error: function(err) {
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    });
}

$(document).ready(function() {
    // When the cart dropdown is shown
    $('#cartDropdown').on('show.bs.dropdown', function() {
        loadDropdownCart();
    });

    // Make sure click on trash icon doesn't navigate away, and calls delete item
    $(document).on('click', '.btn-delete-cart-item', function(e) {
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
        success: function(response) {
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
        error: function() {
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
    
    items.forEach(function(item) {
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
        success: function(response) {
            let isSuccess = (response.result == 1 || response.status == 1);
            if (isSuccess && response.data) {
                renderDropdownCart(response.data);
            } else {
                Swal.fire('แจ้งเตือน', response.msg || 'ไม่สามารถลบรายการได้', 'warning');
            }
        },
        error: function() {
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    });
}
