<?php
/**
 * s3_cleanup_orphans.php — กวาดล้าง "ไฟล์กำพร้า" ใน S3 ที่ไม่มี record ใดชี้หาแล้ว
 *
 * ต้นเหตุ: เดิม Update handler อัปไฟล์ใหม่แล้วไม่ลบไฟล์เก่า → ไฟล์เก่าค้างสะสมใน S3
 * สคริปต์นี้จะเทียบ object ใน S3 กับ URL ที่ยังอ้างอิงอยู่ใน DB แล้วรายงาน/ลบเฉพาะไฟล์ที่ไม่มีใครอ้างถึง
 *
 * รันจาก command line เท่านั้น (ต้องมี .env + vendor ครบ):
 *   php scripts/s3_cleanup_orphans.php                # dry-run: รายงานอย่างเดียว (ค่าเริ่มต้น)
 *   php scripts/s3_cleanup_orphans.php --delete       # ลบจริง
 *   php scripts/s3_cleanup_orphans.php --prefix=course/   # จำกัดเฉพาะบาง prefix
 *   php scripts/s3_cleanup_orphans.php --list          # แสดงรายชื่อ key กำพร้าทั้งหมด (ไม่ตัดจอ)
 *   php scripts/s3_cleanup_orphans.php --delete --force # ลบทั้งที่บาง prefix ไม่มี overlap (ใช้เมื่อมั่นใจว่าต่อ DB ตัวจริง)
 *
 * ความปลอดภัย:
 *  - นับ record ที่ถูก soft-delete (delete_at) เป็น "ยังอ้างอิงอยู่" ด้วย เพราะอาจถูกกู้คืนภายหลัง
 *  - กวาดเฉพาะ prefix ที่เรามีรายการอ้างอิงครบเท่านั้น (เช่น lesson file ไม่ถูกแตะ)
 *  - ถ้า query อ้างอิงตัวใดล้มเหลว จะ "ไม่ลบ" เด็ดขาด (กันลบไฟล์ที่ยังใช้อยู่จากชุดอ้างอิงไม่ครบ)
 */

use App\Database\Connection;
use App\Utility\AwsS3;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

/* ---------- อ่าน argument ---------- */
$argvAll     = $argv ?? [];
$do_delete   = in_array('--delete', $argvAll, true);
$show_list   = in_array('--list', $argvAll, true);
$force       = in_array('--force', $argvAll, true); // ข้ามด่านกัน "ต่อผิด DB" (ใช้เมื่อมั่นใจว่าต่อ DB ตัวจริง)
$only_prefix = null;
foreach ($argvAll as $a) {
    if (strpos($a, '--prefix=') === 0) {
        $only_prefix = substr($a, strlen('--prefix='));
    }
}

/* ---------- prefix ที่จะกวาด (เรามีรายการอ้างอิงครบทุกตัว) ---------- */
$prefixes = ['course/', 'banner/', 'reviews/reviewer/', 'website/', 'question/', 'exam/'];
if ($only_prefix !== null && $only_prefix !== '') {
    $prefixes = array_values(array_filter($prefixes, fn($p) => $p === $only_prefix));
    if (!$prefixes) {
        $prefixes = [$only_prefix]; // อนุญาต prefix กำหนดเองได้ (แต่ต้องมั่นใจว่ามีอ้างอิงครบ)
    }
}

/* ---------- คอลัมน์ใน DB ที่เก็บ URL ของไฟล์ (ทุกตัวที่ผูกกับ prefix ข้างบน) ---------- */
$references = [
    ['tbl_course',          'course_cover_image'],
    ['tbl_banner',          'banner_image'],
    ['tbl_reviews',         'reviewer_image'],
    ['tbl_website_setting', 'image_path'],
    ['tbl_question',        'question_image'],
    ['tbl_question',        'question_file'],
    ['tbl_exam',            'exam_image'],
    ['tbl_exam',            'exam_file'],
];

echo "=== S3 orphan cleanup ===\n";
echo $do_delete ? "โหมด: ลบจริง (--delete)\n" : "โหมด: DRY-RUN (รายงานอย่างเดียว)\n";
echo "Prefix ที่กวาด: " . implode(', ', $prefixes) . "\n\n";

/* ---------- 1) รวบรวม key ที่ยังถูกอ้างอิงจาก DB ---------- */
$pdo = (new Connection())->getPdo();

$referenced = [];   // set: key => true
$ref_failed = false;
foreach ($references as [$table, $col]) {
    try {
        $rows = $pdo->query("SELECT `$col` FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $val) {
            $key = AwsS3::urlToKey((string) $val);
            if ($key !== '') {
                $referenced[$key] = true;
            }
        }
    } catch (Throwable $e) {
        $ref_failed = true;
        fwrite(STDERR, "!! อ่านอ้างอิงจาก $table.$col ไม่สำเร็จ: " . $e->getMessage() . "\n");
    }
}
echo "อ้างอิงในฐานข้อมูล: " . count($referenced) . " ไฟล์\n\n";

