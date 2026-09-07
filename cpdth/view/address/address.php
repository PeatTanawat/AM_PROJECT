<?php
    $input   = json_decode(file_get_contents('php://input'), true);
    $addresses = $input['addresses'] ?? [];
?>
<div class="col-12" style="width: 100%; max-width: 100%;">
    <div class="content-card">
        <div class="tab-content" id="mainTabContent">

            <div class="tab-pane show active" id="tab-address">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h3 class="tab-title m-0"><i class="bi bi-geo-alt-fill"></i> ที่อยู่ออกใบกำกับภาษี</h3>
                    <button class="btn-add-address" type="button" onclick="Getmodal_Addaddress()">+ เพิ่มที่อยู่</button>
                </div>

                <?php if (!empty($addresses)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-card">
                                <?php if (!empty($address['addr_is_default'])): ?>
                                    <div class="address-default" style="right: 60px; top: 22px;">
                                        <span class="badge px-2 py-1 fw-medium text-white" style="font-family: 'Prompt', sans-serif; font-size: 0.8rem; border-radius: 4px; background-color: #10b981 !important;">ค่าเริ่มต้น</span>
                                    </div>
                                <?php endif; ?>
                                <div class="address-edit" onclick="Getmodal_Editaddress(<?php echo (int)$address['addr_id']; ?>)" style="top: 22px; right: 25px; bottom: auto;">
                                    <i class="bi bi-pencil-fill"></i>
                                </div>
                                <p class="address-text" style="padding-right: 60px;">
                                    <strong><?php echo htmlspecialchars($address['addr_name'] ?? ''); ?></strong>
                                    <?php if (!empty($address['addr_branch_name'])): ?>
                                        <span> (สาขา: <?php echo htmlspecialchars($address['addr_branch_name']); ?>)</span>
                                    <?php endif; ?>
                                    <br>
                                    
                                    <?php if (!empty($address['addr_tax_id'])): ?>
                                        เลขประจำตัวผู้เสียภาษี : <?php echo htmlspecialchars($address['addr_tax_id']); ?><br>
                                    <?php endif; ?>
                                    
                                    เบอร์โทรศัพท์ : <?php echo htmlspecialchars($address['addr_phone'] ?? ''); ?><br>
                                    
                                    <?php 
                                        $detail = $address['addr_detail'] ?? '';
                                        $subdistrict = $address['addr_subdistrict'] ?? '';
                                        $district = $address['addr_district'] ?? '';
                                        $province = $address['addr_province'] ?? '';
                                        $zipcode = $address['addr_zipcode'] ?? '';
                                        
                                        // จัดรูปแบบการแสดงที่อยู่แบบไทย
                                        $full_address = $detail;
                                        if (!empty($subdistrict)) {
                                            $full_address .= (strpos($province, 'กรุงเทพ') !== false) ? ' แขวง' . $subdistrict : ' ตำบล' . $subdistrict;
                                        }
                                        if (!empty($district)) {
                                            $full_address .= (strpos($province, 'กรุงเทพ') !== false) ? ' เขต' . $district : ' อำเภอ' . $district;
                                        }
                                        if (!empty($province)) {
                                            $full_address .= ' จังหวัด' . $province;
                                        }
                                        if (!empty($zipcode)) {
                                            $full_address .= ' ' . $zipcode;
                                        }
                                        echo htmlspecialchars($full_address);
                                    ?>
                                </p>
                                
                                <?php if (empty($address['addr_is_default'])): ?>
                                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top" style="border-color: #f1f5f9 !important;">
                                        <button class="btn btn-sm btn-outline-primary px-3 py-1.5 d-flex align-items-center gap-1" 
                                                style="font-size: 0.85rem; font-weight: 500; font-family: 'Prompt', sans-serif; border-color: #3b5998; color: #3b5998; background: transparent;" 
                                                onclick="setDefaultAddress(<?php echo (int)$address['addr_id']; ?>)"
                                                onmouseover="this.style.backgroundColor='#3b5998'; this.style.color='#fff';"
                                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='#3b5998';">
                                            <i class="bi bi-check-circle-fill"></i> ตั้งค่าเริ่มต้น
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger px-3 py-1.5 d-flex align-items-center gap-1" 
                                                style="font-size: 0.85rem; font-weight: 500; font-family: 'Prompt', sans-serif;" 
                                                onclick="deleteAddress(<?php echo (int)$address['addr_id']; ?>)">
                                            <i class="bi bi-trash-fill"></i> ลบ
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php 
                    $total_pages = $input['total_pages'] ?? 1;
                    $current_page = $input['current_page'] ?? 1;
                    
                    if (!empty($addresses) && $total_pages > 1): ?>
                        <div id="address-pagination" class="d-flex justify-content-center mt-4 pb-3">
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="#" onclick="window.loaddataaddress(<?php echo $current_page - 1; ?>); return false;"><i class="bi bi-chevron-left"></i></a></li>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $current_page - 2);
                                $endPage = min($total_pages, $current_page + 2);
                                
                                if ($startPage > 1): ?>
                                    <li class="page-item"><a class="page-link" href="#" onclick="window.loaddataaddress(1); return false;">1</a></li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <li class="page-item active"><a class="page-link" href="#" onclick="window.loaddataaddress(<?php echo $i; ?>); return false;"><?php echo $i; ?></a></li>
                                    <?php else: ?>
                                        <li class="page-item"><a class="page-link" href="#" onclick="window.loaddataaddress(<?php echo $i; ?>); return false;"><?php echo $i; ?></a></li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $total_pages): ?>
                                    <?php if ($endPage < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="#" onclick="window.loaddataaddress(<?php echo $total_pages; ?>); return false;"><?php echo $total_pages; ?></a></li>
                                <?php endif; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link" href="#" onclick="window.loaddataaddress(<?php echo $current_page + 1; ?>); return false;"><i class="bi bi-chevron-right"></i></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5 text-muted border rounded" style="background-color: #f8fafc; border-style: dashed !important;">
                        <i class="bi bi-geo-alt" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <p class="mt-3" style="font-family: 'Prompt', sans-serif;">ยังไม่มีที่อยู่ออกใบกำกับภาษี</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
