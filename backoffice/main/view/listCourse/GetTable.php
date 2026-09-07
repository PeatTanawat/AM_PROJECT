<?php
// คอร์สเรียน — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total);

// ดึงรหัสวิชาตามประเภท (cpd หรือ cpa) เฉพาะที่ไม่ว่าง
function course_codes(array $row, string $type): array {
    $codes = [];
    foreach (['1', '2', '3', '4'] as $i) {
        $v = trim((string)($row["course_code_{$type}_{$i}"] ?? ''));
        $v = str_replace(['[', ']'], '', $v); // ตัดวงเล็บปี/ไตรมาสออกตอนแสดงผล
        if ($v !== '') { $codes[] = $v; }
    }
    return $codes;
}
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width: 60px;">ลำดับ</th>
                        <th scope="col" class="text-center" style="width: 90px;">รูป</th>
                        <th scope="col">ประเภท / หมวดหมู่</th>
                        <th scope="col">ชื่อคอร์สเรียน</th>
                        <th scope="col" class="text-end">ราคา</th>
                        <th scope="col">รหัสวิชา CPD</th>
                        <th scope="col">รหัสวิชา CPA</th>
                        <th scope="col" class="text-center">แสดงในหน้าหลัก</th>
                        <th scope="col" class="text-center">สถานะ</th>
                        <th scope="col" class="text-center" style="width: 110px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $n = $from; foreach ($list as $row): ?>
                        <?php
                            $codes_cpd  = course_codes($row, 'cpd');
                            $codes_cpa  = course_codes($row, 'cpa');
                            $price      = (float)($row['course_price'] ?? 0);
                            $promotion  = (float)($row['course_promotion'] ?? 0);
                            $cover      = trim((string)($row['course_cover_image'] ?? ''));
                            $is_display = (string)($row['course_display'] ?? '0') === '1';
                            $is_active  = (string)($row['course_status'] ?? '0') === '1';
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $n++; ?></td>

                            <td class="text-center">
                                <?php if ($cover !== ''): ?>
                                    <img src="<?php echo (preg_match('~^https?://~i', $cover) ? '' : '../') . htmlspecialchars($cover); ?>" alt="cover"
                                         style="width:64px;height:48px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <span class="d-inline-flex align-items-center justify-content-center text-muted"
                                          style="width:64px;height:48px;border-radius:8px;background:var(--bg);" aria-hidden="true">
                                        <i class="ri-image-line fs-18"></i>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium"><?php echo htmlspecialchars($row['type_name'] ?? '-'); ?></span>
                                    <span class="text-secondary small"><?php echo htmlspecialchars($row['group_name'] ?? '-'); ?></span>
                                </div>
                            </td>

                            <td class="text-secondary"><?php echo htmlspecialchars($row['course_name'] ?? ''); ?></td>

                            <td class="text-end">
                                <?php if ($promotion > 0 && $promotion < $price): ?>
                                    <span class="text-muted text-decoration-line-through small"><?php echo number_format($price, 2); ?></span><br>
                                    <span class="fw-medium text-danger"><?php echo number_format($promotion, 2); ?></span>
                                <?php else: ?>
                                    <span class="fw-medium"><?php echo number_format($price, 2); ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (count($codes_cpd) > 0): ?>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <?php foreach ($codes_cpd as $code): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($code); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (count($codes_cpa) > 0): ?>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <?php foreach ($codes_cpa as $code): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($code); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php if ($is_display): ?>
                                    <span class="badge bg-success">แสดง</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ไม่แสดง</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php if ($is_active): ?>
                                    <span class="badge bg-success">เปิดใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ปิดใช้งาน</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-warning d-inline-flex align-items-center gap-1 px-3 text-white"
                                        onclick="GetEditCourse('<?php echo \App\Utility\Cipher::encrypt($row['course_id']); ?>');" title="แก้ไข" aria-label="แก้ไขคอร์สเรียน">
                                        <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                                        <span>แก้ไข</span>
                                    </button>
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

