<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CPDTH' ?></title>

    <?php
    $root_dir = str_replace('\\', '/', dirname(__DIR__));
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
    $relative_path = str_replace($root_dir, '', $script_dir);
    $depth = substr_count(trim($relative_path, '/'), '/');
    if (trim($relative_path, '/') !== '') {
        $depth++;
    }
    $base_url = str_repeat('../', $depth);
    ?>

    <link rel="icon" type="image/png" href="<?php echo $base_url ?? '' ?>assets/images/logo/am-group-logo.png">
    <link rel="apple-touch-icon" href="<?php echo $base_url ?? '' ?>assets/images/logo/am-group-logo.png">
    <!-- Google Fonts: Kanit + Prompt -->
    <!-- <link
        href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Prompt:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" defer> -->
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" defer>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- LoadingOverlay -->
    <script src="<?php echo $base_url ?? '' ?>assets/js/loadingoverlay.js"></script>
    <!-- Swiper.js (โหลดเฉพาะหน้าที่ตั้งค่า $useSwiper = true) -->
    <?php if (!empty($useSwiper)): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" defer>
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <?php endif; ?>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"
        defer>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url ?? '' ?>assets/css/style.css?v=1.0.1">
</head>

<body>
    <!-- Global Page Loader (Spinner) -->
    <div id="globalPageLoader"
        style="position: fixed; top: 0; left: 0; bottom: 0; right: 0; width: 100%; height: 100%; z-index: 10000; display: flex; align-items: center; justify-content: center; background: white; transition: opacity 0.3s ease;">
        <div
            style="width: 50px; height: 50px; border: 4px solid rgba(59, 89, 152, 0.2); border-top-color: #3b5998; border-radius: 50%; animation: globalSpin 0.8s linear infinite;">
        </div>
    </div>
    <style>
        @keyframes globalSpin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <script>
        // Hide loader once page is fully loaded
        window.addEventListener('load', function () {
            var loader = document.getElementById('globalPageLoader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(function () {
                    loader.style.display = 'none';
                }, 300);
            }
        });
        // Show loader when navigating to another page
        window.addEventListener('beforeunload', function () {
            var loader = document.getElementById('globalPageLoader');
            if (loader) {
                loader.style.display = 'flex';
                loader.style.opacity = '1';
            }
        });
        // Hide loader if page is restored from bfcache (e.g. clicking Back button)
        window.addEventListener('pageshow', function (event) {
            var loader = document.getElementById('globalPageLoader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(function () {
                    loader.style.display = 'none';
                }, 300);
            }
        });

        // ป้องกัน SweetAlert2 คืนโฟกัสกลับไปที่ปุ่มใน Modal ที่ซ่อนไปแล้ว (เลี่ยงปัญหา aria-hidden warning)
        $(document).ready(function () {
            if (window.Swal) {
                Swal = Swal.mixin({
                    returnFocus: false,
                    scrollbarPadding: false /* ป้องกันหน้าเว็บขยับซ้ายขวาตอนป๊อปอัปเด้ง */
                });
            }
        });
    </script>

    <div class="sticky-header sticky-top">
        <?php include 'topbar.php'; ?>
        <?php include 'navbar.php'; ?>
    </div>