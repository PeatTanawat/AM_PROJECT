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
        $group_id   = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        $group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';

        if ($group_id <= 0) {
            Response::json(0, 'ไม่พบหมวดหมู่ที่ต้องการแก้ไข', null);
        }

        if ($group_name == "") {
            Response::json(0, 'กรุณากรอกชื่อหมวดหมู่', null);
        }

        try {
            $sql_update = "UPDATE tbl_course_group SET
                group_name = :group_name
                WHERE group_id = :group_id AND delete_at IS NULL;";

            $stmt_update = $pdo_connect->prepare($sql_update);
            $update = $stmt_update->execute([
                ':group_name' => $group_name,
                ':group_id'   => $group_id,
            ]);
            $stmt_update->closeCursor();

            if (!$update) {
                throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }

            Response::json(1, "แก้ไขข้อมูลสำเร็จ", null);
        } catch (Exception $e) {
            error_log("Update Category Error: " . $e->getMessage());
            Response::json(0, 'เกิดข้อผิดพลาด', null);
        } finally {
            $pdo_connect = null;
        }
    } else {
        Response::json(0, 'Unauthorized', null);
    }

?>
