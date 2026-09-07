<?php
$pageTitle = 'รายละเอียดคำสั่งซื้อ';
include '../../components/header.php';

// รองรับทั้งแบบ /order/?id=123 และ /order/123
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $parts = explode('/', $path);
    $last = end($parts);
    if (is_numeric($last)) {
        $order_id = (int)$last;
    }
}
?>

<style>
    body {
        background-color: #fff;
    }
    .v-card {
        background-color: #fff;
        color: rgba(0, 0, 0, .87);
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .v-card__title {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        font-size: 1.1rem;
        font-weight: 600;
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
    }
    .v-card__text {
        padding: 20px;
    }
    .v-data-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .v-data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.95rem;
    }
    .v-data-table tr:last-child td {
        border-bottom: none;
    }
    .v-chip {
        border-radius: 16px;
        font-size: 13px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        padding: 0 16px;
        color: #fff;
        font-weight: 500;
    }
    .v-chip.green { background-color: #4caf50; }
    .v-chip.orange { background-color: #ff9800; }
    .v-chip.grey { background-color: #9e9e9e; }
</style>

<div class="v-main__wrap" style="min-height: 70vh;">
    <main>
        <div class="page-header pb-5 pt-5 text-center" style="background-color: #f4f5f7;">
            <div class="container d-flex flex-column align-items-center mt-5 mb-5">
                <p class="mb-1 text-muted" style="font-size: 1.1rem;">หมายเลขคำสั่งซื้อ</p>
                <h1 class="!tw-text-[32px] tw-font-bold" id="detail-order-ref" style="font-size: 2.2rem; font-weight: bold;">กำลังโหลด...</h1>
            </div>
        </div> 
        <div class=" mb-5" style="margin-top: 30px; margin-left: 80px; margin-right: 80px;">
            <div class="row mt-4 justify-content-center" id="order-content" style="display: none;">
                <div class="col-lg-7 col-xl-7 col-12 mb-4">
                    <div class="v-card v-sheet v-sheet--outlined theme--light elevation-0 rounded-lg">
                        <div class="v-card__title">
                            <i aria-hidden="true" class="v-icon notranslate v-icon--left mdi mdi-clipboard-text-outline theme--light black--text me-2"></i>
                            รายการสั่งซื้อคอร์สเรียน
                        </div> 
                        <div class="v-card__text p-0">
                            <div class="v-data-table theme--light">
                                <div class="v-data-table__wrapper">
                                    <table>
                                        <tbody id="detail-items-body">
                                            <!-- Items -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> 
                <div class="col-lg-5 col-xl-5 col-12 mb-4">
                    <div class="v-card v-sheet v-sheet--outlined theme--light elevation-0 rounded-lg">
                        <div class="v-card__title">
                            <i aria-hidden="true" class="v-icon notranslate v-icon--left mdi mdi-clipboard-text-outline theme--light black--text me-2"></i>
                            สรุปคำสั่งซื้อ
                        </div> 
                        <div class="v-card__text p-0">
                            <div class="v-data-table theme--light">
                                <div class="v-data-table__wrapper">
                                    <table>
                                        <tbody>
                                            <tr>
                                                <td class="text-muted">หมายเลขคำสั่งซื้อ</td> 
                                                <td class="text-end fw-medium" id="summary-order-ref"></td>
                                            </tr> 
                                            <tr>
                                                <td class="text-muted">สถานะ</td> 
                                                <td class="text-end" id="summary-order-status"></td>
                                            </tr> 
                                            <tr>
                                                <td class="text-muted">ช่องทางการชำระเงิน</td> 
                                                <td class="text-end fw-medium" id="summary-payment-method"></td>
                                            </tr> 
                                            <tr>
                                                <td class="text-muted">วันที่ทำรายการ</td> 
                                                <td class="text-end fw-medium" id="summary-order-date"></td>
                                            </tr> 
                                            <tr>
                                                <td class="text-muted">จำนวน</td> 
                                                <td class="text-end fw-medium" id="summary-total-items"></td>
                                            </tr> 
                                            <tr>
                                                <td class="text-muted">ราคาก่อนภาษี</td> 
                                                <td class="text-end fw-medium" id="summary-subtotal"></td>
                                            </tr> 
                                            <tr>
                                                <td class="text-muted">ภาษีมูลค่าเพิ่ม 7%</td> 
                                                <td class="text-end fw-medium" id="summary-vat"></td>
                                            </tr> 
                                            <tr>
                                                <td class="font-weight-bold text-body-1" style="font-size:1.1rem;">ราคาสุทธิ</td> 
                                                <td class="font-weight-bold text-end" style="color: #ff5722; font-size: 1.2rem;" id="summary-total-price"></td>
                                            </tr> 
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="error-content" style="display: none;" class="text-center py-5">
                <h3 class="text-danger">ไม่พบข้อมูลคำสั่งซื้อ</h3>
                <a href="../profile-menu.php" class="btn btn-primary mt-3">กลับไปหน้าประวัติการชำระเงิน</a>
            </div>
        </div>
    </main>
</div>


<script>
$(document).ready(function() {
    // เนื่องจากหน้านี้อยู่ลึกเข้ามาในโฟลเดอร์ view/order/ และเราไม่ได้แก้ header/navbar
    // จึงใช้ JS ช่วยปรับ Path ของลิงก์เมนูและรูปภาพต่างๆ ให้ถูกต้องแบบอัตโนมัติ
    $('a').each(function() {
        let href = $(this).attr('href');
        if (href && !href.startsWith('http') && !href.startsWith('/') && !href.startsWith('#') && !href.startsWith('.')) {
            $(this).attr('href', '../../' + href);
        }
    });
    $('img').each(function() {
        let src = $(this).attr('src');
        if (src && !src.startsWith('http') && !src.startsWith('/') && !src.startsWith('.')) {
            $(this).attr('src', '../../' + src);
        }
    });

    const orderId = <?php echo json_encode($order_id) ?>;
    
    if (!orderId) {
        $('#detail-order-ref').text('ข้อผิดพลาด');
        $('#error-content').show();
        return;
    }

    $.ajax({
        beforeSend: function() {
            Swal.fire({
                title: 'กำลังโหลดข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        },
        type: "POST",
        url: "../../core.php",
        data: {
            request_state: "history",
            request_function: "get_detail",
            order_id: orderId
        },
        dataType: "json",
        success: function (response) {
            Swal.close();
            let isSuccess = (response.result == 1 || response.status == 1);
            if (isSuccess && response.data && response.data.order) {
                const order = response.data.order;
                const ref = order.transaction_ref || ('#' + order.order_id);
                
                $('#detail-order-ref').text(ref);
                $('#summary-order-ref').text(ref);
                $('#summary-order-date').text(order.created_at);
                
                let totalPrice = parseFloat(order.total_price);
                // ดึง VAT ออกมาโชว์ (สมมติว่าเป็น VAT 7% รวมอยู่ในราคาสุทธิ) หรือถ้าเป็นราคาไม่รวม VAT ก็ปรับได้
                let vat = (totalPrice * 7) / 107;
                let subTotal = totalPrice - vat;
                
                $('#summary-subtotal').text(subTotal.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿');
                $('#summary-vat').text(vat.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿');
                $('#summary-total-price').text(totalPrice.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿');
                
                let statusHtml = '';
                if (order.payment_status == '1') {
                    statusHtml = '<span class="v-chip theme--dark v-size--default green"><span class="v-chip__content">ดำเนินการแล้ว</span></span>';
                } else if (order.payment_status == '0') {
                    statusHtml = '<span class="v-chip theme--dark v-size--default orange"><span class="v-chip__content">รอชำระเงิน</span></span>';
                } else {
                    statusHtml = '<span class="v-chip theme--dark v-size--default grey"><span class="v-chip__content">ยกเลิก/หมดอายุ</span></span>';
                }
                $('#summary-order-status').html(statusHtml);

                let paymentMethod = 'ไม่ได้ระบุ';
                if (order.payment_method == '1') paymentMethod = 'PromptPay';
                else if (order.payment_method == '2') paymentMethod = 'โอนเงินเข้าบัญชี';
                else if (order.payment_method == '3') paymentMethod = 'บัตรเครดิต';
                $('#summary-payment-method').text(paymentMethod);

                const tbody = $('#detail-items-body');
                tbody.empty();

                let totalItems = 0;
                if (order.items && order.items.length > 0) {
                    let html = '';
                    order.items.forEach(item => {
                        let price = parseFloat(item.price_at_purchase).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿';
                        let name = item.course_name || 'คอร์สเรียนไม่ทราบชื่อ';
                        html += `
                            <tr>
                                <td class="fw-medium">${name}</td>
                                <td>1 หน่วย</td>
                                <td class="text-end">${price}</td>
                            </tr>
                        `;
                        totalItems++;
                    });
                    tbody.html(html);
                } else {
                    tbody.html('<tr><td colspan="3" class="text-center text-muted">ไม่พบรายการสินค้า</td></tr>');
                }
                
                $('#summary-total-items').text(totalItems + ' หน่วย');
                
                $('#order-content').fadeIn();

            } else {
                $('#detail-order-ref').text('ข้อผิดพลาด');
                $('#error-content').show();
                Swal.fire('แจ้งเตือน', response.message || 'ไม่สามารถโหลดข้อมูลได้', 'warning');
            }
        },
        error: function (err) {
            console.error('Error:', err);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            $('#detail-order-ref').text('ข้อผิดพลาด');
            $('#error-content').show();
        }
    });
});
</script>

<?php include '../../components/footer.php'; ?>
