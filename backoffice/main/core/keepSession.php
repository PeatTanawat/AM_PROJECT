<?php
    $projectRoot = dirname(__DIR__, 2);
    require_once $projectRoot . '/vendor/autoload.php';

    use Dotenv\Dotenv;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    use Throwable;
    use App\Utility\Auth;
    use App\Utility\Response;

    header('Content-Type: application/json; charset=utf-8');

    $result = 0;
    $accessToken = Auth::bearerToken();

    if ($accessToken !== '') {
        Dotenv::createImmutable($projectRoot)->safeLoad();
        $secretKey = $_ENV['JWT_SECRET'] ?? '';

        if ($secretKey !== '') {
            try {
                $decodedToken = JWT::decode($accessToken, new Key($secretKey, 'HS256'));
                if (($decodedToken->exp ?? 0) >= time()) {
                    $result = 1;
                }
            } catch (Throwable $exception) {
                $result = 0;
            }
        }
    }

    // web-cron: เช็ค/ส่งเมลแจ้งเตือนบัตรประชาชนหมดอายุ วันละครั้ง (เฉพาะ session ที่ใช้งานได้)
    // เช็ค marker ก่อน ถ้าวันนี้รันแล้วจะจบเร็ว ไม่ต่อ DB; ไม่โยน error ออกมา (กันพัง keepalive)
    if ($result === 1) {
        \App\Utility\ExpiredIdCardNotifier::runDailyIfDue($projectRoot . '/upload');
    }

    Response::json($result, $result === 1 ? 'OK' : 'Session expired', ['result' => $result]);
?>
