<?php
$pageTitle = 'ชำระเงิน';
include 'components/header.php';

// โหลด Env เพื่อความปลอดภัยและมั่นใจว่าค่า PROMPTPAY_ID จะไม่ว่างเปล่า
if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    } catch (Exception $e) {
        // Ignore if load fails
    }
}

$userEmail = '';
if ($currentUser) {
    $db = (new App\Database\Connection())->getPdo();
    $stmt = $db->prepare("SELECT user_email FROM tbl_user WHERE user_id = :id LIMIT 1");
    $stmt->execute([':id' => $currentUser->user_id]);
    $userEmail = $stmt->fetchColumn() ?: '';
}

$promptpayIdFromEnv = $_ENV['PROMPTPAY_ID'] ?? '';
?>

<!-- โหลด Library สำหรับสร้าง QR Code -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- โหลด Library ของ Xendit -->
<script type="text/javascript" src="https://js.xendit.co/v1/xendit.min.js"></script>
<script>
    // กำหนด Public API Key ของ Xendit (ดึงจาก .env)
    Xendit.setPublishableKey('<?= $_ENV['XENDIT_PUBLIC_KEY'] ?? '' ?>');
</script>


<style>
    .payment-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* Fix Modal z-index overlapping and backdrop black screen issue */
    .modal {
        backdrop-filter: blur(5px) !important;
        -webkit-backdrop-filter: blur(5px) !important;
        background-color: rgba(15, 23, 42, 0.45) !important;
        z-index: 99999 !important;
    }

    .modal-backdrop {
        display: none !important;
    }

    /* Vertical Payment Methods */
    .payment-method-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
        display: flex;
        align-items: center;
        position: relative;
    }

    .payment-method-item:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .payment-method-item.active {
        border: 2px solid #2d5fa6;
        background: #fff;
        box-shadow: 0 4px 12px rgba(45, 95, 166, 0.08);
    }

    .method-radio-circle {
        width: 20px;
        height: 20px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .payment-method-item.active .method-radio-circle {
        border-color: #2d5fa6;
        background-color: #2d5fa6;
    }

    .payment-method-item.active .method-radio-circle::after {
        content: '';
        width: 8px;
        height: 8px;
        background-color: #fff;
        border-radius: 50%;
        display: block;
    }

    .method-accordion-content {
        margin-bottom: 5px;
    }

    /* Address display styling */
    .address-display-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 20px;
        margin-top: 15px;
    }

    /* Summary Course Card styling */
    .summary-course-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        background: #fff;
        display: flex;
        gap: 15px;
        margin-bottom: 12px;
    }

    .summary-course-img {
        width: 100px;
        height: 56px;
        object-fit: cover;
        border-radius: 4px;
        background: #f1f5f9;
        flex-shrink: 0;
    }

    .summary-course-info {
        flex: 1;
        min-width: 0;
    }

    .summary-course-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .summary-course-instructor {
        font-size: 0.78rem;
        color: #64748b;
        margin-bottom: 4px;
    }

    .summary-course-price {
        font-size: 0.88rem;
        font-weight: 600;
        color: #f97316;
    }

    /* Address selector modal styling */
    .modal-address-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        margin-bottom: 12px;
    }

    .modal-address-item:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .modal-address-item.active {
        border: 2px solid #2d5fa6;
        background: #f0f7ff;
    }

    .sticky-sidebar {
        position: relative;
        align-self: flex-start;
    }

    @media (max-width: 992px) {
        .sticky-sidebar {
            position: static;
        }
    }
</style>

<!-- Container where AJAX will load the HTML view -->
<div id="payment-view-container">
    <div class="container py-5 text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
        <p class="mt-3 text-muted">กำลังโหลดหน้าการชำระเงิน...</p>
    </div>
</div>

<!-- Modal container for adding/editing addresses, identical to profile-menu.php -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true" inert>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content animated fadeIn" id="LoadingMyModal">
            <div id="showModal"></div>
        </div>
    </div>
</div>

