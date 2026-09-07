<?php
// คำขอยืนยันตัวตนผู้ใช้งาน — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total);

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width: 60px;">ลำดับ</th>
                        <th scope="col">ชื่อ-นสมสกุล</th>
                        <th scope="col">อีเมล</th>
                        <th scope="col">เบอร์โทรศัพท์</th>
                        <th scope="col">เลขบัตรประชาชน</th>
                        <th scope="col">เลขที่ผู้ทำบัญชี</th>
                        <th scope="col">เลขที่ผู้สอบบัญชี</th>
                        <th scope="col" class="text-center" style="width: 140px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $i = $from; foreach ($list as $row):
                        $full_name = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
                        $cpd = trim((string) ($row['user_cpd_no'] ?? ''));
                        $cpa = trim((string) ($row['user_cpa_no'] ?? ''));
                        $user_id = (int) ($row['user_id'] ?? 0);
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="fw-medium"><?php echo $esc($full_name !== '' ? $full_name : '-') ?></td>
                            <td class="text-secondary"><?php echo $esc($row['user_email'] ?? '-') ?></td>
                            <td><?php echo $esc($row['user_phone'] ?? '-') ?></td>
                            <td><?php echo $esc($row['user_citizen_id'] ?? '-') ?></td>
                            <td><?php echo $esc($cpd !== '' ? $cpd : '-') ?></td>
                            <td><?php echo $esc($cpa !== '' ? $cpa : '-') ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-warning"
                                    onclick="OpenVerify('<?php echo \App\Utility\Cipher::encrypt($user_id) ?>');">
                                    ตรวจเอกสาร
                                </button>
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

