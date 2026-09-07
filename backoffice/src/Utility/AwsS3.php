<?php
namespace App\Utility;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Dotenv\Dotenv;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AwsS3
{
    private static bool $envLoaded = false;

    private static function initEnv(): void
    {
        if (!self::$envLoaded) {
            $envPath = dirname(__DIR__, 2);
            if (file_exists($envPath . '/.env')) {
                Dotenv::createImmutable($envPath)->safeLoad();
            }
            self::$envLoaded = true;
        }
    }

    /**
     * ตรวจสอบว่าใช้งาน Storage แบบ Local หรือไม่
     */
    public static function isLocalDriver(): bool
    {
        self::initEnv();
        $driver = strtolower(trim((string) ($_ENV['STORAGE_DRIVER'] ?? 'local')));
        return $driver === 'local';
    }

    /**
     * โฟลเดอร์จัดเก็บไฟล์บนเครื่อง Local
     */
    public static function getLocalUploadDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upload';
    }

    /**
     * Relative Path หรือ Base URL สำหรับเข้าถึงไฟล์
     */
    public static function getStorageUrl(string $path = ''): string
    {
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
        if (self::isLocalDriver()) {
            // คืนค่าเป็น relative path (ไม่ใช้ http://) สำหรับจัดเก็บในฐานข้อมูลและแสดงผล
            $clean = preg_replace('~^(am/)?(backoffice/)?upload/~i', '', $cleanPath);
            return 'upload/' . ltrim($clean, '/');
        }

        self::initEnv();
        $baseUrl = trim((string) ($_ENV['AWS_URL'] ?? ''));
        if ($baseUrl === '') {
            $bucket = self::getBucket();
            $region = $_ENV['AWS_DEFAULT_REGION'] ?? 'ap-southeast-2';
            $baseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com";
        }
        return rtrim($baseUrl, '/') . ($cleanPath !== '' ? '/' . $cleanPath : '');
    }

    private static function getClient(): S3Client
    {
        self::initEnv();
        return new S3Client([
            'version'     => 'latest',
            'region'      => $_ENV['AWS_DEFAULT_REGION'] ?? 'ap-southeast-2',
            'credentials' => new Credentials(
                $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
                $_ENV['AWS_SECRET_ACCESS_KEY'] ?? ''
            ),
        ]);
    }

    private static function getBucket(): string
    {
        self::initEnv();
        return $_ENV['AWS_BUCKET'] ?? 'cpdth-storage';
    }

    public static function generateFileKey($folder = null, $length = 32)
    {
        $x         = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomStr = substr(str_shuffle(str_repeat($x, ceil($length / strlen($x)))), 1, $length);
        if (empty($folder)) {
            return $randomStr;
        } else {
            return trim($folder, '/\\') . "/" . $randomStr;
        }
    }

    public static function uploadFileDirectly($file, $is_public = true, $folder = null, $filename = null)
    {
        try {
            if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
                throw new Exception("Invalid file");
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

            if ($filename === null) {
                $upload_path = self::generateFileKey($folder) . "." . $ext;
            } else {
                $upload_path = (!empty($folder) ? trim($folder, '/\\') . "/" : "") . $filename . "." . $ext;
            }

            if (self::isLocalDriver()) {
                $baseDir = self::getLocalUploadDir();
                $targetFile = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload_path);
                $targetDir  = dirname($targetFile);

                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                        throw new Exception("Cannot create target upload directory: " . $targetDir);
                    }
                }

                if (is_uploaded_file($file['tmp_name'])) {
                    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
                        throw new Exception("Failed to move uploaded file");
                    }
                } else {
                    if (!copy($file['tmp_name'], $targetFile)) {
                        throw new Exception("Failed to copy file to target destination");
                    }
                }

                $dbPath = 'upload/' . $upload_path;

                return [
                    'url'       => $dbPath,
                    'path'      => $dbPath,
                    'is_public' => $is_public,
                ];
            }

            // AWS S3
            $s3Client = self::getClient();
            $bucket   = self::getBucket();

            $data = [
                'Bucket'      => $bucket,
                'Key'         => $upload_path,
                'Body'        => fopen($file['tmp_name'], 'r'),
                'ContentType' => $file['type'] ?? 'application/octet-stream',
            ];

            $result = $s3Client->putObject($data);

            return [
                'url'       => $result['ObjectURL'],
                'path'      => $upload_path,
                'is_public' => $is_public,
            ];
        } catch (Exception $exception) {
            return [
                'error'     => $exception->getMessage(),
                'url'       => null,
                'path'      => null,
                'is_public' => $is_public,
            ];
        }
    }

    public static function uploadFileByPath($path, $is_public = true, $folder = null, $filename = null)
    {
        try {
            if (!file_exists($path)) {
                throw new Exception("Source file does not exist: " . $path);
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            if ($filename === null) {
                $upload_path = self::generateFileKey($folder) . "." . $ext;
            } else {
                $upload_path = (!empty($folder) ? trim($folder, '/\\') . "/" : "") . $filename . "." . $ext;
            }

            if (self::isLocalDriver()) {
                $baseDir = self::getLocalUploadDir();
                $targetFile = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload_path);
                $targetDir  = dirname($targetFile);

                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                        throw new Exception("Cannot create target upload directory: " . $targetDir);
                    }
                }

                if (!copy($path, $targetFile)) {
                    throw new Exception("Failed to copy source file");
                }

                $dbPath = 'upload/' . $upload_path;

                return [
                    'url'       => $dbPath,
                    'path'      => $dbPath,
                    'is_public' => $is_public,
                ];
            }

            // AWS S3
            $s3Client = self::getClient();
            $data = [
                'Bucket'     => self::getBucket(),
                'Key'        => $upload_path,
                'SourceFile' => $path,
            ];

            $result = $s3Client->putObject($data);

            return [
                'url'       => $result['ObjectURL'],
                'path'      => $upload_path,
                'is_public' => $is_public,
            ];
        } catch (Exception $exception) {
            return [
                'url'       => null,
                'path'      => null,
                'is_public' => $is_public,
                'error'     => $exception->getMessage(),
            ];
        }
    }

    public static function uploadFileByPathOldName($path, $is_public = true, $folder = null, $filename = null)
    {
        try {
            if (!file_exists($path)) {
                throw new Exception("Source file does not exist: " . $path);
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            if ($filename === null) {
                $upload_path = ($folder) . "." . $ext;
            } else {
                $upload_path = (!empty($folder) ? trim($folder, '/\\') . "/" : "") . $filename . "." . $ext;
            }

            if (self::isLocalDriver()) {
                $baseDir = self::getLocalUploadDir();
                $targetFile = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload_path);
                $targetDir  = dirname($targetFile);

                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                        throw new Exception("Cannot create target upload directory: " . $targetDir);
                    }
                }

                if (!copy($path, $targetFile)) {
                    throw new Exception("Failed to copy source file");
                }

                $dbPath = 'upload/' . $upload_path;

                return [
                    'url'       => $dbPath,
                    'path'      => $dbPath,
                    'is_public' => $is_public,
                ];
            }

            // AWS S3
            $s3Client = self::getClient();
            $data = [
                'Bucket'     => self::getBucket(),
                'Key'        => $upload_path,
                'SourceFile' => $path,
            ];

            $result = $s3Client->putObject($data);

            return [
                'url'       => $result['ObjectURL'],
                'path'      => $upload_path,
                'is_public' => $is_public,
            ];
        } catch (Exception $exception) {
            return [
                'url'       => null,
                'path'      => null,
                'is_public' => $is_public,
                'error'     => $exception->getMessage(),
            ];
        }
    }

    public static function getFileUrl($path, $expire_in = '+30 minutes', $as_base64 = false)
    {
        if (empty($path)) {
            return '';
        }

        try {
            $key = self::urlToKey($path);

            // โหมดแปลงเป็น Base64
            if ($as_base64) {
                // ตรวจสอบไฟล์ในเครื่อง Local ก่อน
                $localPath = self::getLocalUploadDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
                if (is_file($localPath)) {
                    $mime = mime_content_type($localPath) ?: 'image/jpeg';
                    $data = file_get_contents($localPath);
                    return 'data:' . $mime . ';base64,' . base64_encode($data);
                }

                // หากเป็น URL เต็ม ให้ลองดาวน์โหลดเนื้อหา
                if (strpos((string) $path, 'http://') === 0 || strpos((string) $path, 'https://') === 0) {
                    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                    $data = @file_get_contents($path, false, $ctx);
                    if ($data !== false && strlen($data) > 0) {
                        return 'data:image/jpeg;base64,' . base64_encode($data);
                    }
                }
            }

            if (self::isLocalDriver()) {
                // คืน path ภายใน (ไม่ใช้ http://) เช่น upload/xxx/yyy.ext
                return 'upload/' . $key;
            }

            // AWS S3 Presigned URL
            $s3Client = self::getClient();
            $cmd      = $s3Client->getCommand('GetObject', [
                'Bucket' => self::getBucket(),
                'Key'    => $key,
            ]);
            $request = $s3Client->createPresignedRequest($cmd, $expire_in);
            $url     = (string) $request->getUri();

            if ($as_base64) {
                $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                $data = @file_get_contents($url, false, $ctx);
                if ($data !== false && strlen($data) > 0) {
                    return 'data:image/jpeg;base64,' . base64_encode($data);
                }
            }

            return $url;
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }

    public static function checkExistByBigsara($path)
    {
        try {
            $key = self::urlToKey($path);

            if (self::isLocalDriver()) {
                $localPath = self::getLocalUploadDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
                return is_file($localPath) ? "[พบ]\n" : "[ไม่พบ]\n";
            }

            $s3Client = self::getClient();
            $exists   = $s3Client->doesObjectExist(self::getBucket(), $key);
            return $exists ? "[พบ]\n" : "[ไม่พบ]\n";
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }

    public static function deleteFile($path)
    {
        try {
            $key = self::urlToKey($path);

            if (self::isLocalDriver()) {
                $localPath = self::getLocalUploadDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
                if (is_file($localPath)) {
                    @unlink($localPath);
                }
                return true;
            }

            $s3Client = self::getClient();
            $s3Client->deleteObject([
                'Bucket' => self::getBucket(),
                'Key'    => $key,
            ]);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    public static function deleteFileByURL($url)
    {
        return self::deleteFile($url);
    }

    /**
     * แปลง URL เต็ม (ทั้ง S3 และ Local) หรือ path ให้เป็น Clean Relative Key (เช่น course/abc.webp)
     */
    public static function urlToKey($url)
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }

        // หากเป็น URL เต็ม (http:// หรือ https://)
        if (preg_match('~^https?://~i', $url)) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path === null) {
                return '';
            }
            $clean = ltrim($path, '/');
            // ถอด prefix โฟลเดอร์ของแอป เช่น am/backoffice/upload/
            $clean = preg_replace('~^(am/)?(backoffice/)?upload/~i', '', $clean);
            return ltrim($clean, '/');
        }

        // หากเป็น relative path เช่น ../upload/course/... หรือ upload/course/... หรือ course/...
        $clean = ltrim(str_replace('\\', '/', $url), './');
        $clean = preg_replace('~^(am/)?(backoffice/)?upload/~i', '', $clean);
        return ltrim($clean, '/');
    }

    /**
     * list object key ทั้งหมดใต้ prefix
     */
    public static function listKeys($prefix = '')
    {
        $prefix = ltrim(str_replace('\\', '/', $prefix), '/');

        if (self::isLocalDriver()) {
            $baseDir = self::getLocalUploadDir();
            $scanDir = $baseDir . ($prefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix) : '');

            if (!is_dir($scanDir)) {
                return [];
            }

            $keys = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($scanDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $baseDirLen = strlen(rtrim($baseDir, DIRECTORY_SEPARATOR)) + 1;

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $rel = substr($file->getPathname(), $baseDirLen);
                    $keys[] = str_replace('\\', '/', $rel);
                }
            }

            return $keys;
        }

        $keys     = [];
        $s3Client = self::getClient();
        $bucket   = self::getBucket();
        $token    = null;
        do {
            $params = ['Bucket' => $bucket, 'Prefix' => $prefix];
            if ($token !== null) {
                $params['ContinuationToken'] = $token;
            }
            $result = $s3Client->listObjectsV2($params);
            foreach (($result['Contents'] ?? []) as $obj) {
                $keys[] = $obj['Key'];
            }
            $token = ($result['IsTruncated'] ?? false) ? ($result['NextContinuationToken'] ?? null) : null;
        } while ($token !== null);
        return $keys;
    }
}
