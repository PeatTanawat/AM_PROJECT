<?php
require 'c:/xampp/htdocs/am/vendor/autoload.php';
$db = new App\Database\Connection();
$pdo = $db->getPdo();
try {
    $stmt = $pdo->query('DESCRIBE tbl_course_enrollment');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error tbl_course_enrollment: " . $e->getMessage() . "\n";
}

try {
    $stmt2 = $pdo->query('DESCRIBE tbl_lesson_progress');
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error tbl_lesson_progress: " . $e->getMessage() . "\n";
}