<script>
    const currentUserEmail = '<?php echo htmlspecialchars($userEmail); ?>';
    const promptpayId = '<?php echo htmlspecialchars($promptpayIdFromEnv); ?>';
    let allUserAddresses = [];
    let selectedAddress = null;
    let activePaymentMethod = 'bank_transfer';
    let cartTotal = 0;
    let couponDiscount = 0;
    let appliedCouponCode = '';


    $(document).ready(function () {
        loadPaymentView();
    });

    function loadPaymentView() {
        $.ajax({
            type: "POST",
            url: "view/payment/payment.php?_t=" + new Date().getTime(),
            dataType: "html",
            headers: {
                "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
            },
            success: function (responseHtml) {
                $('#payment-view-container').html(responseHtml);
                initializePaymentLogic();
            },
            error: function (xhr, status, error) {
                console.error("Payment view load failed:", xhr.responseText, error);
                $('#payment-view-container').html(
                    '<div class="container py-5 text-center text-danger">' +
                    '<i class="bi bi-exclamation-triangle-fill fs-1"></i>' +
                    '<p class="mt-3">เกิดข้อผิดพลาดในการโหลดหน้าชำระเงิน กรุณาลองใหม่อีกครั้ง</p>' +
                    '</div>'
                );
            }
        });
    }

    function initializePaymentLogic() {
        // 1. Fetch Cart Info
        loadPaymentCartSummary();

        // 2. Fetch User Addresses
        fetchUserAddresses();

        // JS-based Smooth Sticky Sidebar Fallback (เลื่อนการ์ดขวาโดยไม่ให้เกินขอบของการ์ดซ้าย)
        $(window).off('scroll.paymentSticky resize.paymentSticky').on('scroll.paymentSticky resize.paymentSticky', function () {
            let sidebar = $('.sticky-sidebar');
            let sidebarCard = sidebar.find('.card');
            let container = $('.payment-container');
            let sibling = container.find('.col-lg-7');
            if (sidebar.length && sidebarCard.length && sibling.length && window.innerWidth > 992) {
                let scrollTop = $(window).scrollTop();
                let siblingTop = sibling.offset().top;
                let siblingHeight = sibling.outerHeight();
                let sidebarHeight = sidebarCard.outerHeight(); // วัดความสูงที่แท้จริงของการ์ดสรุปคำสั่งซื้อ ไม่ใช่ความสูงคอลัมน์ที่ยืดออก

                // เริ่มต้นสไลด์เมื่อเลื่อนผ่านขอบบนของการ์ดซ้ายลบระยะเมนู Header
                let headerHeight = 110;
                let startScroll = siblingTop - headerHeight;

                if (scrollTop > startScroll) {
                    let limit = siblingHeight - sidebarHeight;
                    if (limit <= 0) {
                        sidebarCard.css('transform', 'none');
                        return;
                    }
                    let y = scrollTop - startScroll;
                    if (y > limit) y = limit;
                    if (y < 0) y = 0;
                    sidebarCard.css({
                        'transform': 'translateY(' + y + 'px)',
                        'transition': 'transform 0.05s ease-out'
                    });
                } else {
                    sidebarCard.css('transform', 'translateY(0px)');
                }
            } else {
                if (sidebarCard.length) sidebarCard.css('transform', 'none');
            }
        });


        // 3. Payment Method Vertical Selector and Accordion Toggles
        $(document).on('click', '.payment-method-item', function () {
            let method = $(this).data('method');
            activePaymentMethod = method;

            $('.payment-method-item').removeClass('active');
            $(this).addClass('active');

            // Toggle form fields accordion style
            if (method === 'card') {
                $('#card-form-wrapper').slideDown(250);
                $('#promptpay-info-wrapper').slideUp(200);
                $('#bank-info-wrapper').slideUp(200);
            } else if (method === 'promptpay') {
                $('#card-form-wrapper').slideUp(200);
                $('#promptpay-info-wrapper').slideDown(250);
                $('#bank-info-wrapper').slideUp(200);
            } else if (method === 'bank_transfer') {
                $('#card-form-wrapper').slideUp(200);
                $('#promptpay-info-wrapper').slideUp(200);
                $('#bank-info-wrapper').slideDown(250);
            }
        });

        // Default accordion state
        $('#bank-info-wrapper').show();
        $('#method-bank-btn').addClass('active');

        // Bank Transfer Slip Preview & Dropzone logic
        $(document).on('change', '#slip-file-input', function (e) {
            let file = e.target.files[0];
            if (file) {
                if (!file.type.match('image.*')) {
                    Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น (.jpg, .jpeg, .png)', 'warning');
                    $(this).val('');
                    return;
                }
                let reader = new FileReader();
                reader.onload = function (evt) {
                    $('#slip-preview-img').attr('src', evt.target.result);
                    $('#slip-file-name').text(file.name);
                    $('#slip-upload-placeholder').hide();
                    $('#slip-preview-wrapper').show();
                };
                reader.readAsDataURL(file);
            }
        });

        $(document).on('dragover', '#slip-dropzone', function (e) {
            e.preventDefault();
            $(this).css('background-color', '#f1f5f9');
        });

        $(document).on('dragleave drop', '#slip-dropzone', function (e) {
            $(this).css('background-color', '#fff');
        });

        // Global copy bank account number function
        window.copyBankAccountNumber = function () {
            let num = $('#bank-account-num').text().replace(/-/g, '');
            navigator.clipboard.writeText(num).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'คัดลอกสำเร็จ',
                    text: 'คัดลอกเลขบัญชี ' + $('#bank-account-num').text() + ' ไปยังคลิปบอร์ดแล้ว',
                    showConfirmButton: false,
                    timer: 1500
                });
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        };

        // Global copy transfer amount function
        window.copyTransferAmount = function () {
            let amountText = $('#bank-transfer-amount').text().replace(/,/g, '');
            navigator.clipboard.writeText(amountText).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'คัดลอกยอดเงินสำเร็จ',
                    text: 'คัดลอกยอดเงินโอน ' + $('#bank-transfer-amount').text() + ' ฿ ไปยังคลิปบอร์ดแล้ว',
                    showConfirmButton: false,
                    timer: 1500
                });
            }).catch(err => {
                console.error('Could not copy amount: ', err);
            });
        };


        // Global remove selected slip function
        window.removeSelectedSlip = function (e) {
            if (e) e.preventDefault();
            $('#slip-file-input').val('');
            $('#slip-preview-img').attr('src', '');
            $('#slip-file-name').text('');
            $('#slip-preview-wrapper').hide();
            $('#slip-upload-placeholder').show();
        };

        // Global remove selected PromptPay slip function
        window.removeSelectedPromptPaySlip = function (e) {
            if (e) e.preventDefault();
            $('#promptpay-slip-file-input').val('');
            $('#promptpay-slip-preview-img').attr('src', '');
            $('#promptpay-slip-file-name').text('');
            $('#promptpay-slip-preview-wrapper').hide();
            $('#promptpay-slip-upload-placeholder').show();
        };

        // PromptPay Slip Preview & Dropzone logic
        $(document).on('change', '#promptpay-slip-file-input', function (e) {
            let file = e.target.files[0];
            if (file) {
                if (!file.type.match('image.*')) {
                    Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น (.jpg, .jpeg, .png)', 'warning');
                    $(this).val('');
                    return;
                }
                let reader = new FileReader();
                reader.onload = function (evt) {
                    $('#promptpay-slip-preview-img').attr('src', evt.target.result);
                    $('#promptpay-slip-file-name').text(file.name);
                    $('#promptpay-slip-upload-placeholder').hide();
                    $('#promptpay-slip-preview-wrapper').show();
                };
                reader.readAsDataURL(file);
            }
        });

        $(document).on('dragover', '#promptpay-slip-dropzone', function (e) {
            e.preventDefault();
            $(this).css('background-color', '#f1f5f9');
        });

        $(document).on('dragleave drop', '#promptpay-slip-dropzone', function (e) {
            $(this).css('background-color', '#fff');
        });

        // Credit Card formatting helpers

        $(document).on('keyup', '#cc-number', function () {
            let val = this.value.replace(/\D/g, '');
            let formatted = val.match(/.{1,4}/g);
            this.value = formatted ? formatted.join(' ') : val;
        });

        $(document).on('keyup', '#cc-exp', function () {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 2) {
                this.value = val.substring(0, 2) + '/' + val.substring(2, 4);
            } else {
                this.value = val;
            }
        });


        // Handle coupon verification interaction
        $(document).on('click', '#btnApplyCoupon', function () {
            let code = $.trim($('#coupon-code').val());
            if (!code) {
                Swal.fire('แจ้งเตือน', 'กรุณากรอกคูปองส่วนลด', 'warning');
                return;
            }

            let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';

            Swal.fire({
                title: 'กำลังตรวจสอบคูปอง...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                type: "POST",
                url: coreUrl,
                data: {
                    request_state: "cart",
                    request_function: "verify_coupon",
                    coupon_code: code
                },
                dataType: "json",
                headers: {
                    "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
                },
                success: function (response) {
                    Swal.close();
                    if (response.result == 1 || response.status == 1) {
                        couponDiscount = parseFloat(response.data.discount_amount !== undefined ? response.data.discount_amount : response.data.coupon_no);
                        appliedCouponCode = response.data.coupon_code;

                        let msg = 'ใช้คูปองส่วนลด ' + couponDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿ สำเร็จแล้ว';
                        if (response.data.coupon_type === 'percent') {
                            let percentVal = parseFloat(response.data.coupon_no);
                            msg = 'ใช้คูปองส่วนลด ' + percentVal + '% (ลด ' + couponDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿) สำเร็จแล้ว';
                        }

                        Swal.fire('สำเร็จ', msg, 'success');

                        // Show discount row and update pricing values
                        $('#coupon-discount-row').css('display', 'flex');
                        let displayCode = appliedCouponCode;
                        if (response.data.coupon_type === 'percent') {
                            displayCode += ' (' + parseFloat(response.data.coupon_no) + '%)';
                        }
                        $('#coupon-display-code').text(displayCode);
                        $('#summary-discount').text('-' + couponDiscount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');

                        // Recalculate net total
                        let netTotal = Math.max(0, cartTotal - couponDiscount);
                        $('#summary-total').text(netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');
                        $('#btnPayAction').text('ชำระเงินสุทธิ ' + netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');
                        $('#bank-transfer-amount').text(netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        updatePromptPayQRCode(netTotal);
                    } else {
                        couponDiscount = 0;
                        appliedCouponCode = '';

                        $('#coupon-discount-row').hide();
                        let netTotal = cartTotal;
                        $('#summary-total').text(netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');
                        $('#btnPayAction').text('ชำระเงินสุทธิ ' + netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');
                        $('#bank-transfer-amount').text(netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        updatePromptPayQRCode(netTotal);

                        Swal.fire('แจ้งเตือน', response.msg || 'ไม่สามารถใช้งานคูปองส่วนลดนี้ได้', 'warning');
                    }

                },
                error: function () {
                    Swal.close();
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์เพื่อตรวจสอบคูปองได้', 'error');
                }
            });
        });



        // Process checkout action
        $(document).on('click', '#btnPayAction', function () {
            if (!selectedAddress) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกที่อยู่ออกใบกำกับภาษี หรือเพิ่มที่อยู่ใหม่ก่อนดำเนินการชำระเงิน', 'warning');
                return;
            }

            // Credit Card validation if active
            if (activePaymentMethod === 'card') {
                let cardNum = $.trim($('#cc-number').val());
                let cardName = $.trim($('#cc-name').val());
                let cardExp = $.trim($('#cc-exp').val());
                let cardCvv = $.trim($('#cc-cvv').val());

                if (!cardNum || !cardName || !cardExp || !cardCvv) {
                    Swal.fire('แจ้งเตือน', 'กรุณากรอกข้อมูลบัตรเครดิตให้ครบถ้วน', 'warning');
                    return;
                }
                
                // Extract month and year from MM/YY
                let expParts = cardExp.split('/');
                if (expParts.length !== 2) {
                    Swal.fire('แจ้งเตือน', 'รูปแบบวันหมดอายุไม่ถูกต้อง (MM/YY)', 'warning');
                    return;
                }
                
                Swal.fire({
                    title: 'กำลังตรวจสอบข้อมูลบัตร...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                let tokenData = {
                    amount: cartTotal,
                    card_number: cardNum.replace(/\s+/g, ''),
                    card_exp_month: expParts[0],
                    card_exp_year: '20' + expParts[1],
                    card_cvn: cardCvv,
                    is_multiple_use: false,
                    should_authenticate: true
                };

                Xendit.card.createToken(tokenData, function (err, creditCardCharge) {
                    if (err) {
                        Swal.fire('ข้อผิดพลาด', 'ข้อมูลบัตรไม่ถูกต้อง: ' + err.message, 'error');
                        return;
                    }
                    if (creditCardCharge.status === 'APPROVED' || creditCardCharge.status === 'VERIFIED') {
                        // Success, proceed to create order
                        submitOrder(creditCardCharge.id);
                    } else if (creditCardCharge.status === 'IN_REVIEW') {
                        Swal.fire('แจ้งเตือน', 'บัตรกำลังรอการตรวจสอบจากธนาคาร', 'warning');
                    } else {
                        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถใช้บัตรนี้ได้ กรุณาลองใบอื่น', 'error');
                    }
                });
                
                return; // Stop here, submitOrder will be called from callback
            } else {
                // Not a card, proceed directly
                submitOrder(null);
            }

            function submitOrder(xenditTokenId) {

            // Bank Transfer validation
            if (activePaymentMethod === 'bank_transfer') {
                let slipInput = $('#slip-file-input')[0];
                if (!slipInput || slipInput.files.length === 0) {
                    Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดรูปภาพสลิปชำระเงินก่อนยืนยันรายการสั่งซื้อ', 'warning');
                    return;
                }
            }

            let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';

            Swal.fire({
                title: (activePaymentMethod === 'bank_transfer') ? 'กำลังตรวจสอบสลิปและสั่งซื้อ...' : 'กำลังสร้างรายการสั่งซื้อ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });


            // Format address string
            let fullAddr = '';
            if (selectedAddress.addr_detail) fullAddr += selectedAddress.addr_detail;
            if (selectedAddress.addr_subdistrict) {
                fullAddr += (selectedAddress.addr_province.includes('กรุงเทพ')) ? ' แขวง' + selectedAddress.addr_subdistrict : ' ตำบล' + selectedAddress.addr_subdistrict;
            }
            if (selectedAddress.addr_district) {
                fullAddr += (selectedAddress.addr_province.includes('กรุงเทพ')) ? ' เขต' + selectedAddress.addr_district : ' อำเภอ' + selectedAddress.addr_district;
            }
            if (selectedAddress.addr_province) fullAddr += ' จังหวัด' + selectedAddress.addr_province;
            if (selectedAddress.addr_zipcode) fullAddr += ' ' + selectedAddress.addr_zipcode;

            let isCorpAddress = (selectedAddress.addr_type === 'corporate' || selectedAddress.addr_type === '2' || selectedAddress.addr_type === 'typeCorporate');

            // Create FormData to support file uploads
            let formData = new FormData();
            formData.append('request_state', 'cart');
            formData.append('request_function', 'create_order');
            formData.append('payment_method', activePaymentMethod);
            formData.append('etax_type', isCorpAddress ? 'corporate' : 'personal');
            formData.append('etax_name', selectedAddress.addr_name);
            formData.append('etax_id', selectedAddress.addr_tax_id);
            formData.append('etax_address', fullAddr.trim());
            formData.append('etax_email', currentUserEmail);
            formData.append('coupon_code', appliedCouponCode);
            formData.append('order_internal_note', $.trim($('#order-remark').val() || ''));
            formData.append('order_addr_type', isCorpAddress ? '2' : '1');
            formData.append('order_addr_tax_id', selectedAddress.addr_tax_id || '');
            formData.append('order_addr_branch', isCorpAddress ? (selectedAddress.addr_branch || '') : '');
            formData.append('order_addr_branch_name', isCorpAddress ? (selectedAddress.addr_branch_name || '') : '');
            formData.append('order_addr_phone', selectedAddress.addr_phone || '');
            formData.append('order_addr_detail', selectedAddress.addr_detail || '');
            formData.append('order_addr_subdistrict', selectedAddress.addr_subdistrict || '');
            formData.append('order_addr_district', selectedAddress.addr_district || '');
            formData.append('order_addr_province', selectedAddress.addr_province || '');
            formData.append('order_addr_zipcode', selectedAddress.addr_zipcode || '');
            formData.append('order_addr_name', selectedAddress.addr_name || '');

            if (activePaymentMethod === 'bank_transfer') {
                let slipInput = $('#slip-file-input')[0];
                if (slipInput && slipInput.files.length > 0) {
                    formData.append('slip_file', slipInput.files[0]);
                }
            } else if (activePaymentMethod === 'promptpay') {
                // Xendit PromptPay uses Dynamic QR, no slip upload needed at checkout
            } else if (activePaymentMethod === 'card' && xenditTokenId) {
                formData.append('xendit_token', xenditTokenId);
            }


            $.ajax({
                type: "POST",
                url: coreUrl,
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                headers: {
                    "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
                },
                success: function (response) {
                    Swal.close();
                    if (response.result == 1 || response.status == 1) {
                        let orderData = response.data;

                        if (activePaymentMethod === 'promptpay') {
                            // Render Dynamic QR from Xendit
                            showXenditPromptPayPopup(orderData);
                            return;
                        }

                        let refNo = orderData.transaction_ref;
                        let dateStr = orderData.created_at;
                        let priceStr = parseFloat(orderData.total_price).toLocaleString() + ' ฿';
                        let isAutoApproved = orderData.payment_status === '1';
                        let overpaidVal = parseFloat(orderData.overpaid || 0);


                        let swalTitle = 'ชำระเงินสำเร็จ !';
                        let swalIconColor = '#10b981';
                        let infoText = 'หลังจากชำระเงินสำเร็จแล้ว ระบบจะประมวลผลคำสั่งซื้อและคอร์สเรียนจะพร้อมใช้งานภายใน 5 นาที คุณจะได้รับอีเมลเมื่อคอร์สเรียนพร้อมใช้งาน';

                        if (activePaymentMethod === 'bank_transfer') {
                            if (isAutoApproved) {
                                if (overpaidVal > 0) {
                                    swalTitle = 'ชำระเงินเกินจำนวน !';
                                    swalIconColor = '#f59e0b';
                                    infoText = 'ระบบตรวจพบว่าคุณโอนเงินเกินจำนวนมา <strong>' + overpaidVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿</strong> (ราคาสุทธิคือ ' + priceStr + ') ทั้งนี้ระบบได้อนุมัติคอร์สเรียนเรียบร้อยแล้ว กรุณาติดต่อ Admin เพื่อขอรับเงินคืนในส่วนเกินครับ';
                                } else {
                                    swalTitle = 'อนุมัติหลักสูตรแล้ว';
                                    swalIconColor = '#10b981';
                                    infoText = '<div style="text-align: center; width: 100%;">' +
                                        '    <div style="color: #ef4444; font-weight: bold; font-size: 1.1rem; margin-bottom: 5px;">ระบบประมวลผล 3 นาที</div>' +
                                        '    <div style="font-weight: 500; margin-bottom: 12px; color: #1e293b;">เข้าอบรมที่ชื่อลูกค้ามุมขวาบน เมนูคอร์สเรียนของฉัน</div>' +
                                        '    <div style="font-size: 0.85rem; color: #64748b; border-top: 1px dashed #cbd5e1; pt-2; mt-2; padding-top: 10px;">' +
                                        '        หากครบ 3 นาทีไม่พบคอร์สเรียนติดต่อ<br>' +
                                        '        <span style="font-weight: 600; color: #0f172a;">Line : @cpdth (มี@)</span>' +
                                        '    </div>' +
                                        '</div>';
                                }
                            } else {
                                swalTitle = 'กรุณารอเจ้าหน้าที่อนุมัติคำสั่งซื้อ !';
                                swalIconColor = '#fffa5fff';
                                infoText = 'กรุณารอเจ้าหน้าที่ตรวจสอบหลักฐานการชำระเงินเพื่ออนุมัติสิทธิ์เข้าเรียน คอร์สเรียนของคุณจะเปิดใช้งานภายใน 24 ชม.';
                            }
                        }

                        let htmlContent = `
                        <div style="font-family: 'Prompt', sans-serif; text-align: center; padding: 10px;">
                            <div style="margin-bottom: 20px;">
                                <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background-color: ${(!isAutoApproved && activePaymentMethod === 'bank_transfer') ? '#eff6ff' : (overpaidVal > 0 ? '#fef3c7' : '#ecfdf5')}; color: ${(!isAutoApproved && activePaymentMethod === 'bank_transfer') ? '#e8ce4dff' : (overpaidVal > 0 ? '#d97706' : '#10b981')}; font-size: 3.2rem; margin-bottom: 15px;">
                                    <i class="bi ${(!isAutoApproved && activePaymentMethod === 'bank_transfer') ? 'bi-info-circle-fill' : (overpaidVal > 0 ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill')}"></i>
                                </div>
                                <h3 style="font-weight: 700; color: ${(!isAutoApproved && activePaymentMethod === 'bank_transfer') ? '#e8ce4dff' : (overpaidVal > 0 ? '#d97706' : '#10b981')}; font-size: 1.6rem; margin-bottom: 15px;">${swalTitle}</h3>
                            </div>

                            <div style="background-color: ${overpaidVal > 0 ? '#fffbeb' : '#eff6ff'}; border-left: 4px solid ${overpaidVal > 0 ? '#f59e0b' : '#e8ce4dff'}; padding: 12px 15px; border-radius: 6px; text-align: left; font-size: 0.85rem; color: ${overpaidVal > 0 ? '#78350f' : '#1e3a8a'}; line-height: 1.5; margin-bottom: 25px;">
                                <i class="bi bi-info-circle-fill" style="margin-right: 5px;"></i>
                                ${infoText}
                            </div>


                            <div style="border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; padding: 15px 0; margin-bottom: 25px;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                    <tr style="height: 35px;">
                                        <td style="text-align: left; color: #64748b; font-weight: 500;"><i class="bi bi-file-earmark-text text-secondary" style="margin-right: 8px;"></i> หมายเลขคำสั่งซื้อ</td>
                                        <td style="text-align: right; color: #1e293b; font-weight: 600;">#${refNo}</td>
                                    </tr>
                                    <tr style="height: 35px;">
                                        <td style="text-align: left; color: #64748b; font-weight: 500;"><i class="bi bi-calendar-event text-secondary" style="margin-right: 8px;"></i> วันที่ทำรายการ</td>
                                        <td style="text-align: right; color: #1e293b; font-weight: 600;">${dateStr}</td>
                                    </tr>
                                    <tr style="height: 35px;">
                                        <td style="text-align: left; color: #64748b; font-weight: 500;"><i class="bi bi-cash-stack text-secondary" style="margin-right: 8px;"></i> จำนวนเงิน</td>
                                        <td style="text-align: right; color: #f97316; font-weight: 700; font-size: 1.05rem;">${priceStr}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                                <a href="index.php" class="btn btn-primary w-100 py-2.5" style="border-radius: 50px; font-weight: 600; font-size: 0.95rem; background-color: #2d5fa6; border-color: #2d5fa6; box-shadow: 0 4px 10px rgba(45,95,166,0.25); display: inline-flex; align-items: center; justify-content: center; gap: 8px;"><i class="bi bi-house-door-fill"></i> กลับสู่หน้าหลัก</a>
                                <a href="profile-menu.php?tab=course" class="text-decoration-none fw-semibold" style="color: #3b82f6; font-size: 0.9rem; transition: color 0.2s;">คอร์สเรียนของฉัน</a>
                            </div>
                        </div>
                    `;

                        Swal.fire({
                            html: htmlContent,
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            customClass: {
                                popup: 'border-0 shadow-lg',
                            }
                        });
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', response.msg || 'ไม่สามารถสร้างรายการสั่งซื้อได้', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    Swal.close();
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์เพื่อบันทึกคำสั่งซื้อได้', 'error');
                }
            });
            } // End of submitOrder function
        });


    }

    function fetchUserAddresses(selectedId = null) {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';

        $.ajax({
            type: "POST",
            url: coreUrl,
            data: {
                request_state: "address",
                request_function: "get_address"
            },
            dataType: "json",
            headers: {
                "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
            },
            success: function (response) {
                if ((response.result == 1 || response.status == 1) && response.data && response.data.addresses && response.data.addresses.length > 0) {
                    allUserAddresses = response.data.addresses;

                    // Find which address to select: default or specified selectedId
                    if (selectedId) {
                        let found = allUserAddresses.find(a => parseInt(a.addr_id) === parseInt(selectedId));
                        selectedAddress = found ? found : allUserAddresses[0];
                    } else {
                        let defaultAddr = allUserAddresses.find(a => a.addr_is_default == '1');
                        selectedAddress = defaultAddr ? defaultAddr : allUserAddresses[0];
                    }

                    renderActiveAddress();
                } else {
                    allUserAddresses = [];
                    selectedAddress = null;
                    $('#active-address-container').html(
                        '<div class="text-center py-4 text-muted">' +
                        '<i class="bi bi-geo-alt fs-2 mb-2 d-block"></i>' +
                        'ยังไม่มีที่อยู่ออกใบกำกับภาษี กรุณาคลิกปุ่มด้านบนเพื่อเพิ่มที่อยู่ใหม่' +
                        '</div>'
                    );
                }
            },
            error: function (xhr, status, error) {
                console.error("Address fetch error:", error);
                $('#active-address-container').html('<div class="text-danger">ไม่สามารถดึงข้อมูลที่อยู่ได้</div>');
            }
        });
    }

    function renderActiveAddress() {
        if (!selectedAddress) return;

        let fullAddr = '';
        if (selectedAddress.addr_detail) fullAddr += selectedAddress.addr_detail;
        if (selectedAddress.addr_subdistrict) {
            fullAddr += (selectedAddress.addr_province.includes('กรุงเทพ')) ? ' แขวง' + selectedAddress.addr_subdistrict : ' ตำบล' + selectedAddress.addr_subdistrict;
        }
        if (selectedAddress.addr_district) {
            fullAddr += (selectedAddress.addr_province.includes('กรุงเทพ')) ? ' เขต' + selectedAddress.addr_district : ' อำเภอ' + selectedAddress.addr_district;
        }
        if (selectedAddress.addr_province) fullAddr += ' จังหวัด' + selectedAddress.addr_province;
        if (selectedAddress.addr_zipcode) fullAddr += ' ' + selectedAddress.addr_zipcode;

        let branchText = '';
        if (selectedAddress.addr_type === 'corporate') {
            branchText = selectedAddress.addr_branch ? ` (สาขา: ${selectedAddress.addr_branch})` : ' (สำนักงานใหญ่)';
        }

        let html = `
        <div class="fw-bold text-dark fs-6 mb-1">${selectedAddress.addr_name}${branchText}</div>
        <div>โทรศัพท์ ${selectedAddress.addr_phone || '-'}</div>
        <div>เลขประจำตัวผู้เสียภาษี ${selectedAddress.addr_tax_id || '-'}</div>
        <div>${fullAddr}</div>
    `;

        $('#active-address-container').html(html);
    }

    function openAddressSelectorModal(page = 1) {
        let container = $('#address-list-modal-body');
        if (!$('#addressSelectorModal').is(':visible')) {
            $('#addressSelectorModal').modal('show');
        }
        container.html('<div class="text-center h-100 d-flex flex-column justify-content-center align-items-center" style="min-height: 350px;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">กำลังโหลดรายการที่อยู่...</p></div>');

        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';
        $.ajax({
            type: "POST",
            url: coreUrl,
            data: {
                request_state: "address",
                request_function: "get_address",
                page: page,
                limit: 4
            },
            dataType: "json",
            headers: {
                "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
            },
            success: function (response) {
                container.empty();
                if ((response.result == 1 || response.status == 1) && response.data && response.data.addresses && response.data.addresses.length > 0) {
                    let modalAddresses = response.data.addresses;
                    modalAddresses.forEach(function (addr) {
                        let fullAddr = '';
                        if (addr.addr_detail) fullAddr += addr.addr_detail;
                        if (addr.addr_subdistrict) {
                            fullAddr += (addr.addr_province.includes('กรุงเทพ')) ? ' แขวง' + addr.addr_subdistrict : ' ตำบล' + addr.addr_subdistrict;
                        }
                        if (addr.addr_district) {
                            fullAddr += (addr.addr_province.includes('กรุงเทพ')) ? ' เขต' + addr.addr_district : ' อำเภอ' + addr.addr_district;
                        }
                        if (addr.addr_province) fullAddr += ' จังหวัด' + addr.addr_province;
                        if (addr.addr_zipcode) fullAddr += ' ' + addr.addr_zipcode;

                        let branchText = '';
                        if (addr.addr_type === 'corporate') {
                            branchText = addr.addr_branch ? ` (สาขา: ${addr.addr_branch})` : ' (สำนักงานใหญ่)';
                        }

                        let isActive = selectedAddress && (parseInt(addr.addr_id) === parseInt(selectedAddress.addr_id));
                        let activeClass = isActive ? 'active' : '';
                        let badgeHtml = (addr.addr_is_default == '1') ? '<span class="badge bg-success float-end">ค่าเริ่มต้น</span>' : '';

                        let itemHtml = `
                        <div class="modal-address-item ${activeClass}" data-id="${addr.addr_id}">
                            ${badgeHtml}
                            <div class="fw-bold text-dark mb-1">${addr.addr_name}${branchText}</div>
                            <div class="text-secondary small mb-1">เลขประจำตัวผู้เสียภาษี: ${addr.addr_tax_id || '-'}</div>
                            <div class="text-secondary small">${fullAddr}</div>
                        </div>
                    `;
                        container.append(itemHtml);
                    });

                    // Pagination
                    let totalPages = response.data.total_pages;
                    let currPage = response.data.current_page;

                    if (totalPages > 1) {
                        let paginationHtml = '<div id="address-pagination" class="d-flex justify-content-center mt-4 pb-3"><ul class="pagination">';

                        if (currPage > 1) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="openAddressSelectorModal(${currPage - 1}); return false;"><i class="bi bi-chevron-left"></i></a></li>`;
                        }

                        let startPage = Math.max(1, currPage - 2);
                        let endPage = Math.min(totalPages, currPage + 2);

                        if (startPage > 1) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="openAddressSelectorModal(1); return false;">1</a></li>`;
                            if (startPage > 2) {
                                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                            }
                        }

                        for (let p = startPage; p <= endPage; p++) {
                            let active = (p == currPage) ? 'active' : '';
                            paginationHtml += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="openAddressSelectorModal(${p}); return false;">${p}</a></li>`;
                        }

                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                            }
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="openAddressSelectorModal(${totalPages}); return false;">${totalPages}</a></li>`;
                        }

                        if (currPage < totalPages) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="openAddressSelectorModal(${currPage + 1}); return false;"><i class="bi bi-chevron-right"></i></a></li>`;
                        }

                        paginationHtml += '</ul></div>';
                        container.append(paginationHtml);
                    }

                    $('.modal-address-item').off('click').on('click', function () {
                        let addrId = $(this).data('id');
                        $('#addressSelectorModal').modal('hide');
                        let found = modalAddresses.find(a => parseInt(a.addr_id) === parseInt(addrId));
                        if (found) {
                            selectedAddress = found;
                            renderActiveAddress();
                        } else {
                            fetchUserAddresses(addrId);
                        }
                    });

                } else {
                    container.html('<div class="text-center py-4 text-muted">ไม่พบข้อมูลที่อยู่อื่น ๆ</div>');
                }
            },
            error: function (xhr, status, error) {
                console.error("Address fetch error:", error);
                container.html('<div class="text-danger text-center py-4">ไม่สามารถดึงข้อมูลที่อยู่ได้</div>');
            }
        });
    }

    // Global modal triggering function for adding new address
    window.Getmodal_Addaddress = function () {
        $("#showModal").html('<div class="text-center py-5"><div class="spinner-border text-primary mb-2" role="status"></div><div>กำลังโหลด...</div></div>');
        $("#myModal").modal("show");

        $.ajax({
            type: "POST",
            url: "view/address/modal_add_address.php",
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
            },
            error: function () {
                $("#myModal").modal("hide");
                Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถโหลดหน้าต่างเพิ่มที่อยู่ได้", "error");
            }
        });
    };

    // Override saveAddress function locally to refresh active payment address
    window.saveAddress = function () {
        // Validation
        let isValid = true;
        $(".invalid-feedback-custom").hide();
        $("input, select").removeClass("is-invalid");
        // สำหรับ select2
        $(".select2-selection").removeClass("is-invalid");

        const showError = (name) => {
            let el = $("input[name='" + name + "'], select[name='" + name + "']");
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

        var formData = new FormData($("#formAddAddress")[0]);
        formData.append("request_state", "address");
        formData.append("request_function", "save_address");

        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "JSON",
            success: function (response) {
                Swal.close();
                if (response.result == 1) {
                    Swal.fire("บันทึกสำเร็จ", "บันทึกข้อมูลที่อยู่ออกใบกำกับภาษีเรียบร้อยแล้ว", "success");
                    $("#myModal").modal("hide");

                    // Refresh address list and select the first/newest one
                    fetchUserAddresses();
                } else {
                    Swal.fire("เกิดข้อผิดพลาด", response.message || response.msg, "error");
                }
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถบันทึกข้อมูลที่อยู่ได้", "error");
            }
        });
    };

    function processMockPayment(orderId) {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';

        Swal.fire({
            title: 'กำลังบันทึกสถานะการชำระเงิน...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            type: "POST",
            url: coreUrl,
            data: {
                request_state: "cart",
                request_function: "mock_pay",
                order_id: orderId
            },
            dataType: "json",
            headers: {
                "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
            },
            success: function (response) {
                Swal.close();
                if (response.result == 1 || response.status == 1) {
                    Swal.fire({
                        title: 'สำเร็จ',
                        text: 'ระบบบันทึกรายการชำระเงินและสมัครคอร์สเรียนของคุณเรียบร้อยแล้ว',
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', response.msg || 'ไม่สามารถยืนยันยอดเงินได้', 'error');
                }
            },
            error: function (xhr, status, error) {
                Swal.close();
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์เพื่อชำระเงินได้', 'error');
            }
        });
    }

    function loadPaymentCartSummary() {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';
        let container = $('#summary-items-list');

        $.ajax({
            type: "POST",
            url: coreUrl,
            data: {
                request_state: "cart",
                request_function: "get"
            },
            dataType: "json",
            headers: {
                "Authorization": "Bearer " + (localStorage.getItem("access_token") || "")
            },
            success: function (response) {
                // console.log("Cart Response:", response);
                let isSuccess = (response.result == 1 || response.status == 1);
                if (isSuccess && response.data && response.data.items && response.data.items.length > 0) {
                    let items = response.data.items;
                    let html = '';
                    let backofficePath = window.location.pathname.includes('/pages/') ? '../../backoffice/' : '../backoffice/';

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
                            `<img src="${imgUrl}" class="summary-course-img" onerror="this.style.display='none';">` :
                            `<div class="summary-course-img bg-light d-flex align-items-center justify-content-center text-muted" style="font-size: 10px;">No Image</div>`;

                        let instructor = item.lecturer_name || 'สุรพงษ์ ลักษณานุกูล';
                        
                        let deleteBtn = items.length > 1 ? `<button type="button" class="btn btn-sm text-danger p-0 border-0" onclick="removePaymentCartItem(${item.item_id})" title="ลบรายการนี้" style="font-size: 1.1rem; line-height: 1;"><i class="bi bi-trash3-fill"></i></button>` : '';

                        html += `
                        <div class="summary-course-card position-relative">
                            ${imgHtml}
                            <div class="summary-course-info w-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="summary-course-title mb-1" title="${item.course_name}">${item.course_name}</div>
                                    ${deleteBtn}
                                </div>
                                <div class="summary-course-instructor">${instructor}</div>
                                <div class="summary-course-price">ราคา ${item.course_price_fmt} ฿</div>
                            </div>
                        </div>
                    `;
                    });

                    container.html(html);

                    // Update totals
                    cartTotal = parseFloat(response.data.total);
                    let subtotal = cartTotal / 1.07;
                    let vat = cartTotal - subtotal;

                    $('#summary-total-original').text(cartTotal.toLocaleString() + ' ฿');
                    $('#summary-subtotal').text(subtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');
                    $('#summary-vat').text(vat.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿');

                    let netTotal = Math.max(0, cartTotal - couponDiscount);
                    $('#summary-total').text(netTotal.toLocaleString() + ' ฿');
                    $('#btnPayAction').text('ชำระเงินสุทธิ ' + netTotal.toLocaleString() + ' ฿');
                    $('#bank-transfer-amount').text(netTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    updatePromptPayQRCode(netTotal);

                    // Update company bank account info dynamically
                    if (response.data && response.data.bank) {
                        let banks = response.data.bank;

                        // ฟังก์ชันย่อยสำหรับอัปเดตข้อมูลธนาคารบนหน้าจอ
                        function updateDisplayedBank(bank) {
                            if (!bank) return;
                            let bankLabel = bank.bank_name + (bank.bank_abbreviation ? ' (' + bank.bank_abbreviation + ')' : '');
                            $('#bank-name-display').text(bankLabel);
                            $('#bank-account-name-display').text('ชื่อบัญชี: ' + bank.account_name);
                            $('#bank-account-num').text(bank.account_no);

                            if (bank.logo_image_url) {
                                $('#bank-logo-wrapper').html(`<img src="${bank.logo_image_url}" alt="${bank.bank_abbreviation || ''}" style="width: 100%; height: 100%; object-fit: cover;">`);
                            } else {
                                $('#bank-logo-wrapper').html(`<i class="bi bi-bank" style="font-size: 1.3rem;"></i>`);
                            }
                        }

                        if (Array.isArray(banks) && banks.length > 0) {
                            if (banks.length === 1) {
                                $('#bank-chevron-icon').hide().css('cursor', 'default');
                                $('#bank-list-collapse').hide();
                                updateDisplayedBank(banks[0]);
                            } else {
                                // กรณีมีหลายธนาคาร: แสดงปุ่มลูกศรลง และทำลิสต์ตัวเลือกแบบสไลด์
                                let altContainer = $('#bank-list-alternatives');
                                altContainer.empty();
                                banks.forEach(function (bank, idx) {
                                    let bankLabel = bank.bank_name + (bank.bank_abbreviation ? ' (' + bank.bank_abbreviation + ')' : '');
                                    let logoHtml = bank.logo_image_url ?
                                        `<img src="${bank.logo_image_url}" alt="${bank.bank_abbreviation || ''}" style="width: 100%; height: 100%; object-fit: cover;">` :
                                        `<i class="bi bi-bank" style="font-size: 1rem;"></i>`;

                                    let cardHtml = `
                                    <div class="p-2.5 bg-light border rounded-3 d-flex align-items-center justify-content-between bank-select-item" data-index="${idx}" style="cursor: pointer; transition: all 0.2s;">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; overflow: hidden; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                                ${logoHtml}
                                            </div>
                                            <div class="text-start">
                                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">${bankLabel}</div>
                                                <div class="text-secondary" style="font-size: 0.75rem;">ชื่อบัญชี: ${bank.account_name}</div>
                                                <div class="fw-bold text-success" style="font-size: 0.88rem;">${bank.account_no}</div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                    altContainer.append(cardHtml);
                                });
                                $('#bank-chevron-icon').show().css('cursor', 'pointer');

                                // เลือกตัวแรก (ตัวที่ ID น้อยที่สุด ซึ่งถูกเรียงลำดับมาจากหลังบ้านแล้ว) เป็นค่าเริ่มต้น
                                updateDisplayedBank(banks[0]);

                                // สลับเมื่อกดเลือกธนาคารจากลิสต์ที่ขยายออกมา
                                $(document).off('click', '.bank-select-item').on('click', '.bank-select-item', function (e) {
                                    e.preventDefault();
                                    let selectedIndex = $(this).data('index');
                                    updateDisplayedBank(banks[selectedIndex]);

                                    // ปิดแถบที่ขยายลงมา
                                    $('#bank-list-collapse').slideUp(200);
                                    $('#bank-chevron-icon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
                                });

                                // ผูกปุ่มลูกศรให้กดสไลด์เปิด/ปิด
                                $('#bank-chevron-icon').off('click').on('click', function (e) {
                                    e.stopPropagation();
                                    $('#bank-list-collapse').slideToggle(200, function () {
                                        let isVisible = $(this).is(':visible');
                                        if (isVisible) {
                                            $('#bank-chevron-icon').removeClass('bi-chevron-down').addClass('bi-chevron-up');
                                        } else {
                                            $('#bank-chevron-icon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
                                        }
                                    });
                                });
                            }
                        } else if (typeof banks === 'object' && banks !== null) {
                            // รองรับกรณีส่งกลับมาเป็นก้อน Object เดี่ยว (กันเหนียว)
                            $('#bank-chevron-icon').hide().css('cursor', 'default');
                            $('#bank-list-collapse').hide();
                            updateDisplayedBank(banks);
                        }
                    }

                } else {
                    container.html('<div class="text-center py-4 text-muted">ไม่มีสินค้าในตะกร้า</div>');
                    Swal.fire('แจ้งเตือน', 'ตะกร้าสินค้าว่างเปล่า กรุณาเลือกซื้อคอร์สเรียนก่อนทำการชำระเงิน', 'warning').then(() => {
                        window.location.href = 'categories.php';
                    });
                }
            },
            error: function () {
                container.html('<div class="text-center py-4 text-danger">ไม่สามารถดึงข้อมูลรายการสั่งซื้อได้</div>');
            }
        });
    }

    window.removePaymentCartItem = function(itemId) {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: "คุณต้องการลบคอร์สนี้ออกจากคำสั่งซื้อใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
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
                        if (response.result == 1 || response.status == 1) {
                            loadPaymentCartSummary();
                            if (typeof loadDropdownCart === 'function') loadDropdownCart();
                        } else {
                            Swal.fire('แจ้งเตือน', response.msg || 'ไม่สามารถลบรายการได้', 'warning');
                        }
                    },
                    error: function () {
                        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    }
                });
            }
        });
    };

    function generatePromptPayPayload(target, amount) {
        target = target.replace(/\D/g, '');
        let targetType = (target.length === 13) ? '02' : '01';

        if (targetType === '01') {
            target = '0066' + target.substring(1);
        }

        let merchantInfo = '0016A000000677010111' + targetType + String(target.length).padStart(2, '0') + target;

        let payload = '000201' + '010212';
        payload += '29' + String(merchantInfo.length).padStart(2, '0') + merchantInfo;
        payload += '5303764'; // THB

        if (amount !== null && amount !== undefined) {
            let amountStr = parseFloat(amount).toFixed(2);
            payload += '54' + String(amountStr.length).padStart(2, '0') + amountStr;
        }

        payload += '5802TH';
        payload += '6304';

        // CRC16 Checksum
        let crc = 0xFFFF;
        for (let i = 0; i < payload.length; i++) {
            let x = ((crc >> 8) ^ payload.charCodeAt(i)) & 0xFF;
            x ^= x >> 4;
            crc = ((crc << 8) ^ (x << 12) ^ (x << 5) ^ x) & 0xFFFF;
        }
        let crcStr = crc.toString(16).toUpperCase().padStart(4, '0');

        return payload + crcStr;
    }

    function updatePromptPayQRCode(amount) {
        let qrContainer = document.getElementById("promptpay-qrcode-canvas");
        if (!qrContainer) {
            return; // ป้องกัน JS Error จากการที่ไม่มี Element ตัวนี้ในหน้าจอ
        }

        // แสดงเลขบัญชีพร้อมเพย์บนหน้าจอ
        let formattedId = promptpayId;
        if (promptpayId.length === 13) {
            formattedId = promptpayId.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5');
        } else if (promptpayId.length === 10) {
            formattedId = promptpayId.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
        }
        let accountIdEl = $('#promptpay-account-id');
        if (accountIdEl.length) {
            accountIdEl.text(formattedId);
        }

        // เจนรหัสสแกนพร้อมเพย์
        let payload = generatePromptPayPayload(promptpayId, amount);

        // วาดรูป QR Code
        $(qrContainer).html('');
        new QRCode(qrContainer, {
            text: payload,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    function showPromptPaySwalPopup(orderData) {
        let orderId = orderData.order_id;
        let amount = parseFloat(orderData.total_price);
        let payload = generatePromptPayPayload(promptpayId, amount);

        let formattedId = promptpayId;
        if (promptpayId.length === 13) {
            formattedId = promptpayId.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5');
        } else if (promptpayId.length === 10) {
            formattedId = promptpayId.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
        }

        let popupHtml = `
        <div style="font-family: 'Prompt', sans-serif; text-align: center; padding: 10px;">
            <h4 class="fw-bold mb-3" style="color: #0f172a;">สแกน QR Code ชำระเงิน</h4>

            <div class="d-inline-block bg-white p-3 border rounded shadow-sm mb-3" style="border-radius: 12px !important;">
                <div id="swal-qr-canvas" style="min-height: 200px; min-width: 200px;"></div>
            </div>

            <div class="mb-3 text-dark fw-bold" style="font-size: 1.1rem;">
                เลขพร้อมเพย์: <span class="text-primary">${formattedId}</span>
            </div>

            <div class="p-3 mb-3 bg-light rounded border">
                <div class="text-secondary small">ยอดเงินโอนสุทธิ</div>
                <div class="text-danger fw-bold fs-2 my-1" style="font-size: 2.2rem !important;">${amount.toLocaleString()} ฿</div>
            </div>


            <div class="mb-4">
                <div class="text-secondary small mb-1">เวลาทำรายการที่เหลือ</div>
                <div class="fw-bold text-dark fs-5" id="swal-timer">05:00</div>
            </div>

            <button type="button" class="btn btn-primary w-100 py-2.5 fw-bold" id="btn-swal-paid-confirm" style="border-radius: 50px; background-color: #2d5fa6; border-color: #2d5fa6;">
                <i class="bi bi-check-circle-fill me-1"></i> ฉันโอนเงินเรียบร้อยแล้ว
            </button>
        </div>
    `;

        let timerInterval;
        let timeRemaining = 300; // 5 mins

        Swal.fire({
            html: popupHtml,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'ยกเลิกรายการสั่งซื้อ',
            allowOutsideClick: false,
            didOpen: () => {
                // วาดรูป QR Code
                new QRCode(document.getElementById("swal-qr-canvas"), {
                    text: payload,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });

                // ปุ่มคัดลอกใน Swal
                $('#btn-copy-swal-amount').on('click', function () {
                    navigator.clipboard.writeText(amount.toFixed(2)).then(() => {
                        Swal.showValidationMessage('คัดลอกยอดเงิน ' + amount.toFixed(2) + ' ฿ สำเร็จ!');
                        setTimeout(() => {
                            Swal.resetValidationMessage();
                        }, 2000);
                    });
                });

                // เมื่อกดยืนยันโอนเงินเรียบร้อย
                $('#btn-swal-paid-confirm').on('click', function () {
                    clearInterval(timerInterval);
                    Swal.close();
                    // ส่งต่อไปยังระบบยืนยันจำลอง (mock_pay)
                    processMockPayment(orderId);
                });

                // จับเวลาถอยหลัง
                timerInterval = setInterval(() => {
                    timeRemaining--;
                    let mins = Math.floor(timeRemaining / 60);
                    let secs = timeRemaining % 60;
                    $('#swal-timer').text(String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0'));

                    if (timeRemaining <= 0) {
                        clearInterval(timerInterval);
                        Swal.fire('หมดเวลาทำรายการ', 'การทำรายการชำระเงินหมดอายุแล้ว กรุณาสั่งซื้อใหม่อีกครั้ง', 'warning');
                    }
                }, 1000);
            },
            willClose: () => {
                clearInterval(timerInterval);
            }
        });
    }

    function showXenditPromptPayPopup(orderData) {
        let orderId = orderData.order_id;
        let amount = parseFloat(orderData.total_price);
        let qrString = orderData.qr_string;

        let popupHtml = `
        <div style="font-family: 'Prompt', sans-serif; text-align: center; padding: 10px;">
            <h4 class="fw-bold mb-3" style="color: #0f172a;">สแกน QR Code ชำระเงิน</h4>
            <div class="d-inline-block bg-white p-3 border rounded shadow-sm mb-3" style="border-radius: 12px !important;">
                <div id="swal-qr-canvas" style="min-height: 200px; min-width: 200px;"></div>
            </div>
            <div class="p-3 mb-3 bg-light rounded border">
                <div class="text-secondary small">ยอดเงินโอนสุทธิ</div>
                <div class="text-danger fw-bold fs-2 my-1" style="font-size: 2.2rem !important;">${amount.toLocaleString()} ฿</div>
            </div>
            <div class="mb-4">
                <div class="text-secondary small mb-1">เวลาทำรายการที่เหลือ</div>
                <div class="fw-bold text-dark fs-5" id="swal-timer">05:00</div>
            </div>
            <div class="text-muted small">ระบบจะอัปเดตอัตโนมัติเมื่อชำระเงินสำเร็จ</div>
        </div>
        `;

        let timerInterval;
        let pollingInterval;
        let timeRemaining = 300; // 5 mins

        Swal.fire({
            html: popupHtml,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'ยกเลิกรายการสั่งซื้อ',
            allowOutsideClick: false,
            didOpen: () => {
                new QRCode(document.getElementById("swal-qr-canvas"), {
                    text: qrString,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });

                timerInterval = setInterval(() => {
                    timeRemaining--;
                    let mins = Math.floor(timeRemaining / 60);
                    let secs = timeRemaining % 60;
                    $('#swal-timer').text(String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0'));

                    if (timeRemaining <= 0) {
                        clearInterval(timerInterval);
                        clearInterval(pollingInterval);
                        Swal.fire('หมดเวลาทำรายการ', 'การทำรายการชำระเงินหมดอายุแล้ว กรุณาสั่งซื้อใหม่อีกครั้ง', 'warning');
                    }
                }, 1000);

                pollingInterval = setInterval(() => {
                    startPaymentPolling(orderId, timerInterval, pollingInterval);
                }, 5000);
            },
            willClose: () => {
                clearInterval(timerInterval);
                clearInterval(pollingInterval);
            }
        });
    }

    function startPaymentPolling(orderId, timerInterval, pollingInterval) {
        let coreUrl = window.location.pathname.includes('/pages/') ? '../core.php' : 'core.php';
        $.ajax({
            type: "POST",
            url: coreUrl,
            data: { 
                request_state: 'cart',
                request_function: 'check_payment_status',
                order_id: orderId 
            },
            dataType: "json",
            success: function(response) {
                if (response.result === 1 && response.data && response.data.paid === true) {
                    clearInterval(timerInterval);
                    clearInterval(pollingInterval);
                    Swal.fire({
                        title: 'ชำระเงินสำเร็จ !',
                        text: 'ระบบได้รับยอดเงินของคุณเรียบร้อยแล้ว คอร์สเรียนจะเปิดให้ใช้งานทันที',
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.location.href = 'profile-menu.php?tab=course';
                    });
                }
            }
        });
    }

</script>

<?php include 'components/footer.php'; ?>