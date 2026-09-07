<?php
// อัปโหลดคำถามระหว่างรับชมจากไฟล์ Excel
// รูปแบบ (ตาม example_question.xlsx): Question | Choice 1 | Choice 2 | Choice 3 | Choice 4 | Correct Answer(1-4)

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;

$access_token = Auth::requireUserToken();
$user_id = $access_token->user_id ?? null;

if (!$user_id) {
    Response::json(0, 'Unauthorized', null);
}

$lesson_id = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
if ($lesson_id <= 0) {
    Response::json(0, 'ไม่พบรหัสบทเรียน', null);
}

if (empty($_FILES['excel_file']['name']) || ($_FILES['excel_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(0, 'กรุณาเลือกไฟล์ Excel', null);
}
$ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'xls'], true)) {
    Response::json(0, 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls)', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();

if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

try {
    $sheet = IOFactory::load($_FILES['excel_file']['tmp_name'])->getActiveSheet();
    $rows  = $sheet->toArray();

    if (count($rows) < 2) {
        Response::json(0, 'ไฟล์ไม่มีข้อมูลคำถาม', null);
    }

    $pdo_connect->beginTransaction();
    $qStmt = $pdo_connect->prepare(
        "INSERT INTO tbl_question (lesson_id, question_text) VALUES (:lid, :text)"
    );
    $cStmt = $pdo_connect->prepare(
        "INSERT INTO tbl_question_choice (question_id, question_choice_text, question_choice_correct)
         VALUES (:qid, :text, :correct)"
    );

    $imported = 0;
    // ข้ามแถวหัวตาราง (แถวแรก)
    foreach (array_slice($rows, 1) as $r) {
        $qtext   = trim((string)($r[0] ?? ''));
        $choices = [trim((string)($r[1] ?? '')), trim((string)($r[2] ?? '')), trim((string)($r[3] ?? '')), trim((string)($r[4] ?? ''))];
        $correct = (int)($r[5] ?? 0);

        if ($qtext === '') { continue; }                       // ข้ามแถวว่าง
        $filled = array_values(array_filter($choices, fn($c) => $c !== ''));
        if (count($filled) < 2) { continue; }                  // ต้องมีอย่างน้อย 2 ตัวเลือก

        $qStmt->execute([':lid' => $lesson_id, ':text' => $qtext]);
        $qid = (int) $pdo_connect->lastInsertId();

        foreach ($choices as $i => $c) {
            if ($c === '') { continue; }
            $cStmt->execute([
                ':qid'     => $qid,
                ':text'    => $c,
                ':correct' => (($i + 1) === $correct) ? '1' : '0',
            ]);
        }
        $imported++;
    }
    $qStmt->closeCursor();
    $cStmt->closeCursor();
    $pdo_connect->commit();

    if ($imported === 0) {
        Response::json(0, 'ไม่พบคำถามที่ถูกต้องในไฟล์', null);
    }
    Response::json(1, "นำเข้าคำถามสำเร็จ $imported ข้อ", ['imported' => $imported]);
} catch (Exception $e) {
    if ($pdo_connect->inTransaction()) { $pdo_connect->rollBack(); }
    error_log('Upload Question Error: ' . $e->getMessage());
    Response::json(0, 'อ่านไฟล์ Excel ไม่สำเร็จ', null);
} finally {
    $pdo_connect = null;
}
