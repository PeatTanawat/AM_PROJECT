<?php

    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

    $raw = file_get_contents("php://input");

    $data = json_decode($raw, true);

?>
    <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">สร้างหมวดหมู่คอร์สเรียนใหม่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
        <div class="modal-body p-4">
            <form id="formAddCategory" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="group_name" class="form-label fw-semibold">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="group_name" name="group_name" >
                </div>
            </form>
        </div>
    <div class="modal-footer p-3">
        <button type="button" class="btn btn-primary px-4" style="width:100%" onclick="AddCategory()">
            บันทึกข้อมูล
        </button>
    </div>

<script>
    function AddCategory(){

         let group_name = $('#group_name').val();

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

        var formData = new FormData($('#formAddCategory')[0]);

        formData.append('request_state', 'listCourseCategory');

        formData.append('request_function', 'add_category');

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

                        }else {

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

             complete: function() {

                        HideLoadingButton(".BtnSubmitForm");

                    },

            error: function (jqXHR, exception) {

                ShowErrorAjax(jqXHR, exception);

            }

        });
    }
</script>
