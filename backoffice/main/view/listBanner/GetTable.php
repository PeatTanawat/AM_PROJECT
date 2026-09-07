<?php
// แบนเนอร์ทั้งหมด — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total);
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width: 70px;">ลำดับ</th>
                        <th scope="col">ตัวอย่าง</th>
                        <th scope="col">ลิงก์ปลายทาง</th>
                        <th scope="col" class="text-center">สถานะ</th>
                        <th scope="col" class="text-center" style="width: 110px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php foreach ($list as $row): ?>
                        <?php
                            $is_active = (string)($row['banner_status'] ?? '0') === '1';
                            $img_path  = !empty($row['banner_image']) ? (preg_match('~^https?://~i', (string)$row['banner_image']) ? htmlspecialchars($row['banner_image']) : '../' . htmlspecialchars($row['banner_image'])) : '';
                            $raw_url   = trim((string)($row['banner_url'] ?? ''));
                            // อนุญาตเฉพาะ http/https หรือ path แบบ root-relative — กัน javascript:/data: (XSS)
                            $url       = (preg_match('#^https?://#i', $raw_url) || (isset($raw_url[0]) && $raw_url[0] === '/')) ? htmlspecialchars($raw_url) : '-';
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?php echo (int)$row['banner_order']; ?></td>
                            <td>
                                <?php if ($img_path): ?>
                                    <img src="<?php echo $img_path; ?>"
                                         alt="banner"
                                         style="max-height: 100px; max-width: 300px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <span class="text-muted small">ไม่มีรูป</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($url !== '-'): ?>
                                    <a href="<?php echo $url; ?>" target="_blank" class="text-primary text-break small">
                                        <?php echo $url; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_active): ?>
                                    <span class="badge bg-success">เปิดใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ไม่ได้เปิดใช้งาน</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-sm btn-warning text-white"
                                        style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 75px;"
                                        onclick="GetEditBanner('<?php echo \App\Utility\Cipher::encrypt($row['banner_id']); ?>');">
                                        <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">edit</span>แก้ไข
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

