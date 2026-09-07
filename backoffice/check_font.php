<?php
$fontDir = realpath(__DIR__ . '/assets/fonts/sarabun');
echo "fontDir: $fontDir\n";
echo 'R: ' . (file_exists($fontDir . '/THSarabunNew.ttf') ? 'OK' : 'MISSING') . "\n";
echo 'B: ' . (file_exists($fontDir . '/THSarabunNew Bold.ttf') ? 'OK' : 'MISSING') . "\n";
echo 'I: ' . (file_exists($fontDir . '/THSarabunNew Italic.ttf') ? 'OK' : 'MISSING') . "\n";
echo 'BI: ' . (file_exists($fontDir . '/THSarabunNew BoldItalic.ttf') ? 'OK' : 'MISSING') . "\n";
echo "\ntmp path: " . realpath(__DIR__ . '/tmp') . "\n";
echo 'cache thsarabun.mtx.json: ' . (file_exists(__DIR__ . '/tmp/mpdf/ttfontdata/thsarabun.mtx.json') ? 'OK' : 'MISSING') . "\n";
