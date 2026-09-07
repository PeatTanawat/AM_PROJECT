<!DOCTYPE html>

<html lang="th">



    <head>

        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">



        <title>CPDTH - System</title>



        <link rel="icon" type="image/png" href="../assets/images/am-group-logo.png">
        <link rel="apple-touch-icon" href="../assets/images/am-group-logo.png">

        <link rel="stylesheet" href="../template/assets/css/font.css">

        <link rel="stylesheet" href="../template/assets/css/sidebar-menu.css">

        <link rel="stylesheet" href="../template/assets/css/simplebar.css">    

        <link rel="stylesheet" href="../template/assets/css/apexcharts.css">

        <link rel="stylesheet" href="../template/assets/css/prism.css">

        <link rel="stylesheet" href="../template/assets/css/rangeslider.css">

        <!-- rich text editor = TinyMCE (โหลด global ใน script.php); quill.snow.css เอาออกแล้ว -->

        <link rel="stylesheet" href="../template/assets/css/google-icon.css">

        <link rel="stylesheet" href="../template/assets/css/remixicon.css">

        <link rel="stylesheet" href="../template/assets/css/swiper-bundle.min.css">

        <link rel="stylesheet" href="../template/assets/css/fullcalendar.main.css">

        <link rel="stylesheet" href="../template/assets/css/jsvectormap.min.css">

        <link rel="stylesheet" href="../template/assets/css/lightpick.css">
        <link rel="stylesheet" href="../template/assets/css/select2.min.css">

        <link rel="stylesheet" href="../template/assets/css/style.css">

        <link rel="stylesheet" href="../template/assets/css/toastr.min.css">

        <link rel="stylesheet" href="../template/assets/css/dataTables.bootstrap5.min.css">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <link rel="stylesheet" href="../template/assets/css/custom.css?ver=<?php echo @filemtime(dirname(__DIR__) . '/template/assets/css/custom.css') ?: time(); ?>">

        <link rel="stylesheet" href="../template/assets/css/web.css">

        <link rel="stylesheet" href="../template/assets/css/ui.css?ver=<?php echo @filemtime(dirname(__DIR__) . '/template/assets/css/ui.css') ?: time(); ?>">

    </head>



    <body class="boxed-size">

        <div class="preloader" id="preloader"><script>if(sessionStorage.getItem('cpdth_show_preloader')==='1'){sessionStorage.removeItem('cpdth_show_preloader');}else{var _p=document.getElementById('preloader');if(_p){_p.style.display='none';}}</script>

            <div class="preloader">

                <div class="waviy position-relative">

                      <span class="d-inline-block">C</span>

                    <span class="d-inline-block">P</span>

                    <span class="d-inline-block">D</span>

                    <span class="d-inline-block">T</span>

                    <span class="d-inline-block">H</span>

                </div>

            </div>

        </div>



        <?php include "sidebar.php"; ?>