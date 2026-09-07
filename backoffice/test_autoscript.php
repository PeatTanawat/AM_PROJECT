<?php
// ทดสอบ: autoScriptToLang true vs false — ดูว่า font ที่ฝังต่างกันไหม
require_once __DIR__ . '/vendor/autoload.php';

$fontDir = realpath(__DIR__ . '/assets/fonts/sarabun');
$tempDir = realpath(__DIR__ . '/tmp');

$defaultConfig     = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

$base = [
    'mode'         => 'utf-8',
    'tempDir'      => $tempDir,
    'fontDir'      => array_merge($defaultConfig['fontDir'], [$fontDir]),
    'fontdata'     => $defaultFontConfig['fontdata'] + [
        'thsarabun' => [
            'R'      => 'THSarabunNew.ttf',
            'B'      => 'THSarabunNew Bold.ttf',
            'I'      => 'THSarabunNew Italic.ttf',
            'BI'     => 'THSarabunNew BoldItalic.ttf',
            'useOTL' => 0xFF,
        ],
    ],
    'default_font' => 'thsarabun',
];

$html = '<html><head><meta charset="UTF-8"><style>body,p,td,div{font-family:thsarabun,sans-serif;}</style></head>
<body>
  <p>ภาษาไทย: ทดสอบ TH Sarabun New</p>
  <p>Mixed: นับชั่วโมงด้าน 40.00 % CPD e-Learning</p>
  <p><b>Bold ไทย: หนังสือรับรอง</b></p>
</body></html>';

foreach ([true, false] as $autoScript) {
    $opts = $base + ['autoScriptToLang' => $autoScript];
    $mpdf = new \Mpdf\Mpdf($opts);
    $mpdf->WriteHTML($html);
    $pdf = $mpdf->Output('', 'S');

    preg_match_all('/\/BaseFont\s+\/(\S+)/', $pdf, $m);
    $fonts = array_unique($m[1] ?? []);
    echo "autoScriptToLang=" . ($autoScript ? 'true' : 'false') . " => fonts: " . implode(', ', $fonts) . "\n";
}
