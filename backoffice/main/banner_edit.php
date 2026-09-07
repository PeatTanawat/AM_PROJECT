<?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $banner_id = \App\Utility\Cipher::decrypt($key);
    $breadcrumbs = [
        ['label' => 'แบนเนอร์ทั้งหมด', 'url' => 'banner'],
        ['label' => 'แบนเนอร์ #' . ($banner_id !== '' ? $banner_id : '')],
    ];
?>
<?php include "header.php"; ?>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card form-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center p-4">
                    <h2 class="mb-0">แก้ไขแบนเนอร์</h2>
                    <div class="d-flex align-items-center gap-2">
                        <a href="banner" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับ
                        </a>
                        <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-1" onclick="DeleteBanner('<?php echo $banner_id; ?>');">
                            <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">delete</span> ลบแบนเนอร์
                        </button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form id="FormEditBanner" autocomplete="off" enctype="multipart/form-data">
                        <input type="hidden" name="banner_id" value="<?php echo $banner_id; ?>">

                        <h4 class="mb-3 fw-bold">ข้อมูลทั่วไป</h4>
                        <div class="row g-3 mb-2">
                            <div class="col-md-3">
                                <label class="form-label">ลำดับการแสดง <span class="text-danger">*</span>
                                    <small class="text-muted">(ต้องไม่ซ้ำกับแบนเนอร์อื่น)</small>
                                </label>
                                <input type="number" min="1" class="form-control" name="banner_order" placeholder="เช่น 1, 2, 3 ..." onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">URL ปลายทาง</label>
                                <input type="text" class="form-control" name="banner_url" placeholder="https://example.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="banner_image_input">รูปแบนเนอร์ใหม่ (ถ้าต้องการเปลี่ยน)
                                    <small class="text-muted">(ขนาด 2036 x 500 px เท่านั้น)</small>
                                </label>
                                <input type="file" class="form-control" name="banner_image" id="banner_image_input" accept="image/*">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">สถานะ <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="banner_status" id="status_on" value="1">
                                    <label class="form-check-label" for="status_on">เปิดใช้งาน</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="banner_status" id="status_off" value="0">
                                    <label class="form-check-label" for="status_off">ฉบับร่าง/ปิดใช้งาน</label>
                                </div>
                            </div>
                        </div>

                        <!-- Preview รูปแบนเนอร์ปัจจุบัน -->
                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <label class="form-label text-muted small">รูปแบนเนอร์ปัจจุบัน</label>
                                <div id="BannerPreviewWrap" class="border rounded-3 p-3 bg-light text-center" style="min-height: 160px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted" id="BannerPreviewEmpty">กำลังโหลด...</span>
                                    <img id="BannerPreviewImg" src="" alt="banner preview"
                                         style="max-width: 100%; max-height: 300px; display: none; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">ยืนยันการแก้ไขข้อมูล</button>
                            </div>
                        </div>

                    </form>
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
    var BANNER_ID = "<?php echo $banner_id; ?>";

    $(document).ready(function () {
        if (BANNER_ID) LoadBanner();
    });

    function LoadBanner() {
        $.ajax({
            beforeSend: function () { ShowLoadingOverlay("#FormEditBanner"); },
            type: "POST",
            url: "core.php",
            data: { request_state: "list_banner", request_function: "get_banner", banner_id: BANNER_ID },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    FillForm(response.data.banner);
                } else {
                    Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true });
                }
            },
            complete: function () { HideLoadingOverlay("#FormEditBanner"); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    function FillForm(b) {
        if (!b) return;
        var f = $("#FormEditBanner");
        f.find('[name="banner_order"]').val(b.banner_order || "");
        f.find('[name="banner_url"]').val(b.banner_url || "");
        f.find('[name="banner_status"][value="' + (String(b.banner_status) === "1" ? "1" : "0") + '"]').prop("checked", true);

        // แสดงรูปปัจจุบัน
        if (b.banner_image) {
            var imgSrc = /^https?:\/\//.test(b.banner_image) ? b.banner_image : "../" + b.banner_image;
            $("#BannerPreviewEmpty").hide();
            $("#BannerPreviewImg").attr("src", imgSrc).show();
        } else {
            $("#BannerPreviewEmpty").text("ไม่มีรูปแบนเนอร์");
        }
    }

    // Preview รูปใหม่ก่อนอัปโหลด
    $('#banner_image_input').on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#BannerPreviewEmpty').hide();
                $('#BannerPreviewImg').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).on('submit', '#FormEditBanner', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('request_state', 'list_banner');
        formData.append('request_function', 'update_banner');

        // ===== ตรวจช่องบังคับ (รูปไม่บังคับในโหมดแก้ไข) =====
        if (!ValidateRequired([
            { sel: '[name="banner_order"]', label: 'ลำดับการแสดง', type: 'number' }
        ])) { return; }
        if (Number($('[name="banner_order"]').val()) < 1) {
            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">ลำดับการแสดงต้องมากกว่า 0</span>', icon: "warning", showConfirmButton: false, timer: 2000 });
            return;
        }

        Swal.fire({
            title: "ยืนยันการบันทึกข้อมูล?",
            text: "คุณต้องการบันทึกการแก้ไขแบนเนอร์ใช่หรือไม่",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ยืนยัน",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    beforeSend: function () { ShowLoadingOverlay("#FormEditBanner"); },
                    type: "POST",
                    url: "core.php",
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function (response) {
                        if (response.result == 1) {
                            Swal.fire({
                                title: "สำเร็จ",
                                html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                                icon: "success",
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didClose: function () { window.location.href = "banner"; }
                            });
                        } else {
                            Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", confirmButtonText: "ตกลง" });
                        }
                    },
                    complete: function () { HideLoadingOverlay("#FormEditBanner"); },
                    error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
                });
            }
        });
    });

    function DeleteBanner(banner_id) {
        Swal.fire({
            title: "ลบแบนเนอร์?",
            html: '<span class="text-secondary">ระบบจะลบแบนเนอร์นี้ (soft delete)</span>',
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "ลบแบนเนอร์",
            cancelButtonText: "ยกเลิก",
            confirmButtonColor: "#dc3545"
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                type: "POST",
                url: "core.php",
                data: { request_state: "list_banner", request_function: "delete_banner", banner_id: banner_id },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({ title: "สำเร็จ", html: '<span class="fw-bold text-success">' + response.msg + '</span>', icon: "success", showConfirmButton: false, timer: 1500, timerProgressBar: true, didClose: function () { window.location.href = "banner"; } });
                    } else {
                        Swal.fire({ title: "แจ้งเตือน", html: '<span class="fw-bold text-danger">' + response.msg + '</span>', icon: "error", confirmButtonText: "ตกลง" });
                    }
                },
                error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
            });
        });
    }
</script>
