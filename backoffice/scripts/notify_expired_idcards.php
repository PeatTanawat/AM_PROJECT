<?php
/**
 * notify_expired_idcards.php — แจ้งเตือนลูกค้าทางอีเมลเมื่อบัตรประชาชนหมดอายุ (รันมือ/ทดสอบ)
 *
 * หมายเหตุ: ระบบจริงส่งอัตโนมัติแบบ web-cron ผ่าน keepSession อยู่แล้ว (วันละครั้งตอน admin ใช้งาน)
 *   ไฟล์นี้ไว้สำหรับรันมือ/ทดสอบ/ตั้ง OS cron เพิ่มเอง — ใช้ตรรกะร่วมกับ App\Utility\ExpiredIdCardNotifier
 *
 * รันจาก command line เท่านั้น (ต้องมี .env + vendor + คอลัมน์ id_card_expiry_notified):
 *   php scripts/notify_expired_idcards.php               # dry-run: รายงานอย่างเดียว (ค่าเริ่มต้น)
 *   php scripts/notify_expired_idcards.php --send        # ส่งเมลจริง + บันทึกว่าแจ้งแล้ว
 *   php scripts/notify_expired_idcards.php --send --limit=50   # จำกัดจำนวนต่อรอบ
 */

use App\Database\Connection;
use App\Utility\ExpiredIdCardNotifier;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

/* ---------- อ่าน argument ---------- */
$argvAll = $argv ?? [];
$do_send = in_array('--send', $argvAll, true); // ไม่ใส่ = dry-run (รายงานอย่างเดียว)
$limit   = 0;
foreach ($argvAll as $a) {
    if (strpos($a, '--limit=') === 0) {
        $limit = max(0, (int) substr($a, strlen('--limit=')));
    }
}

$log = function (string $m) { fwrite(STDOUT, $m . "\n"); };

/* ---------- ต่อ DB (Connection โหลด .env เข้า $_ENV ให้ Email::send ใช้ด้วย) ---------- */
try {
    $pdo = (new Connection())->getPdo();
} catch (\Throwable $e) {
    fwrite(STDERR, 'DB connect failed: ' . $e->getMessage() . "\n");
    exit(1);
}
if (!$pdo) {
    fwrite(STDERR, "DB connect failed.\n");
    exit(1);
}

/* ---------- รันผ่าน Logic กลาง ---------- */
try {
    $r = ExpiredIdCardNotifier::run($pdo, $do_send, $limit);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Run failed (คอลัมน์ id_card_expiry_notified ยังไม่ถูกสร้าง? ): ' . $e->getMessage() . "\n");
    exit(1);
}

$log('[' . date('Y-m-d H:i:s') . '] พบผู้ใช้ที่บัตรหมดอายุและยังไม่ได้แจ้ง: ' . $r['total'] . ' ราย'
    . ($do_send ? '' : '   (DRY-RUN: ยังไม่ส่งจริง — ใส่ --send เพื่อส่ง)'));

if (!$do_send) {
    foreach ($r['candidates'] as $c) {
        $log(sprintf('  - #%s  %s  <%s>  หมดอายุ %s', $c['user_id'], $c['name'], $c['email'], $c['expiry']));
    }
} else {
    $log('[' . date('Y-m-d H:i:s') . '] สรุป: ส่งสำเร็จ ' . $r['sent'] . ' / ล้มเหลว ' . $r['failed'] . ' / ทั้งหมด ' . $r['total']);
}
exit(0);
