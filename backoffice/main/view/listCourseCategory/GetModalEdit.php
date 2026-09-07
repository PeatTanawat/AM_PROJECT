<?php

    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

    use App\Database\Connection;

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    $group_id = isset($data['group_id']) ? (int) $data['group_id'] : 0;

    // ดึงชื่อหมวดหมู่ปัจจุบันมาแสดงในฟอร์ม
    $group_name = '';
    try {
        $pdo = (new Connection())->getPdo();
        if ($pdo && $group_id > 0) {
            $stmt = $pdo->prepare("SELECT group_name FROM tbl_course_group WHERE group_id = :id AND delete_at IS NULL");
            $stmt->execute([':id' => $group_id]);
            $group_name = (string) ($stmt->fetchColumn() ?: '');
            $stmt->closeCursor();
        }
    } catch (\Throwable $e) {
        $group_name = '';
    }
?>
    <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">แก้ไขหมวดหมู่คอร์สเรียน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
        <div class="modal-body p-4">
            <form id="formEditCategory" method="POST">
                <input type="hidden" id="edit_group_id" name="group_id" value="<?php echo htmlspecialchars((string) $group_id); ?>">
                <div class="mb-3">
                    <label for="edit_group_name" class="form-label fw-semibold">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit_group_name" name="group_name" value="<?php echo htmlspecialchars($group_name); ?>">
                </div>
            </form>
        </div>
    <div class="modal-footer p-3">
        <button type="button" class="btn btn-primary px-4" style="width:100%" onclick="UpdateCategory()">
            บันทึกข้อมูล
        </button>
    </div>

<script>
    function UpdateCategory(){

        let group_name = $('#edit_group_name').val();

        if(group_name == ""){
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณากรอกชื่อหมวดหมู่</span>',
                icon: "error",
                showConfirmButton: false,
                allowOutsideClick: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        var formData = new FormData($('#formEditCategory')[0]);
        formData.append('request_state', 'listCourseCategory');
        formData.append('request_function', 'update_category');

        $.ajax({
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if(response.result == 1){
                    $("#myModal").modal('hide');
                    Swal.fire({
                        title: "สำเร็จ",
                        html: '<span class="fw-bold text-success">'+response.msg+'</span>',
                        icon: "success",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000,
                        timerProgressBar: true,
                    }).then((result) => {
                        LoadData();
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">'+response.msg+'</span>',
                        icon: "error",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            },
            error: function (jqXHR, exception) {
                ShowErrorAjax(jqXHR, exception);
            }
        });
    }
</script>
