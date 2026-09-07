<?php
// ใบกำกับภาษี (E-Tax) — view fragment: render ตาราง + pagination จากข้อมูลที่ส่งมาทาง POST
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
                        <th scope="col" class="text-center" style="width:60px;">ลำดับ</th>
                        <th scope="col">เลขที่เอกสาร</th>
                        <th scope="col">ชื่อ-นามสกุล</th>
                        <th scope="col">เลขประจำตัวผู้เสียภาษี</th>
                        <th scope="col">วันที่ในเอกสาร</th>
                        <th scope="col" class="text-center">สถานะ</th>
                        <th scope="col" class="text-center" style="width:140px;">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                    <?php $i = $from; foreach ($list as $row):
                        $order_id = (int) ($row['order_id'] ?? 0);
                        $status   = (string) ($row['status'] ?? '0');
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td class="fw-medium"><?php echo $esc($row['doc_no'] ?? '') ?></td>
                            <td><?php echo $esc($row['name'] ?? '') ?></td>
                            <td><?php echo $esc($row['tax_id'] ?? '') ?></td>
                            <td><?php echo $esc($row['date'] ?? '') ?></td>
                            <td class="text-center">
                                <?php if ($status === '1'): ?>
                                    <span class="badge bg-success">ออกใบกำกับภาษีแล้ว</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ออกใบกำกับไม่สำเร็จ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="etax_view.php?key=<?php echo \App\Utility\Cipher::encrypt($order_id) ?>" class="btn btn-sm d-inline-flex align-items-center justify-content-center p-0 btn-info text-white icon-btn" title="ดูข้อมูล" aria-label="ดูข้อมูลใบกำกับภาษี">
                                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                                    </a>
                                    <?php if ($status === '1'): ?>
                                        <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center p-0 btn-success icon-btn" onclick="DownloadEtax(<?php echo $order_id ?>, '<?php echo \App\Utility\Cipher::encrypt($order_id) ?>')" title="ดาวน์โหลด PDF" aria-label="ดาวน์โหลด PDF ใบกำกับภาษี">
                                            <span class="material-symbols-outlined" aria-hidden="true">download</span>
                                        </button>
                                        <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center p-0 btn-warning icon-btn" onclick="SendEmail(<?php echo $order_id ?>)" title="ส่งอีเมล" aria-label="ส่งอีเมลใบกำกับภาษี">
                                            <span class="material-symbols-outlined" aria-hidden="true">mail</span>
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

