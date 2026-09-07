<?php
$c = json_decode(file_get_contents('php://input'), true);

if (empty($c)) {
    echo '<p style="padding:20px; text-align:center;">ไม่พบข้อมูลรายละเอียดคอร์ส</p>';
    exit;
}

$instructor = !empty($c['course_instructor']) ? $c['course_instructor'] : 'ไม่มีข้อมูลผู้สอน';
$courseDetail = !empty($c['course_detail']) ? $c['course_detail'] : '<p>ไม่มีรายละเอียด</p>';
$groupName = !empty($c['group_name']) ? $c['group_name'] : '-';
$coursePeriod = isset($c['course_period']) ? (int)$c['course_period'] : 0;

if (!empty($c['course_cover_image'])) {
    $imgRaw = trim($c['course_cover_image']);
    if (strpos($imgRaw, 'http') === 0 || strpos($imgRaw, 'data:') === 0) {
        $imgSrc = $imgRaw;
    } else {
        $imgPath = basename($imgRaw);
        $imgSrc = 'course_image.php?img=' . urlencode($imgPath);
    }
    $imgHtml = '<img src="' . $imgSrc . '" alt="Cover" style="width: 100%; height: auto; display: block; object-fit: cover;">';
} else {
    $imgHtml = '<div style="padding: 40px; color: #94a3b8;">ไม่มีรูปภาพ</div>';
}

$formatCode = function($code) {
    return $code ? preg_replace('/^-|-$/', '', preg_replace('/-+/', '-', preg_replace('/[\[\]]+/', '-', $code))) : '';
};

$month = (int)date('n');
$quarter = ceil($month / 3);

$cpd_key = 'course_code_cpd_' . $quarter;
$cpa_key = 'course_code_cpa_' . $quarter;

$cpd_code = !empty($c[$cpd_key]) ? $c[$cpd_key] : '';
$cpa_code = !empty($c[$cpa_key]) ? $c[$cpa_key] : '';

$codeHtml = '';
if (!empty($cpd_code)) $codeHtml .= 'CPD : ' . htmlspecialchars($formatCode($cpd_code)) . '<br>';
if (!empty($cpa_code)) $codeHtml .= 'CPA : ' . htmlspecialchars($formatCode($cpa_code));
if (empty($codeHtml)) $codeHtml = '-';

$has_promo = isset($c['course_promotion']) && $c['course_promotion'] !== '' && $c['course_promotion'] !== null && (float)$c['course_promotion'] > 0;

