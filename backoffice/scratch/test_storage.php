<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utility\AwsS3;
use App\Database\Connection;

echo "=== 1. Testing Storage Driver Mode ===\n";
$isLocal = AwsS3::isLocalDriver();
echo "Is Local Driver: " . ($isLocal ? "YES" : "NO") . "\n";
echo "Storage URL base: " . AwsS3::getStorageUrl() . "\n";
echo "Local Upload Dir: " . AwsS3::getLocalUploadDir() . "\n\n";

echo "=== 2. Testing File Upload (By Path) ===\n";
$tempFile = __DIR__ . '/test_sample.txt';
file_put_contents($tempFile, 'Hello CPDTH Local Storage Demo! ' . date('Y-m-d H:i:s'));

$res = AwsS3::uploadFileByPath($tempFile, true, 'demo_test', 'test_file');
echo "Upload Result:\n";
print_r($res);

if (!empty($res['url'])) {
    echo "Uploaded URL: " . $res['url'] . "\n";
    echo "Clean Key: " . AwsS3::urlToKey($res['url']) . "\n";
    echo "Check Exist: " . AwsS3::checkExistByBigsara($res['path']);
    echo "Get File URL: " . AwsS3::getFileUrl($res['path']) . "\n";
    echo "Get File as Base64: " . substr(AwsS3::getFileUrl($res['path'], '+30 min', true), 0, 50) . "...\n";
    
    echo "List Keys in demo_test:\n";
    print_r(AwsS3::listKeys('demo_test'));

    echo "Deleting file...\n";
    $del = AwsS3::deleteFileByURL($res['url']);
    echo "Deleted: " . ($del ? "YES" : "NO") . "\n";
    echo "Check Exist After Delete: " . AwsS3::checkExistByBigsara($res['path']);
}

@unlink($tempFile);

echo "\n=== 3. Testing Database Connection ===\n";
try {
    $db = new Connection();
    $pdo = $db->getPdo();
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Database Connected successfully! Total tables found: " . count($tables) . "\n";
} catch (\Throwable $e) {
    echo "Database Notice: " . $e->getMessage() . "\n";
}
