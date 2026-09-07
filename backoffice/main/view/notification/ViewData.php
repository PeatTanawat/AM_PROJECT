<?php
// รับข้อมูลจาก POST
$data = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
$total = isset($_POST['total']) ? (int)$_POST['total'] : 0;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$per_page = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 20;

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>

<div class="default-table-area">
    <div class="table-responsive">
        <table class="table align-middle w-100" id="PageTable">
            <thead>
                <tr>
                    <th scope="col" class="text-center" style="width: 60px;">ลำดับ</th>
                    <th scope="col">หัวข้อ</th>
                    <th scope="col">รายละเอียด</th>
                    <th scope="col" class="text-center">ประเภท</th>
                    <th scope="col" class="text-center" style="width: 150px;">เวลา</th>
                    <th scope="col" class="text-center" style="width: 100px;">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="100" class="text-center py-5">
                            <div class="list-empty-icon mb-2">
                                <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 48px; color: #ccc;">inbox</span>
                            </div>
                            <div class="list-empty-title text-muted">ไม่พบข้อมูลการแจ้งเตือน</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $index => $row): 
                        $no = (($page - 1) * $per_page) + $index + 1;
                        $isUnread = ($row['is_read'] == 0);
                        // ถ้ายังไม่ได้อ่าน ใส่พื้นหลังสีอ่อน และตัวหนังสือหนา เพื่อให้เด่นชัด
                        $rowClass = $isUnread ? 'bg-light' : '';
                        $textClass = $isUnread ? 'fw-bold text-dark' : 'text-muted';
                    ?>
                        <tr class="<?php echo $rowClass ?>">
                            <td class="text-center text-muted"><?php echo $no ?></td>
                            <td>
                                <a href="<?php echo $esc($row['link_url'] ?? '#') ?>" class="text-decoration-none <?php echo $textClass ?>" onclick="markItemAsRead(<?php echo $row['noti_id'] ?>, this)">
                                    <?php echo $esc($row['title']) ?>
                                </a>
                            </td>
                            <td class="<?php echo $textClass ?>"><?php echo $esc($row['message']) ?></td>
                            <td class="text-center">
                                <?php if ($row['type'] === 'user_verify'): ?>
                                    <span class=" text-dark">ยืนยันตัวตน</span>
                                <?php elseif ($row['type'] === 'course_approval'): ?>
                                    <span class=" text-dark">คอร์สเรียน</span>
                                <?php elseif ($row['type'] === 'order_slip'): ?>
                                    <span class=" text-dark">คำสั่งซื้อ</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo $esc($row['type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-muted small">
                                <?php echo date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isUnread): ?>
                                    <span class="badge bg-danger">ใหม่</span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($data)): ?>
    <?php include dirname(__DIR__) . '/_pagination.php'; ?>
<?php endif; ?>
