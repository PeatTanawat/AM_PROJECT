<?php
// ใบรับรองผลการสอบ — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total = (int) ($_POST['total'] ?? count($list));
$page = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to = min($page * $per_page, $total);

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="default-table-area">
    <div class="table-responsive">
        <table class="table align-middle w-100" id="CertTable">
            <thead>
                <tr>
                    <th class="text-center" style="width:60px;">ลำดับ</th>
                    <th class="text-nowrap">เลขที่ใบรับรอง</th>
                    <th>คอร์สเรียน</th>
                    <th class="text-nowrap">ผู้ทำ / ผู้สอบ</th>
                    <th class="text-nowrap">คะแนนที่ได้</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-center">การอนุมัติ</th>
                    <th class="text-center" style="width:310px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list)): ?>
                    <?php $i = $from;
                    foreach ($list as $row):
                        $passed = ((string) ($row['passed'] ?? '0') === '1');
                        $approved = ((string) ($row['approved'] ?? '0') === '1');
                        $issued = ((string) ($row['issued'] ?? '0') === '1');
                        $enroll_id = (int) ($row['enroll_id'] ?? 0);
                        $score = $row['score'] ?? '';
                        $score_txt = ($score === '' || $score === null)
                            ? '<span class="text-muted">-</span>'
                            : $esc($score) . ' คะแนน / ' . $esc($row['percent'] ?? '0.00') . ' %';
                        $cipher_key = \App\Utility\Cipher::encrypt($enroll_id);
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="text-nowrap"><?php echo $esc($row['cert_no'] ?? '') ?></td>
                            <td><?php echo $esc(($row['course'] ?? '') !== '' ? $row['course'] : '-') ?></td>
                            <td class="text-nowrap"><?php echo $esc(($row['examiner'] ?? '') !== '' ? $row['examiner'] : '-') ?>
                            </td>
                            <td class="text-nowrap"><?php echo $score_txt ?></td>
                            <td class="text-center">
                                <?php if ($passed): ?>
                                    <span class="badge bg-success">ผ่าน</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ไม่ผ่าน</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($passed): ?>
                                    <?php if ($approved): ?>
                                        <span class="badge bg-success">อนุมัติ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">รออนุมัติ</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="d-inline-flex gap-2 align-items-center justify-content-center">
                                    <div style="width: 115px;" class="text-center">
                                        <?php if ($passed && $approved): ?>
                                            <button type="button" class="btn btn-sm btn-info text-white w-100"
                                                onclick="OpenCertPreview(<?php echo $enroll_id ?>, 'cpd', '<?php echo $cipher_key; ?>')"
                                                title="โหลด PDF">
                                                <span class="material-symbols-outlined align-middle fs-6">download</span> โหลด PDF
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div style="width: 95px;" class="text-center">
                                        <?php if ($passed): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100"
                                                onclick="OpenManage(<?php echo $enroll_id ?>)">ดำเนินการ</button>
                                        <?php endif; ?>
                                    </div>
                                    <div style="width: 80px;" class="text-center">
                                        <?php if ($issued): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning w-100"
                                                onclick="EditData(<?php echo $enroll_id ?>)" title="แก้ไข">แก้ไข</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="100" class="text-center py-5">
                            <div class="list-empty-icon mb-2"><span class="material-symbols-outlined" aria-hidden="true"
                                    style="font-size: 48px; color: #ccc;">inbox</span></div>
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