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

        // คืนค่าสำรองจาก cookie หากไม่มี header ส่งมา (เช่นการโหลดหน้า PHP ปกติ)
        if (isset($_COOKIE['access_token'])) {
            return $_COOKIE['access_token'];
        }

        return '';
    }

    public static function requireUserToken(): object
    {
        $jwt = self::bearerToken();

        if ($jwt === '') {
            Response::json(0, 'Unauthorized', null);
        }

        static $envLoaded = false;

        if (! $envLoaded) {
            Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
            $envLoaded = true;
        }

        $secretKey = $_ENV['JWT_SECRET'] ?? '';

        if ($secretKey === '') {
            Response::json(0, 'Secret key not found', null);
        }

        try {
            $token = JWT::decode($jwt, new Key($secretKey, 'HS256'));
        } catch (Throwable $exception) {
            Response::json(0, 'Invalid token', null);
        }

        if (($token->exp ?? 0) < time()) {
            Response::json(0, 'Token expired', null);
        }

        $access_token = self::ensureActiveUser($token);

        return $access_token;
    }

    private static function ensureActiveUser(object $token): object
    {
        if (empty($token->jti)) {
            Response::json(0, 'Invalid token', null);
        }

        $db = (new Connection())->getPdo();

        $sql = "SELECT u.user_id, u.user_firstname, u.user_lastname, u.user_status, u.user_email
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
            setcookie('access_token', '', time() - 3600, '/');
            Response::json(0, 'User revoked', null);
        }

        $updateSql = "UPDATE tbl_login_token SET last_active_at = NOW() WHERE token_code = :token_code";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([':token_code' => $token->jti]);

        $payload = [
            'user_id'        => $row['user_id'],
            'user_firstname' => $row['user_firstname'],
            'user_lastname'  => $row['user_lastname'],
            'user_email'     => $row['user_email'],
        ];

        return (object) $payload;
    }

    public static function getUser(): ?object
    {
        $jwt = self::bearerToken();

        if ($jwt === '') {
            return null;
        }

        static $envLoaded = false;
        if (! $envLoaded) {
            Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
            $envLoaded = true;
        }

        $secretKey = $_ENV['JWT_SECRET'] ?? '';
        if ($secretKey === '') {
            return null;
        }

        try {
            $token = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            if (($token->exp ?? 0) < time()) {
                return null;
            }

            if (empty($token->jti)) {
                return null;
            }

            $db  = (new Connection())->getPdo();
            $sql = "SELECT u.user_id, u.user_firstname, u.user_lastname, u.user_email
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
                return null;
            }
            
            $updateSql = "UPDATE tbl_login_token SET last_active_at = NOW() WHERE token_code = :token_code";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':token_code' => $token->jti]);

            return (object) $row;
        } catch (Throwable $exception) {
            return null;
        }
    }
}
