<?php
namespace {
    // กำหนดค่าจำลอง
    $_POST['request_state'] = 'list_certificate';
    $_POST['request_function'] = 'export_certificate';
    $_POST['access_token'] = 'dummy';
    $_POST['enroll_id'] = 1;
    $_POST['mode'] = 'base64';

    require_once 'c:/xampp/htdocs/am/vendor/autoload.php';
}

// ย้าย namespace มาประกาศแยกกันตาม syntax ของ PHP
namespace App\Utility {
    if (!class_exists('App\Utility\Auth', false)) {
        class Auth {
            public static function requireUserToken() {
                $u = new \stdClass();
                $u->user_id = 1;
                return $u;
            }
        }
    }
}

namespace {
    try {
        echo "=== Direct Load of ExportCertificate.php ===\n";
        
        $db = new \App\Database\Connection();
        $pdo = $db->getPdo();
        
        $enroll = $pdo->query("SELECT enroll_id FROM tbl_course_enrollment ORDER BY enroll_id DESC LIMIT 1")->fetchColumn();
        if ($enroll) {
            $_POST['enroll_id'] = $enroll;
            echo "Using enroll_id: $enroll\n";
        }
        
        include 'c:/xampp/htdocs/am/main/core/listCertificate/ExportCertificate.php';
        
    } catch (\Throwable $e) {
        echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
        echo $e->getTraceAsString();
    }
}
