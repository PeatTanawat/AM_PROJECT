<?php
session_start();
include("config/main_function.php");
date_default_timezone_set("Asia/Bangkok");
$connection = connectDB("s?9>9{RW{!Etop/");

$userId = $_POST["userId"];

$sql_check = "SELECT lr.*
        FROM tbl_customer lr 
        WHERE lr.line_token = '$userId'
        AND lr.active_status = '1';";
$rs_check = mysqli_query($connection, $sql_check);
$cmt_check = mysqli_num_rows($rs_check);
$row_check = mysqli_fetch_array($rs_check);
if ($cmt_check > 0) {
    $result = 1;
    // เก็บค่าไว้ใน session
    $_SESSION['customer_id'] = $row_check["customer_id"];
    $arr['customer_id'] = $row_check["customer_id"];
} else {
    $result = 2;
}

$arr['result'] = $result;
echo json_encode($arr);
mysqli_close($connection);
