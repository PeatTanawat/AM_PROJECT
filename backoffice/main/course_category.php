<?php $breadcrumbs = [['label' => 'คอร์สเรียน', 'url' => 'course'], ['label' => 'หมวดหมู่ของคอร์สเรียน']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">หมวดหมู่ของคอร์สเรียน</h2>

                    <div class="d-flex gap-2">
                        <a href="course.php" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span>กลับไปคอร์สเรียน
                        </a>
                        <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="button" onclick="GetModalAdd()">
                            <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">add</span>เพิ่มหมวดหมู่ใหม่
                        </button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ค้นหาชื่อหมวดหมู่">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listCourseCategory/GetTable.php -->
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
        $('#f_search').on('keypress', function (e) { if (e.which === 13) { SearchData(); } });
        GetData(1);
    });

    function SearchData() { GetData(1); }

    // สเต็ป 1: ดึงข้อมูล (JSON) จาก handler
    function GetData(page) {
        page = page || 1;
        currentPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "listCourseCategory",
                request_function: "get_list_category",
                search: $("#f_search").val(),
                page: page
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    view_data(r.data);
                } else {
                    $("#result_box").html('');
                    HideLoadingOverlay("#result_box");
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
                }
            },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // สเต็ป 2: ส่งข้อมูลไป render เป็น HTML แล้วแปะใน #result_box
    function view_data(payload) {
        $.ajax({
            type: "POST",
            url: "view/listCourseCategory/GetTable.php",
            data: {
                data:     payload.list,
                total:    payload.total,
                page:     payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) { $("#result_box").html(html); HideLoadingOverlay("#result_box"); },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // compatibility: modal เพิ่ม/แก้ไข (GetModalAdd.php / GetModalEdit.php) เรียก LoadData() หลังบันทึก
    // เพิ่ม -> กลับหน้าแรก, แก้ไข -> คงหน้าเดิม
    function LoadData() {
        if (addMode) { addMode = false; GetData(1); }
        else { GetData(currentPage); }
    }

    function GetModalAdd() {
        addMode = true;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#myModal"); },
            type: "POST",
            url: "view/listCourseCategory/GetModalAdd.php",
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
                $("#myModal").modal("show");
            },
            complete: function () { HideLoadingOverlay("#myModal"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // แก้ไขหมวดหมู่ -> โหลด modal พร้อมข้อมูลเดิม
    function GetEditCategory(group_id) {
        addMode = false;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#myModal"); },
            type: "POST",
            url: "view/listCourseCategory/GetModalEdit.php",
            data: JSON.stringify({ group_id: group_id }),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
                $("#myModal").modal("show");
            },
            complete: function () { HideLoadingOverlay("#myModal"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // ลบหมวดหมู่ (soft delete) -> ยืนยันก่อน แล้วเรียก handler
    function GetDeleteCategory(group_id) {
        Swal.fire({
            title: "ยืนยันการลบ",
            html: '<span class="text-secondary">ต้องการลบหมวดหมู่นี้ใช่หรือไม่?</span>',
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
                    request_state: "listCourseCategory",
                    request_function: "delete_category",
                    group_id: group_id
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true }).then(() => { GetData(currentPage); });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
                    }
                },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }
</script>
