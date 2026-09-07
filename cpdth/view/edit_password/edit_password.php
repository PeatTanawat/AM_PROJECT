<div class="col-12" style="width: 100%; max-width: 100%;">
    <div class="content-card">
        <div class="tab-content" id="mainTabContent">

            <div class="tab-pane" id="tab-password">
                <h3 class="tab-title"><i class="bi bi-lock-fill"></i> แก้ไขรหัสผ่าน</h3>

                <style>
                    .form-control-pw.is-invalid {
                        border: 1px solid #dc3545 !important;
                    }
                </style>
                <form id="edit-password-form">

                    <div class="form-group-pw" style="position: relative;">
                        <label>รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" id="new_password" class="form-control-pw"  style="padding-right: 40px;">
                        
                        <i class="bi bi-eye-slash-fill toggle-password" style="position: absolute; right: 15px; top: 38px; cursor: pointer; color: #6c757d;" onclick="togglePassword('new_password', this)"></i>
                        <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> <span></span></div>
                    </div>

                    <div class="form-group-pw" style="position: relative; margin-top: 20px;">
                        <label>ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control-pw"  style="padding-right: 40px;">
                        
                        <i class="bi bi-eye-slash-fill toggle-password" style="position: absolute; right: 15px; top: 38px; cursor: pointer; color: #6c757d;" onclick="togglePassword('confirm_password', this)"></i>
                        <div class="invalid-feedback-custom" style="display:none; font-size: 0.85rem; color: #dc3545; text-align: left; margin-top: 5px; padding-left: 12px;"><i class="bi bi-exclamation-circle"></i> <span></span></div>
                    </div>

                    <div class="pw-actions" style="margin-top: 30px;">
                        <button type="button" class="btn-cancel" onclick="$('#edit-password-form')[0].reset();">ยกเลิก</button>
                        <button type="submit" class="btn-save">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#edit-password-form').on('submit', function(e) {
        e.preventDefault();

        const new_password = $('#new_password').val();
        const confirm_password = $('#confirm_password').val();
        let isValid = true;

        $(".invalid-feedback-custom").hide();
        $("input").removeClass("is-invalid");

        const showError = (id, msg) => {
            let el = $('#' + id);
            el.addClass('is-invalid');
            el.siblings('.invalid-feedback-custom').find('span').text(msg);
            el.siblings('.invalid-feedback-custom').show();
            isValid = false;
        };

        if (!new_password) {
            showError('new_password', 'กรุณากรอกรหัสผ่านใหม่');
        } else if (new_password.length < 6) {
            showError('new_password', 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        }

        if (!confirm_password) {
            showError('confirm_password', 'กรุณายืนยันรหัสผ่านใหม่');
        } else if (new_password !== confirm_password) {
            showError('confirm_password', 'การยืนยันรหัสผ่านไม่ตรงกัน');
        }

        if (!isValid) return;

        $.ajax({
            beforeSend: function() {
                Swal.fire({
                    title: 'กำลังบันทึก...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "password",
                request_function: "edit_password",
                new_password: new_password,
                confirm_password: confirm_password
            },
            dataType: "json",
            success: function(response) {
                Swal.close();
                if (response.result == 1 || response.status == 1) {
                    Swal.fire('สำเร็จ', response.message || response.msg, 'success').then(() => {
                        $('#edit-password-form')[0].reset();
                    });
                } else {
                    Swal.fire('แจ้งเตือน', response.message || response.msg, 'warning');
                }
            },
            error: function(err) {
                console.error(err);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    });
});

function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye-slash-fill");
        icon.classList.add("bi-eye-fill");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-fill");
        icon.classList.add("bi-eye-slash-fill");
    }
}
</script>