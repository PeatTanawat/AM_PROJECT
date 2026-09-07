<?php
use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

try {
    // 1. Check Authentication
    $currentUser = Auth::requireUserToken();
    $user_id = (int)$currentUser->user_id;

    $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
    $selected_choice = isset($_POST['selected_choice']) ? (int)$_POST['selected_choice'] : 0;

    if ($question_id <= 0 || $selected_choice <= 0) {
        Response::json(0, 'ข้อมูลการส่งคำตอบไม่ถูกต้อง', null);
    }

    $db_instance = new Connection();
    $db = $db_instance->getPdo();

    if (!$db) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // Fetch choices for question
    $sql_choices = "SELECT question_choice_correct FROM tbl_question_choice WHERE question_id = :qid AND delete_at IS NULL ORDER BY question_choice_id ASC";
    $stmt = $db->prepare($sql_choices);
    $stmt->execute([':qid' => $question_id]);
    $choices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($choices)) {
        Response::json(0, 'ไม่พบตัวเลือกสำหรับคำถามนี้', null);
    }

    $correct_choice_index = 0;
    foreach ($choices as $i => $c) {
        if ($c['question_choice_correct'] == '1' || $c['question_choice_correct'] == 1) {
            $correct_choice_index = $i + 1; // 1-indexed choice position
            break;
        }
    }

    if ($correct_choice_index > 0 && $selected_choice === $correct_choice_index) {
        Response::json(1, 'คำตอบถูกต้อง', null);
    } else {
        Response::json(0, 'คำตอบไม่ถูกต้อง', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการตรวจสอบคำตอบ: ' . $e->getMessage(), null);
}
