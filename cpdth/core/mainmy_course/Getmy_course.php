<?php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use App\Utility\AwsS3;

try {
    $access_token = Auth::requireUserToken();
    $admin_id = $access_token->user_id ?? null;

    if (!$admin_id) {
        Response::json(0, 'Unauthorized', null);
    }

    // หากส่ง user_id มาให้ใช้ค่าที่ส่งมา (เช่นแอดมินดู) หากไม่ได้ส่งมาให้ใช้ ID ของตัวเองที่เข้าสู่ระบบอยู่
    $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int)$admin_id;
    if ($target_id <= 0) {
        Response::json(0, 'ไม่พบรหัสผู้ใช้', null);
    }

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // ข้อมูลการเรียนพร้อมข้อมูลวิชา (LEFT JOIN tbl_exam_attempt แทน Subquery เพื่อเพิ่มความเร็วในการดึงข้อมูล)
    $stmt = $pdo_connect->prepare(
        "SELECT 
         e.enroll_id,
         e.enroll_user_id,
         e.enroll_course_id,
         e.enroll_payment_status,
         e.enroll_date,
         e.enroll_expiry_date,
         e.enroll_is_completed,
         e.enroll_access,
         e.enroll_period,
         c.course_name,
         c.course_cover_image,
         c.course_instructor,
         c.course_cpd_hour,
         c.course_cpd_ethics,
         c.course_cpd_other,
         c.course_cpa_hour,
         c.course_cpa_ethics,
         c.course_cpa_other,
         c.course_period,
         IF(a.attempt_id IS NOT NULL, 1, 0) AS has_passed_exam,
         (SELECT od.order_id 
          FROM tbl_order_detail od 
          JOIN tbl_orders o ON o.order_id = od.order_id
          WHERE od.course_id = e.enroll_course_id 
            AND o.user_id = e.enroll_user_id 
          ORDER BY o.order_id DESC 
          LIMIT 1) AS latest_order_id
         FROM tbl_course_enrollment e
         JOIN tbl_course c ON e.enroll_course_id = c.course_id AND c.delete_at IS NULL
         LEFT JOIN tbl_exam_attempt a ON a.attempt_enroll_id = e.enroll_id AND a.attempt_pass = '1'
         WHERE e.enroll_user_id = :id AND e.delete_at IS NULL
         GROUP BY e.enroll_id
         ORDER BY e.enroll_id DESC"
    );
    $stmt->execute([':id' => $target_id]);
    $enrollment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($enrollment as &$item) {
        if (!empty($item['course_cover_image'])) {
            $item['course_cover_image'] = AwsS3::getFileUrl($item['course_cover_image'], '+30 minutes', false);
        }
    }
    unset($item);

    Response::json(1, 'Success', [
        'enrollment' => $enrollment
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(), null);
}
