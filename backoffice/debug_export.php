<?php
// ไฟล์ตรวจจับ Error ของการออก PDF แบบละเอียด
ini_set('display_errors', 1);
ini_set('display_reporting', E_ALL);
error_reporting(E_ALL);

require_once 'c:/xampp/htdocs/am/vendor/autoload.php';

try {
    echo "<h1>Debug Export Certificate</h1>";
    
    // 1. ทดสอบต่อ Database
    echo "Connecting Database... ";
    $db = new \App\Database\Connection();
    $pdo = $db->getPdo();
    echo "<span style='color:green;'>OK</span><br>";
    
    // 2. หา enroll_id ล่าสุดมาทดสอบ
    $enroll_id = $pdo->query("SELECT enroll_id FROM tbl_course_enrollment ORDER BY enroll_id DESC LIMIT 1")->fetchColumn();
    if (!$enroll_id) {
        throw new Exception("ไม่พบรายการ enroll_id ในตาราง tbl_course_enrollment เลย");
    }
    echo "Using enroll_id: <b>$enroll_id</b><br>";
    
    // 3. จำลอง $_POST สำหรับเรียกใช้งาน ExportCertificate.php
    $_POST['enroll_id'] = $enroll_id;
    $_POST['cert_type'] = 'cpd';
    $_POST['mode'] = 'stream'; // รันออกหน้าจอเลย
    
    // จำลอง Auth token เพื่อไม่ให้ติด Unauthorized
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer debug';
    // Mock class Auth เพื่อให้จำลองค่าได้
    // (หมายเหตุ: คลาส Auth จริงอาจโหลดมาจาก autoload.php แล้ว ถ้ามีการ declare ซ้ำจะ Error)
    
    echo "Running ExportCertificate.php...<br><hr>";
    
    // โหลดไฟล์ทำงานจริง
    include 'c:/xampp/htdocs/am/main/core/listCertificate/ExportCertificate.php';
    
} catch (\Throwable $e) {
    echo "<br><span style='color:red; font-weight:bold;'>FATAL ERROR:</span> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "<h3>Stack Trace:</h3><pre>" . $e->getTraceAsString() . "</pre>";
}
