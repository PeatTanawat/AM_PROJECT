<?php
require_once 'c:/xampp/htdocs/am/vendor/autoload.php';

$host = '103.86.48.64';
$db   = 'bigdemo_cpa';
$user = 'bigdemo_cpau';
$pass = 'eUjxGhsKxKXr2XRqekh3';
$port = 3306;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully to remote database!\n";

    $course_id = 1;
    $is_cpa = true;

    $reg_col = $is_cpa ? 'u.user_cpa_no' : 'u.user_cpd_no';
    $where  = ["e.delete_at IS NULL", "e.enroll_is_completed = '1'", "$reg_col IS NOT NULL", "TRIM($reg_col) <> ''"];
    $params = [];
    $where[] = 'e.enroll_course_id = :course_id'; $params[':course_id'] = $course_id;
    $where_sql = 'WHERE ' . implode(' AND ', $where);

    $sql = "SELECT u.user_prefix,
                   u.user_firstname, 
                   u.user_lastname, 
                   u.user_cpa_no, 
                   u.user_cpd_no,
                   MAX(e.enroll_date) AS enroll_date, 
                   (SELECT a.create_at 
                    FROM tbl_exam_attempt a
                    WHERE a.attempt_user_id = e.enroll_user_id 
                    AND a.attempt_course_id = e.enroll_course_id
                    AND a.attempt_pass = '1'
                    ORDER BY a.attempt_id DESC LIMIT 1) AS enroll_completed_at,
                   MAX(e.create_at) AS create_at,
                   
                   (SELECT a.attempt_score 
                    FROM tbl_exam_attempt a
                    WHERE a.attempt_user_id = e.enroll_user_id 
                    AND a.attempt_course_id = e.enroll_course_id
                    ORDER BY a.attempt_id DESC LIMIT 1) AS score,
                   SUM(COALESCE(lp.progress_last_sec, 0)) AS total_seconds
            FROM tbl_course_enrollment e
            LEFT JOIN tbl_user u   ON e.enroll_user_id = u.user_id
            LEFT JOIN tbl_course c ON e.enroll_course_id = c.course_id
            LEFT JOIN tbl_lesson l ON c.course_id  = l.course_id AND l.delete_at IS NULL
            LEFT JOIN tbl_lesson_progress lp ON l.lesson_id  = lp.progress_lesson_id AND lp.progress_user_id = e.enroll_user_id

            $where_sql
            GROUP BY e.enroll_user_id, e.enroll_course_id, u.user_prefix, u.user_firstname, u.user_lastname, u.user_cpa_no, u.user_cpd_no
            ORDER BY COALESCE(enroll_completed_at, MAX(e.create_at)) ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total Rows: " . count($rows) . "\n";
    foreach ($rows as $index => $row) {
        echo "Row $index:\n";
        echo "  Name: " . $row['user_firstname'] . " " . $row['user_lastname'] . "\n";
        echo "  Total Seconds (minutes): " . ($row['total_seconds'] / 60) . " mins\n";
        echo "  Score: " . $row['score'] . "\n";
        
        // Let's also check the actual attempts for this user
        $user_id = $pdo->query("SELECT user_id FROM tbl_user WHERE user_firstname = " . $pdo->quote($row['user_firstname']) . " LIMIT 1")->fetchColumn();
        if ($user_id) {
            $attempts = $pdo->query("SELECT attempt_id, attempt_score, attempt_pass, create_at FROM tbl_exam_attempt WHERE attempt_user_id = $user_id AND attempt_course_id = 1 ORDER BY attempt_id DESC")->fetchAll();
            echo "  Actual Exam Attempts:\n";
            foreach ($attempts as $att) {
                echo "    ID: {$att['attempt_id']}, Score: {$att['attempt_score']}, Pass: {$att['attempt_pass']}, Created: {$att['create_at']}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
