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
        $group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';

        if ($group_name == "") {
            Response::json(0, 'กรุณากรอกชื่อหมวดหมู่', null);
        }

        try {
            $sql_insert = "INSERT INTO tbl_course_group SET
                group_name = :group_name;";
            
            $stmt_insert = $pdo_connect->prepare($sql_insert);
            $insert = $stmt_insert->execute([
                ':group_name' => $group_name,
            ]);
            $stmt_insert->closeCursor();

            if (!$insert) {
                throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }

            Response::json(1, "บันทึกข้อมูลสำเร็จ", null);
        } catch (Exception $e) {
            error_log("Save Category Error: " . $e->getMessage());
            Response::json(0, 'เกิดข้อผิดพลาด', null);
        } finally {
            $pdo_connect = null;
        }
    } else {
        Response::json(0, 'Unauthorized', null);
    }

?>