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

$group_id = isset($_POST['group_id']) ? (int) trim($_POST['group_id']) : 0;

$page = isset($_POST['page']) ? (int) trim($_POST['page']) : 1;

if ($page < 1) $page = 1;

$limit = 9;

$offset = ($page - 1) * $limit;

$sql_groups = "SELECT group_id , group_name  FROM tbl_course_group WHERE delete_at IS NULL";

$stmt_groups = $pdo_connect->prepare($sql_groups);

$stmt_groups->execute();

$course_groups = $stmt_groups->fetchAll(PDO::FETCH_ASSOC);

$stmt_groups->closeCursor();

$sql_count = "SELECT COUNT(*) as total FROM tbl_course WHERE course_status = '1' AND delete_at IS NULL";

if ($group_id > 0) {

    $sql_count .= " AND course_group = :group_id";

}

$stmt_count = $pdo_connect->prepare($sql_count);

if ($group_id > 0) {

    $stmt_count->bindValue(':group_id', $group_id, PDO::PARAM_INT);

}

$stmt_count->execute();

$row_count = $stmt_count->fetch(PDO::FETCH_ASSOC);

$total_items = (int) $row_count['total'];

$total_pages = ceil($total_items / $limit);

$stmt_count->closeCursor();

$sql_courses = "SELECT course_id, 
                       course_name, 
                       course_detail, 
                       course_price, 
                       course_promotion, 
                       course_period, 
                       course_instructor, 
                       course_cover_image, 
                       course_group, 
                       course_cpd_hour, 
                       course_cpd_ethics, 
                       course_cpd_other, 
                       course_cpa_hour, 
                       course_cpa_ethics, 
                       course_cpa_other 
                FROM tbl_course 
                WHERE course_status = '1' 
                AND delete_at IS NULL";

if ($group_id > 0) {

    $sql_courses .= " AND course_group = :group_id";

}

$sql_courses .= " LIMIT :limit OFFSET :offset";

$stmt_courses = $pdo_connect->prepare($sql_courses);

if ($group_id > 0) {

    $stmt_courses->bindValue(':group_id', $group_id, PDO::PARAM_INT);

}

$stmt_courses->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt_courses->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt_courses->execute();

$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

$stmt_courses->closeCursor();



foreach ($courses as &$c) {

    if (!empty($c['course_cover_image'])) {
        $c['course_cover_image'] = AwsS3::getFileUrl($c['course_cover_image'], '+30 minutes', false);
    }

    $c['course_cpd_hour'] = number_format($c['course_cpd_hour'], 2);

    $c['course_cpd_ethics'] = number_format($c['course_cpd_ethics'], 2);

    $c['course_cpd_other'] = number_format($c['course_cpd_other'], 2);

    $c['course_cpa_hour'] = number_format($c['course_cpa_hour'], 2);

    $c['course_cpa_ethics'] = number_format($c['course_cpa_ethics'], 2);

    $c['course_cpa_other'] = number_format($c['course_cpa_other'], 2);

    $price = $c['course_price'];

    $promotion = $c['course_promotion'];

    $final_price = ($promotion > 0 && $promotion < $price) ? $promotion : $price;

    $c['final_price_fmt'] = number_format($final_price);

    if ($promotion > 0 && $promotion < $price) {

        $c['old_price_fmt'] = number_format($price);

    } else {

        $c['old_price_fmt'] = null;

    }

}

$data = [

    'groups' => $course_groups,

    'courses' => $courses,

    'pagination' => [

        'current_page' => $page,

        'total_pages' => $total_pages,

        'total_items' => $total_items,

        'limit' => $limit

    ]

];

Response::json(1, 'ดึงข้อมูลสำเร็จ', $data);
