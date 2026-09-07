<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Utility\Response;

$id = isset($_POST['id']) ? (int) trim($_POST['id']) : 0;

if ($id <= 0) {
    Response::json(0, 'รหัสคอร์สไม่ถูกต้อง', null);
}

// สามารถเพิ่ม logic อื่นๆ ที่ต้องการ (เช่น การเก็บสถิติ) ได้ที่นี่ในอนาคต

Response::json(1, 'Success', null);
