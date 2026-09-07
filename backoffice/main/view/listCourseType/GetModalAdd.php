<?php
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
?>
<div class="modal-header">
    <h5 class="modal-title" id="myModalLabel">สร้างประเภทคอร์สเรียนใหม่</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <form id="formAddType" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="type_name" class="form-label fw-semibold">ชื่อประเภท <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="type_name" name="type_name" placeholder="เช่น ทั่วไป, เก็บชั่วโมงเรียน">
        </div>
    </form>
</div>
<div class="modal-footer p-3">
    <button type="button" class="btn btn-primary px-4" style="width:100%" onclick="AddType()">
        บันทึกข้อมูล
    </button>
</div>

<script>
    function AddType() {
        let type_name = $('#type_name').val().trim();

        if (type_name == "") {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">กรุณากรอกชื่อประเภท</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
            return;
        }

        var formData = new FormData($('#formAddType')[0]);
        formData.append('request_state', 'listCourseType');
        formData.append('request_function', 'add_type');

        $.ajax({
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    $("#myModal").modal('hide');
                    Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true })
                        .then(() => { LoadData(); });
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
                }
            },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
</script>
