<?php
// บันทึกความคืบหน้าการดูบทเรียน (จำจุดที่ดูค้างไว้) ลง tbl_lesson_progress
// upsert ตาม (progress_user_id, progress_lesson_id) — ไม่ให้ค่า last_sec ถอยหลัง

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
$last_sec  = isset($_POST['last_sec']) ? (int) $_POST['last_sec'] : 0;
$status    = (isset($_POST['status']) && $_POST['status'] === '1') ? '1' : '0';

if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}
if ($last_sec < 0) { $last_sec = 0; }

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    // มีแถวเดิมของ user+lesson หรือยัง
    $sel = $pdo_connect->prepare(
        "SELECT progress_id, progress_last_sec, progress_status
         FROM tbl_lesson_progress
         WHERE progress_user_id = :u AND progress_lesson_id = :l
         LIMIT 1"
    );
    $sel->execute([':u' => $user_id, ':l' => $lesson_id]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    $sel->closeCursor();

    if ($row) {
        // ไม่ให้ถอยหลัง: เก็บ last_sec มากสุด; ถ้าเคยจบ (1) แล้วคงไว้
        $new_sec    = max((int) $row['progress_last_sec'], $last_sec);
        $new_status = ($row['progress_status'] === '1' || $status === '1') ? '1' : '0';

        $upd = $pdo_connect->prepare(
            "UPDATE tbl_lesson_progress
             SET progress_last_sec = :s, progress_status = :st
             WHERE progress_id = :id"
        );
        $upd->execute([':s' => $new_sec, ':st' => $new_status, ':id' => $row['progress_id']]);
        $upd->closeCursor();

        Response::json(1, 'บันทึกความคืบหน้าแล้ว', ['last_sec' => $new_sec, 'status' => $new_status]);
    } else {
        $ins = $pdo_connect->prepare(
            "INSERT INTO tbl_lesson_progress
                (progress_user_id, progress_lesson_id, progress_last_sec, progress_status)
             VALUES (:u, :l, :s, :st)"
        );
        $ins->execute([':u' => $user_id, ':l' => $lesson_id, ':s' => $last_sec, ':st' => $status]);
        $ins->closeCursor();

        Response::json(1, 'บันทึกความคืบหน้าแล้ว', ['last_sec' => $last_sec, 'status' => $status]);
    }
} catch (Throwable $e) {
    error_log('SaveProgress Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
} finally {
    $pdo_connect = null;
}
