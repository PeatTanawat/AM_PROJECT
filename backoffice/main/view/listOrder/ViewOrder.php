<?php
// คำสั่งซื้อทั้งหมด — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
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

// วันที่แสดง 2 บรรทัด: บรรทัดบน = วันที่, บรรทัดล่าง = เวลา (เล็ก/จาง)
$date2 = function ($v) use ($esc): string {
    $v = trim((string) $v);
    if ($v === '') { return '<span class="text-muted">-</span>'; }
    $p = preg_split('/\s+/', $v, 2);
    $out = $esc($p[0]);
    if (isset($p[1]) && $p[1] !== '') { $out .= '<br><span class="text-secondary small">' . $esc($p[1]) . '</span>'; }
    return $out;
};

// แปลงสถานะ -> badge (payment_status: 0=รอชำระ 1=สำเร็จ 2=ยกเลิก)
$status_badge = function (string $s): string {
    if ($s === '1') { return '<span class="badge bg-success">สำเร็จแล้ว</span>'; }
    if ($s === '2') { return '<span class="badge bg-danger">ยกเลิก</span>'; }
    return '<span class="badge bg-secondary">รอชำระเงิน</span>';
};
$payment_badge = function (string $s): string {
    if ($s === '1') { return '<span class="badge bg-success">ชำระแล้ว</span>'; }
    if ($s === '2') { return '<span class="badge bg-secondary">ยกเลิก</span>'; }
    return '<span class="badge bg-danger">ยังไม่ได้ชำระ</span>';
};
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width:60px;">ลำดับ</th>
                        <th scope="col">หมายเลขคำสั่งซื้อ</th>
                        <th scope="col">ชื่อลูกค้า</th>
                        <th scope="col">คอร์สเรียน</th>
                        <th scope="col" class="text-end">ยอดรวม</th>
                        <th scope="col" class="text-center">สถานะ</th>
                        <th scope="col" class="text-center">สถานะการชำระเงิน</th>
                        <th scope="col">สั่งซื้อเมื่อ</th>
                        <th scope="col" class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $i = $from; foreach ($list as $row):
                        $order_id = (int) ($row['order_id'] ?? 0);
                        $status   = (string) ($row['status'] ?? '0');
                        $ref      = trim((string) ($row['order_no'] ?? ''));
                        $full     = trim((string) ($row['customer'] ?? ''));
                        $courses  = isset($row['courses']) && is_array($row['courses']) ? $row['courses'] : [];
                        $course_html = !empty($courses)
                            ? implode('<br>', array_map($esc, $courses))
                            : '<span class="text-muted">-</span>';
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td><?php echo $ref !== '' ? $esc($ref) : '<span class="text-muted">ไม่มีข้อมูล</span>' ?></td>
                            <td class="fw-medium"><?php echo $esc($full !== '' ? $full : '-') ?></td>
                            <td class="text-secondary"><?php echo $course_html ?></td>
                            <td class="text-end"><?php echo number_format((float) ($row['total'] ?? 0), 2) ?></td>
                            <td class="text-center"><?php echo $status_badge($status) ?></td>
                            <td class="text-center"><?php echo $payment_badge($status) ?></td>
                            <td class="text-nowrap"><?php echo $date2($row['created'] ?? '') ?></td>
                            <td class="text-center">
                                <a href="order_detail.php?key=<?php echo urlencode(\App\Utility\Cipher::encrypt($order_id)); ?>" 
                                   style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;" 
                                   class="btn btn-sm btn-info text-white">
                                   <span class="material-symbols-outlined" aria-hidden="true">search</span>
                                   ดูรายละเอียด
                                </a>
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

