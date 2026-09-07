<!DOCTYPE html>
<?php
include('config/main_function.php');
$secure = "s?9>9{RW{!Etop/";
$connection = connectDB($secure);

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Links Of CSS File -->
    <link rel="stylesheet" href="template/assets/css/sidebar-menu.css">
    <link rel="stylesheet" href="template/assets/css/simplebar.css">
    <link rel="stylesheet" href="template/assets/css/apexcharts.css">
    <link rel="stylesheet" href="template/assets/css/prism.css">
    <link rel="stylesheet" href="template/assets/css/rangeslider.css">
    <link rel="stylesheet" href="template/assets/css/quill.snow.css">
    <link rel="stylesheet" href="template/assets/css/google-icon.css">
    <link rel="stylesheet" href="template/assets/css/remixicon.css">
    <link rel="stylesheet" href="template/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="template/assets/css/fullcalendar.main.css">
    <link rel="stylesheet" href="template/assets/css/jsvectormap.min.css">
    <link rel="stylesheet" href="template/assets/css/lightpick.css">
    <link rel="stylesheet" href="template/assets/css/select2.min.css">
    <!-- <link rel="stylesheet" href="template/assets/css/datatables.min.css"> -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap4.css" />
    <link rel="stylesheet" href="template/assets/css/datepicker3.css">
    <link rel="stylesheet" href="template/assets/css/style.css">
    <link rel="stylesheet" href="template/assets/css/toastr.min.css">
    <link rel="stylesheet" href="template/assets/css/sweetalert2.min.css">
    <title>VHV Report</title>
</head>

<style>
    html,
    body {
        font-family: 'Prompt', sans-serif !important;
    }

    body {
        background-color: #ccc;
    }

    .content {
        max-width: 500px;
        width: 100%;
        margin: 0 auto;
        background-color: white;
        min-height: 100vh;
        position: relative;
        padding-bottom: 20px;
    }

    .fixed-bottom {
        position: fixed;
        width: 100%;
        margin: 0 auto;
        bottom: 0;
        left: 0;
    }

    .bottom-content {
        max-width: 500px;
        width: 100%;
        margin: 0 auto;
        background-color: white;
        height: 50px;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #DEE2E2 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px !important;
    }

    .modal-xl {
        max-width: 1140px;
    }
</style>

