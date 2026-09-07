<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use App\Database\Connection;

$input = json_decode(file_get_contents('php://input'), true);
$addr_id = isset($input['addr_id']) ? (int)$input['addr_id'] : 0;

$address = null;
if ($addr_id > 0) {
    try {
        $db_instance = new Connection();
        $pdo_connect = $db_instance->getPdo();
        
        $stmt = $pdo_connect->prepare("SELECT * FROM tbl_user_address WHERE addr_id = :addr_id AND delete_at IS NULL LIMIT 1");
        $stmt->execute([':addr_id' => $addr_id]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch (Exception $e) {
        // เงียบไว้
    }
}

// แยกประเภทที่อยู่เพื่อนำไปตั้งค่าเริ่มต้นในฟอร์ม
$addr_name = $address['addr_name'] ?? '';
$addr_tax_id = $address['addr_tax_id'] ?? '';
$addr_branch = $address['addr_branch'] ?? '';
$addr_branch_name = $address['addr_branch_name'] ?? '';
$addr_phone = $address['addr_phone'] ?? '';
$addr_detail = $address['addr_detail'] ?? '';
$addr_province = $address['addr_province'] ?? '';
$addr_district = $address['addr_district'] ?? '';
$addr_subdistrict = $address['addr_subdistrict'] ?? '';
$addr_zipcode = $address['addr_zipcode'] ?? '';

// ตรวจสอบว่าเป็นนิติบุคคล (เช็คจาก addr_type หรือข้อมูลสาขา/เลขผู้เสียภาษี)
$saved_addr_type = (string)($address['addr_type'] ?? '');
if ($saved_addr_type === '2' || $saved_addr_type === 'corporate' || $saved_addr_type === 'typeCorporate') {
    $is_corporate = true;
} else if ($saved_addr_type === '1' || $saved_addr_type === 'individual' || $saved_addr_type === 'typeIndividual') {
    $is_corporate = false;
} else {
    $is_corporate = !empty($addr_branch) || !empty($addr_tax_id);
}
$is_headoffice = ($addr_branch_name === 'สำนักงานใหญ่' || $addr_branch === 'สำนักงานใหญ่');

$corp_branch_code = $addr_branch;
$corp_branch_name = $addr_branch_name;

if ($is_corporate && empty($corp_branch_code) && $is_headoffice) {
    $corp_branch_code = '00000';
}
?>

<!-- Load Select2 CSS statically -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .address-modal-input, .address-modal-select {
        background-color: #f1f5f9 !important;
        border: 1px solid transparent !important;
        border-radius: 8px !important;
        padding: 14px 20px !important;
        font-size: 0.95rem !important;
        font-family: 'Prompt', sans-serif !important;
        color: #1e293b !important;
        outline: none !important;
        box-shadow: none !important;
        width: 100%;
    }
    
    .address-modal-input.is-invalid, .address-modal-select.is-invalid, .select2-container--default .select2-selection--single.is-invalid {
        border: 1px solid #dc3545 !important;
    }
    
    .address-modal-input::placeholder {
        color: #94a3b8 !important;
        opacity: 1;
    }
    
    .address-modal-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 20px center !important;
        background-size: 14px 10px !important;
        padding-right: 40px !important;
        cursor: pointer;
    }

    .address-form-check {
        cursor: pointer;
        user-select: none;
    }

    .address-form-check-input {
        width: 22px !important;
        height: 22px !important;
        cursor: pointer;
        border-color: #cbd5e1 !important;
    }

    .address-form-check-input:checked {
        background-color: #4a66ac !important;
        border-color: #4a66ac !important;
    }

    .search-container {
        position: relative;
    }

    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .search-input {
        padding-left: 48px !important;
    }

    /* จัดแต่งความสวยงามของ Select2 ให้ตรงกับ Mockup */
    .select2-container--default .select2-selection--single {
        background-color: #f1f5f9 !important;
        border: none !important;
        border-radius: 8px !important;
        height: 50px !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1e293b !important;
        padding-left: 20px !important;
        font-family: 'Prompt', sans-serif !important;
        font-size: 0.95rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 50px !important;
        right: 15px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important;
    }
    .select2-container {
        width: 100% !important;
    }
    /* ปรับแต่ง Dropdown ของ Select2 */
    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        overflow: hidden !important;
    }
    .select2-results__option {
        font-family: 'Prompt', sans-serif !important;
        font-size: 0.95rem !important;
        padding: 10px 20px !important;
    }
    /* เมื่อโดน Disable */
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #e2e8f0 !important;
        opacity: 0.7;
        cursor: not-allowed;
    }
