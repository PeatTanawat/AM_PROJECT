<?php $breadcrumbs = [['label' => 'คอร์สเรียน', 'url' => 'course'], ['label' => 'ประเภทคอร์สเรียน']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">ประเภทคอร์สเรียน</h2>

                    <div class="d-flex gap-2">
                        <a href="course.php" class="btn btn-outline-secondary">กลับไปคอร์สเรียน</a>
                        <button class="btn btn-primary" type="button" onclick="GetModalAdd()">เพิ่มประเภทใหม่</button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ค้นหาชื่อประเภท">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listCourseType/GetTable.php -->
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
                request_state: "listCourseType",
                request_function: "get_list_type",
                search: $("#f_search").val(),
                page: page
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    view_data(response.data);
                } else {
                    $("#result_box").html('');
                    HideLoadingOverlay("#result_box");
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (response.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
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
            url: "view/listCourseType/GetTable.php",
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

    // alias สำหรับ modal เพิ่มประเภท (GetModalAdd.php) ที่เรียก LoadData() หลังบันทึก -> กลับไปหน้า 1
    function LoadData() { GetData(1); }

    function GetModalAdd() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#myModal"); },
            type: "POST",
            url: "view/listCourseType/GetModalAdd.php",
            dataType: "html",
            success: function (response) {
                $("#showModal").html(response);
                $("#myModal").modal("show");
            },
            complete: function () { HideLoadingOverlay("#myModal"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // แก้ไข/ลบ ประเภท — ยังไม่อยู่ในขอบเขตงานนี้ (เหมือนหน้าหมวดหมู่)
    function GetEditType(type_id) {
        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-secondary">การแก้ไขประเภทอยู่ระหว่างพัฒนา</span>', icon: "info", showConfirmButton: true });
    }
    function GetDeleteType(type_id) {
        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-secondary">การลบประเภทอยู่ระหว่างพัฒนา</span>', icon: "info", showConfirmButton: true });
    }
</script>
