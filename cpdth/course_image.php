<?php
$img = isset($_GET['img']) ? $_GET['img'] : '';
$img = basename($img);
$path = __DIR__ . '/../backoffice/upload/course/' . $img;

if ($img && file_exists($path)) {
    $mime = mime_content_type($path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    // Cache for 30 days
    header('Cache-Control: max-age=2592000, public');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
    readfile($path);
    exit;
} else {
    http_response_code(404);
    exit;
}
