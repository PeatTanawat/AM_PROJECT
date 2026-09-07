<!-- Header -->
<div class="modal-header border-0 pb-0">
    <h5 class="modal-title fw-bold px-3 pt-3"
        style="font-size: 1.35rem; color: #1e293b; font-family: 'Prompt', sans-serif;">
        ยืนยันตัวตนด้วยบัตรประจำตัวประชาชน
    </h5>
    <button type="button" class="btn-close me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- Body -->
<div class="modal-body px-4 pb-4" style="font-family: 'Prompt', sans-serif;">

    <!-- Info Warning Box -->
    <div class="mb-4 p-3" style="background-color: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
        <p class="m-0" style="font-size: 0.9rem; color: #1d4ed8; line-height: 1.6; font-weight: 400;">
            ข้อมูลเลขบัตรประจำตัวประชาชน ไฟล์ภาพบัตรประจำตัวประชาชน
            และไฟล์ภาพถ่ายปัจจุบันจะถูกบันทึกเอาไว้เพื่อการยืนยันตัวตนผู้เรียน/สอบเท่านั้น
            และข้อมูลจะถูกเก็บเอาไว้เป็นความลับไม่มีการเปิดเผยต่อสาธารณะ
        </p>
    </div>

    <!-- Form -->
    <form id="formIdentityVerify" enctype="multipart/form-data">

        <!-- เลขประจำตัวประชาชน -->
        <div class="d-flex align-items-center mb-3">
            <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                <i class="bi bi-person-vcard-fill"></i>
            </div>
            <div class="flex-grow-1">
                <input type="text" name="citizen_id" id="citizen_id" class="form-control"
                    placeholder="เลขประจำตัวประชาชน"
                    style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 12px 20px; font-size: 0.95rem; outline: none; box-shadow: none;"
                    required>
            </div>
        </div>

        <!-- วันหมดอายุ -->
        <div class="d-flex align-items-center mb-3">
            <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                <i class="bi bi-calendar-event-fill"></i>
            </div>
            <div class="flex-grow-1">
                <input type="text" name="expire_date" id="expire_date" class="form-control custom-rounded-input"
                    placeholder="วันหมดอายุ (วว/ดด/ปปปป)" required>
            </div>
        </div>

        <!-- อัพโหลดภาพถ่ายบัตรประชาชน -->
        <div class="mb-3">
            <div class="d-flex align-items-center">
                <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                    <i class="bi bi-paperclip"></i>
                </div>
                <div class="flex-grow-1 position-relative">
                    <input type="file" name="file_citizen" id="file_citizen" accept="image/*"
                        class="form-control d-none" required>
                    <label id="label_file_citizen" for="file_citizen" class="form-control m-0 text-muted"
                        style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 12px 20px; font-size: 0.95rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                        อัพโหลดภาพถ่ายบัตรประชาชน (ใช้ไฟล์รูปภาพ ไม่รองรับ PDF)
                    </label>
                </div>
            </div>
            <!-- Image Preview -->
            <div class="mt-2 text-center" style="display: none;" id="preview_citizen_container">
                <img id="preview_citizen" src="" alt="Preview" style="max-height: 150px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain; padding: 4px;">
            </div>
        </div>

        <!-- อัพโหลดภาพภาพถ่ายปัจจุบัน -->
        <div class="mb-4">
            <div class="d-flex align-items-center">
                <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                    <i class="bi bi-paperclip"></i>
                </div>
                <div class="flex-grow-1 position-relative">
                    <input type="file" name="file_avatar" id="file_avatar" accept="image/*" class="form-control d-none"
                        required>
                    <label id="label_file_avatar" for="file_avatar" class="form-control m-0 text-muted"
                        style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 12px 20px; font-size: 0.95rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                        อัพโหลดภาพถ่ายปัจจุบัน (ใช้ไฟล์รูปภาพ ไม่รองรับ PDF)
                    </label>
                </div>
            </div>
            <!-- Image Preview -->
            <div class="mt-2 text-center" style="display: none;" id="preview_avatar_container">
                <img id="preview_avatar" src="" alt="Preview" style="max-height: 150px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain; padding: 4px;">
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end align-items-center gap-3">
            <button type="button" class="btn border-0" data-bs-dismiss="modal"
                style="color: #4a66ac; font-weight: 500; font-size: 1rem; background: none; font-family: 'Prompt', sans-serif;">
                ยกเลิก
            </button>
            <button type="button" class="btn text-white px-4 py-2"
                style="background-color: #3b5998; border-radius: 8px; font-weight: 500; font-size: 1rem; font-family: 'Prompt', sans-serif; min-width: 110px;"
                onclick="saveIdentityVerify();">
                บันทึก
            </button>
        </div>

    </form>
</div>

<style>
    #showModal, #showModal2 {
        display: flex;
        flex-direction: column;
        max-height: 100%;
        height: 100%;
    }
    #showModal .modal-body, #showModal2 .modal-body {
        overflow-y: auto;
        max-height: calc(85vh - 120px);
    }

    .flatpickr-calendar {
        z-index: 1000000 !important;
    }

    .flatpickr-wrapper {
        width: 100%;
        display: block;
    }

    .custom-rounded-input {
        background-color: #f1f5f9 !important;
        border: none !important;
        border-radius: 50px !important;
        padding: 12px 20px !important;
        font-size: 0.95rem !important;
        outline: none !important;
        box-shadow: none !important;
        width: 100% !important;
    }
</style>
<script>
    // Use setTimeout so the DOM is fully ready inside the modal,
    // and use static: true so the calendar is appended directly next to the input
    // bypassing all modal z-index and focus trap issues.
    setTimeout(function () {
        if (typeof InitThaiDatepicker === "function") {
            InitThaiDatepicker("#expire_date", { static: true });
        } else if (typeof flatpickr !== "undefined") {
            flatpickr("#expire_date", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: true,
                static: true
            });
        }
    }, 200);

    window.previewImage = function(input, labelId, previewId, defaultText) {
        const label = document.getElementById(labelId);
        const preview = document.getElementById(previewId);
        const container = document.getElementById(previewId + '_container');

        if (input.files && input.files[0]) {
            label.innerText = input.files[0].name;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                container.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            label.innerText = defaultText;
            preview.src = '';
            container.style.display = 'none';
        }
    };
    
    // ผูก Event แบบ Delegation ป้องกันปัญหาจาก Modal ที่โหลดผ่าน AJAX
    $(document).off('change', '#file_citizen').on('change', '#file_citizen', function() {
        window.previewImage(this, 'label_file_citizen', 'preview_citizen', 'อัพโหลดภาพถ่ายบัตรประชาชน (ใช้ไฟล์รูปภาพ ไม่รองรับ PDF)');
    });

    $(document).off('change', '#file_avatar').on('change', '#file_avatar', function() {
        window.previewImage(this, 'label_file_avatar', 'preview_avatar', 'อัพโหลดภาพถ่ายปัจจุบัน (ใช้ไฟล์รูปภาพ ไม่รองรับ PDF)');
    });
</script>