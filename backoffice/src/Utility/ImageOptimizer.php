<?php

namespace App\Utility;

/**
 * ImageOptimizer — แปลงไฟล์รูปที่ผู้ใช้อัปโหลด (ใน $_FILES) ให้เป็น WebP ก่อนนำไปอัปโหลดจริง
 *
 * ใช้กับ "ช่องที่เป็นรูปภาพล้วน" เท่านั้น (banner / course / review / logo / exam_image / question_image)
 * เรียกก่อนบรรทัด AwsS3::uploadFileDirectly(...) แล้วปล่อยให้ตัวอัปโหลดทำงานต่อตามเดิม
 * (อย่าใช้กับช่องเอกสาร เช่น lesson_file / exam_file / question_file เพราะอาจเป็น pdf/doc/zip)
 *
 * พฤติกรรม:
 *  - รองรับแปลง JPEG และ PNG (คงพื้นโปร่งใสของ PNG ไว้) ให้เป็น WebP
 *  - ข้าม GIF (กันภาพเคลื่อนไหวเสียเหลือเฟรมเดียว) และไฟล์ที่เป็น WebP อยู่แล้ว
 *  - ถ้าแปลงไม่ได้ด้วยเหตุใดก็ตาม จะ "ไม่แตะไฟล์เดิม" -> อัปโหลดของเดิมต่อได้ตามปกติ (ฟีเจอร์ไม่พัง)
 *  - เมื่อแปลงสำเร็จจะอัปเดต name (.webp) / type (image/webp) / size ใน $_FILES ให้เอง
 *    เพื่อให้ปลายทาง (S3 key ลงท้าย .webp และ ContentType) ถูกต้อง
 */
class ImageOptimizer
{
    /** คุณภาพเริ่มต้นของ WebP (0-100) — 82 คือจุดสมดุลระหว่างคุณภาพกับขนาดไฟล์ */
    public const DEFAULT_QUALITY = 82;

    /** กันหน่วยความจำระเบิดกับรูปใหญ่ผิดปกติ (ข้ามการแปลงถ้าเกิน ~40 ล้านพิกเซล) */
    private const MAX_PIXELS = 40000000;

    /**
     * แปลงไฟล์รูปในช่อง $_FILES[$field] ให้เป็น WebP แบบแก้ไขในตัว (in place)
     *
     * @param string $field   ชื่อ key ใน $_FILES เช่น 'banner_image'
     * @param int    $quality คุณภาพ WebP 0-100
     * @return bool true = แปลงสำเร็จและอัปเดต $_FILES แล้ว, false = ไม่ได้แปลง (คงไฟล์เดิม)
     */
    public static function toWebp(string $field, int $quality = self::DEFAULT_QUALITY): bool
    {
        // ต้องมี GD ที่รองรับ WebP ก่อน
        if (!function_exists('imagewebp') || !function_exists('getimagesize')) {
            return false;
        }

        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return false;
        }
        $file = &$_FILES[$field];

        // ต้องเป็นไฟล์ที่อัปโหลดมาจริงและไม่มี error เท่านั้น
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            return false;
        }

        // อ่านชนิดจากเนื้อไฟล์จริง (ไม่เชื่อนามสกุล) + กันรูปใหญ่เกิน
        $info = @getimagesize($tmp);
        if ($info === false) {
            return false;
        }
        $width  = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        $type   = (int) ($info[2] ?? 0);
        if ($width <= 0 || $height <= 0 || ($width * $height) > self::MAX_PIXELS) {
            return false;
        }

        // แปลงเฉพาะ JPEG / PNG — อย่างอื่น (GIF / WebP / ฯลฯ) ปล่อยไว้ตามเดิม
        switch ($type) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($tmp);
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($tmp);
                break;
            default:
                return false;
        }
        if (!$img) {
            return false;
        }

        // คงพื้นโปร่งใส (สำคัญกับ PNG เช่น โลโก้)
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($img);
        }
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // เขียนทับ tmp เดิมให้เป็น WebP (PHP จะลบไฟล์ tmp ให้เองเมื่อจบ request)
        $ok = @imagewebp($img, $tmp, $quality);
        imagedestroy($img);
        if (!$ok) {
            return false;
        }
        clearstatcache(true, $tmp);

        // อัปเดต metadata ให้ปลายทางรู้ว่าเป็น webp แล้ว
        $base = pathinfo((string) ($file['name'] ?? 'image'), PATHINFO_FILENAME);
        $file['name'] = ($base !== '' ? $base : 'image') . '.webp';
        $file['type'] = 'image/webp';
        $file['size'] = @filesize($tmp) ?: ($file['size'] ?? 0);

        return true;
    }
}
