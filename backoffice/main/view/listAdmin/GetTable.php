<?php
// ผู้ดูแลระบบทั้งหมด — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page, current_admin_id  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list             = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total            = (int) ($_POST['total'] ?? count($list));
$page             = max(1, (int) ($_POST['page'] ?? 1));
$per_page         = max(1, (int) ($_POST['per_page'] ?? 10));
$current_admin_id = (int) ($_POST['current_admin_id'] ?? 0);
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
                        <th scope="col">ชื่อ-นามสกุล</th>
                        <th scope="col">อีเมล</th>
                        <th scope="col" class="text-center" style="width: 160px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $i = $from; foreach ($list as $row):
                        $is_self = ((int) ($row['user_id'] ?? 0) === $current_admin_id);
                        $user_id = $esc($row['user_id'] ?? '');
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="fw-medium"><?php echo $esc(($row['full_name'] ?? '') !== '' ? $row['full_name'] : '-') ?></td>
                            <td class="text-secondary"><?php echo $esc($row['user_email'] ?? '-') ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-warning text-white"
                                        style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 75px;"
                                        onclick="GetEditAdmin('<?php echo \App\Utility\Cipher::encrypt($user_id) ?>');">
                                        <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">edit</span>แก้ไข
                                    </button>
                                    <?php if (!$is_self): ?>
                                        <button type="button" class="btn btn-sm btn-danger text-white"
                                            style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 75px;"
                                            onclick="DeleteAdmin('<?php echo $user_id ?>');">
                                            <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">delete</span>ลบ
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

