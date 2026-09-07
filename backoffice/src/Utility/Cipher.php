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
}
