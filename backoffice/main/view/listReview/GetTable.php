<?php
// รีวิวจากลูกค้า — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// ดาวคะแนน: เต็ม/ว่าง 5 ดวง ตามค่า rating (1-5) โดยใช้ Remix Icons
$stars = function ($rating): string {
    $n = max(0, min(5, (int) $rating));
    $html = '<span class="review-stars" aria-hidden="true">';
    for ($i = 1; $i <= 5; $i++) {
        $icon = $i <= $n ? 'ri-star-fill' : 'ri-star-line';
        $html .= '<i class="' . $icon . '" style="font-size:16px;"></i>';
    }
    $html .= '</span>';
    return $html;
};
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width: 60px;">ลำดับ</th>
                        <th scope="col">ผู้รีวิว</th>
                        <th scope="col" class="text-center" style="width: 140px;">คะแนน</th>
                        <th scope="col">ความคิดเห็น</th>
                        <th scope="col" class="text-center" style="width: 130px;">วันที่รีวิว</th>
                        <th scope="col" class="text-center" style="width: 130px;">สถานะแสดงผล</th>
                        <th scope="col" class="text-center" style="width: 180px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $n = ($page - 1) * $per_page; foreach ($list as $row): $n++; ?>
                        <tr>
                            <td class="text-center"><?php echo $n; ?></td>
                            <td>
                                <div class="fw-medium"><?php echo $esc($row['reviewer'] ?? '-'); ?></div>
                                <!-- <div class="text-secondary small"><?php echo $esc($row['user_email'] ?? ''); ?></div> -->
                            </td>
                            <td class="text-center"><?php echo $stars($row['rating'] ?? 0); ?></td>
                            <td class="text-secondary"><?php echo nl2br($esc(mb_strimwidth((string) ($row['comment'] ?? ''), 0, 120, '…'))); ?></td>
                            <td class="text-center text-secondary"><?php echo $esc(date('d/m/Y', strtotime((string) ($row['created_at'] ?? '')))); ?></td>
                            <td class="text-center">
                                <?php if ((string) ($row['is_approved'] ?? '0') === '1'): ?>
                                    <span class="badge bg-success">แสดงผล</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ซ่อนอยู่</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-warning text-white"
                                        style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 75px;"
                                        onclick="GetEditReview('<?php echo $esc($row['review_id']); ?>');">
                                        <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">edit</span>แก้ไข
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger text-white"
                                        style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 75px;"
                                        onclick="GetDeleteReview('<?php echo $esc($row['review_id']); ?>');">
                                        <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">delete</span>ลบ
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

