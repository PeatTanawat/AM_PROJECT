<?php
// ตั้งค่าที่อยู่ — view fragment: render ตารางระดับตำบล/แขวง

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 25));
$from     = $total > 0 ? ($page - 1) * $per_page + 1 : 0;

$esc  = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="default-table-area">
    <div class="table-responsive">
        <table class="table align-middle w-100" id="SubDistrictTable" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th scope="col" class="text-center" style="width: 8%;">ลำดับ</th>
                    <th scope="col" class="text-start" style="width: 50%;">ตำบล</th>
                    <th scope="col" class="text-center" style="width: 18%;">รหัสไปรษณีย์</th>
                    <th scope="col" class="text-center" style="width: 24%;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list)): ?>
                    <?php $n = $from; foreach ($list as $row): ?>
                        <?php 
                            $sub_district_th = $row['name_th'] ?? $row['sub_district_name_th'] ?? '-';
                            $sub_district_en = $row['name_en'] ?? $row['sub_district_name_en'] ?? '';
                        ?>
                        <tr>
                            <td class="text-center fw-medium text-secondary"><?php echo $n++; ?></td>
                            <td class="text-start">
                                <span class="fw-semibold text-dark d-block"><?php echo $esc($sub_district_th); ?></span>
                                <?php if (!empty($sub_district_en)): ?>
                                    <small class="text-muted font-monospace" style="font-size: 0.75rem;"><?php echo $esc($sub_district_en); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="fw-medium text-dark"><?php echo $esc($row['zip_code'] ?? '-'); ?></span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-warning d-inline-flex align-items-center gap-1 text-white px-2 py-1"
                                            title="แก้ไข"
                                            onclick="GetEditSubDistrict(<?php echo (int)$row['id']; ?>, <?php echo (int)($row['amphure_id'] ?? 0); ?>, '<?php echo $esc(addslashes($sub_district_th)); ?>', '<?php echo $esc(addslashes($sub_district_en)); ?>', '<?php echo $esc(addslashes($row['zip_code'] ?? '')); ?>')">
                                        <span class="material-symbols-outlined" style="font-size: 25px;">edit</span>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 text-white px-2 py-1"
                                            title="ลบ"
                                            onclick="DeleteSubDistrict(<?php echo (int)$row['id']; ?>, '<?php echo $esc(addslashes($sub_district_th)); ?>')">
                                        <span class="material-symbols-outlined" style="font-size: 25px;">delete</span>
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
                            <div class="list-empty-title text-muted">ไม่พบข้อมูลตำบล</div>
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
