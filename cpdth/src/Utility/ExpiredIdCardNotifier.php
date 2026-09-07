<?php
namespace App\Utility;

use PDO;
use App\Database\Connection;

/**
 * แจ้งเตือนลูกค้าทางอีเมลเมื่อบัตรประชาชนหมดอายุ
 * ใช้ร่วมกันทั้งสคริปต์ CLI (scripts/notify_expired_idcards.php) และ web-cron (keepSession.php)
 *
 * กันส่งซ้ำด้วยคอลัมน์ tbl_user.id_card_expiry_notified (เก็บวันหมดอายุที่แจ้งไปแล้ว)
 *   -> ส่ง 1 ครั้งต่อบัตร 1 ใบ; ต่อบัตรใหม่แล้วหมดอายุอีก (วันเปลี่ยน) จะแจ้งใหม่
 */
class ExpiredIdCardNotifier
{
    /**
     * ค้นหาผู้ใช้ที่บัตรหมดอายุและยังไม่ได้แจ้ง แล้วส่งเมล (หรือแค่รายงานถ้า $send=false)
     *
     * @param PDO  $pdo
     * @param bool $send  true=ส่งจริง+บันทึกว่าแจ้งแล้ว, false=dry-run (แค่คืนรายชื่อ)
     * @param int  $limit จำกัดจำนวนต่อรอบ (0 = ไม่จำกัด)
     * @return array ['total'=>int, 'sent'=>int, 'failed'=>int, 'candidates'=>array]
     */
    public static function run(PDO $pdo, bool $send, int $limit = 0): array
    {
        $sql = "SELECT user_id, user_firstname, user_lastname, user_email, id_card_expiry_date
                FROM tbl_user
                WHERE delete_at IS NULL
                  AND user_email IS NOT NULL AND user_email <> ''
                  AND id_card_expiry_date IS NOT NULL
                  AND id_card_expiry_date <> '0000-00-00'
                  AND id_card_expiry_date < CURDATE()
                  AND (id_card_expiry_notified IS NULL OR id_card_expiry_notified <> id_card_expiry_date)
                ORDER BY id_card_expiry_date ASC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $rows  = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $total = count($rows);
        $sent = 0;
        $failed = 0;
        $candidates = [];

        if ($total === 0) {
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'candidates' => []];
        }

        $upd = $pdo->prepare(
            "UPDATE tbl_user SET id_card_expiry_notified = :exp WHERE user_id = :id AND delete_at IS NULL"
        );

        foreach ($rows as $r) {
            $email  = trim((string) ($r['user_email'] ?? ''));
            $name   = trim(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''));
            if ($name === '') { $name = 'ลูกค้า'; }
            $exp    = (string) $r['id_card_expiry_date'];
            $exp_th = date('d/m/Y', strtotime($exp));

            if (!$send) {
                $candidates[] = ['user_id' => $r['user_id'], 'name' => $name, 'email' => $email, 'expiry' => $exp_th];
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                continue;
            }

            $ok = Email::send($email, 'แจ้งเตือน: บัตรประชาชนของท่านหมดอายุ - CPDTH', self::buildBody($name, $exp_th), true);
            if ($ok) {
                // บันทึกว่าแจ้งสำหรับวันหมดอายุนี้แล้ว (กันส่งซ้ำ)
                $upd->execute([':exp' => $exp, ':id' => (int) $r['user_id']]);
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['total' => $total, 'sent' => $sent, 'failed' => $failed, 'candidates' => $candidates];
    }

