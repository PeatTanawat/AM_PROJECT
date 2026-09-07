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

$id_param = isset($_POST['id']) ? trim($_POST['id']) : '';
if (!is_numeric($id_param)) {
    $id = \App\Utility\Cipher::decrypt($id_param);
} else {
    $id = (int)$id_param;
}

if ($id <= 0) {

    Response::json(0, 'รหัสคอร์สไม่ถูกต้อง', null);

}

$sql_course = "SELECT c.*, g.group_name 
               FROM tbl_course c 
               LEFT JOIN tbl_course_group g ON c.course_group = g.group_id 
               WHERE c.course_id = :id AND c.course_status = '1' AND c.delete_at IS NULL";

$stmt = $pdo_connect->prepare($sql_course);

$stmt->bindValue(':id', $id, PDO::PARAM_INT);

$stmt->execute();

$course = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt->closeCursor();

if (!$course) {

    Response::json(0, 'ไม่พบคอร์สเรียนนี้', null);

}

if (!empty($course['course_cover_image'])) {
    $course['course_cover_image'] = AwsS3::getFileUrl($course['course_cover_image'], '+30 minutes', false);
}

$course['course_cpd_hour'] = number_format($course['course_cpd_hour'], 2);

$course['course_cpd_ethics'] = number_format($course['course_cpd_ethics'], 2);

$course['course_cpd_other'] = number_format($course['course_cpd_other'], 2);

$course['course_cpa_hour'] = number_format($course['course_cpa_hour'], 2);

$course['course_cpa_ethics'] = number_format($course['course_cpa_ethics'], 2);

$course['course_cpa_other'] = number_format($course['course_cpa_other'], 2);

$price = $course['course_price'];

$promotion = $course['course_promotion'];

$final_price = ($promotion > 0 && $promotion < $price) ? $promotion : $price;

$course['final_price_fmt'] = number_format($final_price);

if ($promotion > 0 && $promotion < $price) {

    $course['old_price_fmt'] = number_format($price);

} else {

    $course['old_price_fmt'] = null;

}

Response::json(1, 'ดึงข้อมูลสำเร็จ', $course);
