<?php
// แก้ไขสิทธิ์การเข้าถึงคอร์ส (ใช้ enroll_access เป็นสถานะ ไม่ลบทิ้ง -> แก้ไข/เปิดใหม่ได้)
// status: '1' = ให้สิทธิ์ใช้งาน (enroll_access=1) + ตั้งวันหมดอายุตามที่กรอก (ว่าง=ไม่มีกำหนด)
//         '0' = ยกเลิกสิทธิ์ (enroll_access=0)
// หมายเหตุ: ตอนเปิดใช้งานจากที่ยกเลิก ฝั่งหน้าเว็บจะ default ช่องวันหมดอายุเป็น 90 วันนับจากวันนี้ (แก้ไข/ล้างได้)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$enroll_id = isset($_POST['enroll_id']) ? (int) $_POST['enroll_id'] : 0;
$status    = (isset($_POST['status']) && $_POST['status'] === '0') ? '0' : '1';
$expiry    = trim((string) ($_POST['expiry'] ?? ''));

if ($enroll_id <= 0) {
    Response::json(0, 'ไม่พบรายการ', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// มีรายการจริง + ยังไม่ถูกยกเลิก
$chk = $pdo_connect->prepare("SELECT enroll_id FROM tbl_course_enrollment WHERE enroll_id = :id AND delete_at IS NULL LIMIT 1");
$chk->execute([':id' => $enroll_id]);
if (!$chk->fetchColumn()) { $chk->closeCursor(); Response::json(0, 'ไม่พบรายการนี้ หรือถูกยกเลิกไปแล้ว', null); }
$chk->closeCursor();

try {
    if ($status === '0') {
        // ยกเลิกสิทธิ์ -> enroll_access = 0 (คงรายการไว้ แก้ไข/เปิดใหม่ได้ภายหลัง)
        $stmt = $pdo_connect->prepare("UPDATE tbl_course_enrollment SET enroll_access = '0' WHERE enroll_id = :id AND delete_at IS NULL");
        $stmt->execute([':id' => $enroll_id]);
        $stmt->closeCursor();
        Response::json(1, 'ยกเลิกสิทธิ์การเข้าถึงคอร์สแล้ว', null);
    } else {
        // ให้สิทธิ์ใช้งาน -> ตั้ง enroll_access=1 + วันหมดอายุตามที่กรอก (ว่าง = ไม่มีกำหนด)
        $exp = null;
        if ($expiry !== '') {
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $expiry, $m)) {
                $exp = $m[3] . '-' . $m[2] . '-' . $m[1] . ' 23:59:59';
            } else {
                $exp = $expiry;
            }
        }
        $stmt = $pdo_connect->prepare("UPDATE tbl_course_enrollment SET enroll_access = '1', enroll_expiry_date = :exp WHERE enroll_id = :id AND delete_at IS NULL");
        $stmt->execute([':exp' => $exp, ':id' => $enroll_id]);
        $stmt->closeCursor();
        Response::json(1, 'บันทึกการแก้ไขสำเร็จ', null);
    }
} catch (Throwable $e) {
    error_log('UpdateEnrollment Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
