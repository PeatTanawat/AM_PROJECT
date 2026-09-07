<?php
use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;
if (!$user_id) {
    Response::json(0, 'กรุณาเข้าสู่ระบบก่อน', null);
}

$db = new Connection();
$pdo = $db->getPdo();

try {
    // Pagination
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 5;
    $offset = ($page - 1) * $limit;

    // Count Total
    $stmtCount = $pdo->prepare("
        SELECT COUNT(e.enroll_id)
        FROM tbl_course_enrollment e
        WHERE e.enroll_user_id = :user_id 
          AND e.delete_at IS NULL
          AND EXISTS (
              SELECT 1 FROM tbl_exam_attempt a2 
              WHERE a2.attempt_user_id = e.enroll_user_id 
                AND a2.attempt_enroll_id = e.enroll_id 
                AND a2.attempt_pass = '1'
          )
    ");
    $stmtCount->execute([':user_id' => $user_id]);
    $totalItems = (int)$stmtCount->fetchColumn();
    $totalPages = ceil($totalItems / $limit);

    // ใบรับรองผลการสอบ: ผู้ใช้ต้องสอบผ่าน (มีประวัติสอบผ่านในรอบการลงทะเบียนนี้)
    $stmt = $pdo->prepare("
        SELECT e.enroll_id, e.enroll_course_id, e.enroll_is_completed, e.create_at,
               c.course_name, c.course_instructor, c.course_number_exam,
               u.identity_verified, u.user_firstname, u.user_lastname,
               (SELECT snap.cert_no FROM tbl_certificate_snapshot snap
                 WHERE snap.enroll_id = e.enroll_id 
                 ORDER BY snap.cert_id ASC LIMIT 1) AS snap_cert_no,
               (SELECT a.attempt_score FROM tbl_exam_attempt a
                 WHERE a.attempt_user_id = e.enroll_user_id AND a.attempt_enroll_id = e.enroll_id
                 ORDER BY a.attempt_id DESC LIMIT 1) AS score
        FROM tbl_course_enrollment e
        JOIN tbl_course c ON e.enroll_course_id = c.course_id
        JOIN tbl_user u ON e.enroll_user_id = u.user_id
        WHERE e.enroll_user_id = :user_id 
          AND e.delete_at IS NULL
          AND EXISTS (
              SELECT 1 FROM tbl_exam_attempt a2 
              WHERE a2.attempt_user_id = e.enroll_user_id 
                AND a2.attempt_enroll_id = e.enroll_id 
                AND a2.attempt_pass = '1'
          )
        ORDER BY e.enroll_id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $ts = $r['create_at'] ? strtotime($r['create_at']) : time();
        $cert_no = !empty($r['snap_cert_no']) ? $r['snap_cert_no'] : (date('ym', $ts) . str_pad((string) $r['enroll_id'], 4, '0', STR_PAD_LEFT));

        $total = (int) ($r['course_number_exam'] ?? 0);
        $sc = (int) ($r['score'] ?? 0);
        $pct = $total > 0 ? number_format($sc / $total * 100, 2) : '0.00';

        $full_name = trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''));

        // การอนุมัติ (อิงจาก enroll_is_completed = 1)
        $is_approved = ((string) ($r['enroll_is_completed'] ?? '0') === '1');
        
        $instructor_name = trim($r['course_instructor'] ?? '');

        $data[] = [
            'enroll_id' => (int) $r['enroll_id'],
            'enroll_key' => \App\Utility\Cipher::encrypt((string) $r['enroll_id']),
            'course_id' => (int) $r['enroll_course_id'],
            'cert_no' => $cert_no,
            'course_name' => $r['course_name'] ?? '-',
            'examiner' => $instructor_name !== '' ? $instructor_name : '-',
            'score_txt' => $sc . ' คะแนน / ' . $pct . ' %',
            'is_approved' => $is_approved,
        ];
    }

    Response::json(1, 'Success', [
        'certificate_list' => $data,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาด: ' . $e->getMessage(), null);
}
