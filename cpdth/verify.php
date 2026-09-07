<?php
    require_once __DIR__ . '/vendor/autoload.php';

    use App\Database\Connection;
    use Dotenv\Dotenv;
    use Firebase\JWT\JWT;

    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();



    $token    = isset($_GET['token']) ? trim($_GET['token']) : '';
    $verified = false;
    $message  = '';

    if (empty($token)) {
    $message = 'ไม่พบรหัสโทเค็นสำหรับยืนยันตัวตน';
    } else {
    try {
        $db_instance = new Connection();
        $pdo_connect = $db_instance->getPdo();

        if ($pdo_connect) {
            // 1. ค้นหาบัญชีผู้ใช้ที่มี verification_token ตรงกัน
            $sql_check  = "SELECT * FROM tbl_user WHERE verification_token = :token AND delete_at IS NULL LIMIT 1";
            $stmt_check = $pdo_connect->prepare($sql_check);
            $stmt_check->execute([':token' => $token]);
            $row_user = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $stmt_check->closeCursor();

            if ($row_user) {
                // 2. อัปเดตสถานะ user_status = 1, email_status = 1 และเคลียร์ verification_token
                $sql_update = "UPDATE tbl_user SET
                    user_status = 1,
                    email_status = 1,
                    verification_token = NULL
                    WHERE user_id = :user_id";
                $stmt_update   = $pdo_connect->prepare($sql_update);
                $update_result = $stmt_update->execute([':user_id' => $row_user['user_id']]);
                $stmt_update->closeCursor();

                if ($update_result) {
                    // 3. ทำการเข้าสู่ระบบแบบอัตโนมัติ (Auto Login)
                    $now       = time();
                    $createdAt = date('Y-m-d H:i:s', $now);
                    $expiresAt = date('Y-m-d H:i:s', $now + 86400);
                    $ip        = $_SERVER['REMOTE_ADDR'] ?? null;
                    $agent     = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $jti       = bin2hex(random_bytes(16));

                    // ปิด Token เก่าที่ยังไม่หมดอายุ
                    $sql_edit = "UPDATE tbl_login_token SET
                        end_datetime = :end_datetime
                        WHERE user_id = :user_id AND end_datetime IS NULL";
                    $stmt_edit = $pdo_connect->prepare($sql_edit);
                    $stmt_edit->bindValue(":user_id", $row_user['user_id'], PDO::PARAM_STR);
                    $stmt_edit->bindValue(":end_datetime", $createdAt, PDO::PARAM_STR);
                    $stmt_edit->execute();
                    $stmt_edit->closeCursor();

                    // บันทึก Token ใหม่
                    $sql_insert = "INSERT INTO tbl_login_token SET
                        token_code = :token_code,
                        user_id = :user_id,
                        create_datetime = :create_datetime,
                        expire_datetime = :expire_datetime,
                        ip_address = :ip_address,
                        user_agent = :user_agent";
                    $stmt_insert = $pdo_connect->prepare($sql_insert);
                    $stmt_insert->bindValue(":token_code", $jti, PDO::PARAM_STR);
                    $stmt_insert->bindValue(":user_id", $row_user['user_id'], PDO::PARAM_STR);
                    $stmt_insert->bindValue(":create_datetime", $createdAt, PDO::PARAM_STR);
                    $stmt_insert->bindValue(":expire_datetime", $expiresAt, PDO::PARAM_STR);
                    $stmt_insert->bindValue(":ip_address", $ip, PDO::PARAM_STR);
                    $stmt_insert->bindValue(":user_agent", $agent, PDO::PARAM_STR);
                    $stmt_insert->execute();
                    $stmt_insert->closeCursor();

                    // สร้าง JWT
                    $payload = [
                        'jti' => $jti,
                        'iat' => $now,
                        'exp' => $now + 86400,
                    ];
                    $secret = $_ENV['JWT_SECRET'] ?? '';

                    if (! empty($secret)) {
                        $jwt_token                = JWT::encode($payload, $secret, 'HS256');
                        setcookie('access_token', $jwt_token, [
                            'expires' => time() + 25200,
                            'path' => '/',
                            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                            'httponly' => false,
                            'samesite' => 'Lax'
                        ]);
                        $verified                 = true;
                        $message                  = 'ยืนยันอีเมลสำเร็จ ระบบกำลังนำคุณเข้าสู่เว็บไซต์...';
                    } else {
                        $message = 'เกิดข้อผิดพลาดในการสร้างเซสชันสำหรับเข้าสู่ระบบอัตโนมัติ';
                    }
                } else {
                    $message = 'ไม่สามารถอัปเดตข้อมูลการยืนยันอีเมลได้';
                }
            } else {
                $message = 'โทเค็นยืนยันตัวตนไม่ถูกต้อง หรืออาจจะหมดอายุการใช้งานแล้ว';
            }
        } else {
            $message = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้';
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาดในการประมวลผลระบบ: ' . $e->getMessage();
    }
    }

$pageTitle = 'ยืนยันตัวตนสำเร็จ';
include 'components/header.php';
?>


<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8fafc;
        margin: 0;
        padding: 0;
    }
    .verify-page-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 350px);
        padding: 150px 20px 80px;
    }
</style>

<div class="verify-page-wrapper">
    <!-- หน้าเว็บจะแสดง SweetAlert อัตโนมัติและนำทางผู้ใช้ -->
</div>

<script>
$(document).ready(function() {
    const isVerified = <?php echo $verified ? 'true' : 'false' ?>;
    const msg = "<?php echo addslashes($message) ?>";
    const jwt_token = "<?php echo isset($jwt_token) ? $jwt_token : '' ?>";

    if (isVerified) {
        if (jwt_token) {
            localStorage.setItem('access_token', jwt_token);
            document.cookie = "access_token=" + jwt_token + "; path=/; max-age=25200; SameSite=Lax";
        }
        Swal.fire({
            title: "ยินยันตัวตนสำเร็จ",
            html: '<span class="fw-bold text-success">' + msg + '</span>',
            icon: "success",
            showConfirmButton: false,
            allowOutsideClick: false,
            timer: 2500,
            timerProgressBar: true,
            didClose: () => {
                window.location.replace("index");
            }
        });
    } else {
        Swal.fire({
            title: "เกิดข้อผิดพลาด",
            html: '<span class="fw-bold text-danger">' + msg + '</span>',
            icon: "error",
            confirmButtonText: "ตกลง",
            allowOutsideClick: false,
            didClose: () => {
                window.location.replace("login");
            }
        });
    }
});
</script>

<?php include 'components/footer.php'; ?>
