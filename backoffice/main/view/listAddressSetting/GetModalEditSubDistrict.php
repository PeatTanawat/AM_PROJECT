<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$id         = (int) ($_POST['id'] ?? 0);
$amphure_id = (int) ($_POST['amphure_id'] ?? 0);
$name_th    = (string) ($_POST['name_th'] ?? '');
$name_en    = (string) ($_POST['name_en'] ?? '');
$zip_code   = (string) ($_POST['zip_code'] ?? '');
?>
<div class="modal-header">
    <h5 class="modal-title" id="myModalLabel">แก้ไขข้อมูลตำบล</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <form id="formEditSubDistrict" method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="amphure_id" value="<?php echo $amphure_id; ?>">
        <div class="mb-3">
            <label for="sd_edit_name_th" class="form-label fw-semibold">ชื่อตำบล (ภาษาไทย) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="sd_edit_name_th" name="name_th" value="<?php echo $esc($name_th); ?>" required>
        </div>
        <div class="mb-3">
            <label for="sd_edit_name_en" class="form-label fw-semibold">ชื่อตำบล (ภาษาอังกฤษ)</label>
            <input type="text" class="form-control" id="sd_edit_name_en" name="name_en" value="<?php echo $esc($name_en); ?>">
        </div>
        <div class="mb-3">
            <label for="sd_edit_zip_code" class="form-label fw-semibold">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="sd_edit_zip_code" name="zip_code" value="<?php echo $esc($zip_code); ?>" required>
        </div>
    </form>
</div>
<div class="modal-footer p-3">
    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
    <button type="button" class="btn btn-primary px-4 BtnSubmitForm" onclick="SubmitEditSubDistrict()">บันทึกข้อมูล</button>
</div>

<script>
function SubmitEditSubDistrict() {
    let name_th  = $('#sd_edit_name_th').val().trim();
    let zip_code = $('#sd_edit_zip_code').val().trim();

    if (name_th === "") {
        Swal.fire({
            title: "แจ้งเตือน",
            html: '<span class="fw-bold text-danger">กรุณากรอกชื่อตำบล/แขวง (ภาษาไทย)</span>',
            icon: "error",
            timer: 2000,
            timerProgressBar: true
        });
        return;
    }
    if (zip_code === "") {
        Swal.fire({
            title: "แจ้งเตือน",
            html: '<span class="fw-bold text-danger">กรุณากรอกรหัสไปรษณีย์</span>',
            icon: "error",
            timer: 2000,
            timerProgressBar: true
        });
        return;
    }

    var formData = new FormData($('#formEditSubDistrict')[0]);
    formData.append('request_state', 'list_address_setting');
    formData.append('request_function', 'edit_sub_district');

    $.ajax({
        type: "POST",
        url: "core.php",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (res) {
            if (res.result == 1) {
                $("#myModal").modal('hide');
                Swal.fire({
                    title: "สำเร็จ",
                    html: '<span class="fw-bold text-success">' + res.msg + '</span>',
                    icon: "success",
                    timer: 1500,
                    timerProgressBar: true
                }).then(() => {
                    GetData(currentPage);
                });
            } else {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">' + res.msg + '</span>',
                    icon: "error"
                });
            }
        },
        error: function (jqXHR, exception) {
            if (typeof ShowErrorAjax === 'function') ShowErrorAjax(jqXHR, exception);
        }
    });
}
</script>
