<?php
namespace App\Utility;

use App\Database\Connection;
use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use Throwable;

class Auth
{

    public static function bearerToken(): string
    {

        $header = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {

            $header = $_SERVER['HTTP_AUTHORIZATION'];

        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {

            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

        } elseif (function_exists('getallheaders')) {

            foreach (getallheaders() as $name => $value) {

                if (strcasecmp($name, 'Authorization') === 0) {

                    $header = $value;

                    break;

                }

            }

        }

        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {

            return trim($matches[1]);

        }

        // สำรอง 1: เช็คจาก $_POST / $_GET / $_REQUEST ('access_token')
        $paramToken = $_POST['access_token'] ?? $_GET['access_token'] ?? $_REQUEST['access_token'] ?? '';

        if ($paramToken !== '') {

            if (preg_match('/Bearer\s+(\S+)/i', $paramToken, $matches)) {

                return trim($matches[1]);

            }

            return trim($paramToken);

        }

        // สำรอง 2: เช็คจาก Cookie ('bo_access_token' หรือ 'access_token') กันกรณี Web Server ดรอป Header ตอนรีหน้ารัวๆ
        $cookieToken = $_COOKIE['bo_access_token'] ?? $_COOKIE['access_token'] ?? '';

        if ($cookieToken !== '') {

            if (preg_match('/Bearer\s+(\S+)/i', $cookieToken, $matches)) {

                return trim($matches[1]);

            }

            return trim($cookieToken);

        }

        return '';

    }

    public static function requireUserToken(): object
    {

        $jwt = self::bearerToken();

        if ($jwt === '') {
            file_put_contents(dirname(__DIR__, 2) . '/debug_auth.log', "[" . date('Y-m-d H:i:s') . "] No JWT token found in headers.\n", FILE_APPEND);
            Response::json(0, 'Unauthorized', null);

        }

        static $envLoaded = false;

        if (! $envLoaded) {

            Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

            $envLoaded = true;

        }

        $secretKey = $_ENV['JWT_SECRET'] ?? '';

        if ($secretKey === '') {
            file_put_contents(dirname(__DIR__, 2) . '/debug_auth.log', "[" . date('Y-m-d H:i:s') . "] JWT_SECRET not found in env.\n", FILE_APPEND);
            Response::json(0, 'Secret key not found', null);

        }

        try {

            $token = JWT::decode($jwt, new Key($secretKey, 'HS256'));

        } catch (Throwable $exception) {
            file_put_contents(dirname(__DIR__, 2) . '/debug_auth.log', "[" . date('Y-m-d H:i:s') . "] JWT decode failed: " . $exception->getMessage() . " Token: " . $jwt . "\n", FILE_APPEND);
            Response::json(0, 'Invalid token', null);

        }

        if (($token->exp ?? 0) < time()) {
            file_put_contents(dirname(__DIR__, 2) . '/debug_auth.log', "[" . date('Y-m-d H:i:s') . "] Token expired. Exp: " . ($token->exp ?? 0) . " vs Now: " . time() . "\n", FILE_APPEND);
            Response::json(0, 'Token expired', null);

        }

        $access_token = self::ensureActiveUser($token);

        return $access_token;

    }

    private static function ensureActiveUser(object $token): object
    {

        if (empty($token->jti)) {
            file_put_contents(dirname(__DIR__, 2) . '/debug_auth.log', "[" . date('Y-m-d H:i:s') . "] Token jti is empty.\n", FILE_APPEND);
            Response::json(0, 'Invalid token', null);

        }

        $db = (new Connection())->getPdo();

        $sql = "SELECT u.user_id, u.user_firstname, u.user_lastname, u.user_email, NULL AS profile_image, NULL AS n_name, 'ผู้ดูแลระบบ' AS access_level, u.is_super_admin

                FROM tbl_login_token lt

                JOIN tbl_user u ON lt.user_id = u.user_id

                WHERE lt.token_code = :token_code AND u.user_status = 1 AND lt.end_datetime IS NULL AND lt.expire_datetime > NOW()

                LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->execute([

            ':token_code' => $token->jti ?? '',

        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $row) {
            // Let's run diagnostic queries to find why it failed
            $diag_sql = "SELECT lt.token_code, lt.user_id, lt.end_datetime, lt.expire_datetime, NOW() as db_now, u.user_status
                         FROM tbl_login_token lt
                         LEFT JOIN tbl_user u ON lt.user_id = u.user_id
                         WHERE lt.token_code = :token_code";
            $diag_stmt = $db->prepare($diag_sql);
            $diag_stmt->execute([':token_code' => $token->jti ?? '']);
            $diag_row = $diag_stmt->fetch(PDO::FETCH_ASSOC);
            
            $log_msg = "[" . date('Y-m-d H:i:s') . "] ensureActiveUser failed. Token JTI: " . ($token->jti ?? 'empty') . "\n";
            if ($diag_row) {
                $log_msg .= "Diagnostic Row: " . json_encode($diag_row) . "\n";
            } else {
                $log_msg .= "No token found in tbl_login_token matching JTI.\n";
            }
            setcookie('bo_access_token', '', time() - 3600, '/');
            Response::json(0, 'User revoked', null);

        }

        $fullname = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        if ($fullname === '') {
            $fullname = $row['user_email'] ?? 'Admin';
        }

        $is_super = (int) ($row['is_super_admin'] ?? 0);
        $role_name = ($is_super === 1) ? 'Super Admin' : ($row['access_level'] ?? 'ผู้ดูแลระบบ');

        $payload = [

            'user_id'       => $row['user_id'],

            'fullname'      => $fullname,

            'profile_image' => $row['profile_image'],

            'n_name'        => $row['n_name'],

            'access_level'  => $role_name,

            'is_super_admin'=> $is_super,

        ];

        return (object) $payload;

    }

}

