<?php
// ล้าง font cache ทั้งหมด และ build ใหม่ผ่าน Apache (PHP เดียวกับที่ใช้ generate PDF)
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$tempDir = realpath(dirname(__DIR__, 2) . '/tmp');
$cacheDir = $tempDir . '/mpdf/ttfontdata';

// ลบ cache ทั้งหมด
$deleted = 0;
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*') as $file) {
        if (is_file($file)) { unlink($file); $deleted++; }
    }
}
echo "Deleted $deleted cache files\n";

// Build ใหม่
$fontDir = realpath(dirname(__DIR__, 2) . '/assets/fonts/sarabun');
$defaultConfig     = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

$html = '<html><head><meta charset="UTF-8"><style>body,p{font-family:thsarabun,sans-serif;}</style></head><body>
  <p>Regular: ทดสอบ TH Sarabun New ภาษาไทย</p>
  <p><b>Bold: หนังสือรับรอง</b></p>
  <p><i>Italic: ทดสอบ</i></p>
  <p><b><i>BoldItalic: ทดสอบ</i></b></p>
</body></html>';

$mpdf = new \Mpdf\Mpdf([
    'mode'             => 'utf-8',
    'tempDir'          => $tempDir,
    'fontDir'          => array_merge($defaultConfig['fontDir'], [$fontDir]),
    'fontdata'         => $defaultFontConfig['fontdata'] + [
        'thsarabun' => [
            'R'      => 'THSarabunNew.ttf',
            'B'      => 'THSarabunNew Bold.ttf',
            'I'      => 'THSarabunNew Italic.ttf',
            'BI'     => 'THSarabunNew BoldItalic.ttf',
            'useOTL' => 0xFF,
        ],
    ],
    'default_font'     => 'thsarabun',
    'autoScriptToLang' => true,
]);
$mpdf->WriteHTML($html);
$pdf = $mpdf->Output('', 'S');

// ตรวจสอบ
preg_match_all('/\/BaseFont\s+\/(\S+)/', $pdf, $m);
$fonts = array_unique($m[1] ?? []);

echo "PDF size: " . number_format(strlen($pdf)) . " bytes\n";
echo "Fonts embedded: " . implode(', ', $fonts) . "\n\n";

echo "Cache files rebuilt:\n";
foreach (glob($cacheDir . '/thsarabun*') as $f) {
    echo "  " . basename($f) . "\n";
}
