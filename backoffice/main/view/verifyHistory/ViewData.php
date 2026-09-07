<?php
// ประวัติการยืนยันตัวตน — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
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
                        <th scope="col">ชื่อ</th>
                        <th scope="col">เลขบัตรประชาชน</th>
                        <th scope="col">รายละเอียด</th>
                        <th scope="col" class="text-center">สถานะ</th>
                        <th scope="col">ผู้ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $i = $from; foreach ($list as $row):
                        $full   = ($row['full_name'] ?? '') !== '' ? $row['full_name'] : '-';
                        $cid    = ($row['citizen_id'] ?? '') !== '' ? $row['citizen_id'] : '-';
                        $remark = ($row['remark'] ?? '') !== '' ? $row['remark'] : '-';
                        $admin  = ($row['admin_name'] ?? '') !== '' ? $row['admin_name'] : 'ไม่มีข้อมูล';
                        $act    = (string) ($row['action_type'] ?? '0');
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="fw-medium"><?php echo $esc($full) ?></td>
                            <td><?php echo $esc($cid) ?></td>
                            <td class="text-secondary"><?php echo $esc($remark) ?></td>
                            <td class="text-center">
                                <?php if ($act === '1'): ?>
                                    <span class="badge bg-success">ผ่านแล้ว</span>
                                <?php elseif ($act === '2'): ?>
                                    <span class="badge bg-danger">ไม่อนุมัติ</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $esc($admin) ?></td>
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

