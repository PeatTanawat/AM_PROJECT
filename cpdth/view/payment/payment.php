<style>
    .col-lg-7 {
        display: flex;
        flex-direction: column;
    }

    /* การ์ดใบแรก (ที่อยู่) คงความสูงตามเนื้อหาปกติ ไม่ต้องยืด */
    .col-lg-7>.card:first-child {
        flex: 0 0 auto;
    }

    /* การ์ดใบสุดท้าย (วิธีชำระเงิน) ให้ดันเต็มพื้นที่ที่เหลือ */
    .col-lg-7>.card:last-child {
        flex: 1 1 auto;
    }
</style>

<div class="payment-container" style="font-family: 'Prompt', sans-serif;">
    <h2 class="text-center fw-bold mb-4" style="color: #1f2937; font-size: 2.2rem;">ดำเนินการชำระเงิน</h2>

    <div class="row g-4">
        <!-- Left Column: Address & Payment Methods -->
        <div class="col-lg-7">
            <!-- Card 1: Tax Invoice Address -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden; background: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold d-flex align-items-center gap-2"
                            style="font-size: 1.15rem; color: #1e293b;">
                            <i class="bi bi-person-vcard-fill text-primary" style="font-size: 1.4rem;"></i>
                            ที่อยู่ออกใบกำกับภาษี
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm px-3 py-1.5" type="button" id="btnChangeAddress"
                                onclick="openAddressSelectorModal()"
                                style="font-size: 0.85rem; border-radius: 6px; background-color: #2d5fa6; border-color: #2d5fa6;">
                                เปลี่ยนที่อยู่
                            </button>
                            <button class="btn btn-outline-primary btn-sm px-3 py-1.5" type="button"
                                onclick="Getmodal_Addaddress()"
                                style="font-size: 0.85rem; border-radius: 6px; border-color: #2d5fa6; color: #2d5fa6;">
                                เพิ่มที่อยู่ใหม่
                            </button>
                        </div>
                    </div>

                    <!-- Active Address Info Container -->
                    <div id="active-address-container" class="address-display-box"
                        style="font-size: 0.95rem; color: #475569; line-height: 1.8;">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                            <div>กำลังดึงข้อมูลที่อยู่...</div>
                        </div>
                    </div>

                    <!-- Notices -->
                    <div class="mt-4">
                        <p class="mb-2 text-danger" style="font-size: 0.82rem; line-height: 1.5;">
                            ระบบจะออกใบกำกับภาษีให้คุณโดยอัตโนมัติภายใน 1 วันในรูปแบบใบกำกับภาษีอิเล็กทรอนิกส์ (E-Tax
                            Invoice) และนำส่งให้กรมสรรพากรภายใน 1 วันหลังสั่งซื้อ
                            กรุณาตรวจสอบที่อยู่ก่อนยืนยันคำสั่งซื้อ
                        </p>
                        <div class="p-2 fw-medium text-dark text-center"
                            style="background-color: #fef08a; border-radius: 6px; font-size: 0.82rem; border: 1px solid #fde047;">
                            กรุณาตรวจสอบความถูกต้องก่อนออกใบกำกับภาษี ไม่สามารถแก้ไขได้ทุกกรณี
                        </div>
                    </div>
                </div>
            </div>


            <!-- Card 2: Payment Methods Selector -->
            <div class="card shadow-sm border-0 mb-0" style="border-radius: 12px; overflow: hidden; background: #fff; margin-bottom: 0 !important;">
                <div class="card-body p-4">
                    <div class="fw-bold d-flex align-items-center gap-2 mb-3"
                        style="font-size: 1.15rem; color: #1e293b;">
                        <i class="bi bi-wallet2 text-primary" style="font-size: 1.4rem;"></i> วิธีการชำระเงิน
                    </div>

                    <div class="payment-methods-list d-flex flex-column gap-3">
                        <!-- Method 1: Credit/Debit Card -->
                        <div class="payment-method-item" data-method="card" id="method-card-btn" style="display: none !important;">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="method-text fw-medium">บัตรเครดิต / เดบิต</span>
                                    <div class="d-flex gap-1">
                                        <img src="https://img.icons8.com/color/36/visa.png" alt="Visa">
                                        <img src="https://img.icons8.com/color/36/mastercard.png" alt="Mastercard">
                                    </div>
                                </div>
                                <div class="method-radio-circle"></div>
                            </div>
                        </div>

                        <!-- Card Form Accordion -->
                        <div id="card-form-wrapper" class="method-accordion-content" style="display: none;">
                            <div class="p-4 bg-light rounded-3 border mt-2">
                                <style>
                                    #card-form-wrapper input::placeholder {
                                        color: #adb5bd !important;
                                        opacity: 0.6;
                                        font-weight: 300;
                                    }
                                </style>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label text-secondary fw-medium"
                                            style="font-size: 0.85rem;">หมายเลขบัตร</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control px-3 py-2" id="cc-number"
                                                placeholder="0000 0000 0000 0000" maxlength="19"
                                                style="border-radius: 8px;">
                                            <i
                                                class="bi bi-credit-card-2-front position-absolute top-50 end-0 translate-middle-y me-3 text-secondary"></i>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-secondary fw-medium"
                                            style="font-size: 0.85rem;">ชื่อบนบัตร</label>
                                        <input type="text" class="form-control px-3 py-2" id="cc-name"
                                            placeholder="ชื่อภาษาอังกฤษสะกดตามหน้าบัตร" style="border-radius: 8px;">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-secondary fw-medium"
                                            style="font-size: 0.85rem;">วันหมดอายุ</label>
                                        <input type="text" class="form-control px-3 py-2 text-center" id="cc-exp"
                                            placeholder="MM/YY" maxlength="5" style="border-radius: 8px;">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-secondary fw-medium"
                                            style="font-size: 0.85rem;">CVV / CVC</label>
                                        <input type="password" class="form-control px-3 py-2 text-center" id="cc-cvv"
                                            placeholder="•••" maxlength="3" style="border-radius: 8px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Method 2: PromptPay -->
                        <div class="payment-method-item" data-method="promptpay" id="method-promptpay-btn" style="display: none !important;">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="method-text fw-medium">พร้อมเพย์</span>
                                    <span class="badge bg-primary px-2.5 py-1.5 fw-semibold"
                                        style="font-size: 0.75rem; background: #002e62 !important;">Prompt Pay</span>
                                </div>
                                <div class="method-radio-circle"></div>
                            </div>
                        </div>

                        <!-- PromptPay Accordion -->
                        <div id="promptpay-info-wrapper" class="method-accordion-content" style="display: none;">
                            <!-- ซ่อนข้อความแจ้งเตือนตามต้องการ -->
                        </div>




                        <!-- Method 3: Bank Transfer -->
                        <div class="payment-method-item" data-method="bank_transfer" id="method-bank-btn">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="method-text fw-medium">โอนเงินผ่านธนาคาร</span>
                                    <i class="bi bi-bank text-secondary fs-5"></i>
                                </div>
                                <div class="method-radio-circle"></div>
                            </div>
                        </div>

                        <!-- Bank Transfer Accordion -->
                        <div id="bank-info-wrapper" class="method-accordion-content" style="display: none;">
                            <div class="p-4 bg-light rounded-3 border mt-2">
                                <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2"
                                    style="font-size: 0.85rem; border-radius: 6px; color: #856404; background-color: #fff3cd; border-color: #ffeeba;">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>กรุณาชำระเงินตามบัญชีด้านล่างและอัปโหลดสลิปเพื่อตรวจสอบ</span>
                                </div>

                                <!-- Bank Account Card Details -->
                                <div class="p-3 bg-white border rounded-3 mb-3 position-relative">
                                    <div class="d-flex align-items-center justify-content-between"
                                        style="user-select: none;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div id="bank-logo-wrapper"
                                                class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 45px; height: 45px; background: #138f2e !important; overflow: hidden; flex-shrink: 0;">
                                                <i class="bi bi-bank" style="font-size: 1.3rem;"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark d-flex align-items-center gap-1"
                                                    style="font-size: 0.95rem;">
                                                    <span id="bank-name-display">กำลังดึงข้อมูลธนาคาร...</span>
                                                    <i class="bi bi-chevron-down text-secondary" id="bank-chevron-icon"
                                                        style="display: none; font-size: 0.9rem; -webkit-text-stroke: 0.5px; cursor: pointer;"></i>
                                                </div>
                                                <div class="text-secondary small" id="bank-account-name-display">
                                                    ชื่อบัญชี: กำลังดึงข้อมูล...</div>
                                                <div class="fw-bold text-success mt-1" style="font-size: 1.05rem;"
                                                    id="bank-account-num">กำลังดึงข้อมูล...</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-3"
                                            onclick="copyBankAccountNumber()"
                                            style="border-radius: 6px; font-size: 0.8rem; flex-shrink: 0;">
                                            <i class="bi bi-clipboard-check me-1"></i> คัดลอก
                                        </button>
                                    </div>

                                    <!-- เลือกลิสต์ธนาคารอื่นๆ ที่จะสไลด์ลงมา -->
                                    <div id="bank-list-collapse" style="display: none;" class="mt-3 pt-3 border-top">
                                        <div class="text-secondary small fw-medium mb-2">เลือกบัญชีธนาคารปลายทางอื่น:
                                        </div>
                                        <div class="d-flex flex-column gap-2" id="bank-list-alternatives">
                                            <!-- รายการบัญชีธนาคารอื่นๆ -->
                                        </div>
                                    </div>
                                </div>

                                <!-- ยอดเงินที่ต้องโอน -->
                                <div class="p-3 mb-3 border rounded-3 bg-white text-center">
                                    <div class="text-secondary small fw-medium">ยอดเงินที่ต้องโอนชำระ</div>
                                    <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                        <div class="fw-bold fs-3 text-danger"><span
                                                id="bank-transfer-amount">0.00</span> ฿</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2"
                                            onclick="copyTransferAmount()"
                                            style="border-radius: 6px; font-size: 0.75rem;">
                                            <i class="bi bi-clipboard-check"></i> คัดลอกยอดเงิน
                                        </button>
                                    </div>
                                    <div class="text-muted small mt-2" style="font-size: 0.8rem;">
                                        กรุณาโอนยอดให้ตรงตามจำนวนที่ระบุหากยอดตรง <span
                                            class="fw-semibold text-danger">ระบบจะอนุมัติคอร์สให้เข้าเรียนโดยอัตโนมัติ แต่หากเป็น Slip ที่ไม่มี QR Code รอเจ้าหน้าที่ตรวจสอบและอนุมัติหลักสูตร</span>
                                    </div>
                                </div>




                                <!-- Upload Slip Box -->
                                <div class="form-group-payment">
                                    <label class="form-label text-secondary fw-medium"
                                        style="font-size: 0.85rem;">อัปโหลดสลิปชำระเงิน</label>
                                    <div class="border border-2 border-dashed rounded-3 p-4 text-center bg-white position-relative"
                                        id="slip-dropzone"
                                        style="border-color: #cbd5e1 !important; border-style: dashed !important; cursor: pointer; transition: background-color 0.2s;">
                                        <input type="file" id="slip-file-input" accept="image/*"
                                            style="opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer;">
                                        <div id="slip-upload-placeholder">
                                            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2.2rem;"></i>
                                            <div class="fw-semibold text-dark mt-2" style="font-size: 0.9rem;">
                                                คลิกเพื่ออัปโหลด หรือ ลากรูปภาพสลิปมาวางที่นี่</div>
                                            <div class="text-muted small mt-1">รองรับไฟล์ภาพ JPG, JPEG, PNG เท่านั้น
                                            </div>
                                        </div>
                                        <div id="slip-preview-wrapper" style="display: none;">
                                            <img id="slip-preview-img" class="img-fluid rounded border mb-2"
                                                style="max-height: 180px; object-fit: contain;">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <span id="slip-file-name"
                                                    class="text-dark small fw-medium text-truncate"
                                                    style="max-width: 150px;"></span>
                                                <button type="button" class="btn btn-link btn-sm text-danger p-0"
                                                    onclick="removeSelectedSlip(event)"><i
                                                        class="bi bi-trash3-fill"></i> ลบรูป</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Order Summary & Actions -->
        <div class="col-lg-5 sticky-sidebar">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; background: #fff;">
                <div class="card-body p-4">
                    <div class="fw-bold d-flex align-items-center gap-2 mb-3"
                        style="font-size: 1.15rem; color: #1e293b;">
                        <i class="bi bi-receipt-cutoff text-primary" style="font-size: 1.4rem;"></i> สรุปคำสั่งซื้อ
                    </div>

                    <!-- Cart Item Box -->
                    <div id="summary-items-list" class="mb-4">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                            <div>กำลังดึงข้อมูลตะกร้าสินค้า...</div>
                        </div>
                    </div>

                    <!-- Pricing Table -->
                    <div class="pt-3 border-top mt-3" style="font-size: 0.95rem;">
                        <div class="d-flex justify-content-between mb-2 text-secondary">
                            <span>ราคา</span>
                            <span id="summary-total-original" class="fw-medium text-dark">0 ฿</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-secondary">
                            <span>ราคาก่อนรวมภาษีมูลค่าเพิ่ม</span>
                            <span id="summary-subtotal" class="fw-medium text-dark">0 ฿</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-secondary">
                            <span>ภาษีมูลค่าเพิ่ม</span>
                            <span id="summary-vat" class="fw-medium text-dark">0 ฿</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-secondary" id="coupon-discount-row"
                            style="display: none !important;">
                            <span>ส่วนลดคูปอง (<span id="coupon-display-code">-</span>)</span>
                            <span id="summary-discount" class="fw-medium text-danger">-0 ฿</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mb-4">
                            <span class="fw-bold text-dark fs-5">ราคาสุทธิ</span>
                            <span id="summary-total" class="fw-bold fs-4" style="color: #f97316;">0 ฿</span>
                        </div>
                    </div>

                    <!-- Coupon Input Group -->
                    <div class="input-group mb-3" style="border-radius: 8px; overflow: hidden;">
                        <input type="text" id="coupon-code" class="form-control border-end-0 py-2.5 px-3"
                            placeholder="คูปองส่วนลด" style="font-size: 0.9rem; border-color: #cbd5e1;">
                        <button class="btn btn-primary px-4 border-start-0" type="button" id="btnApplyCoupon"
                            style="font-size: 0.9rem; background-color: #2d5fa6; border-color: #2d5fa6;">ตรวจสอบ</button>
                    </div>

                    <!-- Note / Remark Input Group -->
                    <div class="mb-4">
                        <textarea id="order-remark" class="form-control py-2.5 px-3" placeholder="หมายเหตุ" rows="3"
                            style="font-size: 0.9rem; border-color: #cbd5e1; border-radius: 8px; resize: vertical;"></textarea>
                    </div>

                    <!-- Checkout Button -->
                    <button class="btn btn-primary w-100 py-3 fw-bold" id="btnPayAction"
                        style="font-size: 1.1rem; border-radius: 8px; background-color: #2d5fa6; border-color: #2d5fa6;">
                        ชำระเงินสุทธิ 0 ฿
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Address Selector (เปลี่ยนที่อยู่) -->
<div class="modal fade" id="addressSelectorModal" tabindex="-1" aria-labelledby="addressSelectorModalLabel"
    aria-hidden="true" data-bs-backdrop="static" inert>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 600px;">
        <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0 bg-light py-3">
                <h6 class="modal-title fw-bold text-dark" id="addressSelectorModalLabel">
                    <i class="bi bi-geo-alt-fill text-primary"></i> เลือกที่อยู่ออกใบกำกับภาษี
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="address-list-modal-body" style="min-height: 450px;">
                <!-- Address options will be loaded dynamically -->
                <div class="text-center h-100 d-flex flex-column justify-content-center align-items-center"
                    style="min-height: 350px;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">กำลังโหลด...</p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light p-3">
                <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal"
                    style="font-size: 0.85rem; border-radius: 8px;">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Pagination Style for Address Modal */
    #address-pagination .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 30px;
        list-style: none;
        padding: 0;
    }

    #address-pagination .page-item .page-link {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        color: #4b5563;
        font-size: 0.9rem;
        text-decoration: none;
        background-color: transparent;
        box-shadow: none;
    }

    #address-pagination .page-item.active .page-link {
        background-color: #2d5fa6;
        color: #fff;
        border-color: #2d5fa6;
    }

    #address-pagination .page-item.disabled .page-link {
        color: #9ca3af;
        background-color: transparent;
        border-color: #e5e7eb;
    }
</style>