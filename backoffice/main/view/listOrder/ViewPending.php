<?php
    // คำสั่งซื้อรอยืนยัน — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
    // รับ: data (list), total, page, per_page  ->  คืน HTML แปะใน #result_box

    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

    $list        = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
    $total       = (int) ($_POST['total'] ?? count($list));
    $page        = max(1, (int) ($_POST['page'] ?? 1));
    $per_page    = max(1, (int) ($_POST['per_page'] ?? 10));
    $total_pages = (int) ceil($total / $per_page);
    $from        = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
    $to          = min($page * $per_page, $total);

    $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

    // วันที่แสดง 2 บรรทัด: บรรทัดบน = วันที่, บรรทัดล่าง = เวลา (เล็ก/จาง)
    $date2 = function ($v) use ($esc): string {
    $v = trim((string) $v);
    if ($v === '') {return '<span class="text-muted">-</span>';}
    $p   = preg_split('/\s+/', $v, 2);
    $out = $esc($p[0]);
    if (isset($p[1]) && $p[1] !== '') {$out .= '<br><span class="text-secondary small">' . $esc($p[1]) . '</span>';}
    return $out;
    };
?>
<div class="default-table-area">
        <div class="table-responsive">
            <table class="table align-middle w-100" id="PageTable">
                <thead>
                    <tr>
                        <th scope="col" class="text-center" style="width:60px;">ลำดับ</th>
                        <th scope="col" class="text-center" style="width:180px;">หมายเลขคำสั่งซื้อ</th>
                        <th scope="col" style="min-width:160px;">ชื่อลูกค้า</th>
                        <th scope="col" style="min-width:280px;">คอร์สเรียน</th>
                        <th scope="col" class="text-end text-nowrap" style="width:1%;">ยอดรวม</th>
                        <th scope="col" class="text-nowrap" style="width:1%;">สั่งซื้อเมื่อ</th>
                        <th scope="col" class="text-center text-nowrap" style="width:1%;">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($list)): ?>
                    <?php $i = $from;foreach ($list as $row):
                            $order_id                   = (int) ($row['order_id'] ?? 0);
                            $customer                   = ($row['customer'] ?? '') !== '' ? $row['customer'] : '-';
                            $courses                    = trim((string) ($row['courses'] ?? ''));
                            $course_html                = $courses !== ''
                                ? implode('<br>', array_map($esc, explode("\n", $courses)))
                                : '<span class="text-muted">-</span>';
                            $created = $date2($row['created'] ?? '');
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="text-center"><?php echo $row['ref'] ?></td>
                            <td class="fw-medium"><?php echo $esc($customer) ?></td>
                            <td class="text-secondary"><?php echo $course_html ?></td>
                            <td class="text-end text-nowrap"><?php echo $esc($row['total'] ?? '') ?></td>
                            <td class="text-nowrap"><?php echo $created ?></td>
                            <td class="text-center text-nowrap">
                                <a href="order_detail2.php?key=<?php echo urlencode(\App\Utility\Cipher::encrypt($order_id)); ?>" class="btn btn-sm btn-info text-white">ดูรายละเอียด</a>
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

    <?php if (! empty($list)): ?>
    <?php include dirname(__DIR__) . '/_pagination.php'; ?>
<?php endif; ?>

