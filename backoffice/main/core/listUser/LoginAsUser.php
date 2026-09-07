<?php
// ล็อกอินเข้าเว็บไซต์ (cpdth) แทนผู้ใช้ — มินต์ token ให้ผู้ใช้เป้าหมาย
// ใช้กลไกเดียวกับ login จริงของ cpdth: สร้างแถวใน tbl_login_token (token_code = jti)
// + JWT payload {jti,iat,exp} เซ็นด้วย JWT_SECRET (ตรงกับ cpdth) -> cpdth ตรวจผ่าน

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;
use Firebase\JWT\JWT;

$access_token = Auth::requireUserToken();   // ต้องเป็นแอดมินที่ล็อกอินหลังบ้านอยู่
$admin_id = $access_token->user_id ?? null;
if (!$admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($user_id <= 0) {
    Response::json(0, 'ไม่พบผู้ใช้', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (!$pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// ตรวจผู้ใช้เป้าหมาย (ต้องมีอยู่จริง ไม่ถูกลบ และไม่ถูกระงับ)
$st = $pdo_connect->prepare("SELECT user_id, user_status FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1");
$st->execute([':id' => $user_id]);
$u = $st->fetch(PDO::FETCH_ASSOC);
$st->closeCursor();
if (!$u) {
    Response::json(0, 'ไม่พบผู้ใช้นี้ หรือถูกลบไปแล้ว', null);
}
if ((int) $u['user_status'] !== 1) {
    Response::json(0, 'บัญชีผู้ใช้นี้ถูกระงับ ไม่สามารถเข้าสู่ระบบแทนได้', null);
}

// JWT_SECRET (Auth::requireUserToken โหลด .env ให้แล้ว; เผื่อไว้โหลดซ้ำ)
$secret = $_ENV['JWT_SECRET'] ?? '';
if ($secret === '') {
    \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();
    $secret = $_ENV['JWT_SECRET'] ?? '';
}
if ($secret === '') {
    Response::json(0, 'ไม่สามารถสร้างโทเค็นได้ (JWT_SECRET ไม่พบ)', null);
}

try {
    $now = time();
    $ttl = 25200; // 7 ชั่วโมง (เท่า login ปกติของ cpdth)
    $jti = bin2hex(random_bytes(16));

    // สร้าง login token ให้ผู้ใช้ (ไม่ปิด token เดิมของผู้ใช้ กันไปเตะเซสชันจริงของเขา)
    $ins = $pdo_connect->prepare(
        "INSERT INTO tbl_login_token SET
            token_code      = :t,
            user_id         = :u,
            create_datetime = :c,
            expire_datetime = :e,
            ip_address      = :ip,
            user_agent      = :ua"
    );
    $ins->execute([
        ':t'  => $jti,
        ':u'  => $user_id,
        ':c'  => date('Y-m-d H:i:s', $now),
        ':e'  => date('Y-m-d H:i:s', $now + $ttl),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => trim(($_SERVER['HTTP_USER_AGENT'] ?? '') . ' [impersonated by admin#' . $admin_id . ']'),
    ]);
    $ins->closeCursor();

    $jwt = JWT::encode(['jti' => $jti, 'iat' => $now, 'exp' => $now + $ttl], $secret, 'HS256');

    Response::json(1, 'สำเร็จ', ['token' => $jwt]);

} catch (\Throwable $e) {
    error_log('LoginAsUser Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