if ($ref_failed && $do_delete) {
    fwrite(STDERR, "ยกเลิกการลบ: ชุดอ้างอิงไม่ครบ (มี query ล้มเหลว) — เพื่อความปลอดภัยจะไม่ลบอะไรทั้งสิ้น\n");
    $do_delete = false;
}

/* ---------- 2) list S3 ต่อ prefix แล้วหา orphan ---------- */
$all_orphans = [];
$total_s3    = 0;
$zero_overlap_prefixes = [];
foreach ($prefixes as $prefix) {
    try {
        $keys = AwsS3::listKeys($prefix);
    } catch (Throwable $e) {
        fwrite(STDERR, "!! list S3 prefix '$prefix' ไม่สำเร็จ: " . $e->getMessage() . "\n");
        continue;
    }
    $files   = 0; // object จริง (ไม่นับ folder marker)
    $overlap = 0; // object ที่ DB อ้างอิงตรงกัน
    $orphans = [];
    foreach ($keys as $k) {
        if (substr($k, -1) === '/') { continue; } // ข้าม "โฟลเดอร์" (key ว่างลงท้าย /)
        $files++;
        if (isset($referenced[$k])) {
            $overlap++;
        } else {
            $orphans[] = $k;
        }
    }
    $total_s3 += $files;
    // ธงเตือน: prefix ที่มีไฟล์ใน S3 แต่ DB ไม่อ้างอิงตรงสักตัว = น่าจะต่อผิด DB (ไม่ใช่เจ้าของไฟล์)
    if ($files > 0 && $overlap === 0) {
        $zero_overlap_prefixes[] = $prefix;
    }
    echo sprintf("  %-20s S3=%d  DB-ตรงกัน=%d  กำพร้า=%d\n", $prefix, $files, $overlap, count($orphans));
    $all_orphans = array_merge($all_orphans, $orphans);
}

echo "\nรวม object ใน S3 ที่สแกน: $total_s3\n";
echo "รวมไฟล์กำพร้า: " . count($all_orphans) . "\n";

if ($show_list || (!$do_delete && count($all_orphans) <= 50)) {
    foreach ($all_orphans as $k) {
        echo "   - $k\n";
    }
} elseif (!$do_delete && $all_orphans) {
    echo "   (ใส่ --list เพื่อดูรายชื่อทั้งหมด)\n";
}

/* ---------- ด่านกันต่อผิด DB: prefix ที่ไม่มี overlap เลย = DB นี้ไม่ใช่เจ้าของไฟล์ ---------- */
if ($zero_overlap_prefixes) {
    fwrite(STDERR, "\n!! เตือน: prefix ต่อไปนี้มีไฟล์ใน S3 แต่ DB นี้ไม่อ้างอิงตรงสักตัว:\n");
    fwrite(STDERR, "     " . implode(', ', $zero_overlap_prefixes) . "\n");
    fwrite(STDERR, "   แปลว่าน่าจะกำลังต่อ DB ผิดตัว (ไม่ใช่ตัวที่เป็นเจ้าของไฟล์ในบัคเก็ตนี้)\n");
    fwrite(STDERR, "   เช่น DB local เก็บ path ในเครื่อง ส่วนไฟล์จริงถูกอัปโดยระบบ production ที่ใช้ DB คนละตัว\n");
    if ($do_delete && !$force) {
        fwrite(STDERR, "   ==> ยกเลิกการลบทั้งหมดเพื่อความปลอดภัย. รันบนที่ที่ต่อ DB ตัวจริง หรือใส่ --force ถ้ามั่นใจ\n");
        $do_delete = false;
    } elseif ($do_delete && $force) {
        fwrite(STDERR, "   ==> มี --force: ข้ามด่านนี้และจะลบต่อ (คุณรับผิดชอบเองว่าต่อ DB ถูกตัว)\n");
    }
}

/* ---------- 3) ลบจริง (เมื่อสั่ง --delete) ---------- */
if ($do_delete && $all_orphans) {
    echo "\nกำลังลบ...\n";
    $deleted = 0;
    $failed  = 0;
    foreach ($all_orphans as $k) {
        if (AwsS3::deleteFile($k)) {
            $deleted++;
        } else {
            $failed++;
            fwrite(STDERR, "   ลบไม่สำเร็จ: $k\n");
        }
    }
    echo "ลบสำเร็จ: $deleted, ล้มเหลว: $failed\n";
} elseif (!$do_delete && $all_orphans) {
    echo "\n(dry-run) ยังไม่ได้ลบ — เพิ่ม --delete เพื่อลบจริง\n";
}

echo "เสร็จสิ้น\n";
