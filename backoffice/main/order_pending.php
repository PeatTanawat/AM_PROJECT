<?php $breadcrumbs = [['label' => 'คำสั่งซื้อรอยืนยัน']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">
        <?php include "navbar.php"; ?>
        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h4 class="mb-0">คำสั่งซื้อรอยืนยัน</h4>
                </div>

                <div class="card-body p-4">
                    <!-- ===== FILTER ROW ===== -->
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6 col-lg">
                            <label class="form-label fw-medium" for="f_order">หมายเลขคำสั่งซื้อ</label>
                            <input type="text" class="form-control" id="f_order" placeholder="เช่น ORD-..." autocomplete="off">
                        </div>
                        <div class="col-md-6 col-lg">
                            <label class="form-label fw-medium" for="f_customer">ลูกค้า</label>
                            <input type="text" class="form-control" id="f_customer" placeholder="ชื่อลูกค้า" autocomplete="off">
                        </div>
                        <div class="col-md-6 col-lg">
                            <label class="form-label fw-medium" for="f_date">วันที่สั่งซื้อ</label>
                            <input type="text" class="form-control" id="f_date" placeholder="วัน/เดือน/ปี" autocomplete="off">
                        </div>
                        <div class="col-md-6 col-lg-auto">
                            <button type="button" class="btn btn-primary w-100 px-4" onclick="SearchOrder()">ค้นหา</button>
                        </div>
                    </div>

                    <!-- ตาราง + pagination render จาก view/listOrder/ViewPending.php -->
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
        if (typeof flatpickr !== "undefined") {
            flatpickr("#f_date", { dateFormat: "d/m/Y", allowInput: true });
        }
        $('#f_order, #f_customer').on('keypress', function (e) { if (e.which === 13) { GetData(1); } });
        GetData(1);
    });

    function SearchOrder() { GetData(1); }

    // สเต็ป 1: ดึงข้อมูล (JSON) จาก handler
    function GetData(page) {
        page = page || 1;
        currentPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_order",
                request_function: "get_list_pending",
                f_order: $("#f_order").val(),
                f_customer: $("#f_customer").val(),
                f_date: $("#f_date").val(),
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
            type: "POST", url: "view/listOrder/ViewPending.php",
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


</script>
