<?php

namespace App\Utility;

use Mpdf\Mpdf;

/**
 * ตัวช่วยสร้าง PDF กลาง (mPDF) — ใช้ฟอนต์ไทย A4
 * ใช้สำหรับเอกสารทางการ เช่น ใบรับรองผลการสอบ / ใบกำกับภาษี
 */
class Pdf
{
    /** สร้าง PDF แล้วคืนเป็น string (binary) */
       public static function make(string $html, array $opts = []): string
    {
        $fontDir = realpath(dirname(__DIR__, 2) . '/assets/fonts/sarabun') ?: (dirname(__DIR__, 2) . '/assets/fonts/sarabun');
        
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData          = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
    'mode'              => 'utf-8',
    'format'            => $opts['format'] ?? 'Letter',
    'orientation'       => $opts['orientation'] ?? 'P',

    'tempDir'           => realpath(dirname(__DIR__, 2) . '/tmp')
        ?: (dirname(__DIR__, 2) . '/tmp'),

    'fontDir'           => array_merge($fontDirs, [$fontDir]),

    'fontdata'          => $fontData + [
        'thsarabun' => [
            'R'  => 'THSarabunNew.ttf',
            'B'  => 'THSarabunNew Bold.ttf',
            'I'  => 'THSarabunNew Italic.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf',
        ],
    ],

    'default_font' => $opts['font'] ?? 'thsarabun',

    'margin_left'   => $opts['margin_left'] ?? 15,
    'margin_right'  => $opts['margin_right'] ?? 15,
    'margin_top'    => $opts['margin_top'] ?? 15,
    'margin_bottom' => $opts['margin_bottom'] ?? 15,

    'autoScriptToLang' => false,
    'useOTL'           => 0xFF,
]);
        $mpdf->SetTitle($opts['title'] ?? 'Document');

        if (!empty($opts['password'])) {
            $mpdf->SetProtection(['print', 'copy'], (string) $opts['password']);
        }

        // แปลงเว้นวรรคพิเศษ (nbsp และตัวตัดคำล่องหน) ให้เป็นเว้นวรรคธรรมดา เพื่อแก้ปัญหากล่องสี่เหลี่ยมใน THSarabunNew
        $html = str_replace(["\xc2\xa0", "&nbsp;", "\xe2\x80\x8b"], [" ", " ", ""], $html);

        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }

    /**
     * ส่ง PDF ออกทาง HTTP
     * @param bool $inline true = เปิดใน viewer ของเบราว์เซอร์, false = บังคับดาวน์โหลด
     */
    public static function stream(string $html, string $filename, bool $inline = true, array $opts = []): void
    {
        if (!isset($opts['title'])) {
            $opts['title'] = pathinfo($filename, PATHINFO_FILENAME);
        }
        $pdf = self::make($html, $opts);

        // เคลียร์ output ที่อาจหลุดมาก่อนหน้า (กัน PDF เสีย)
        while (ob_get_level() > 0) { @ob_end_clean(); }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdf;
        exit;
    }

    /** แปลงรูปไฟล์ในเครื่องเป็น data URI (ให้ mPDF ฝังรูปได้ชัวร์) */
    public static function fileToDataUri(string $absPath): string
    {
        if ($absPath === '' || !is_file($absPath)) { return ''; }
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
        $bin = @file_get_contents($absPath);
        if ($bin === false) { return ''; }
        return 'data:' . $mime . ';base64,' . base64_encode($bin);
    }
}