if ($has_promo) {
    $price_main = number_format((float)$c['course_promotion']);
    $price_strike = number_format((float)($c['course_price'] ?? 0));
} else {
    $price_main = number_format((float)($c['course_price'] ?? 0));
    $price_strike = '';
}
?>
<!-- Main content (left) -->
<section class="detail-content" style="flex: 1; min-width: 0; background: #fff; padding: 30px; font-family: 'Kanit', sans-serif;">
    <a href="javascript:void(0)" onclick="goBackToList()" style="text-decoration: none; color: #64748b; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 25px; transition: color 0.2s;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> ย้อนกลับไปหน้าคอร์สเรียน
    </a><br>
    
    <!-- Category Badge tag -->
    <div style="background-color: #e0f2fe; color: #0284c7; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-bottom: 10px;">
        <?php echo htmlspecialchars($groupName); ?>
    </div>

    <h2 id="detailTitle" style="color: #1e3a8a; font-size: 1.6rem; font-weight: 600; margin-bottom: 8px; line-height: 1.4;">
        <?php echo htmlspecialchars($c['course_name']); ?>
    </h2>
    
    <div id="detailInstructor" style="color: #64748b; font-size: 0.95rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <div style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
            <i class="bi bi-person-fill"></i>
        </div>
        <span>สอนโดย <strong style="color: #334155;"><?php echo htmlspecialchars($instructor); ?></strong></span>
    </div>

    <!-- Cover Image inside Left Content -->
    <div style="border-radius: 12px; overflow: hidden; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); max-width: 600px;">
        <?php echo $imgHtml; ?>
    </div>

    <!-- Tabs Header -->
    <div style="display: flex; gap: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 25px;">
        <div style="font-weight: 600; color: #3b5998; border-bottom: 3px solid #3b5998; padding-bottom: 10px; cursor: pointer;">
            รายละเอียดหลักสูตร
        </div>
        <!-- <div style="font-weight: 500; color: #94a3b8; padding-bottom: 10px; cursor: pointer;">
            รีวิวจากผู้เรียน
        </div> -->
    </div>

    <!-- Syllabus Details & Structured Course Conditions -->
    <div id="detailHtml" class="course-html-content" style="color: #475569; font-size: 0.95rem; line-height: 1.8;">
        
        <!-- 1. Training Conditions -->
        <div style="margin-bottom: 25px;">
            <h4 style="font-size: 1.05rem; font-weight: 600; color: #1e293b; margin-bottom: 8px;">เงื่อนไขการเข้าอบรม</h4>
            <div style="font-size: 0.95rem; color: #475569; line-height: 1.7;">
                หลักสูตรมีอายุ <?php echo $coursePeriod; ?> วัน ต้องอบรมและสอบภายใน <?php echo $coursePeriod; ?> วัน<br>
                ต้องการชั่วโมง CPD ปีไหนให้ซื้อปีนั้น <span style="background-color: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; font-weight: 600; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;">[ใช้เก็บ CPD ข้ามปีไม่ได้]</span>
            </div>
        </div>

        <!-- 2. Course Codes List -->
        <?php 
        $be_year = (int)date('Y') + 543;
        $be_short = $be_year % 100;
        ?>
        <div style="margin-bottom: 25px;">
            <h4 style="font-size: 1.05rem; font-weight: 600; color: #1e293b; margin-bottom: 8px;">รหัสหลักสูตร</h4>
            <div style="font-size: 0.95rem; color: #475569; line-height: 1.8;">
                <?php if (!empty($c['course_code_cpd_1'])): ?>
                    ไตรมาส 1/<?php echo $be_short; ?> : <?php echo htmlspecialchars($formatCode($c['course_code_cpd_1'])); ?><br>
                <?php endif; ?>
                <?php if (!empty($c['course_code_cpd_2'])): ?>
                    ไตรมาส 2/<?php echo $be_short; ?> : <?php echo htmlspecialchars($formatCode($c['course_code_cpd_2'])); ?><br>
                <?php endif; ?>
                <?php if (!empty($c['course_code_cpd_3'])): ?>
                    ไตรมาส 3/<?php echo $be_short; ?> : <?php echo htmlspecialchars($formatCode($c['course_code_cpd_3'])); ?><br>
                <?php endif; ?>
                <?php if (!empty($c['course_code_cpd_4'])): ?>
                    ไตรมาส 4/<?php echo $be_short; ?> : <?php echo htmlspecialchars($formatCode($c['course_code_cpd_4'])); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Hour Summary Badges -->
        <div style="margin-bottom: 25px; display: flex; flex-direction: column; gap: 6px;">
            <?php 
            $cpd_hours_text = [];
            if ((float)($c['course_cpd_hour'] ?? 0) > 0) $cpd_hours_text[] = 'บัญชี ' . number_format((float)$c['course_cpd_hour'], 2) . ' ชม.';
            if ((float)($c['course_cpd_ethics'] ?? 0) > 0) $cpd_hours_text[] = 'ชั่วโมงจรรยาบรรณ ' . number_format((float)$c['course_cpd_ethics'], 2) . ' ชม.';
            if ((float)($c['course_cpd_other'] ?? 0) > 0) $cpd_hours_text[] = 'อื่น ๆ ' . number_format((float)$c['course_cpd_other'], 2) . ' ชม.';
            
            if (!empty($cpd_hours_text)):
            ?>
                <div style="font-size: 0.95rem; font-weight: 600; color: #ea580c;">
                    ผู้ทำบัญชีได้ชั่วโมง<?php echo implode(' และ', $cpd_hours_text); ?>
                </div>
            <?php endif; ?>

            <?php 
            $cpa_hours_text = [];
            if ((float)($c['course_cpa_hour'] ?? 0) > 0) $cpa_hours_text[] = 'บัญชี ' . number_format((float)$c['course_cpa_hour'], 2) . ' ชม.';
            if ((float)($c['course_cpa_ethics'] ?? 0) > 0) $cpa_hours_text[] = 'ชั่วโมงจรรยาบรรณ ' . number_format((float)$c['course_cpa_ethics'], 2) . ' ชม.';
            if ((float)($c['course_cpa_other'] ?? 0) > 0) $cpa_hours_text[] = 'อื่น ๆ ' . number_format((float)$c['course_cpa_other'], 2) . ' ชม.';
            
            if (!empty($cpa_hours_text)):
            ?>
                <div style="font-size: 0.95rem; font-weight: 600; color: #ea580c;">
                    ผู้สอบบัญชีได้ชั่วโมง<?php echo implode(' และ', $cpa_hours_text); ?>
                </div>
            <?php endif; ?>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">

        <!-- 4. HTML Detail Content -->
        <div style="margin-top: 10px;">
            <?php echo $courseDetail; ?>
        </div>
    </div>
