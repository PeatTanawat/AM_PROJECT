<?php $breadcrumbs = [['label' => 'คูปองส่วนลด']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">คูปองส่วนลด</h2>
                    <a href="coupon_fromadd.php" style="display: inline-flex; align-items: center; gap: 5px;" class="btn btn-primary">
                        <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 20px;">add</span>
                        สร้างคูปองส่วนลดใหม่
                    </a>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6 col-lg-4">
                            <label for="f_search" class="form-label fw-medium">ค้นหา</label>
                            <input type="text" class="form-control" id="f_search" placeholder="ค้นหาจากโค้ด / รายละเอียดคูปอง">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="f_status" class="form-label fw-medium">สถานะ</label>
                            <select class="form-select" id="f_status" onchange="SearchData()">
                                <option value="">ทั้งหมด</option>
                                <option value="1">เปิดใช้งาน</option>
                                <option value="0">ปิดใช้งาน</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;" class="btn btn-primary w-100" onclick="SearchData()">
                            <span class="material-symbols-outlined" aria-hidden="true">search</span>
                            ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listCoupon/GetTable.php -->
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
                request_state: "list_coupon",
                request_function: "get_list_coupon",
                search: $("#f_search").val(),
                f_status: $("#f_status").val(),
                page: page
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    view_data(r.data);
                } else {
                    $("#result_box").html('');
                    HideLoadingOverlay("#result_box");
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถโหลดข้อมูลได้') + '</span>', icon: "error" });
                }
            },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // สเต็ป 2: ส่งข้อมูลไป render เป็น HTML แล้วแปะใน #result_box
    function view_data(payload) {
        $.ajax({
            type: "POST",
            url: "view/listCoupon/GetTable.php",
            data: {
                data:     payload.list,
                total:    payload.total,
                page:     payload.page,
                per_page: payload.per_page
            },
            dataType: "html",
            success: function (html) { $("#result_box").html(html); HideLoadingOverlay("#result_box"); },
            complete: function () { HideLoadingOverlay("#result_box"); },
            error: function (j, e) { ShowErrorAjax(j, e); }
        });
    }

    // ดูรายละเอียด/แก้ไข -> ไปหน้าแก้ไขคูปอง
    function GetEditCoupon(coupon_id) {
        window.location.href = "coupon_edit.php?key=" + coupon_id;
    }

    // สลับสถานะเปิด/ปิดใช้งานคูปอง (คลิกที่ป้ายสถานะในตาราง)
    function ToggleCouponStatus(coupon_id, newStatus) {
        var turnOn = String(newStatus) === '1';
        Swal.fire({
            title: turnOn ? 'เปิดใช้งานคูปองนี้?' : 'ปิดใช้งานคูปองนี้?',
            html: turnOn
                ? '<span class="text-secondary">ลูกค้าจะใช้คูปองนี้ได้</span>'
                : '<span class="text-secondary">ลูกค้าจะใช้คูปองนี้ไม่ได้</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: turnOn ? '#198754' : '#6c757d'
        }).then(function (res) {
            if (!res.isConfirmed) { return; }
            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "list_coupon",
                    request_function: "update_coupon_status",
                    coupon_id: coupon_id,
                    status: newStatus
                },
                dataType: "json",
                success: function (r) {
                    if (r.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + r.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true });
                        GetData(currentPage);
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + (r.msg || 'ไม่สามารถอัปเดตสถานะได้') + '</span>', icon: "error" });
                    }
                },
                error: function (j, e) { ShowErrorAjax(j, e); }
            });
        });
    }
</script>
