<?php
$conn = mysqli_connect('localhost', 'root', '', 'am');
$res = mysqli_query($conn, 'SHOW COLUMNS FROM tbl_etax');
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
