<?php $breadcrumbs = [['label' => 'ประวัติการแจ้งเตือนทั้งหมด']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">
        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center p-4">
                    <h2 class="mb-0">ประวัติการแจ้งเตือนทั้งหมด</h2>
                    <button class="btn btn-outline-primary" id="pageMarkAllReadBtn">ทำเครื่องหมายว่าอ่านแล้วทั้งหมด</button>
                </div>

                <div class="card-body p-4">
                    <!-- ตาราง + pagination render จาก view/notification/ViewData.php -->
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
        GetData(1);

        $('#pageMarkAllReadBtn').on('click', function() {
            markAsRead('all', null);
            setTimeout(function() {
                GetData(currentPage);
                if (typeof fetchNotifications === 'function') fetchNotifications();
            }, 500);
        });
    });

    function SearchData() { GetData(1); }

    function GetData(page) {
        page = page || 1;
        currentPage = page;
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#result_box"); },
            type: "POST", url: "core.php",
            data: {
                request_state: "list_notification",
                request_function: "get_list",
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

    function view_data(payload) {
        $.ajax({
            type: "POST", url: "view/notification/ViewData.php",
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

    // Function to click mark as read on individual item
    function markItemAsRead(notiId, element) {
        if (typeof markAsRead === 'function') {
            markAsRead(notiId, null);
            $(element).closest('tr').removeClass('table-light');
            $(element).closest('tr').find('.fw-bold').removeClass('fw-bold');
        }
    }
</script>