</style>

<!-- Header -->
<div class="modal-header border-0 pb-0" style="font-family: 'Prompt', sans-serif;">
    <h5 class="modal-title fw-bold px-3 pt-3" style="font-size: 1.35rem; color: #1e293b;">
        แก้ไขที่อยู่ออกใบกำกับภาษี
    </h5>
    <button type="button" class="btn-close me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- Body -->
<div class="modal-body px-4 pb-4" style="font-family: 'Prompt', sans-serif; max-height: calc(100vh - 150px); overflow-y: auto;">
    
    <!-- Form -->
    <form id="formAddAddress" autocomplete="off" onsubmit="event.preventDefault(); saveAddress();">
        <input type="hidden" name="addr_id" value="<?php echo $addr_id; ?>">
        
        <!-- ประเภทบุคคล -->
        <div class="px-3 mb-4">
            <div class="d-flex align-items-center gap-4">
                <div class="form-check d-flex align-items-center gap-2 m-0 p-0 address-form-check">
                    <input class="form-check-input address-form-check-input m-0" type="radio" name="addr_type" id="type_individual" value="individual" <?php echo !$is_corporate ? 'checked' : ''; ?>>
                    <label class="form-check-label m-0 fw-medium" for="type_individual" style="color: #4b5563; font-size: 1rem; cursor: pointer;">
                        บุคคลธรรมดา
                    </label>
                </div>
                <div class="form-check d-flex align-items-center gap-2 m-0 p-0 address-form-check">
                    <input class="form-check-input address-form-check-input m-0" type="radio" name="addr_type" id="type_corporate" value="corporate" <?php echo $is_corporate ? 'checked' : ''; ?>>
                    <label class="form-check-label m-0 fw-medium" for="type_corporate" style="color: #4b5563; font-size: 1rem; cursor: pointer;">
                        นิติบุคคล
                    </label>
                </div>
            </div>
        </div>

        <div class="px-3">
            <!-- 1. ส่วนของ บุคคลธรรมดา -->
            <div id="individual-section" style="<?php echo !$is_corporate ? 'display: block;' : 'display: none;'; ?>">
                <!-- ชื่อ - นามสกุล -->
                <div class="mb-3">
                    <input type="text" name="indiv_name" id="indiv_name" class="address-modal-input" placeholder="ชื่อ - นามสกุล" value="<?php echo !$is_corporate ? htmlspecialchars($addr_name) : ''; ?>" <?php echo !$is_corporate ? 'required' : ''; ?>>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกชื่อ-นามสกุล</div>
                </div>

                <!-- เลขประจำตัวประชาชน -->
                <div class="mb-3">
                    <input type="text" name="indiv_tax_id" id="indiv_tax_id" class="address-modal-input" placeholder="เลขที่บัตรประชาชน" maxlength="13" value="<?php echo !$is_corporate ? htmlspecialchars($addr_tax_id) : ''; ?>" <?php echo !$is_corporate ? 'required' : ''; ?>>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกเลขที่บัตรประชาชน 13 หลัก</div>
                </div>
            </div>

            <!-- 2. ส่วนของ นิติบุคคล -->
            <div id="corporate-section" style="<?php echo $is_corporate ? 'display: block;' : 'display: none;'; ?>">
                <!-- ชื่อบริษัท -->
                <div class="mb-3">
                    <input type="text" name="corp_name" id="corp_name" class="address-modal-input" placeholder="ชื่อบริษัท" value="<?php echo $is_corporate ? htmlspecialchars($addr_name) : ''; ?>" <?php echo $is_corporate ? 'required' : ''; ?>>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกชื่อบริษัท</div>
                </div>

                <!-- หมายเลขประจำตัวผู้เสียภาษี -->
                <div class="mb-3">
                    <input type="text" name="corp_tax_id" id="corp_tax_id" class="address-modal-input" placeholder="หมายเลขประจำตัวผู้เสียภาษี" maxlength="13" value="<?php echo $is_corporate ? htmlspecialchars($addr_tax_id) : ''; ?>" <?php echo $is_corporate ? 'required' : ''; ?>>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกหมายเลขประจำตัวผู้เสียภาษี 13 หลัก</div>
                </div>

                <!-- รหัสสาขา -->
                <div class="mb-3">
                    <input type="text" name="corp_branch_code" id="corp_branch_code" class="address-modal-input" placeholder="รหัสสาขา (เช่น 00000)" value="<?php echo $is_corporate ? htmlspecialchars($corp_branch_code) : ''; ?>">
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกรหัสสาขา</div>
                </div>

                <!-- Checkbox สำนักงานใหญ่ -->
                <div class="form-check d-flex align-items-center gap-2 mb-3 p-0 address-form-check">
                    <input class="form-check-input address-form-check-input m-0" type="checkbox" name="corp_is_headoffice" id="corp_is_headoffice" value="1" <?php echo $is_headoffice ? 'checked' : ''; ?>>
                    <label class="form-check-label m-0 fw-medium" for="corp_is_headoffice" style="color: #4b5563; font-size: 0.95rem; cursor: pointer;">
                        สำนักงานใหญ่
                    </label>
                </div>

                <!-- ชื่อสาขา (ซ่อนไว้ถ้าเป็นสำนักงานใหญ่) -->
                <div class="mb-3" id="branch_container" style="<?php echo !$is_headoffice && $is_corporate ? 'display: block;' : 'display: none;'; ?>">
                    <input type="text" name="corp_branch_name" id="corp_branch_name" class="address-modal-input" placeholder="ชื่อสาขา (เช่น สาขาบางนา)" value="<?php echo !$is_headoffice && $is_corporate ? htmlspecialchars($corp_branch_name) : ''; ?>">
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกชื่อสาขา</div>
                </div>

                <!-- ค้นหาข้อมูลผู้เสียภาษี (ดีไซน์ตาม Mockup) -->
                <!-- <div class="mb-3 search-container">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="corp_search" class="address-modal-input search-input" placeholder="ค้นหาข้อมูลผู้เสียภาษี">
                </div> -->
            </div>

            <!-- 3. ส่วนข้อมูลที่อยู่หลัก (ใช้ร่วมกันทั้ง 2 ประเภท) -->
            
            <!-- เบอร์โทรศัพท์ -->
            <div class="mb-3">
                <input type="text" name="addr_phone" id="addr_phone" class="address-modal-input" placeholder="เบอร์โทรศัพท์" maxlength="10" value="<?php echo htmlspecialchars($addr_phone); ?>" required>
                <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกเบอร์โทรศัพท์ (10 หลัก)</div>
            </div>

            <!-- ที่อยู่รายละเอียด -->
            <div class="mb-3">
                <input type="text" name="addr_detail" id="addr_detail" class="address-modal-input" placeholder="ที่อยู่" value="<?php echo htmlspecialchars($addr_detail); ?>" required>
                <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณากรอกรายละเอียดที่อยู่</div>
            </div>

            <!-- จังหวัด & อำเภอ -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <select name="addr_province" id="addr_province" class="address-modal-select" required>
                        <option value="" disabled selected hidden>จังหวัด</option>
                    </select>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณาเลือกจังหวัด</div>
                </div>
                <div class="col-6">
                    <select name="addr_district" id="addr_district" class="address-modal-select" disabled required>
                        <option value="" disabled selected hidden>เขต/อำเภอ</option>
                    </select>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณาเลือกเขต/อำเภอ</div>
                </div>
            </div>

            <!-- ตำบล & รหัสไปรษณีย์ -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <select name="addr_subdistrict" id="addr_subdistrict" class="address-modal-select" disabled required>
                        <option value="" disabled selected hidden>แขวง/ตำบล</option>
                    </select>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณาเลือกแขวง/ตำบล</div>
                </div>
                <div class="col-6">
                    <select name="addr_zipcode" id="addr_zipcode" class="address-modal-select" disabled required>
                        <option value="" disabled selected hidden>รหัสไปรษณีย์</option>
                    </select>
                    <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> กรุณาเลือกรหัสไปรษณีย์</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end align-items-center gap-3">
                <button type="button" class="btn border-0 fw-medium" data-bs-dismiss="modal" style="color: #1e293b; font-size: 1rem; background: none;">
                    ปิด
                </button>
                <button type="submit" class="btn text-white px-4 py-2" 
                        style="background-color: #3b5998; border-radius: 8px; font-weight: 500; font-size: 1rem; min-width: 110px;">
                    ยืนยัน
                </button>
            </div>
        </div>

    </form>