    /**
     * แจ้งเตือนผู้ใช้ 1 คนทางอีเมล หากบัตรหมดอายุและยังไม่ได้แจ้งสำหรับวันหมดอายุนี้
     *
     * @param PDO   $pdo
     * @param array $user ต้องมี user_id, user_email, user_firstname, user_lastname, id_card_expiry_date, id_card_expiry_notified
     * @return bool true หากส่งอีเมลสำเร็จ
     */
    public static function notifyUser(PDO $pdo, array $user): bool
    {
        $userId = (int) ($user['user_id'] ?? 0);
        $email  = trim((string) ($user['user_email'] ?? ''));
        $exp    = (string) ($user['id_card_expiry_date'] ?? '');
        $notified = $user['id_card_expiry_notified'] ?? null;

        if ($userId <= 0 || empty($email) || empty($exp) || $exp === '0000-00-00') {
            return false;
        }

        $today = date('Y-m-d');
        if ($exp >= $today) {
            return false; // ยังไม่หมดอายุ
        }

        if (!empty($notified) && $notified === $exp) {
            return false; // แจ้งไปแล้วสำหรับวันหมดอายุนี้
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $name = trim(($user['user_firstname'] ?? '') . ' ' . ($user['user_lastname'] ?? ''));
        if ($name === '') {
            $name = 'ลูกค้า';
        }
        $exp_th = date('d/m/Y', strtotime($exp));

        $ok = Email::send($email, 'แจ้งเตือน: บัตรประชาชนของท่านหมดอายุ - CPDTH', self::buildBody($name, $exp_th), true);
        if ($ok) {
            $upd = $pdo->prepare("UPDATE tbl_user SET id_card_expiry_notified = :exp WHERE user_id = :id AND delete_at IS NULL");
            $upd->execute([':exp' => $exp, ':id' => $userId]);
            return true;
        }

        return false;
    }

    /**
     * web-cron: รันได้มากสุดวันละครั้ง (เช็คจากไฟล์ marker) — เรียกจาก keepSession
     * เช็ค marker ก่อน (ไฟล์, ราคาถูก) ยังไม่ต่อ DB ถ้าวันนี้รันแล้ว
     * ไม่โยน exception ออกไป (กันพังการทำงานหลักที่เรียกมา)
     *
     * @param string $markerDir โฟลเดอร์ที่เขียนได้สำหรับเก็บไฟล์ marker (เช่น <backoffice>/upload)
     * @param int    $limit     จำกัดจำนวนเมลต่อรอบ (กัน SMTP โดน throttle / กันหน่วง request)
     */
    public static function runDailyIfDue(string $markerDir, int $limit = 50): void
    {
        try {
            // โหลด .env เผื่อ caller ยังไม่โหลด (ต้องใช้ทั้ง flag เปิด/ปิด และ SMTP)
            \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

            // สวิตช์ความปลอดภัย: ส่งเมลอัตโนมัติเฉพาะเมื่อ IDCARD_NOTIFY_ENABLED=true ใน .env
            //   -> local/dev ที่ไม่ได้ตั้ง จะไม่ยิงเมลเอง (กันสแปมคนจริงตอนพัฒนา)
            //   -> production ตั้ง IDCARD_NOTIFY_ENABLED=true ครั้งเดียว = ทำงานอัตโนมัติ
            if (($_ENV['IDCARD_NOTIFY_ENABLED'] ?? '') !== 'true') {
                return;
            }

            $today  = date('Y-m-d');
            $marker = rtrim($markerDir, "/\\") . DIRECTORY_SEPARATOR . '.idcard_notify_last_run';

            $last = is_file($marker) ? trim((string) @file_get_contents($marker)) : '';
            if ($last === $today) {
                return; // วันนี้รันไปแล้ว — จบเร็ว ไม่ต่อ DB
            }

            // จองก่อนรัน (กันยิงซ้ำจาก request ที่เข้ามาพร้อมกัน; ด่านสุดท้ายคือ dedup ระดับ DB)
            @file_put_contents($marker, $today, LOCK_EX);

            $pdo = (new Connection())->getPdo();
            if ($pdo) {
                self::run($pdo, true, $limit);
            }
        } catch (\Throwable $e) {
            error_log('ExpiredIdCardNotifier daily tick error: ' . $e->getMessage());
        }
    }

    /** เนื้อหาอีเมล (HTML) */
    private static function buildBody(string $name, string $expiryTh): string
    {
        $esc = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if (!empty($host)) {
            $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $base_dir = preg_replace('#/(core|scripts|src|main|backoffice)(/[^/]+)*$#i', '', $script_dir);
            if ($base_dir === DIRECTORY_SEPARATOR || $base_dir === '/') {
                $base_dir = '';
            }
            $base_url = $protocol . '://' . $host . $base_dir;
            if (strpos($base_url, '/cpdth') === false && strpos($host, 'localhost') !== false) {
                $base_url = rtrim($base_url, '/') . '/cpdth';
            }
        } else {
            $base_url = 'https://bigsara-demo.com/am/cpdth';
        }

        $logo_url    = rtrim($base_url, '/') . '/assets/images/logo/G_AM_logo-01.jpg';
        $profile_url = rtrim($base_url, '/') . '/profile.php';

        return '
        <div style="font-family: \'Prompt\', sans-serif; background-color: #f3f4f6; padding: 40px 20px;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <div style="background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 800; letter-spacing: 2px;">LOGO</h2>
                </div>
                <div style="padding: 30px 40px;">
                    <h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem;">สวัสดี คุณ ' . $esc($name) . '</h3>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                        ระบบตรวจพบว่า <strong>บัตรประชาชนที่ท่านใช้ยืนยันตัวตนกับ CPDTH หมดอายุแล้ว</strong> (วันหมดอายุ: <strong>' . $esc($expiryTh) . '</strong>)
                    </p>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;">
                        เพื่อให้ท่านใช้งานระบบและออกเอกสาร/ใบรับรองได้อย่างต่อเนื่อง กรุณาอัปเดตข้อมูลบัตรประชาชนใบใหม่ และยืนยันตัวตนอีกครั้งผ่านระบบ
                    </p>
                    
                    <p style="color: #475569; font-size: 0.95rem; margin-bottom: 20px;">
                        หากท่านได้ต่ออายุ/อัปเดตข้อมูลเรียบร้อยแล้ว สามารถละเว้นอีเมลฉบับนี้ได้
                    </p>
                   
                    <p style="color: #94a3b8; font-size: 0.8rem; line-height: 1.5; margin: 0;">
                        อีเมลฉบับนี้ส่งจากระบบ CPDTH โดยอัตโนมัติ กรุณาอย่าตอบกลับ <br>
                    </p>
                </div>
            </div>
        </div>';
    }
}
