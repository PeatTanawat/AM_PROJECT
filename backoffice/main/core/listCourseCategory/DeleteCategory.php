<?php

    use App\Utility\Auth;
    use App\Utility\Response;
    use App\Database\Connection;

    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    if ($user_id) {
        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;

        if ($group_id <= 0) {
            Response::json(0, 'ไม่พบหมวดหมู่ที่ต้องการลบ', null);
        }

        try {
            // กันลบ: ถ้ายังมีคอร์สเรียนอยู่ในหมวดหมู่นี้ (ที่ยังไม่ถูกลบ)
            $sql_check = "SELECT COUNT(*) FROM tbl_course
                          WHERE course_group = :group_id AND delete_at IS NULL;";
            $stmt_check = $pdo_connect->prepare($sql_check);
            $stmt_check->execute([':group_id' => $group_id]);
            $course_count = (int) $stmt_check->fetchColumn();
            $stmt_check->closeCursor();

            if ($course_count > 0) {
                Response::json(0, 'ไม่สามารถลบได้ เนื่องจากยังมีคอร์สเรียนอยู่ในหมวดหมู่นี้', null);
            }

            // soft delete
            $sql_delete = "UPDATE tbl_course_group SET
                delete_at = NOW()
                WHERE group_id = :group_id AND delete_at IS NULL;";

            $stmt_delete = $pdo_connect->prepare($sql_delete);
            $delete = $stmt_delete->execute([':group_id' => $group_id]);
            $stmt_delete->closeCursor();

            if (!$delete) {
                throw new Exception('เกิดข้อผิดพลาดในการลบข้อมูล');
            }

            Response::json(1, "ลบข้อมูลสำเร็จ", null);
        } catch (Exception $e) {
            error_log("Delete Category Error: " . $e->getMessage());
            Response::json(0, 'เกิดข้อผิดพลาด', null);
        } finally {
            $pdo_connect = null;
        }
    } else {
        Response::json(0, 'Unauthorized', null);
    }

?>
