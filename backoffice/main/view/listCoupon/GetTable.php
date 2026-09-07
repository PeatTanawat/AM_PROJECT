<?php
// คูปองส่วนลด — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
// รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$list     = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total    = (int) ($_POST['total'] ?? count($list));
$page     = max(1, (int) ($_POST['page'] ?? 1));
$per_page = max(1, (int) ($_POST['per_page'] ?? 10));
$total_pages = (int) ceil($total / $per_page);
$from = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total);

// แปลงวันที่ Y-m-d -> วว/ดด/ปปปป (พ.ศ.)
function coupon_date($d): string {
    $d = trim((string)$d);
    if ($d === '' || $d === '0000-00-00') return '-';
    $ts = strtotime($d);
    if (!$ts) return '-';
    return date('d/m/', $ts) . ((int) date('Y', $ts) + 543); // ปี พ.ศ.
}
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width: 60px;">ลำดับ</th>
                        <th scope="col">CODE</th>
                        <th scope="col">ประเภทส่วนลด</th>
                        <th scope="col">ใช้ไปแล้ว / คงเหลือ</th>
                        <th scope="col">เงื่อนไข</th>
                        <th scope="col">เริ่ม</th>
                        <th scope="col">สิ้นสุด</th>
                        <th scope="col" class="text-center">สถานะ</th>
                        <th scope="col" class="text-center" style="width: 180px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $n = $from; foreach ($list as $row): ?>
                        <?php
                            $type      = (string)($row['coupon_type'] ?? '');
                            $type_text = $type === 'percent' ? 'เปอร์เซ็นต์' : ($type === 'fixed' ? 'จำนวนเงิน' : '-');
                            $limit     = $row['coupon_limit'];
                            $limit_txt = ($limit === null || $limit === '') ? 'ไม่จำกัด' : (int)$limit;
                            $min       = (float)($row['coupon_min'] ?? 0);
                            $max       = (float)($row['coupon_max'] ?? 0);
                            $is_active = (string)($row['coupon_status'] ?? '0') === '1';
                            // หมดอายุ = มีวันสิ้นสุด และเลยวันไปแล้ว
                            $end_raw    = trim((string)($row['coupon_end'] ?? ''));
                            $end_ts     = ($end_raw !== '' && $end_raw !== '0000-00-00') ? strtotime($end_raw) : false;
                            $is_expired = $end_ts !== false && $end_ts < strtotime(date('Y-m-d'));
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $n++; ?></td>
                            <td class="fw-medium"><?php echo htmlspecialchars($row['coupon_code'] ?? '-'); ?></td>
                            <td><?php echo $type_text; ?></td>
                            <td>
                                <?php
                                    $used      = (int)($row['coupon_used'] ?? 0);
                                    $remaining = $row['coupon_remaining'] ?? null;  // NULL = ไม่จำกัด
                                    $remaining_txt = ($remaining === null) ? 'ไม่จำกัด' : (int)$remaining;
                                ?>
                                <span class="<?php echo $used > 0 ? 'text-danger fw-medium' : 'text-muted'; ?>">
                                    <?php echo $used; ?>
                                </span>
                                / <?php echo $remaining_txt; ?>
                            </td>
                            <td>
                                <div class="small fw-medium text-primary mb-1">ส่วนลด: <?php echo number_format($row['coupon_no'] ?? 0, 0); ?> <?php echo $type === 'percent' ? '%' : '฿'; ?></div>
                                <div class="small">ยอดซื้อขั้นต่ำ: <?php echo number_format($min, 0); ?> ฿</div>
                                <?php if ($max > 0): ?>
                                    <div class="small text-secondary">ลดสูงสุด: <?php echo number_format($max, 0); ?> ฿</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo coupon_date($row['coupon_start'] ?? ''); ?></td>
                            <td><?php echo coupon_date($row['coupon_end'] ?? ''); ?></td>
                            <td class="text-center">
                                <?php $next_status = $is_active ? '0' : '1'; ?>
                                <span class="badge <?php echo $is_active ? 'bg-success' : 'bg-secondary'; ?>"
                                      style="cursor:pointer; user-select:none;"
                                      title="คลิกเพื่อ<?php echo $is_active ? 'ปิด' : 'เปิด'; ?>ใช้งาน"
                                      onclick="ToggleCouponStatus('<?php echo $row['coupon_id']; ?>', '<?php echo $next_status; ?>');">
                                    <?php echo $is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                </span>
                                <?php if ($is_expired): ?>
                                    <span class="badge bg-danger ms-1" title="เลยวันสิ้นสุดแล้ว">หมดอายุ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-sm btn-info text-white"
                                        style="display: inline-flex; align-items: center; justify-content: center; gap: 5px;"
                                        onclick="GetEditCoupon('<?php echo \App\Utility\Cipher::encrypt($row['coupon_id']); ?>');">
                                        <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 16px;">search</span>
                                        รายละเอียด
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