</section>

<!-- Sidebar (right) -->
<aside class="detail-sidebar" style="width: 350px; min-width: 350px; flex-shrink: 0;">
    <div class="detail-card" style="border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 25px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); font-family: 'Kanit', sans-serif;">
        <!-- Price Area -->
        <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: flex-start;">
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <span style="font-size: 2.2rem; color: #f97316; font-weight: 700;"><?php echo $price_main; ?> ฿</span>
                <?php if (!empty($price_strike)): ?>
                    <span style="font-size: 1.1rem; color: #cbd5e1; text-decoration: line-through; font-weight: 400;"><?php echo $price_strike; ?> ฿</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($price_strike)): ?>
                <div style="display: inline-flex; align-items: center; gap: 4px; background-color: #fee2e2; color: #ef4444; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 9999px; margin-top: 4px;">
                    <i class="bi bi-fire"></i> ราคาพิเศษ
                </div>
            <?php endif; ?>
        </div>

        <!-- Add to Cart Button -->
        <button class="btn-add-cart" type="button" onclick="addCart(<?php echo (int)$c['course_id']; ?>)" style="width: 100%; padding: 14px; font-size: 1.05rem; border-radius: 8px; display: flex; justify-content: center; align-items: center; gap: 8px; background-color: #3b5998; border: none; color: white; font-weight: 500; margin-bottom: 20px;">
            <i class="bi bi-cart3" style="font-size: 1.25rem;"></i>
            <span>เพิ่มลงตะกร้า</span>
        </button>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px; margin-top: 20px;">

        <!-- Course Code Row -->
        <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
            <div style="font-size: 1.4rem; color: #94a3b8; margin-top: 2px;">
                <i class="bi bi-upc-scan"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: #94a3b8; font-weight: 500;">รหัสหลักสูตร</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #334155; line-height: 1.5; margin-top: 2px;">
                    <?php if (!empty($cpd_code)): ?>
                        CPD: <?php echo htmlspecialchars($formatCode($cpd_code)); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($cpa_code)): ?>
                        CPA: <?php echo htmlspecialchars($formatCode($cpa_code)); ?>
                    <?php endif; ?>
                    <?php if (empty($cpd_code) && empty($cpa_code)): ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Duration Row -->
        <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px;">
            <div style="font-size: 1.4rem; color: #94a3b8; margin-top: 2px;">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: #94a3b8; font-weight: 500;">ระยะเวลาอบรม</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #334155; margin-top: 2px;"><?php echo $coursePeriod; ?> วัน</div>
            </div>
        </div>

        <!-- CPD Hours Card -->
        <div style="background-color: #f8fafc; border-radius: 8px; padding: 15px; margin-bottom: 15px; border: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1e293b; font-size: 0.95rem; margin-bottom: 12px;">
                <i class="bi bi-patch-check-fill" style="color: #f97316; font-size: 1.1rem;"></i>
                <span>นับชั่วโมง CPD</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; color: #64748b;">
                    <span>บัญชี:</span>
                    <span style="font-weight: 600; color: #334155;"><?php echo number_format((float)($c['course_cpd_hour'] ?? 0), 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; color: #64748b;">
                    <span>จรรยาบรรณ:</span>
                    <span style="font-weight: 600; color: #334155;"><?php echo number_format((float)($c['course_cpd_ethics'] ?? 0), 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; color: #64748b;">
                    <span>อื่นๆ:</span>
                    <span style="font-weight: 600; color: #334155;"><?php echo number_format((float)($c['course_cpd_other'] ?? 0), 2); ?></span>
                </div>
            </div>
        </div>

        <!-- CPA Hours Card -->
        <div style="background-color: #f8fafc; border-radius: 8px; padding: 15px; border: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1e293b; font-size: 0.95rem; margin-bottom: 12px;">
                <i class="bi bi-award-fill" style="color: #3b5998; font-size: 1.1rem;"></i>
                <span>นับชั่วโมง CPA</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; color: #64748b;">
                    <span>บัญชี:</span>
                    <span style="font-weight: 600; color: #334155;"><?php echo number_format((float)($c['course_cpa_hour'] ?? 0), 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; color: #64748b;">
                    <span>จรรยาบรรณ:</span>
                    <span style="font-weight: 600; color: #334155;"><?php echo number_format((float)($c['course_cpa_ethics'] ?? 0), 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; color: #64748b;">
                    <span>อื่นๆ:</span>
                    <span style="font-weight: 600; color: #334155;"><?php echo number_format((float)($c['course_cpa_other'] ?? 0), 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</aside>