</div>

<script>
    (function() {
        // รับค่าเก่าจากฝั่ง PHP มาใช้ใน JavaScript สำหรับตั้งค่าเริ่มต้น
        const savedProvince = <?php echo json_encode($addr_province); ?>;
        const savedDistrict = <?php echo json_encode($addr_district); ?>;
        const savedSubdistrict = <?php echo json_encode($addr_subdistrict); ?>;
        const savedZipcode = <?php echo json_encode($addr_zipcode); ?>;

        // จัดการการสลับประเภท บุคคลธรรมดา / นิติบุคคล
        const typeIndividual = document.getElementById('type_individual');
        const typeCorporate = document.getElementById('type_corporate');
        const indivSection = document.getElementById('individual-section');
        const corpSection = document.getElementById('corporate-section');
        
        const indivName = document.getElementById('indiv_name');
        const indivTaxId = document.getElementById('indiv_tax_id');
        const corpName = document.getElementById('corp_name');
        const corpTaxId = document.getElementById('corp_tax_id');

        function toggleSections() {
            if (typeIndividual.checked) {
                indivSection.style.display = 'block';
                corpSection.style.display = 'none';
                
                indivName.required = true;
                indivTaxId.required = true;
                corpName.required = false;
                corpTaxId.required = false;
            } else {
                indivSection.style.display = 'none';
                corpSection.style.display = 'block';
                
                indivName.required = false;
                indivTaxId.required = false;
                corpName.required = true;
                corpTaxId.required = true;
            }
        }

        typeIndividual.addEventListener('change', toggleSections);
        typeCorporate.addEventListener('change', toggleSections);

        // จัดการการเปิด/ปิดช่องกรอกสาขา นิติบุคคล
        const corpIsHeadOffice = document.getElementById('corp_is_headoffice');
        const branchContainer = document.getElementById('branch_container');
        const corpBranchName = document.getElementById('corp_branch_name');

        corpIsHeadOffice.addEventListener('change', function() {
            if (this.checked) {
                branchContainer.style.display = 'none';
                corpBranchName.required = false;
            } else {
                branchContainer.style.display = 'block';
                corpBranchName.required = true;
            }
        });

        // จำกัดให้กรอกตัวเลขในเลขบัตรประชาชน / เลขผู้เสียภาษี / เบอร์โทรศัพท์
        const numInputs = [indivTaxId, corpTaxId, document.getElementById('addr_phone')];
        numInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });

        // โค้ดดึงสคริปต์ Select2 แบบ Dynamic เพื่อให้แน่ใจว่าโหลดครบแล้วจึงเริ่มสร้างออบเจกต์
        function loadSelect2(callback) {
            if ($.fn.select2) {
                callback();
                return;
            }
            // โหลดสคริปต์ Select2 JS
            $.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function() {
                callback();
            });
        }

        loadSelect2(function() {
            // ส่วนของ Select2 และข้อมูลประเทศไทย
            const s2Options = {
                dropdownParent: $('#myModal'),
                width: '100%'
            };

            const $province = $('#addr_province').select2($.extend({}, s2Options, { placeholder: 'จังหวัด' }));
            const $district = $('#addr_district').select2($.extend({}, s2Options, { placeholder: 'เขต/อำเภอ' }));
            const $subdistrict = $('#addr_subdistrict').select2($.extend({}, s2Options, { placeholder: 'แขวง/ตำบล' }));
            const $zip = $('#addr_zipcode').select2($.extend({}, s2Options, { placeholder: 'รหัสไปรษณีย์' }));

            let thailandData = [];

            function initProvinces(data) {
                thailandData = data;
                
                $province.html('<option value="" disabled selected hidden>จังหวัด</option>');
                thailandData.sort((a, b) => a.name_th.localeCompare(b.name_th, 'th'));
                
                thailandData.forEach(province => {
                    const opt = new Option(province.name_th, province.name_th);
                    opt.dataset.id = province.id;
                    $province.append(opt);
                });
                
                // หากมีจังหวัดเดิมอยู่แล้ว ให้เลือกและทริกเกอร์
                if (savedProvince) {
                    $province.val(savedProvince).trigger('change');
                } else {
                    $province.trigger('change');
                }
            }

            // โหลดข้อมูลจังหวัดจากฐานข้อมูลผ่าน core.php
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "address",
                    request_function: "get_thai_address"
                },
                dataType: "json",
                success: function(response) {
                    if (response.result == 1) {
                        initProvinces(response.data);
                    } else {
                        console.error("Failed to load address data from core:", response.msg || response.message);
                    }
                },
                error: function(err) {
                    console.error("Error fetching address data from core:", err);
                }
            });

            // เมื่อเลือกจังหวัด
            $province.on('change', function() {
                const selectedOpt = this.options[this.selectedIndex];
                if (!selectedOpt || selectedOpt.value === "") {
                    return;
                }
                const provinceId = parseInt(selectedOpt.dataset.id);
                const province = thailandData.find(p => p.id === provinceId);
                
                $district.html('<option value="" disabled selected hidden>เขต/อำเภอ</option>').prop('disabled', true).trigger('change');
                $subdistrict.html('<option value="" disabled selected hidden>แขวง/ตำบล</option>').prop('disabled', true).trigger('change');
                $zip.html('<option value="" disabled selected hidden>รหัสไปรษณีย์</option>').prop('disabled', true).trigger('change');
                
                if (province && province.amphure) {
                    $district.prop('disabled', false);
                    
                    province.amphure.sort((a, b) => a.name_th.localeCompare(b.name_th, 'th'));
                    province.amphure.forEach(amp => {
                        const opt = new Option(amp.name_th, amp.name_th);
                        opt.dataset.id = amp.id;
                        $district.append(opt);
                    });

                    // หากมีอำเภอเดิมอยู่แล้ว ให้เลือกและทริกเกอร์
                    if (savedDistrict && $province.val() === savedProvince) {
                        $district.val(savedDistrict).trigger('change');
                    } else {
                        $district.trigger('change');
                    }
                }
            });

            // เมื่อเลือกเขต/อำเภอ
            $district.on('change', function() {
                const selectedOpt = this.options[this.selectedIndex];
                if (!selectedOpt || selectedOpt.value === "") {
                    return;
                }
                const amphureId = parseInt(selectedOpt.dataset.id);
                
                const provinceOpt = $province[0].options[$province[0].selectedIndex];
                const provinceId = parseInt(provinceOpt.dataset.id);
                
                const province = thailandData.find(p => p.id === provinceId);
                const amphure = province ? province.amphure.find(a => a.id === amphureId) : null;
                
                $subdistrict.html('<option value="" disabled selected hidden>แขวง/ตำบล</option>').prop('disabled', true).trigger('change');
                $zip.html('<option value="" disabled selected hidden>รหัสไปรษณีย์</option>').prop('disabled', true).trigger('change');
                
                if (amphure && amphure.tambon) {
                    $subdistrict.prop('disabled', false);
                    
                    amphure.tambon.sort((a, b) => a.name_th.localeCompare(b.name_th, 'th'));
                    amphure.tambon.forEach(tam => {
                        const opt = new Option(tam.name_th, tam.name_th);
                        opt.dataset.id = tam.id;
                        opt.dataset.zip = tam.zip_code;
                        $subdistrict.append(opt);
                    });

                    // หากมีตำบลเดิมอยู่แล้ว ให้เลือกและทริกเกอร์
                    if (savedSubdistrict && $district.val() === savedDistrict) {
                        $subdistrict.val(savedSubdistrict).trigger('change');
                    } else {
                        $subdistrict.trigger('change');
                    }
                }
            });

            // เมื่อเลือกตำบลเพื่อหารหัสไปรษณีย์
            $subdistrict.on('change', function() {
                const selectedOpt = this.options[this.selectedIndex];
                if (!selectedOpt || selectedOpt.value === "") {
                    return;
                }
                const zipCode = selectedOpt.dataset.zip;
                
                $zip.html('<option value="" disabled selected hidden>รหัสไปรษณีย์</option>').prop('disabled', true).trigger('change');
                
                if (zipCode) {
                    $zip.prop('disabled', false);
                    const opt = new Option(zipCode, zipCode, true, true);
                    $zip.append(opt).trigger('change');
                }
            });
        });

    })();
</script>
