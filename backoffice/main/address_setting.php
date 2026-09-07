<?php $breadcrumbs = [['label' => 'ตั้งค่าระบบ', 'url' => 'website_setting'], ['label' => 'จัดการตั้งค่าที่อยู่']]; ?>
<?php include "header.php"; ?>

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

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    .breadcrumb-item+.breadcrumb-item::before {
        content: "›";
        font-size: 1.1rem;
        vertical-align: middle;
    }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">จัดการตั้งค่าที่อยู่</h2>
                    <button type="button"
                        class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2"
                        onclick="GetAddModal()">
                        <span class="material-symbols-outlined" style="font-size: 20px;">add</span>
                        <span id="btn_add_title">เพิ่มจังหวัด</span>
                    </button>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6 col-lg-3">
                            <label for="f_province" class="form-label fw-medium">จังหวัด</label>
                            <select class="form-select select2" id="f_province">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="f_amphure" class="form-label fw-medium">อำเภอ</label>
                            <select class="form-select select2" id="f_amphure">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="f_tambon" class="form-label fw-medium">ตำบล</label>
                            <select class="form-select select2" id="f_tambon">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="f_zipcode" class="form-label fw-medium">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" id="f_zipcode" placeholder="เช่น 12170">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <button type="button"
                                style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;"
                                class="btn btn-primary w-100" onclick="SearchData()">
                                <span class="material-symbols-outlined" aria-hidden="true">search</span>
                                ค้นหา
                            </button>
                        </div>
                    </div>

                    <!-- Breadcrumb Navigation แสดงลำดับชั้น -->
                    <div id="breadcrumb_box"></div>

                    <!-- ตาราง render จาก view/listAddressSetting/ GetProvinceTable / GetDistrictTable / GetSubDistrictTable -->
                    <div id="result_box"></div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<!-- Modal Container กลางสำหรับ Add/Edit -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="modal_content"></div>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var currentLevel = 'province'; // 'province' | 'district' | 'sub_district'
    var selectedProvinceId = null;
    var selectedProvinceName = '';
    var selectedDistrictId = null;
    var selectedDistrictName = '';
    var selectedSubDistrictId = null;
    var selectedSubDistrictName = '';

    $(document).ready(function () {
        $('#f_zipcode').on('keypress', function (e) {
            if (e.which === 13) {
                SearchData();
            }
        });

        if ($.fn.select2) {
            $('#f_province, #f_amphure, #f_tambon').select2({
                allowClear: false,
                width: '100%'
            });
        }

        // เมื่อเปลี่ยนจังหวัดใน Dropdown -> โหลดตัวเลือกอำเภอเตรียมไว้ใน Dropdown ทันที (ยังไม่ค้นหาตารางจนกว่าจะกดค้นหา)
        $('#f_province').on('change', function () {
            var provId = $(this).val();
            selectedDistrictId = null;
            selectedDistrictName = '';
            selectedSubDistrictId = null;
            selectedSubDistrictName = '';
            $('#f_tambon').html('<option value="">ทั้งหมด</option>');
            if ($.fn.select2) {
                $('#f_tambon').trigger('change.select2');
            }

            if (provId) {
                LoadDistrictOptions(provId);
            } else {
                $('#f_amphure').html('<option value="">ทั้งหมด</option>');
                if ($.fn.select2) {
                    $('#f_amphure').trigger('change.select2');
                }
            }
        });

        // เมื่อเปลี่ยนอำเภอใน Dropdown -> โหลดตัวเลือกตำบลเตรียมไว้ใน Dropdown ทันที (ยังไม่ค้นหาตารางจนกว่าจะกดค้นหา)
        $('#f_amphure').on('change', function () {
            var ampId = $(this).val();
            selectedSubDistrictId = null;
            selectedSubDistrictName = '';

            if (ampId) {
                LoadSubDistrictOptions(ampId);
            } else {
                $('#f_tambon').html('<option value="">ทั้งหมด</option>');
                if ($.fn.select2) {
                    $('#f_tambon').trigger('change.select2');
                }
            }
        });

        LoadProvinceFilterOptions();
        LoadProvinces();
    });

    // กดปุ่มค้นหา -> ประมวลผลตามเงื่อนไขที่เลือกใน Filter
    function SearchData() {
        var provId = $('#f_province').val();
        var provName = $('#f_province option:selected').val() ? $('#f_province option:selected').text() : '';
        var ampId = $('#f_amphure').val();
        var ampName = $('#f_amphure option:selected').val() ? $('#f_amphure option:selected').text() : '';
        var tamId = $('#f_tambon').val();
        var tamName = $('#f_tambon option:selected').val() ? $('#f_tambon option:selected').text() : '';

        if (tamId && tamId !== '') {
            currentLevel = 'sub_district';
            selectedProvinceId = provId;
            selectedProvinceName = provName;
            selectedDistrictId = ampId;
            selectedDistrictName = ampName;
            selectedSubDistrictId = tamId;
            selectedSubDistrictName = tamName;
            RenderBreadcrumb();
            FetchSubDistricts(ampId, ampName);
        } else if (ampId && ampId !== '') {
            currentLevel = 'district';
            selectedProvinceId = provId;
            selectedProvinceName = provName;
            selectedDistrictId = ampId;
            selectedDistrictName = ampName;
            selectedSubDistrictId = null;
            selectedSubDistrictName = '';
            RenderBreadcrumb();
            FetchDistricts(provId, provName);
        } else {
            currentLevel = 'province';
            selectedProvinceId = provId || null;
            selectedProvinceName = provName || '';
            selectedDistrictId = null;
            selectedDistrictName = '';
            selectedSubDistrictId = null;
            selectedSubDistrictName = '';
            RenderBreadcrumb();
            FetchProvinces();
        }
    }

    function SearchZipcode(zip) {
        if (!zip) return;
        $('#f_zipcode').val(zip);
        SearchData();
    }

    // โหลดรายชื่อจังหวัดสำหรับ Dropdown Filter
    function LoadProvinceFilterOptions() {
        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_address_setting",
                request_function: "get_list_province",
                limit: 0
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    var html = '<option value="">ทั้งหมด</option>';
                    $.each(res.data.rows, function (i, item) {
                        html += `<option value="${item.id}">${escapeHtml(item.name_th)}</option>`;
                    });
                    $('#f_province').html(html);
                    if ($.fn.select2) {
                        $('#f_province').val(selectedProvinceId || '').trigger('change.select2');
                    }
                }
            }
        });
    }

    // โหลดรายชื่ออำเภอในจังหวัดใส่ Dropdown Filter
    function LoadDistrictOptions(province_id, callback) {
        $('#f_amphure').html('<option value="">ทั้งหมด</option>');
        if ($.fn.select2) {
            $('#f_amphure').trigger('change.select2');
        }

        if (province_id) {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_address_setting",
                    request_function: "get_list_district",
                    province_id: province_id,
                    limit: 0
                },
                dataType: "json",
                success: function (res) {
                    if (res.result == 1) {
                        var html = '<option value="">ทั้งหมด</option>';
                        $.each(res.data.rows, function (i, item) {
                            html += `<option value="${item.id}">${escapeHtml(item.name_th)}</option>`;
                        });
                        $('#f_amphure').html(html);
                        if (selectedDistrictId) {
                            $('#f_amphure').val(selectedDistrictId);
                        }
                        if ($.fn.select2) {
                            $('#f_amphure').trigger('change.select2');
                        }
                        if (typeof callback === 'function') callback();
                    }
                }
            });
        }
    }

    // โหลดรายชื่อตำบลในอำเภอใส่ Dropdown Filter
    function LoadSubDistrictOptions(amphure_id, callback) {
        $('#f_tambon').html('<option value="">ทั้งหมด</option>');
        if ($.fn.select2) {
            $('#f_tambon').trigger('change.select2');
        }

        if (amphure_id) {
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_address_setting",
                    request_function: "get_list_sub_district",
                    amphure_id: amphure_id,
                    limit: 0
                },
                dataType: "json",
                success: function (res) {
                    if (res.result == 1) {
                        var html = '<option value="">ทั้งหมด</option>';
                        $.each(res.data.rows, function (i, item) {
                            html += `<option value="${item.id}">${escapeHtml(item.name_th)}</option>`;
                        });
                        $('#f_tambon').html(html);
                        if (selectedSubDistrictId) {
                            $('#f_tambon').val(selectedSubDistrictId);
                        }
                        if ($.fn.select2) {
                            $('#f_tambon').trigger('change.select2');
                        }
                        if (typeof callback === 'function') callback();
                    }
                }
            });
        }
    }

    function GetData(page) {
        page = page || 1;
        currentPage = page;
        if (currentLevel === 'province') {
            FetchProvinces(page);
        } else if (currentLevel === 'district') {
            FetchDistricts(selectedProvinceId, selectedProvinceName, page);
        } else if (currentLevel === 'sub_district') {
            FetchSubDistricts(selectedDistrictId, selectedDistrictName, page);
        }
    }

    // -------------------------------------------------------------
    // ระดับ 1: จังหวัด
    // -------------------------------------------------------------
    function LoadProvinces() {
        currentLevel = 'province';
        selectedProvinceId = null;
        selectedProvinceName = '';
        selectedDistrictId = null;
        selectedDistrictName = '';
        selectedSubDistrictId = null;
        selectedSubDistrictName = '';

        $('#f_amphure').html('<option value="">ทั้งหมด</option>');
        $('#f_tambon').html('<option value="">ทั้งหมด</option>');
        if ($.fn.select2) {
            $('#f_province').val('').trigger('change.select2');
            $('#f_amphure, #f_tambon').trigger('change.select2');
        } else {
            $('#f_province').val('');
        }

        RenderBreadcrumb();
        FetchProvinces(1);
    }

    function FetchProvinces(page) {
        page = page || 1;
        currentPage = page;
        var search = $('#f_zipcode').val();
        var provId = $('#f_province').val();
        $.ajax({
            beforeSend: function () {
                if (typeof ShowLoadingOverlay === 'function') ShowLoadingOverlay("#result_box");
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_address_setting",
                request_function: "get_list_province",
                province_id: provId,
                search: search,
                page: page,
                limit: 25
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    RenderProvinceTable(res.data);
                } else {
                    $("#result_box").html('<div class="alert alert-danger mb-0">' + (res.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</div>');
                }
            },
            complete: function () {
                if (typeof HideLoadingOverlay === 'function') HideLoadingOverlay("#result_box");
            },
            error: function (jqXHR, exception) {
                if (typeof ShowErrorAjax === 'function') ShowErrorAjax(jqXHR, exception);
            }
        });
    }

    function RenderProvinceTable(payload) {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetProvinceTable.php",
            data: {
                data: payload.rows,
                total: payload.total,
                page: payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) {
                $("#result_box").html(html);
            }
        });
    }

    // -------------------------------------------------------------
    // ระดับ 2: อำเภอ
    // -------------------------------------------------------------
    function SelectProvince(province_id, province_name) {
        currentLevel = 'district';
        selectedProvinceId = province_id;
        selectedProvinceName = province_name;
        selectedDistrictId = null;
        selectedDistrictName = '';
        selectedSubDistrictId = null;
        selectedSubDistrictName = '';

        if ($.fn.select2) {
            $('#f_province').val(province_id).trigger('change.select2');
        } else {
            $('#f_province').val(province_id);
        }

        LoadDistrictOptions(province_id);
        RenderBreadcrumb();
        FetchDistricts(province_id, province_name, 1);
    }

    function FetchDistricts(province_id, province_name, page) {
        page = page || 1;
        currentPage = page;
        var search = $('#f_zipcode').val();
        var districtId = $('#f_amphure').val();
        $.ajax({
            beforeSend: function () {
                if (typeof ShowLoadingOverlay === 'function') ShowLoadingOverlay("#result_box");
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_address_setting",
                request_function: "get_list_district",
                province_id: province_id,
                district_id: districtId,
                search: search,
                page: page,
                limit: 25
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    RenderDistrictTable(res.data);
                } else {
                    $("#result_box").html('<div class="alert alert-danger mb-0">' + (res.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</div>');
                }
            },
            complete: function () {
                if (typeof HideLoadingOverlay === 'function') HideLoadingOverlay("#result_box");
            },
            error: function (jqXHR, exception) {
                if (typeof ShowErrorAjax === 'function') ShowErrorAjax(jqXHR, exception);
            }
        });
    }

    function RenderDistrictTable(payload) {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetDistrictTable.php",
            data: {
                data: payload.rows,
                total: payload.total,
                page: payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) {
                $("#result_box").html(html);
            }
        });
    }

    // -------------------------------------------------------------
    // ระดับ 3: ตำบล
    // -------------------------------------------------------------
    function SelectDistrict(amphure_id, amphure_name) {
        currentLevel = 'sub_district';
        selectedDistrictId = amphure_id;
        selectedDistrictName = amphure_name;

        if ($.fn.select2) {
            $('#f_amphure').val(amphure_id).trigger('change.select2');
        } else {
            $('#f_amphure').val(amphure_id);
        }

        RenderBreadcrumb();
        FetchSubDistricts(amphure_id, amphure_name, 1);
    }

    function FetchSubDistricts(amphure_id, amphure_name, page) {
        page = page || 1;
        currentPage = page;
        var search = $('#f_zipcode').val();
        var subDistrictId = $('#f_tambon').val();
        $.ajax({
            beforeSend: function () {
                if (typeof ShowLoadingOverlay === 'function') ShowLoadingOverlay("#result_box");
            },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_address_setting",
                request_function: "get_list_sub_district",
                amphure_id: amphure_id,
                sub_district_id: subDistrictId,
                search: search,
                page: page,
                limit: 25
            },
            dataType: "json",
            success: function (res) {
                if (res.result == 1) {
                    RenderSubDistrictTable(res.data);
                } else {
                    $("#result_box").html('<div class="alert alert-danger mb-0">' + (res.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</div>');
                }
            },
            complete: function () {
                if (typeof HideLoadingOverlay === 'function') HideLoadingOverlay("#result_box");
            },
            error: function (jqXHR, exception) {
                if (typeof ShowErrorAjax === 'function') ShowErrorAjax(jqXHR, exception);
            }
        });
    }

    function RenderSubDistrictTable(payload) {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetSubDistrictTable.php",
            data: {
                data: payload.rows,
                total: payload.total,
                page: payload.page,
                per_page: payload.per_page,
                province_name: selectedProvinceName,
                district_name: selectedDistrictName
            },
            dataType: "html",
            success: function (html) {
                $("#result_box").html(html);
            }
        });
    }

    // -------------------------------------------------------------
    // Breadcrumb Navigation & Dynamic Button Title
    // -------------------------------------------------------------
    function RenderBreadcrumb() {
        var html = '<nav aria-label="breadcrumb" class="mb-3">';
        html += '<ol class="breadcrumb mb-0 fw-medium fs-15">';

        if (currentLevel === 'province') {
            html += '<li class="breadcrumb-item text-dark active" aria-current="page">รายชื่อจังหวัดทั้งหมด</li>';
        } else if (currentLevel === 'district') {
            html += '<li class="breadcrumb-item"><a href="javascript:void(0)" onclick="LoadProvinces()" class="text-decoration-none">จังหวัดทั้งหมด</a></li>';
            html += '<li class="breadcrumb-item text-dark active" aria-current="page">จังหวัด' + escapeHtml(selectedProvinceName) + '</li>';
        } else if (currentLevel === 'sub_district') {
            html += '<li class="breadcrumb-item"><a href="javascript:void(0)" onclick="LoadProvinces()" class="text-decoration-none">จังหวัดทั้งหมด</a></li>';
            html += '<li class="breadcrumb-item"><a href="javascript:void(0)" onclick="SelectProvince(' + selectedProvinceId + ', \'' + escapeHtml(selectedProvinceName) + '\')" class="text-decoration-none">จังหวัด' + escapeHtml(selectedProvinceName) + '</a></li>';
            html += '<li class="breadcrumb-item text-dark active" aria-current="page">อำเภอ' + escapeHtml(selectedDistrictName) + '</li>';
        }

        html += '</ol></nav>';
        $('#breadcrumb_box').html(html);

        if (currentLevel === 'province') {
            $('#btn_add_title').text('เพิ่มจังหวัด');
        } else if (currentLevel === 'district') {
            $('#btn_add_title').text('เพิ่มอำเภอ');
        } else if (currentLevel === 'sub_district') {
            $('#btn_add_title').text('เพิ่มตำบล');
        }
    }

    // -------------------------------------------------------------
    // Dynamic Modal Handlers (Add / Edit / Delete)
    // -------------------------------------------------------------
    function GetAddModal() {
        if (currentLevel === 'province') {
            GetAddProvince();
        } else if (currentLevel === 'district') {
            GetAddDistrict();
        } else if (currentLevel === 'sub_district') {
            GetAddSubDistrict();
        }
    }

    // --- จังหวัด ---
    function GetAddProvince() {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetModalAddProvince.php",
            dataType: "html",
            success: function (html) {
                $('#modal_content').html(html);
                $('#myModal').modal('show');
            }
        });
    }

    function GetEditProvince(id, name_th, name_en) {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetModalEditProvince.php",
            data: { id: id, name_th: name_th, name_en: name_en },
            dataType: "html",
            success: function (html) {
                $('#modal_content').html(html);
                $('#myModal').modal('show');
            }
        });
    }

    function DeleteProvince(id, name_th) {
        Swal.fire({
            title: "ยืนยันการลบ?",
            html: 'คุณต้องการลบจังหวัด <span class="fw-bold text-danger">' + escapeHtml(name_th) + '</span> หรือไม่?',
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "ยืนยันการลบ",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_address_setting",
                        request_function: "delete_province",
                        id: id
                    },
                    dataType: "json",
                    success: function (res) {
                        if (res.result == 1) {
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
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error" });
                        }
                    }
                });
            }
        });
    }

    // --- อำเภอ ---
    function GetAddDistrict() {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetModalAddDistrict.php",
            data: { province_id: selectedProvinceId, province_name: selectedProvinceName },
            dataType: "html",
            success: function (html) {
                $('#modal_content').html(html);
                $('#myModal').modal('show');
            }
        });
    }

    function GetEditDistrict(id, province_id, name_th, name_en) {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetModalEditDistrict.php",
            data: { id: id, province_id: province_id, name_th: name_th, name_en: name_en },
            dataType: "html",
            success: function (html) {
                $('#modal_content').html(html);
                $('#myModal').modal('show');
            }
        });
    }

    function DeleteDistrict(id, name_th) {
        Swal.fire({
            title: "ยืนยันการลบ?",
            html: 'คุณต้องการลบอำเภอ <span class="fw-bold text-danger">' + escapeHtml(name_th) + '</span> หรือไม่?',
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "ยืนยันการลบ",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_address_setting",
                        request_function: "delete_district",
                        id: id
                    },
                    dataType: "json",
                    success: function (res) {
                        if (res.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + res.msg + '</span>',
                                icon: "success",
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                if (selectedProvinceId) LoadDistrictOptions(selectedProvinceId);
                                GetData(currentPage);
                            });
                        } else {
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error" });
                        }
                    }
                });
            }
        });
    }

    // --- ตำบล ---
    function GetAddSubDistrict() {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetModalAddSubDistrict.php",
            data: { amphure_id: selectedDistrictId, district_name: selectedDistrictName },
            dataType: "html",
            success: function (html) {
                $('#modal_content').html(html);
                $('#myModal').modal('show');
            }
        });
    }

    function GetEditSubDistrict(id, amphure_id, name_th, name_en, zip_code) {
        $.ajax({
            type: "POST",
            url: "view/listAddressSetting/GetModalEditSubDistrict.php",
            data: { id: id, amphure_id: amphure_id, name_th: name_th, name_en: name_en, zip_code: zip_code },
            dataType: "html",
            success: function (html) {
                $('#modal_content').html(html);
                $('#myModal').modal('show');
            }
        });
    }

    function DeleteSubDistrict(id, name_th) {
        Swal.fire({
            title: "ยืนยันการลบ?",
            html: 'คุณต้องการลบตำบล <span class="fw-bold text-danger">' + escapeHtml(name_th) + '</span> หรือไม่?',
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "ยืนยันการลบ",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "core.php",
                    data: {
                        request_state: "list_address_setting",
                        request_function: "delete_sub_district",
                        id: id
                    },
                    dataType: "json",
                    success: function (res) {
                        if (res.result == 1) {
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
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + res.msg + '</span>', icon: "error" });
                        }
                    }
                });
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return "";
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>