<!-- Header -->
<div class="modal-header border-0 pb-0">
    <h5 class="modal-title fw-bold px-3 pt-3"
        style="font-size: 1.45rem; color: #1e293b; font-family: 'Prompt', sans-serif;">
        เปลี่ยนเบอร์โทรศัพท์
    </h5>
</div>

<!-- Body -->
<div class="modal-body px-4 pb-4" style="font-family: 'Prompt', sans-serif;">
    <!-- Form -->
    <form id="formChangePhone" enctype="multipart/form-data" onsubmit="event.preventDefault(); saveChangePhone();">

        <!-- เบอร์โทร -->
        <div class="position-relative mb-2">
            <input type="text" name="phone" id="phone" class="form-control" placeholder="กรอกหมายเลขโทรศัพท์"
                maxlength="10"
                style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 15px 50px 15px 25px; font-size: 1rem; outline: none; box-shadow: none; font-family: 'Prompt', sans-serif;"
                required>
            <div class="position-absolute end-0 top-50 translate-middle-y pe-4" id="phone-icon-wrapper"
                style="font-size: 1.25rem;">
                <i class="bi bi-telephone-fill" id="phone-icon" style="color: #ff4d4f; transition: color 0.2s;"></i>
            </div>
        </div>

        <!-- Error Message -->
        <div id="phone-error-msg" class="px-3 mb-4"
            style="color: #ff4d4f; font-size: 0.85rem; font-family: 'Prompt', sans-serif; min-height: 20px;">
            กรุณากรอก เบอร์โทรศัพท์
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end align-items-center gap-3">
            <button type="button" class="btn border-0" data-bs-dismiss="modal"
                style="color: #4a66ac; font-weight: 500; font-size: 1rem; background: none; font-family: 'Prompt', sans-serif; box-shadow: none; outline: none;">
                ยกเลิก
            </button>
            <button type="submit" class="btn text-white px-4 py-2"
                style="background-color: #3b5998; border-radius: 8px; font-weight: 500; font-size: 1rem; font-family: 'Prompt', sans-serif; min-width: 110px; box-shadow: none;">
                บันทึก
            </button>
        </div>

    </form>
</div>

<script>
    $(document).ready(function () {
        var $phoneInput = $('#phone');
        var $errorMsg = $('#phone-error-msg');
        var $phoneIcon = $('#phone-icon');

        function validatePhone() {
            var val = $phoneInput.val().trim();
            if (val.length === 0) {
                $errorMsg.text('กรุณากรอก เบอร์โทรศัพท์').css('visibility', 'visible');
                $phoneIcon.css('color', '#ff4d4f');
            } else if (val.length < 10) {
                $errorMsg.text('เบอร์โทรศัพท์ ต้องมีความยาวอย่างน้อย 10 ตัวอักษร').css('visibility', 'visible');
                $phoneIcon.css('color', '#ff4d4f');
            } else {
                $errorMsg.css('visibility', 'hidden');
                $phoneIcon.css('color', '#3b5998');
            }
        }

        $phoneInput.on('input', function () {
            // กรองเฉพาะตัวเลข
            this.value = this.value.replace(/[^0-9]/g, '');
            validatePhone();
        });

        // ตรวจสอบค่าเริ่มต้นตอนเปิด modal
        validatePhone();
    });
</script>