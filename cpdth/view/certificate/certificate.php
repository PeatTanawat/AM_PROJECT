<?php
$json = file_get_contents('php://input');
$data = json_decode($json, true) ?: [];
$certificate_list = $data['certificate_list'] ?? $data['data'] ?? [];
?>
<div class="col-12" style="width: 100%; max-width: 100%;">
    <div class="content-card">
        <div class="tab-content" id="mainTabContent">

            <div class="tab-pane" id="tab-cert">
                <h3 class="tab-title"><i class="bi bi-file-earmark-check-fill"></i> ใบรับรองการสอบ</h3>
                <div class="table-container">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <!-- <th class="text-center">#</th> -->
                                <th class="text-center">หมายเลขใบรับรอง</th>
                                <th class="text-center">ชื่อคอร์สเรียน</th>
                                <th class="text-center">วิทยากร</th>
                                <th class="text-center">คะแนนสอบเฉลี่ย</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($certificate_list)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">ไม่มีข้อมูลใบรับรองการสอบ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($certificate_list as $index => $cert): ?>
                                    <tr>
                                        <!-- <td data-label="#" class="text-center"><?php echo $index + 1; ?></td> -->
                                        <td data-label="หมายเลขใบรับรอง" class="text-center"><?php echo htmlspecialchars($cert['cert_no'] ?? '-'); ?></td>
                                        <td data-label="ชื่อคอร์สเรียน" class="text-center" style="white-space: wrap;" ><?php echo htmlspecialchars($cert['course_name'] ?? '-'); ?></td>
                                        <td  style="white-space: nowrap;" data-label="วิทยากร" class="text-center"><?php echo htmlspecialchars($cert['examiner'] ?? '-'); ?></td>
                                        <td  style="white-space: nowrap;" data-label="คะแนนสอบเฉลี่ย" class="text-center"><?php echo htmlspecialchars($cert['score_txt'] ?? '-'); ?></td>
                                        <td data-label="สถานะ" class="text-center">
                                            <?php if (!empty($cert['is_approved'])): ?>
                                                <span class="status-badge bg-success-badge">อนุมัติ</span>
                                            <?php else: ?>
                                                <span class="status-badge bg-warning-badge" style="background:#fff3cd; color:#856404; padding: 4px 8px; border-radius: 4px;">รออนุมัติ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="" class="text-center">
                                            <?php if (!empty($cert['is_approved'])): ?>
                                                <button type="button" class="btn-download" onclick="chooseCertType('<?php echo htmlspecialchars($cert['enroll_key'] ?? ''); ?>', <?php echo (int)($cert['course_id'] ?? 0); ?>, <?php echo (int)($cert['enroll_id'] ?? 0); ?>)">ดาวน์โหลด</button>
                                            <?php else: ?>
                                                <button type="button" class="btn-download" style="background:#ccc; cursor:not-allowed;" disabled>ดาวน์โหลด</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php 
                $total_pages = $data['total_pages'] ?? 1;
                $current_page = $data['current_page'] ?? 1;
                
                if (!empty($certificate_list) && $total_pages > 1): ?>
                    <div id="cert-pagination" class="d-flex justify-content-center mt-4 pb-3">
                        <ul class="pagination">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.loaddatacertificate(<?php echo $current_page - 1; ?>); return false;"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $current_page - 2);
                            $endPage = min($total_pages, $current_page + 2);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.loaddatacertificate(1); return false;">1</a></li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <li class="page-item active"><a class="page-link" href="#" onclick="window.loaddatacertificate(<?php echo $i; ?>); return false;"><?php echo $i; ?></a></li>
                                <?php else: ?>
                                    <li class="page-item"><a class="page-link" href="#" onclick="window.loaddatacertificate(<?php echo $i; ?>); return false;"><?php echo $i; ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $total_pages): ?>
                                <?php if ($endPage < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.loaddatacertificate(<?php echo $total_pages; ?>); return false;"><?php echo $total_pages; ?></a></li>
                            <?php endif; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.loaddatacertificate(<?php echo $current_page + 1; ?>); return false;"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof chooseCertType !== 'function') {
    function chooseCertType(enrollKey, courseId, enrollId) {
        var param = enrollKey ? ('key=' + encodeURIComponent(enrollKey)) : ('id=' + (enrollId || 0));
        window.open('print/print_etax_certificate.php?type=certificate&' + param + (courseId ? '&course_id=' + courseId : '') + '&cert_type=cpd&t=' + new Date().getTime(), '_blank');
    }
}
</script>
