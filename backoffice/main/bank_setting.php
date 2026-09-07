<?php $breadcrumbs = [['label' => 'ตั้งค่าระบบ', 'url' => 'website_setting'], ['label' => 'จัดการตั้งค่าธนาคาร']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">จัดการตั้งค่าธนาคาร</h2>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="button" onclick="GetModalAdd()">
                            <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">add</span>เพิ่มธนาคารใหม่
                        </button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ชื่อบัญชี, เลขที่บัญชี ">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn type-button btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listBankSetting/GetTable.php -->
                    <div id="result_box"></div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content animated fadeIn" id="LoadingMyModal">
            <div id="showModal"></div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var currentPage = 1;
    var addMode = false;

    $(document).ready(function () {
        $('#f_search').on('keypress', function (e) { 
            if (e.which === 13) { SearchData(); } 
        });
        GetData(1);
    });

    function SearchData() { 
        GetData(1); 
    }

    // ดึงข้อมูล (JSON) จาก handler ใน core.php
    function GetData(page) {
        page = page || 1;
        currentPage = page;
        $.ajax({
            beforeSend: function () { 
                if (typeof ShowLoadingOverlay === 'function') {
                    ShowLoadingOverlay("#result_box"); 
                }
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_bank_setting",
                request_function: "get_list_bank",
                search: $("#f_search").val(),
                page: page
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    view_data(r.data);
                } else {
                    $("#result_box").html('');
                    if (typeof HideLoadingOverlay === 'function') {
                        HideLoadingOverlay("#result_box");
                    }
                    Swal.fire({ 
                        title: "แจ้งเตือน", 
                        html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', 
                        icon: "error", 
                        showConfirmButton: false, 
                        timer: 2000, 
                        timerProgressBar: true 
                    });
                }
            },
            complete: function () { 
                if (typeof HideLoadingOverlay === 'function') {
                    HideLoadingOverlay("#result_box"); 
                }
            },
            error: function (jqXHR, exception) { 
                if (typeof ShowErrorAjax === 'function') {
                    ShowErrorAjax(jqXHR, exception); 
                }
            }
        });
    }

    // ส่งข้อมูลไป render เป็น HTML ผ่าน view/listBankSetting/GetTable.php
    function view_data(payload) {
        $.ajax({
            type: "POST",
            url: "view/listBankSetting/GetTable.php",
            data: {
                data:     payload.list,
                total:    payload.total,
                page:     payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) { 
                $("#result_box").html(html); 
            },
            complete: function () { 
                if (typeof HideLoadingOverlay === 'function') {
                    HideLoadingOverlay("#result_box"); 
                }
            },
            error: function (jqXHR, exception) { 
                if (typeof ShowErrorAjax === 'function') {
                    ShowErrorAjax(jqXHR, exception); 
                }
            }
        });
    }

    // เรียกหลังบันทึก เพิ่ม/แก้ไข
    function LoadData() {
        if (addMode) { 
            addMode = false; 
            GetData(1); 
        } else { 
            GetData(currentPage); 
        }
    }

    // ดึงรายชื่อธนาคารจาก tbl_bank_mapping (request_function: get_bank_mapping)
    function GetBankMapping(target_select, selected_val) {
        target_select = target_select || '#add_bank_id';
        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_bank_setting",
                request_function: "get_bank_mapping"
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1 && r.data) {
                    let html = '<option value="">--- กรุณาเลือกธนาคาร ---</option>';
                    r.data.forEach(function (item) {
                        let isSelected = (selected_val && selected_val == item.bank_id) ? 'selected' : '';
                        let text = item.bank_name + (item.bank_abbreviation ? ' (' + item.bank_abbreviation + ')' : '');
                        html += '<option value="' + item.bank_id + '" ' + isSelected + '>' + text + '</option>';
                    });
                    $(target_select).html(html);
                    if ($.fn.select2) {
                        $(target_select).select2({
                            width: '100%',
                            dropdownParent: $('#myModal')
                        });
                    }
                }
            }
        });
    }

    // โหลด Modal สำหรับเพิ่มธนาคาร
    function GetModalAdd() {
        addMode = true;
        $.ajax({
            beforeSend: function () { 
                if (typeof ShowLoadingOverlay === 'function') {
                    ShowLoadingOverlay("#myModal"); 
                }
            },
            type: "POST",
            url: "view/listBankSetting/GetModalAdd.php",
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
                $("#myModal").modal("show");
            },
            complete: function () { 
                if (typeof HideLoadingOverlay === 'function') {
                    HideLoadingOverlay("#myModal"); 
                }
            },
            error: function (jqXHR, exception) { 
                if (typeof ShowErrorAjax === 'function') {
                    ShowErrorAjax(jqXHR, exception); 
                }
            }
        });
    }

    // โหลด Modal สำหรับแก้ไขธนาคาร
    function GetEditBank(id) {
        addMode = false;
        $.ajax({
            beforeSend: function () { 
                if (typeof ShowLoadingOverlay === 'function') {
                    ShowLoadingOverlay("#myModal"); 
                }
            },
            type: "POST",
            url: "view/listBankSetting/GetModalEdit.php",
            data: JSON.stringify({ id: id }),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
                $("#myModal").modal("show");
            },
            complete: function () { 
                if (typeof HideLoadingOverlay === 'function') {
                    HideLoadingOverlay("#myModal"); 
                }
            },
            error: function (jqXHR, exception) { 
                if (typeof ShowErrorAjax === 'function') {
                    ShowErrorAjax(jqXHR, exception); 
                }
            }
        });
    }

    // สลับสถานะเปิด/ปิดใช้งานบัญชีธนาคาร
    function ToggleBankStatus(id, currentStatus) {
        let newStatus = currentStatus === '1' ? '0' : '1';
        let statusText = newStatus === '1' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        Swal.fire({
            title: "ยืนยันการเปลี่ยนสถานะ",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "ตกลง",
            cancelButtonText: "ยกเลิก",
            confirmButtonColor: "#0d6efd"
        }).then((result) => {
            if (!result.isConfirmed) { return; }
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_bank_setting",
                    request_function: "update_bank_status",
                    id: id,
                    active_status: newStatus
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "สำเร็จ",
                            html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        }).then(() => {
                            GetData(currentPage);
                        });
                    } else {
                        Swal.fire({
                            title: "แจ้งเตือน",
                            html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                            icon: "error",
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                },
                error: function (jqXHR, exception) {
                    if (typeof ShowErrorAjax === 'function') {
                        ShowErrorAjax(jqXHR, exception);
                    }
                }
            });
        });
    }

    // ลบรายการธนาคาร
    function GetDeleteBank(id) {
        Swal.fire({
            title: "ยืนยันการลบ",
            html: '<span class="text-secondary">ต้องการลบรายการธนาคารนี้ใช่หรือไม่?</span>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ลบ",
            cancelButtonText: "ยกเลิก",
            confirmButtonColor: "#dc3545"
        }).then((result) => {
            if (!result.isConfirmed) { return; }
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_bank_setting",
                    request_function: "delete_bank",
                    id: id
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ 
                            title: "สำเร็จ", 
                            html: '<span class="fw-bold text-success">' + response.msg + '</span>', 
                            icon: "success", 
                            showConfirmButton: false, 
                            timer: 2000, 
                            timerProgressBar: true 
                        }).then(() => { 
                            GetData(currentPage); 
                        });
                    } else {
                        Swal.fire({ 
                            title: "แจ้งเตือน", 
                            html: '<span class="fw-bold text-danger">' + response.msg + '</span>', 
                            icon: "error", 
                            showConfirmButton: false, 
                            timer: 2000, 
                            timerProgressBar: true 
                        });
                    }
                },
                error: function (jqXHR, exception) { 
                    if (typeof ShowErrorAjax === 'function') {
                        ShowErrorAjax(jqXHR, exception); 
                    }
                }
            });
        });
    }
</script>
