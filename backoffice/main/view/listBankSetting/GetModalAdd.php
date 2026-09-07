<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Utility\Auth;

Auth::requireUserToken();
?>
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 0.75rem !important;
        color: var(--bs-body-color) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<div class="modal-header">
    <h5 class="modal-title" id="myModalLabel">เพิ่มข้อมูลบัญชีธนาคาร</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <form id="formAddBank" method="POST">
        <div class="mb-3">
            <label for="add_bank_id" class="form-label fw-semibold">เลือกธนาคาร <span class="text-danger"> *</span></label>
            <select class="form-select" id="add_bank_id" name="bank_id">
                <option value="">กำลังโหลดรายชื่อธนาคาร...</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="account_name" class="form-label fw-semibold">กรอกชื่อ account <span class="text-danger"> *</span></label>
            <input type="text" class="form-control" id="account_name" name="account_name" placeholder="กรอกชื่อบัญชีธนาคาร">
        </div>
        <div class="mb-3">
            <label for="account_no" class="form-label fw-semibold">กรอกเลขที่บัญชี <span class="text-danger"> *</span></label>
            <input type="text" class="form-control" id="account_no" name="account_no" placeholder="กรอกเลขที่บัญชี">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold d-block">สถานะ</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="active_status" id="add_status_active" value="1" checked>
                <label class="form-check-label" for="add_status_active">เปิดใช้งาน</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="active_status" id="add_status_inactive" value="0">
                <label class="form-check-label" for="add_status_inactive">ปิดใช้งาน</label>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer p-3">
    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
    <button type="button" class="btn btn-primary px-4 BtnSubmitForm" onclick="AddBank()">
        บันทึกข้อมูล
    </button>
</div>

<script>
    (function () {
        if (typeof GetBankMapping === 'function') {
            GetBankMapping('#add_bank_id');
        }
    })();

    function AddBank() {
        let bank_id = $('#add_bank_id').val();
        let account_name = $('#account_name').val().trim();
        let account_no = $('#account_no').val().trim();

        if (!bank_id) {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณาเลือกธนาคาร</span>',
                icon: "error",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        if (account_name === "") {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณากรอกชื่อ account</span>',
                icon: "error",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        if (account_no === "") {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณากรอกเลขที่บัญชี</span>',
                icon: "error",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        var formData = new FormData($('#formAddBank')[0]);
        formData.append('request_state', 'list_bank_setting');
        formData.append('request_function', 'add_bank');

        $.ajax({
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function () {
                if (typeof ShowLoadingButton === 'function') {
                    ShowLoadingButton(".BtnSubmitForm");
                }
            },
            success: function (response) {
                if (response.result == 1) {
                    $("#myModal").modal('hide');
                    Swal.fire({
                        title: "สำเร็จ",
                        html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                        icon: "success",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    }).then(() => {
                        LoadData();
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            },
            complete: function () {
                if (typeof HideLoadingButton === 'function') {
                    HideLoadingButton(".BtnSubmitForm");
                }
            },
            error: function (jqXHR, exception) {
                ShowErrorAjax(jqXHR, exception);
            }
        });
    }
</script>