<body>
    <div class="content pt-md-6 pt-3 px-3">
        <div class="row border-bottom white-bg">
            <nav class="navbar navbar-expand-lg navbar-static-top" role="navigation">
                <ul class="nav navbar-top-links navbar-right">
                </ul>
            </nav>
        </div>
        <div class="container">
            <div class="main-content d-flex flex-column p-0">
                <div class="m-auto m-1230">
                    <div class="row align-items-center">
                        <div class="col-lg-12">
                            <div class="mw-480 ms-lg-auto">
                                <div class="mb-4" style="text-align: center;">
                                    <img src="Uploads/logo_vhv.png" class="rounded-3 for-light-logo mx-auto d-block "
                                        alt="login" style="max-width: 40%;"> <!---- Logo  ---->
                                </div>
                                <div id="registerForm" style="display: none;">
                                    <div class="mb-4" style="text-align: center;">
                                        <p>กรุณาลงทะเบียนด้วยชื่อของคุณ</p>
                                    </div>
                                    <form>
                                        <div class="form-group mb-4">
                                            <input type="text" id="fullname" class="form-control h-55"
                                                placeholder="ชื่อ">
                                        </div>

                                        <div class="form-group mb-4">
                                            <button type="button" class="btn btn-primary fw-medium py-2 px-3 w-100"
                                                onclick="CheckLogin()">
                                                <div class="d-flex align-items-center justify-content-center py-1">
                                                    <i class="material-symbols-outlined text-white fs-20 me-2">login</i>
                                                    <span>ลงทะเบียน</span>
                                                </div>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div id="successCard" class="card mb-3 mt-4"
                                    style="display:none; text-align:center; border: 2px solid #28a745; border-radius: 10px; background-color: #f8fff9;">
                                    <div class="card-body">
                                        <h5 class="card-title"
                                            style="color: #28a745; font-weight: bold; font-size: 1.2rem;">
                                            ลงทะเบียนสำเร็จ</h5>
                                        <p class="card-text" style="color: #28a745; font-size: 1rem;">
                                            สามารถพิมพ์ข้อความเพื่อแจ้งปัญหาได้เลยค่ะ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="footer-text text-center mt-3">
            <p>พบปัญหาการใช้งาน กรุณาติดต่อ xxxxxxxxxxx</p>
        </div> -->
    </div>


    <script src="template/assets/js/jquery-3.1.1.min.js"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <!--===============================================================================================-->
    <script src="template/assets/js/bootstrap.bundle.min.js"></script>
    <!--===============================================================================================-->
    <script src="template/assets/js/select2.full.min.js"></script>
    <!--===============================================================================================-->
    <script src="template/assets/js/moment.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap4.js"></script>
    <script src="template/assets/js/bootstrap-datepicker.js"></script>
    <script src="template/assets/js/toastr.min.js"></script>
    <!--============================================ Sweet alert ===================================================-->
    <script src="template/assets/js/sweetalert2.min.js"></script>


    <script>
        // Initialize LIFF to allow closing the window later
        liff.init({
            liffId: "2010339527-Sw2SZGxy"
        }, () => {
            console.log("LIFF initialized successfully");
            if (liff.isLoggedIn()) {
                const context = liff.getContext();
                let userId = null;
                if (context && context.userId) {
                    userId = context.userId;
                    checkExistingLogin(userId);
                } else {
                    liff.getProfile().then(profile => {
                        checkExistingLogin(profile.userId);
                    }).catch(err => {
                        console.error("getProfile failed:", err);
                        $('#registerForm').show();
                    });
                }
            } else {
                liff.login();
            }
        }, err => {
            console.error("LIFF initialization failed", err);
            $('#registerForm').show();
        });

        function checkExistingLogin(userId) {
            localStorage.setItem('line_token', userId);
            $.post("webhook/CheckLogin.php", {
                "userId": userId
            }, function (data) {
                if (data.result == 1) {
                    // พบ line_token ในระบบ
                    $('#registerForm').hide();
                    $('#successCard').show();
                } else {
                    // ไม่พบ line_token
                    $('#registerForm').show();
                    $('#successCard').hide();
                }
            }, "json");
        }

        function CheckLogin() {

            let fullname = $('#fullname').val();
            let line_token = localStorage.getItem('line_token');
            let xapikey = "kE3XdfehWW";

            if (fullname == "") {
                Swal.fire({
                    icon: "warning",
                    title: "กรุณากรอกข้อมูลให้ครบถ้วน",
                    timer: 2500,
                    showConfirmButton: false
                });
                return false;
            }
            $.ajax({
                type: "POST",
                url: "webhook/AuthLine.php",
                data: {
                    fullname: fullname,
                    xapikey: xapikey,
                    line_token: line_token
                },
                dataType: "json",
                success: function (data) {
                    if (data.result == 1) {
                        Swal.fire({
                            icon: "success",
                            title: "ลงทะเบียนสำเร็จ",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            liff.closeWindow();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "ลงทะเบียนไม่สำเร็จ กรุณาลองใหม่อีกครั้ง",
                            timer: 2500,
                            showConfirmButton: false
                        });
                        return false;
                    }
                },
            });
        }

        // 1. ป้องกันการ Pinch Zoom (ใช้นิ้วถ่างขยาย) บน iOS Safari
        document.addEventListener('gesturestart', function (e) {
            e.preventDefault();
        });
        document.addEventListener('gesturechange', function (e) {
            e.preventDefault();
        });
        document.addEventListener('gestureend', function (e) {
            e.preventDefault();
        });

        // 2. (เผื่อไว้) ป้องกันการกด Ctrl + Scroll บน Desktop และ Pinch บนบาง Browser
        document.addEventListener('wheel', function (e) {
            if (e.ctrlKey) {
                e.preventDefault();
            }
        }, {
            passive: false
        });

        // 3. ป้องกันการกด Double Tap เพื่อซูม (แบบ JS)
        var lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            var now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>

</html>