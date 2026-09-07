<?php
// ตั้งค่าธนาคาร — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list        = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total       = (int) ($_POST['total'] ?? count($list));
$page        = max(1, (int) ($_POST['page'] ?? 1));
$per_page    = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from        = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to          = min($page * $per_page, $total);

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="default-table-area">
    <div class="table-responsive">
        <table class="table align-middle w-100" id="PageTable" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th scope="col" class="text-center" style="width: 6%;">ลำดับ</th>
                    <th scope="col" class="text-center" style="width: 14%;">รูปภาพ</th>
                    <th scope="col" class="text-start" style="width: 26%;">ชื่อธนาคาร</th>
                    <th scope="col" class="text-start" style="width: 20%;">ชื่อบัญชี</th>
                    <th scope="col" class="text-center" style="width: 16%;">เลขบัญชี</th>
                    <th scope="col" class="text-center" style="width: 8%;">สถานะ</th>
                    <th scope="col" class="text-center" style="width: 10%;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list)): ?>
                    <?php $n = $from; foreach ($list as $row): ?>
                        <tr>
                            <td class="text-center fw-medium"><?php echo $n++; ?></td>
                            <td class="text-center">
                                <?php if (!empty($row['logo_image'])): ?>
                                    <img src="<?php echo $esc($row['logo_image']); ?>" alt="logo" class="rounded border p-1" style="height: 36px; width: 36px; object-fit: contain;">
                                <?php else: ?>
                                    <div class="d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded-circle border" style="width: 36px; height: 36px;">
                                        <span class="material-symbols-outlined" style="font-size: 20px;">account_balance</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary fw-semibold">
                                <?php 
                                    $b_name = $row['bank_name'] ?? '';
                                    $b_abbr = !empty($row['bank_abbreviation']) ? ' ' . $row['bank_abbreviation'] : '';
                                    echo $esc($b_name . $b_abbr); 
                                ?>
                            </td>
                            <td class="fw-medium text-dark"><?php echo $esc($row['account_name'] ?? ''); ?></td>
                            <td class="text-center fw-bold text-primary"><?php echo $esc($row['account_no'] ?? ''); ?></td>
                            <td class="text-center">
                                <?php if (($row['active_status'] ?? '1') === '1'): ?>
                                    <span class="badge text-white " style="background-color: #22c55e;  cursor: pointer; font-size: 13px;" title="คลิกเพื่อสลับสถานะ" onclick="ToggleBankStatus('<?php echo $esc($row['id']); ?>', '1');">ใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge text-white " style="background-color: #ef4444;  cursor: pointer; font-size: 13px;" title="คลิกเพื่อสลับสถานะ" onclick="ToggleBankStatus('<?php echo $esc($row['id']); ?>', '0');">ปิดใช้งาน</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-warning d-inline-flex align-items-center justify-content-center gap-1 text-white px-2"
                                        onclick="GetEditBank('<?php echo $esc($row['id']); ?>');">
                                        <span class="material-symbols-outlined" style="font-size: 16px;" aria-hidden="true">edit</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center gap-1 text-white px-2"
                                        onclick="GetDeleteBank('<?php echo $esc($row['id']); ?>');">
                                        <span class="material-symbols-outlined" style="font-size: 16px;" aria-hidden="true">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="100" class="text-center py-5">
                            <div class="list-empty-icon mb-2">
                                <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 48px; color: #ccc;">inbox</span>
                            </div>
                            <div class="list-empty-title text-muted">ไม่พบข้อมูลบัญชีธนาคาร</div>
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
