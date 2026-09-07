<?php
// ตรวจสอบ environment ที่ Apache ใช้งานจริง
echo "PHP version: " . phpversion() . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "php.ini: " . php_ini_loaded_file() . "\n";
echo "\n--- tempDir resolution ---\n";
$tempDir = realpath(dirname(__DIR__, 2) . '/tmp');
echo "dirname(__DIR__, 2): " . dirname(__DIR__, 2) . "\n";
echo "__DIR__ = " . __DIR__ . "\n";
echo "tempDir realpath: " . ($tempDir ? $tempDir : 'FAIL - path not found!') . "\n";
echo "\n--- fontDir resolution ---\n";
$fontDir = realpath(dirname(__DIR__, 2) . '/assets/fonts/sarabun');
echo "fontDir realpath: " . ($fontDir ? $fontDir : 'FAIL - path not found!') . "\n";
echo "THSarabunNew.ttf: " . (file_exists($fontDir . '/THSarabunNew.ttf') ? 'OK' : 'MISSING') . "\n";
echo "\n--- tmp writable ---\n";
$tmpMpdf = dirname(__DIR__, 2) . '/tmp/mpdf/ttfontdata';
echo "ttfontdata exists: " . (is_dir($tmpMpdf) ? 'YES' : 'NO') . "\n";
echo "ttfontdata writable: " . (is_writable($tmpMpdf) ? 'YES' : 'NO') . "\n";
echo "thsarabun.mtx.json: " . (file_exists($tmpMpdf . '/thsarabun.mtx.json') ? 'OK' : 'MISSING') . "\n";
echo "thsarabunB.mtx.json: " . (file_exists($tmpMpdf . '/thsarabunB.mtx.json') ? 'OK' : 'MISSING') . "\n";
