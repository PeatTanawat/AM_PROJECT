<?php
// คอร์สเรียนคงเหลือในระบบ — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Database\Connection;
try {
    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();
} catch (Exception $e) {
    $pdo_connect = null;
}

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total);

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// วันที่แสดง 2 บรรทัด: บรรทัดบน = วันที่, บรรทัดล่าง = เวลา (เล็ก/จาง)
$date2 = function ($v) use ($esc): string {
    $v = trim((string) $v);
    if ($v === '') { return '<span class="text-muted">-</span>'; }
    $p = preg_split('/\s+/', $v, 2);
    $out = $esc($p[0]);
    if (isset($p[1]) && $p[1] !== '') { $out .= '<br><span class="text-secondary small">' . $esc($p[1]) . '</span>'; }
    return $out;
};

// วันที่แสดงเฉพาะวันเดือนปี
$dateOnly = function ($v) use ($esc): string {
    $v = trim((string) $v);
    if ($v === '') { return '<span class="text-muted">-</span>'; }
    $p = preg_split('/\s+/', $v, 2);
    return $esc($p[0]);
};
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 0px;">ลำดับ</th>
                        <th style="min-width: 150px;">ชื่อ-นามสกุล</th>
                        <th style="min-width: 100px;">เบอร์โทรติดต่อ</th>
                        <th style="min-width: 150px;">ชื่อคอร์ส</th>
                        <th style="width: 100px;">วันที่ซื้อ</th>
                        <th style="width: 100px;">วันที่เปิดใช้</th>
                        <th style="width: 100px;">วันหมดอายุ</th>
                        <th class="text-center" style="width: 50px;">อายุคงเหลือ</th>
                        <th class="text-end" style="width: 90px;">ราคา</th>
                        <th class="text-center" style="width: 60px;">สถานะ</th>
                        <th class="text-center" style="width: 180px;">จัดการ</th>
                    </tr>
                </thead>    
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $i = $from; foreach ($list as $row):
                        $enroll_id        = (int) ($row['enroll_id'] ?? 0);
                        $enroll_user_id   = (int) ($row['enroll_user_id'] ?? 0);
                        $enroll_course_id = (int) ($row['enroll_course_id'] ?? 0);
                        $remain_days      = $row['remain_days'] ?? null;
                        $expiry           = (string) ($row['expiry'] ?? '');
                        $status           = (string) ($row['status'] ?? '1');   // 1=ใช้งาน, 0=ยกเลิก
                        $is_completed     = (string) ($row['is_completed'] ?? '0');

                        $course_number_time = 0;
                        $attempts_count     = 0;
                        $has_passed         = false;
                        $is_passed_last_attempt = false;

                        if ($pdo_connect && $enroll_course_id > 0 && $enroll_user_id > 0) {
                            // 1. ไปดูที่ tbl_course ก่อน ว่าคอร์สนั้นมี course_number_time เท่าใด
                            $stmt_c = $pdo_connect->prepare("SELECT course_number_time FROM tbl_course WHERE course_id = :cid LIMIT 1");
                            $stmt_c->execute([':cid' => $enroll_course_id]);
                            $course_number_time = (int) $stmt_c->fetchColumn();
                            $stmt_c->closeCursor();

                            // 2. ไปเช็ค tbl_exam_attempt ว่าคนๆนี้ สอบคอร์สนี้ ไปกี่รอบแล้ว
                            $stmt_a = $pdo_connect->prepare("SELECT attempt_pass FROM tbl_exam_attempt WHERE attempt_user_id = :uid AND attempt_course_id = :cid ORDER BY attempt_id ASC");
                            $stmt_a->execute([':uid' => $enroll_user_id, ':cid' => $enroll_course_id]);
                            $attempts = $stmt_a->fetchAll(PDO::FETCH_COLUMN);
                            $stmt_a->closeCursor();

                            $attempts_count = count($attempts);
                            foreach ($attempts as $pass_val) {
                                  if ($pass_val === '1' || $pass_val === 1) {
                                      $has_passed = true;
                                  }
                            }

                            // เช็คว่าสอบผ่านในรอบสุดท้ายของ course_number_time หรือไม่
                            if ($course_number_time > 0 && $attempts_count >= $course_number_time) {
                                $last_pass_val = $attempts[$attempts_count - 1] ?? '0';
                                if ($last_pass_val === '1' || $last_pass_val === 1) {
                                    $is_passed_last_attempt = true;
                                }
                            }
                        }

                        $display_label = '';
                        $is_failed_limit = false;
                        $is_locked = (string) ($row['is_locked'] ?? '0');

                        if ($is_locked === '1') {
                            if ($is_passed_last_attempt) {
                                if ($is_completed === '0') {
                                    // สอบผ่านในรอบสุดท้ายของ course_number_time และยังไม่ได้อนุมัติ
                                $display_label = '<div></div>';
                                } else {
                                $display_label = '<div></div>';
                                }
                            } else {
                                // ถ้าสอบถึงรอบสุดท้ายแล้วไม่ผ่าน
                                if ($course_number_time > 0 && $attempts_count >= $course_number_time) {
                                    $is_failed_limit = true;
                                    $display_label = '<div class="text-danger mt-1" style="font-size: 0.85rem;"><i class="bi bi-x-circle-fill"></i> ผู้ใช้งานสอบไม่ผ่านเกินจำนวน</div>';
                                }
                            }
                        }
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="fw-medium">
                                <?php echo $esc(($row['member'] ?? '') !== '' ? $row['member'] : '-') ?>
                                <?php echo $display_label; ?>
                            </td>
                            <td><?php echo $esc(($row['phone'] ?? '') !== '' ? $row['phone'] : '-') ?></td>
                            <td><?php echo $esc(($row['course'] ?? '') !== '' ? $row['course'] : '-') ?></td>
                            <td class="text-nowrap"><?php echo $dateOnly($row['buy_at'] ?? '') ?></td>
                            <td class="text-nowrap"><?php echo $dateOnly($row['open_at'] ?? '') ?></td>
                            <td class="text-nowrap">
                                <?php if ($expiry === ''): ?>
                                    <span class="text-muted">ไม่มีกำหนด</span>
                                <?php else: ?>
                                    <?php echo $dateOnly($expiry) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <?php if ($status === '0'): ?>
                                    <span class="text-muted">-</span>
                                <?php elseif ($remain_days === null || $remain_days === ''): ?>
                                    <span class="text-muted">-</span>
                                <?php elseif ((int) $remain_days > 0): ?>
                                    <?php if ((int) $remain_days <= 10): ?>
                                        <span class="text-danger fw-bold">จะหมดอายุในอีก <?php echo (int) $remain_days ?> วัน</span>
                                    <?php else: ?>
                                        <span class="text-success"><?php echo (int) $remain_days ?> วัน</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-danger">หมดอายุ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo $esc($row['price'] ?? '0.00') ?></td>
                            <td class="text-center">
                                <?php if ($status === '1'): ?>
                                    <span class="badge bg-success">ใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ยกเลิก</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    <button type="button" class="btn btn-sm btn-warning text-white" 
                                           style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 130px;" 
                                           onclick="OpenEdit(<?php echo $enroll_id ?>)">
                                             <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">edit</span> แก้ไข
                                    </button>
                                     <?php if ($is_failed_limit): ?>
                                       <button type="button" class="btn btn-sm btn-success text-white" 
                                               style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 130px;" 
                                               onclick="UnlockCourse(<?php echo $enroll_id ?>)" title="ปลดล็อกสิทธิ์สอบ">
                                               <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">lock</span> ปลดล็อก
                                        </button>
                                     <?php endif; ?>
                                     <?php if ($status === '1' && $remain_days !== null && $remain_days > 0 && $remain_days <= 10): ?>
                                       <button type="button" class="btn btn-sm btn-info text-white" 
                                               style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 130px;" 
                                               onclick="SendExpiryEmail(<?php echo $enroll_id ?>)" title="ส่งอีเมลแจ้งเตือนการหมดอายุ">
                                               <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">mail</span> ส่งเมลแจ้งเตือน
                                        </button>
                                     <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="100" class="text-center py-5">
                            <div class="list-empty-icon mb-2"><span class="material-symbols-outlined" aria-hidden="true" style="font-size: 48px; color: #ccc;">inbox</span></div>
                            <div class="list-empty-title text-muted">ไม่พบข้อมูล</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($list)): ?>
    <?php include dirname(__DIR__) . '/_pagination.php'; ?>
<?php endif; ?>

