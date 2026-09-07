<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$id      = (int) ($_POST['id'] ?? 0);
$name_th = (string) ($_POST['name_th'] ?? '');
$name_en = (string) ($_POST['name_en'] ?? '');
?>
<div class="modal-header">
    <h5 class="modal-title" id="myModalLabel">แก้ไขข้อมูลจังหวัด</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <form id="formEditProvince" method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="mb-3">
            <label for="p_edit_name_th" class="form-label fw-semibold">ชื่อจังหวัด (ภาษาไทย) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="p_edit_name_th" name="name_th" value="<?php echo $esc($name_th); ?>" required>
        </div>
        <div class="mb-3">
            <label for="p_edit_name_en" class="form-label fw-semibold">ชื่อจังหวัด (ภาษาอังกฤษ)</label>
            <input type="text" class="form-control" id="p_edit_name_en" name="name_en" value="<?php echo $esc($name_en); ?>">
        </div>
    </form>
</div>
<div class="modal-footer p-3">
    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
    <button type="button" class="btn btn-primary px-4 BtnSubmitForm" onclick="SubmitEditProvince()">บันทึกข้อมูล</button>
</div>

<script>
function SubmitEditProvince() {
    let name_th = $('#p_edit_name_th').val().trim();
    if (name_th === "") {
        Swal.fire({
            title: "แจ้งเตือน",
            html: '<span class="fw-bold text-danger">กรุณากรอกชื่อจังหวัด (ภาษาไทย)</span>',
            icon: "error",
            timer: 2000,
            timerProgressBar: true
        });
        return;
    }

    var formData = new FormData($('#formEditProvince')[0]);
    formData.append('request_state', 'list_address_setting');
    formData.append('request_function', 'edit_province');

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
                    LoadProvinceFilterOptions();
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
