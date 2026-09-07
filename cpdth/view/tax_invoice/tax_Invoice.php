<?php
$data = json_decode(file_get_contents('php://input'), true);
$etax_list = $data['etax_list'] ?? [];
$total_pages = $data['total_pages'] ?? 1;
$current_page = $data['current_page'] ?? 1;

if (!function_exists('formatThaiDateNum')) {
    function formatThaiDateNum($dateStr)
    {
        if (empty($dateStr) || $dateStr === '0000-00-00 00:00:00')
            return '';
        $time = strtotime($dateStr);
        if ($time === false)
            return '';
        $d = date('d', $time);
        $m = date('m', $time);
        $y = date('Y', $time) + 543;
        return "$d/$m/$y";
    }
}
?>
<div class="col-12" style="width: 100%; max-width: 100%;">
    <div class="content-card">
        <div class="tab-content" id="mainTabContent">

            <div class="tab-pane" id="tab-tax">
                <h3 class="tab-title"><i class="bi bi-receipt"></i> ใบกำกับภาษี</h3>
                <div class="table-container">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th class="text-center">เลขที่เอกสาร</th>
                                <th class="text-center">ชื่อ-นามสกุล</th>
                                <th class="text-center">วันที่ในเอกสาร</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($etax_list)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">ไม่มีข้อมูลใบกำกับภาษี</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($etax_list as $tax): 
                                    $tax_id_str = $tax['user_citizen_id'] ?? '';
                                    $tax_digits = preg_replace('/\D/', '', (string)$tax_id_str);
                                    $pwd = strlen($tax_digits) >= 4 ? substr($tax_digits, -4) : '';
                                ?>
                                    <tr class="tax-row">
                                        <td data-label="เลขที่เอกสาร" class="text-center"><?php echo htmlspecialchars($tax['etax_no'] ?? '-'); ?>
                                        </td>
                                        <td data-label="ชื่อ-นามสกุล" class="text-center"><?php echo htmlspecialchars($tax['addr_name'] ?? '-'); ?></td>
                                        <td data-label="วันที่ในเอกสาร" class="text-center"><?php echo formatThaiDateNum($tax['created_at']); ?>
                                        </td>
                                        <td data-label="สถานะ" class="text-center"><span
                                                class="status-badge bg-success-badge">ออกใบกำกับภาษีแล้ว</span></td>
                                        <td data-label="" class="text-center">
                                            <button type="button" class="btn-download"
                                                onclick="downloadEtax(<?php echo (int) $tax['order_id']; ?>, '<?php echo htmlspecialchars($pwd); ?>')">ดาวน์โหลด</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($etax_list) && $total_pages > 1): ?>
                    <div id="tax-pagination" class="d-flex justify-content-center mt-4 pb-3">
                        <ul class="pagination">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.GetTaxInvoice(<?php echo $current_page - 1; ?>); return false;"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $current_page - 2);
                            $endPage = min($total_pages, $current_page + 2);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.GetTaxInvoice(1); return false;">1</a></li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <li class="page-item active"><a class="page-link" href="#" onclick="window.GetTaxInvoice(<?php echo $i; ?>); return false;"><?php echo $i; ?></a></li>
                                <?php else: ?>
                                    <li class="page-item"><a class="page-link" href="#" onclick="window.GetTaxInvoice(<?php echo $i; ?>); return false;"><?php echo $i; ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $total_pages): ?>
                                <?php if ($endPage < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.GetTaxInvoice(<?php echo $total_pages; ?>); return false;"><?php echo $total_pages; ?></a></li>
                            <?php endif; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item"><a class="page-link" href="#" onclick="window.GetTaxInvoice(<?php echo $current_page + 1; ?>); return false;"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>
<script>
function downloadEtax(orderId, pwd) {
    let displayPwd = pwd ? pwd : 'ไม่ได้ตั้งค่าไว้';
    let targetUrl = 'print/print_etax_certificate.php?type=etax&id=' + orderId;
    
    // 1. เปิดเอกสารใบกำกับภาษี etax ในแท็บใหม่
    let win = window.open(targetUrl, '_blank');
    if (win) {
        try { win.blur(); } catch (e) {}
    }

    // 2. แสดง Swal และดึงโฟกัสกลับมาที่หน้าเดิมและตัว Swal อย่างต่อเนื่อง
    Swal.fire({
        icon: 'success',
        title: 'ดาวน์โหลดใบกำกับภาษี',
        html: 'รหัสผ่านใบกำกับภาษีของคุณคือ <b>' + displayPwd + '</b>',
        confirmButtonText: 'คัดลอก',
        confirmButtonColor: '#6366f1',
        showCloseButton: true,
        allowOutsideClick: true,
        didOpen: () => {
            const pullFocus = () => {
                if (win && !win.closed) {
                    try { win.blur(); } catch (e) {}
                }
                window.focus();
                const confirmBtn = Swal.getConfirmButton();
                if (confirmBtn) confirmBtn.focus();
            };

            pullFocus();
            [50, 100, 200, 400, 700].forEach(delay => {
                setTimeout(pullFocus, delay);
            });
        },
        preConfirm: () => {
            if (pwd) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(pwd);
                } else {
                    let textArea = document.createElement("textarea");
                    textArea.value = pwd;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
            }

            // เล่น animation ตรงส่วน icon ของ swal ใหม่
            const icon = Swal.getIcon();
            if (icon && icon.parentNode) {
                const newIcon = icon.cloneNode(true);
                icon.parentNode.replaceChild(newIcon, icon);
            }

            // ไม่ปิด modal
            return false;
        }
    });
}
</script>
</div>