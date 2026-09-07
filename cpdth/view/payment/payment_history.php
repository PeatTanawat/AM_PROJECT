<style>
    .v-card {
        background-color: #fff;
        color: rgba(0, 0, 0, .87);
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .v-card__title {
        align-items: center;
        display: flex;
        font-size: 1rem;
        font-weight: 600;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        background-color: #f8fafc;
        border-radius: 8px 8px 0 0;
    }
    .v-card__text {
        padding: 0;
    }
    .v-data-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .v-data-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
    }
    .v-data-table tr:last-child td {
        border-bottom: none;
    }
    .v-chip {
        border-radius: 16px;
        font-size: 12px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        padding: 0 10px;
        color: #fff;
        font-weight: 500;
    }
    .v-chip.green { background-color: #4caf50; }
    .v-chip.yellow { background-color: #facc15; color: #fff; }
    .v-chip.grey { background-color: #9e9e9e; }
    .v-chip.red { background-color: #dc3545; }
    .status-badge.bg-yellow-badge { background-color: #facc15; color: #fff; }
</style>
<div class="col-md-8 col-lg-9 col-12" style="width: 100%;">
    <!-- List View -->
    <div id="history-list-view" class="content-card">
        <div class="tab-content" id="mainTabContent">
            <div class="tab-pane active" id="tab-history">
                <h3 class="tab-title"><i class="bi bi-clock-history"></i> ประวัติการชำระเงิน</h3>
                <div class="table-container">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th style="width: 20%;">หมายเลขคำสั่งซื้อ</th>
                                <th class="text-end" style="width: 20%;">ราคา</th>
                                <th style="width: 30%;">วันที่ทำรายการ</th>
                                <th class="text-center"  style="width: 20%;" >สถานะ</th>
                                <th style="width: 10%;"></th>
                            </tr>
                        </thead>
                        <tbody id="history-table-body">
                            <tr>
                                <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="history-pagination" class="d-flex justify-content-center mt-4 pb-3"></div>
            </div>
        </div>
    </div>

    <!-- Detail View -->
    <div id="history-detail-view" class="content-card" style="display: none;">
        <div class="mb-3">
            <a href="#" onclick="showListView(event)" class="text-decoration-none text-secondary" style="font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-arrow-left" style="font-size: 1rem;"></i> ย้อนกลับไปประวัติการชำระเงิน
            </a>
        </div>

        <div class="page-header text-center mb-4">
            <p class="mb-1 text-muted" style="font-size: 0.9rem;">หมายเลขคำสั่งซื้อ</p>
            <h2 class="tw-font-bold m-0" id="detail-order-ref" style="font-size: 1.5rem; font-weight: bold; color: #1e293b;">กำลังโหลด...</h2>
        </div>

        <div class="mt-4 d-flex flex-column flex-xl-row" style="gap: 20px;" id="order-content">
            <div style="flex: 1 1 58%; min-width: 0;">
                <div class="v-card v-sheet v-sheet--outlined theme--light elevation-0 rounded-lg h-100">
                    <div class="v-card__title">
                        <i class="bi bi-clipboard-data me-2"></i> รายการสั่งซื้อคอร์สเรียน
                    </div>
                    <div class="v-card__text p-0">
                        <div class="v-data-table theme--light">
                            <div class="v-data-table__wrapper">
                                <table style="table-layout: fixed; width: 100%;">
                                    <tbody id="detail-items-body">
                                        <!-- Items -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div style="flex: 1 1 42%; min-width: 0;">
                <div class="v-card v-sheet v-sheet--outlined theme--light elevation-0 rounded-lg h-100">
                    <div class="v-card__title">
                        <i class="bi bi-receipt me-2"></i> สรุปคำสั่งซื้อ
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
                                            <td class="font-weight-bold" style="font-size:1rem;">ราคาสุทธิ</td>
                                            <td class="font-weight-bold text-end" style="color: #ff5722; font-size: 1.1rem;" id="summary-total-price"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div id="edit-payment-container" class="p-3 text-end" style="display: none; border-top: 1px solid #e2e8f0; background-color: #f8fafc; border-radius: 0 0 8px 8px;">
                        <div id="reject-reason-text" class="text-danger mb-2 fw-bold text-end"></div>
                        <button type="button" class="btn btn-danger px-4 py-2" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#reuploadSlipModal">
                            <i class="bi bi-pencil-square me-1"></i> แก้ไขการชำระเงิน
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Re-upload Slip -->
<div class="modal fade" id="reuploadSlipModal" tabindex="-1" role="dialog" aria-labelledby="reuploadSlipModalLabel" aria-hidden="true" inert>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title mb-0" id="reuploadSlipModalLabel"><i class="bi bi-file-earmark-arrow-up me-2"></i> แก้ไขการชำระเงิน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formReuploadSlip" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" id="reupload_order_id">
                    <div class="mb-3">
                        <label for="slip_file" class="form-label fw-bold">แนบสลิปการโอนเงินใหม่</label>
                        <input type="file" class="form-control" name="slip_file" id="slip_file" accept="image/jpeg, image/png, image/jpg" required>
                        <small class="text-muted d-block mt-2">รองรับไฟล์รูปภาพ .jpg, .jpeg, .png</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">ยกเลิก</button>
                <button type="button" class="btn btn-danger" onclick="submitReuploadSlip()" style="border-radius: 8px;">บันทึกข้อมูล</button>
            </div>
        </div>
    </div>
</div>


<!-- Template สำหรับใช้ใน Javascript (เพื่อให้แก้ไข HTML ได้ง่าย) -->
<template id="order-row-template">
    <tr>
        <td data-label="#" class="row-index"></td>
        <td data-label="หมายเลขคำสั่งซื้อ" class="row-ref"></td>
        <td data-label="ราคา" class="text-end row-price"></td>
        <td data-label="วันที่ทำรายการ" class="row-date"></td>
        <td data-label="สถานะ" class="text-center row-status"></td>
        <td data-label=""><button class="btn-action row-btn" title="ดูรายละเอียด"><i class="bi bi-search"></i></button></td>
    </tr>
</template>

<script>
    var historyItemsPerPage = 5;

    $(document).ready(function () {
        loadPaymentHistory(1);

        // ถ้ามี order_id แนบมาใน URL ให้เปิดหน้ารายละเอียดเลย
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        if (orderId) {
            setTimeout(() => {
                showOrderDetail(orderId);
                
                // ลบ order_id ออกจาก URL หลังจากเปิดเสร็จแล้ว เพื่อไม่ให้หน้าต่างค้างเวลาเปลี่ยนเมนู
                let currentUrl = new URL(window.location);
                currentUrl.searchParams.delete('order_id');
                window.history.replaceState({}, document.title, currentUrl.toString());
            }, 100);
        }
    });

    window.showHistoryPage = function(page, e) {
        if(e) e.preventDefault();
        loadPaymentHistory(page);
    };

    function loadPaymentHistory(page = 1) {
        $.ajax({
            beforeSend: function () {
                $('#history-table-body').html('<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>');
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "history",
                request_function: "get_history",
                page: page,
                limit: historyItemsPerPage
            },
            dataType: "json",
            success: function (response) {
                let isSuccess = (response.result == 1 || response.status == 1);
                const tbody = $('#history-table-body');
                tbody.empty();

                if (isSuccess && response.data && response.data.orders && response.data.orders.length > 0) {
                    let templateHtml = $('#order-row-template').html();
                    let startIndex = (page - 1) * historyItemsPerPage;

                    response.data.orders.forEach((order, index) => {
                        let statusBadge = '';
                        if (order.payment_status == '1') {
                            statusBadge = '<span class="status-badge bg-success-badge">ดำเนินการแล้ว</span>';
                        } else if (order.payment_status == '0') {
                            statusBadge = '<span class="status-badge bg-yellow-badge">รอตรวจสอบ</span>';
                        } else {
                            statusBadge = '<span class="v-chip red white--text" style="color: white !important;">คำสั่งซื้อไม่สำเร็จ</span>';
                        }

                        let price = parseFloat(order.total_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';
                        let ref = order.transaction_ref || '-';
                        let dateStr = order.created_at;

                        let tr = $(templateHtml);
                        tr.find('.row-index').text(startIndex + index + 1);
                        tr.find('.row-ref').text(ref);
                        tr.find('.row-price').text(price);
                        tr.find('.row-date').text(dateStr);
                        tr.find('.row-status').html(statusBadge);
                        tr.find('.row-btn').attr('onclick', `showOrderDetail(${order.order_id})`);

                        tbody.append(tr);
                    });

                    // Render pagination only when items exceed 5 items (totalPages > 1)
                    let totalPages = response.data.total_pages || 1;
                    if (totalPages > 1) {
                        let paginationHtml = '<ul class="pagination">';

                        if (page > 1) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showHistoryPage(${page - 1}, event)"><i class="bi bi-chevron-left"></i></a></li>`;
                        }

                        let startPage = Math.max(1, page - 2);
                        let endPage = Math.min(totalPages, page + 2);

                        if (startPage > 1) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showHistoryPage(1, event)">1</a></li>`;
                            if (startPage > 2) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }

                        for (let i = startPage; i <= endPage; i++) {
                            if (i === page) {
                                paginationHtml += `<li class="page-item active"><a class="page-link" href="#" onclick="showHistoryPage(${i}, event)">${i}</a></li>`;
                            } else {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showHistoryPage(${i}, event)">${i}</a></li>`;
                            }
                        }

                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showHistoryPage(${totalPages}, event)">${totalPages}</a></li>`;
                        }

                        if (page < totalPages) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showHistoryPage(${page + 1}, event)"><i class="bi bi-chevron-right"></i></a></li>`;
                        }
                        paginationHtml += '</ul>';
                        $('#history-pagination').html(paginationHtml);
                    } else {
                        $('#history-pagination').empty();
                    }

                } else {
                    tbody.html('<tr><td colspan="6" class="text-center">ไม่มีประวัติการสั่งซื้อ</td></tr>');
                    $('#history-pagination').empty();
                }
            },
            error: function (err) {
                console.error('Error:', err);
                $('#history-table-body').html('<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
            }
        });
    }

    window.showListView = function(e) {
        if(e) e.preventDefault();
        $('#history-detail-view').hide();
        $('#history-list-view').fadeIn();
    };

    window.showOrderDetail = function(orderId) {
        Swal.fire({
            title: 'กำลังโหลดข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "history",
                request_function: "get_detail",
                order_id: orderId
            },
            dataType: "json",
            success: function(response) {
                Swal.close();
                let isSuccess = (response.result == 1 || response.status == 1);
                if (isSuccess && response.data && response.data.order) {
                    let order = response.data.order;
                    let items = order.items || [];

                    $('#detail-order-ref').text(order.transaction_ref || order.order_id);
                    $('#summary-order-ref').text(order.transaction_ref || order.order_id);

                    let statusHtml = '';
                    if (order.payment_status == '1') {
                        statusHtml = '<span class="v-chip green">ดำเนินการแล้ว</span>';
                    } else if (order.payment_status == '0') {
                        statusHtml = '<span class="v-chip yellow">รอตรวจสอบ</span>';
                    } else {
                        statusHtml = '<span class="v-chip red white--text">คำสั่งซื้อไม่สำเร็จ</span>';
                    }
                    $('#summary-order-status').html(statusHtml);

                    let paymentMethod = 'ไม่ได้ระบุ';
                    if (order.payment_method == '1') paymentMethod = 'บัตรเครดิต';
                    else if (order.payment_method == '2') paymentMethod = 'PromPay';
                    else if (order.payment_method == '3') paymentMethod = 'โอนเงินเข้าบัญชี';

                    $('#summary-payment-method').text(paymentMethod);
                    $('#summary-order-date').text(order.created_at || '-');

                    let totalItems = 0;
                    let tbody = $('#detail-items-body');
                    tbody.empty();

                    if (items.length > 0) {
                        items.forEach(function(item) {
                            totalItems++;
                            let itemPrice = parseFloat(item.price_at_purchase) || 0;
                            let name = item.course_name || 'คอร์สเรียนไม่ทราบชื่อ';

                            let tr = `<tr>
                                <td style="padding: 10px 14px;" class="fw-medium">${name}</td>
                                <td class="text-center" style="width: 100px; padding: 10px 14px;">1 หน่วย</td>
                                <td class="text-end fw-medium" style="width: 120px; padding: 10px 14px;">
                                    ฿${itemPrice.toLocaleString('th-TH', {minimumFractionDigits: 2})}
                                </td>
                            </tr>`;
                            tbody.append(tr);
                        });
                    } else {
                        tbody.html('<tr><td colspan="3" class="text-center text-muted py-3">ไม่พบรายการสินค้า</td></tr>');
                    }

                    $('#summary-total-items').text(totalItems + ' รายการ');

                    let subtotal = parseFloat(order.total_price) || 0;
                    let vat = 0;
                    let total = subtotal + vat;

                    $('#summary-subtotal').text('฿' + subtotal.toLocaleString('th-TH', {minimumFractionDigits: 2}));
                    $('#summary-vat').text('฿' + vat.toLocaleString('th-TH', {minimumFractionDigits: 2}));
                    $('#summary-total-price').text('฿' + total.toLocaleString('th-TH', {minimumFractionDigits: 2}));

                    $('#history-list-view').hide();
                    $('#history-detail-view').fadeIn();
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    if (order.payment_status != '1' && order.payment_status != '0') {
                        $('#edit-payment-container').show();
                        $('#reupload_order_id').val(order.order_id);
                        if (order.remark_reject) {
                            $('#reject-reason-text').text('หมายเหตุ: ' + order.remark_reject);
                        } else {
                            $('#reject-reason-text').text('');
                        }
                    } else {
                        $('#edit-payment-container').hide();
                    }

                } else {
                    Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลคำสั่งซื้อ', 'error');
                }
            },
            error: function(err) {
                Swal.close();
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    };

    window.submitReuploadSlip = function() {
        let slipFile = $('#slip_file')[0].files[0];
        if (!slipFile) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกไฟล์สลิปชำระเงินใหม่', 'warning');
            return;
        }

        let formData = new FormData($('#formReuploadSlip')[0]);
        formData.append('request_state', 'history');
        formData.append('request_function', 'reupload_slip');

        // ปิด Modal ทันทีที่กดบันทึก
        var modalEl = document.getElementById('reuploadSlipModal');
        if (typeof bootstrap !== 'undefined') {
            var bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();
            else $('#reuploadSlipModal').modal('hide');
        } else {
            $('#reuploadSlipModal').modal('hide');
        }
        
        // บังคับเคลียร์ backdrop ทันที
        $('#reuploadSlipModal .btn-close').trigger('click');
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
        }, 100);

        Swal.fire({
            title: 'กำลังอัปโหลด...',
            text: 'กรุณารอสักครู่ ระบบกำลังอัปโหลดสลิป',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.result == 1) {
                    Swal.fire({
                        title: 'สำเร็จ',
                        text: response.message || response.msg,
                        icon: 'success'
                    }).then(() => {
                        $('#formReuploadSlip')[0].reset();
                        showOrderDetail($('#reupload_order_id').val());
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', response.message || response.msg || 'เกิดข้อผิดพลาด', 'error');
                }
            },
            error: function() {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    };

    $(document).ready(function() {
        // Toggle inert for accessibility when modal is shown/hidden
        $('#reuploadSlipModal').on('show.bs.modal', function () {
            this.removeAttribute('inert');
        });
        $('#reuploadSlipModal').on('hidden.bs.modal', function () {
            this.setAttribute('inert', '');
        });
    });
</script>
