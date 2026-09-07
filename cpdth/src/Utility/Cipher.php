<?php
namespace App\Utility;

class Cipher
{
    private static $method = 'AES-128-ECB';
    private static $key = 'CPDTH_SECURE_SALT_2026';

    /**
     * Encrypt an integer ID into a URL-safe base64 string.
     *
     * @param int|string $id
     * @return string
     */
    public static function encrypt($id)
    {
        $encrypted = openssl_encrypt((string)$id, self::$method, self::$key);
        if ($encrypted === false) {
            return '';
        }
        return str_replace(['+', '/', '='], ['-', '_', ''], $encrypted);
    }

    /**
     * Decrypt a URL-safe base64 string back into an integer ID.
     *
     * @param string $token
     * @return int
     */
    public static function decrypt($token)
    {
        if (empty($token)) {
            return 0;
        }
        $data = str_replace(['-', '_'], ['+', '/'], $token);
        $decrypted = openssl_decrypt($data, self::$method, self::$key);
        return $decrypted !== false ? (int)$decrypted : 0;
    }

    /**
     * Decrypt a URL-safe base64 string back into a raw string.
     *
     * @param string $token
     * @return string
     */
    public static function decryptString($token)
    {
        if (empty($token)) {
            return '';
        }
        $data = str_replace(['-', '_'], ['+', '/'], $token);
        $decrypted = openssl_decrypt($data, self::$method, self::$key);
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Encrypt a lesson ID mixed with a user ID to prevent link sharing.
     */
    public static function encryptLessonKey($lesson_id, $user_id)
    {
        $data = $lesson_id . '|' . $user_id;
        return self::encrypt($data);
    }

    /**
     * Decrypt a lesson key and verify it belongs to the given user ID.
     */
    public static function decryptLessonKey($token, $user_id)
    {
        $decrypted = self::decryptString($token);
        if (empty($decrypted)) {
            return 0;
        }
        $parts = explode('|', $decrypted);
        if (count($parts) !== 2) {
            // It might be an old format key (just lesson_id)
            // But since we want to enforce new security, we can block it or fallback.
            // Let's block it for full security.
            return 0;
        }
        if ((int)$parts[1] !== (int)$user_id) {
            return 0; // User mismatch
        }
        return (int)$parts[0];
    }
}
