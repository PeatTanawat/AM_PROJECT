<?php $breadcrumbs = [['label' => 'คอร์สเรียน', 'url' => 'course'], ['label' => 'เพิ่มคอร์สเรียน']]; ?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div id="GetFormAdd" class="px-2"></div>

    </div>

    <?php include "footer.php"; ?>
</div>

<?php include "script.php"; ?>

<script>
    $(document).ready(function () {
        LoadData();
    });

    // ดึงตัวเลือก หมวดหมู่ + ประเภท แล้วค่อย render ฟอร์ม
    function LoadData() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetFormAdd"); },
            type: "POST",
            url: "core.php",
            data: {
                request_state: "list_course",
                request_function: "get_select_category",
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    RenderForm(response.data);
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
                }
            },
            complete: function () { HideLoadingOverlay("#GetFormAdd"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function RenderForm(data) {
        const payload = {
            groups: data.groups || [],
            types: data.types || [],
        };

        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#GetFormAdd"); },
            type: "POST",
            url: "view/listCourse/FormAddCourse.php",
            data: JSON.stringify(payload),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (response) {
                // แทรกฟอร์ม — ตัวฟอร์ม (FormAddCourse.php) จะ init select2 + TinyMCE ของตัวเอง
                $("#GetFormAdd").html(response);
            },
            complete: function () { HideLoadingOverlay("#GetFormAdd"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }
</script>

</body>

</html>
