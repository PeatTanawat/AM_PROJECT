<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Database\Connection;
use App\Utility\Response;
use Dotenv\Dotenv;
use App\Utility\AwsS3;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (! $pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    /* ─── 1. Banners ─── */
    $stmt_banner = $pdo_connect->prepare(
        "SELECT banner_id, banner_order, banner_url, banner_image
         FROM tbl_banner
         WHERE banner_status = '1'
           AND delete_at IS NULL
         ORDER BY banner_order ASC"
    );
    $stmt_banner->execute();
    $banners = $stmt_banner->fetchAll(PDO::FETCH_ASSOC);
    $stmt_banner->closeCursor();

    foreach ($banners as &$banner) {
        if (!empty($banner['banner_image'])) {
            $banner['banner_image'] = AwsS3::getFileUrl($banner['banner_image']);
        }
        $url = trim((string)($banner['banner_url'] ?? ''));
        $banner['banner_url']  = $url;
        $banner['has_link']    = ($url !== '');
        $banner['link_target'] = (preg_match('/^https?:\/\//i', $url)) ? '_blank' : '_self';
    }
    unset($banner);

    /* ─── 2. Courses ─── */
    if (!function_exists('formatCourse')) {
        function formatCourse(array &$c): void
        {
            $c['course_cpd_hour']    = number_format((float)$c['course_cpd_hour'], 2);
            $c['course_cpd_ethics']  = number_format((float)$c['course_cpd_ethics'], 2);
            $c['course_cpd_other']   = number_format((float)$c['course_cpd_other'], 2);
            $c['encrypted_id']       = \App\Utility\Cipher::encrypt((string)$c['course_id']);
            $c['course_cpa_hour']    = number_format((float)$c['course_cpa_hour'], 2);
            $c['course_cpa_ethics']  = number_format((float)$c['course_cpa_ethics'], 2);
            $c['course_cpa_other']   = number_format((float)$c['course_cpa_other'], 2);

            $price     = (float)$c['course_price'];
            $promo     = (float)$c['course_promotion'];
            $has_promo = ($promo > 0 && $promo < $price);

            $c['final_price_fmt'] = number_format($has_promo ? $promo : $price, 0, '.', ',');
            $c['old_price_fmt']   = $has_promo ? number_format($price, 0, '.', ',') : null;
            $c['has_promotion']   = $has_promo;

            if (!empty($c['course_cover_image'])) {
                $c['course_cover_image'] = AwsS3::getFileUrl($c['course_cover_image']);
            }
        }
    }

    // Promo Courses
    $sql_promo = "SELECT * FROM tbl_course
                  WHERE course_status = '1'
                    AND course_display = '1'
                    AND delete_at IS NULL
                  ORDER BY RAND()
                  LIMIT 5";
    $stmt_promo = $pdo_connect->prepare($sql_promo);
    $stmt_promo->execute();
    $promo_courses = $stmt_promo->fetchAll(PDO::FETCH_ASSOC);
    $stmt_promo->closeCursor();

    foreach ($promo_courses as &$c) {
        formatCourse($c);
    }
    unset($c);

    // Recommended Courses
    $sql_rec = "SELECT * FROM tbl_course
                WHERE course_status = '1'
                  AND delete_at IS NULL
                  AND course_display = '1'
                ORDER BY create_at DESC
                LIMIT 12";
    $stmt_rec = $pdo_connect->prepare($sql_rec);
    $stmt_rec->execute();
    $rec_courses = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);
    $stmt_rec->closeCursor();

    foreach ($rec_courses as &$c) {
        formatCourse($c);
    }
    unset($c);

    /* ─── 3. Website Settings ─── */
    $stmt_setting = $pdo_connect->prepare(
        "SELECT text_1,
                youtube_id,
                image_path,
                text_2,
                facebook_link,
                x_link,
                line_link,
                about_us,
                contact_us
         FROM tbl_website_setting
         LIMIT 1"
    );
    $stmt_setting->execute();
    $setting = $stmt_setting->fetch(PDO::FETCH_ASSOC);
    $stmt_setting->closeCursor();

    if ($setting && !empty($setting['image_path'])) {
        $setting['image_path'] = AwsS3::getFileUrl($setting['image_path']);
    }

    $pdo_connect = null;

    Response::json(1, 'ดึงข้อมูลหน้าแรกสำเร็จ', [
        'banners'     => $banners,
        'promo'       => $promo_courses,
        'recommended' => $rec_courses,
        'setting'     => $setting ? $setting : null
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
