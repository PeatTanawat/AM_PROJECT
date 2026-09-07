<?php
namespace App\Utility;

use App\Database\Connection;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class Notification
{
    /**
     * บันทึกการแจ้งเตือนลงฐานข้อมูล และส่ง Web Push ทันที
     *
     * @param string $title หัวข้อการแจ้งเตือน
     * @param string $message ข้อความ
     * @param string $type ประเภท (เช่น 'user_verify', 'course_approval', 'new_order')
     * @param string $link_url ลิงก์เมื่อกดคลิก
     * @return bool
     */
    public static function send($title, $message, $type = 'general', $link_url = '#', $reference_id = null)
    {
        try {
            $db = (new Connection())->getPdo();
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // 1. บันทึกลงฐานข้อมูล tbl_notifications
            $stmt = $db->prepare("
                INSERT INTO tbl_notifications (type, title, message, link_url, reference_id, is_read, created_at) 
                VALUES (:type, :title, :message, :link_url, :reference_id, 0, NOW())
            ");
            $stmt->execute([
                ':type' => $type,
                ':title' => $title,
                ':message' => $message,
                ':link_url' => $link_url,
                ':reference_id' => $reference_id
            ]);

            // 2. ดึงข้อมูล Subscription ของแอดมินทั้งหมดจากตาราง tbl_web_push_subscriptions
            $subStmt = $db->query("SELECT * FROM tbl_web_push_subscriptions");
            $subscriptions = $subStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($subscriptions) > 0) {
                try {
                    // ดึง VAPID Keys จาก ENV
                    $auth = [
                        'VAPID' => [
                            'subject' => $_ENV['VAPID_SUBJECT'] ?? 'mailto:admin@example.com',
                            'publicKey' => $_ENV['VAPID_PUBLIC_KEY'] ?? '',
                            'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '',
                        ],
                    ];

                    $webPush = new WebPush($auth);
                    $payload = json_encode([
                        'title' => $title,
                        'body'  => $message,
                        'url'   => $link_url,
                        'icon'  => '../assets/images/favicon.png'
                    ]);

                    foreach ($subscriptions as $sub) {
                        $subscription = Subscription::create([
                            'endpoint' => $sub['endpoint'],
                            'publicKey' => $sub['public_key'],
                            'authToken' => $sub['auth_token'],
                        ]);
                        $webPush->queueNotification($subscription, $payload);
                    }

                    // สั่งยิง Push พร้อมกันทั้งหมด
                    foreach ($webPush->flush() as $report) {
                        $endpoint = $report->getRequest()->getUri()->__toString();
                        if (!$report->isSuccess()) {
                            error_log("[WebPush] Failed for {$endpoint}: {$report->getReason()}");
                            
                            // ถ้าเป็น 410 Gone (ผู้ใช้บล็อคการแจ้งเตือนหรือหมดอายุ) ให้ลบออกจาก DB
                            if ($report->getResponse() && $report->getResponse()->getStatusCode() == 410) {
                                $delStmt = $db->prepare("DELETE FROM tbl_web_push_subscriptions WHERE endpoint = :endpoint");
                                $delStmt->execute([':endpoint' => $endpoint]);
                            }
                        }
                    }
                } catch (\Exception $pushEx) {
                    // ถ้า Push พัง ไม่ต้องโยน Error ออกไปให้รบกวนระบบหลัก เพราะบันทึก DB สำเร็จแล้ว
                    error_log("WebPush Error: " . $pushEx->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            throw new \Exception("Notification Error: " . $e->getMessage());
        }
    }
}
