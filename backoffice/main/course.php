<?php $breadcrumbs = [['label' => 'คอร์สเรียน']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">


        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">คอร์สเรียน</h2>

                    <div class="d-flex flex-wrap gap-2">
                        <?php /* ปิดปุ่ม "จัดการประเภท" ไว้ชั่วคราว — เปิดกลับได้โดยลบ comment นี้
              <a href="course_type.php" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                  <span class="material-symbols-outlined" style="font-size:18px;">category</span> จัดการประเภท
              </a>
              */ ?>
                        <a href="course_category.php"
                            class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size:18px;"
                                aria-hidden="true">folder</span> จัดการหมวดหมู่
                        </a>
                        <a href="course_fromadd.php" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size:18px;"
                                aria-hidden="true">add</span> เพิ่มคอร์สเรียน
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-4 col-lg-3">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ชื่อคอร์ส">
                        </div>
                        <div class="col-md-3 col-lg-3">
                            <label for="f_type" class="form-label fw-medium">ประเภท</label>
                            <select class="form-select" id="f_type">
                                <option value="">ทั้งหมด</option>
                                <option value="1">ทั่วไป</option>
                                <option value="2">เก็บชมเรียน</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-3">
                            <label for="f_category" class="form-label fw-medium">หมวดหมู่</label>
                            <select class="form-select" id="f_category">
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-lg-2">
                            <button type="button" class="btn btn-primary w-100" onclick="SearchData()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listCourse/GetTable.php -->
                    <div id="result_box"></div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>

    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var currentPage = 1;

    $(document).ready(function () {
        $('#f_search').on('keypress', function (e) { if (e.which === 13) { SearchData(); } });
        LoadFilterOptions();
    });

    function LoadFilterOptions() {
        $.ajax({
            type: "POST",
            url: "core.php",
            data: { request_state: "list_course", request_function: "get_select_category" },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    var catOpts = '<option value="">ทั้งหมด</option>';
                    (response.data.groups || []).forEach(function (g) {
                        catOpts += '<option value="' + g.group_id + '">' + g.group_name + '</option>';
                    });
                    $('#f_category').html(catOpts);
                }
                GetData(1);
            },
            error: function () { GetData(1); }
        });
    }

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
                request_state: "list_course",
                request_function: "get_list_course",
                search: $("#f_search").val(),
                f_type: $("#f_type").val() || '',
                f_category: $("#f_category").val() || '',
                page: page
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    view_data(response.data);
                } else {
                    $("#result_box").html('');
                    HideLoadingOverlay("#result_box");
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (response.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error" });
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
            url: "view/listCourse/GetTable.php",
            data: {
                data: payload.list,
                total: payload.total,
                page: payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) { $("#result_box").html(html); HideLoadingOverlay("#result_box"); },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // ปุ่มแก้ไข → ไปหน้าแก้ไขคอร์ส (4 แท็บ)
    function GetEditCourse(course_id) {
        window.location.href = "course_edit.php?key=" + course_id;
    }
</script>