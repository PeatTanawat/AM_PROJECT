<?php $breadcrumbs = [['label' => 'รีวิวจากลูกค้า']]; ?>
<?php include "header.php"; ?>

<style>
    /* สีดาวคะแนน (เฉพาะหน้านี้) */
    .review-stars .material-symbols-outlined,
    .review-stars i { color: #f5a623; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">รีวิวจากลูกค้า</h2>
                    <button type="button" class="btn btn-primary" onclick="GetAddReview()"><span class="material-symbols-outlined align-middle" style="font-size:18px;" aria-hidden="true">add</span> เพิ่มรีวิว</button>
                </div>

                <div class="card-body p-4">   
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ชื่อผู้รีวิว / ข้อความรีวิว">
                        </div>
                        <div class="col-md-4">
                            <label for="f_status" class="form-label fw-medium">สถานะแสดงผล</label>
                            <select class="form-select" id="f_status">
                                <option value="">ทั้งหมด</option>
                                <option value="1">แสดงผล</option>
                                <option value="0">ซ่อนอยู่</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" style="display: inline-flex; align-items: center; justify-content: center; gap: 5px;" onclick="SearchData()">
                                <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 20px;">search</span> ค้นหา
                            </button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listReview/GetTable.php -->
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
                request_state: "list_review",
                request_function: "get_list_review",
                search: $("#f_search").val(),
                status: $("#f_status").val(),
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
            url: "view/listReview/GetTable.php",
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

    // compatibility: modal แก้ไข (GetModalEdit.php) เรียก LoadData() หลังบันทึก -> คงหน้าเดิม
    function LoadData() { GetData(currentPage); }

    // แก้ไขรีวิว -> โหลด modal พร้อมข้อมูลเดิม
    function GetEditReview(review_id) {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#myModal"); },
            type: "POST",
            url: "view/listReview/GetModalEdit.php",
            data: JSON.stringify({ review_id: review_id }),
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

    // ลบรีวิว (hard delete — tbl_reviews ไม่มี delete_at) -> ยืนยันก่อน แล้วเรียก handler
    function GetDeleteReview(review_id) {
        Swal.fire({
            title: "ยืนยันการลบ",
            html: '<span class="text-secondary">ต้องการลบรีวิวนี้ใช่หรือไม่? (ลบแล้วกู้คืนไม่ได้)</span>',
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
                    request_state: "list_review",
                    request_function: "delete_review",
                    review_id: review_id
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

    // เปิด modal เพิ่มรีวิว (โหลดฟอร์มจาก view/listReview/GetModalAdd.php)
    function GetAddReview() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#myModal"); },
            type: "POST",
            url: "view/listReview/GetModalAdd.php",
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
                $("#myModal").modal("show");
            },
            complete: function () { HideLoadingOverlay("#myModal"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
</script>
