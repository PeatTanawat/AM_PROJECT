<?php
require 'vendor/autoload.php';
$db = new App\Database\Connection();
$pdo = $db->getPdo();
$tables = ['tbl_course', 'tbl_course_enrollment', 'tbl_exam_attempt'];
foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
}
