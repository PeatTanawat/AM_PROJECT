<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use App\Database\Connection;

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    echo "Connection failed\n";
    exit;
}

try {
    $sql_data = "SELECT a.*,
                        COUNT(b.course_id) as course_count 
                FROM tbl_course_group a
                LEFT JOIN tbl_course b ON a.group_id = b.group_id 
                GROUP BY a.group_id
                ORDER BY a.group_id   DESC;";
    $stmt_data = $pdo_connect->prepare($sql_data);
    $stmt_data->execute();
    $result_data = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
    print_r($result_data);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
