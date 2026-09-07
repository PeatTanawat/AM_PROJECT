<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
$input = json_decode(file_get_contents('php://input'), true);
$courses = $input['courses'] ?? [];

if (empty($courses)) {
    ?>
    <div style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 100px 20px; text-align: center;">
        <svg width="100" height="100" viewBox="0 0 24 24" style="margin-bottom: 20px;">
            <circle cx="12" cy="12" r="11" fill="#cbd5e1" />
            <path d="M12 7v6" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="12" cy="16.5" r="1.5" fill="#ffffff" />
        </svg>
        <div style="font-size: 1.1rem; color: #64748b; font-weight: 500;">ไม่พบรายการคอร์สเรียน</div>
    </div>
    <?php
    exit;
}

foreach ($courses as $c):
    if (!empty($c['course_cover_image'])) {
        $imgRaw = trim($c['course_cover_image']);
        if (strpos($imgRaw, 'http') === 0 || strpos($imgRaw, 'data:') === 0) {
            $imgSrc = $imgRaw;
        } else {
            $imgPath = basename($imgRaw);
            $imgSrc = 'course_image.php?img=' . urlencode($imgPath);
        }
        $imgHtml = '<img src="' . $imgSrc . '" alt="' . htmlspecialchars($c['course_name']) . '" class="course-thumb-img" onerror="this.style.display=\'none\';">';
    } else {
        $imgHtml = '<span style="color:#94a3b8;">No Image</span>';
    }
    
    $instructor = !empty($c['course_instructor']) ? htmlspecialchars($c['course_instructor']) : 'ชื่อวิทยากร';
    
    $oldPriceHtml = !empty($c['old_price_fmt']) 
        ? '<div class="price-original">' . htmlspecialchars($c['old_price_fmt']) . ' ฿</div>' 
        : '<div class="price-original">&nbsp;</div>';
    
    $currentPrice = !empty($c['final_price_fmt']) ? htmlspecialchars($c['final_price_fmt']) : '0';
    
    $cpdAccount = !empty($c['course_cpd_hour']) ? number_format((float)$c['course_cpd_hour'], 2) : '0.00';
    $cpdEthics = !empty($c['course_cpd_ethics']) ? number_format((float)$c['course_cpd_ethics'], 2) : '0.00';
    $cpdOther = !empty($c['course_cpd_other']) ? number_format((float)$c['course_cpd_other'], 2) : '0.00';

    $cpaAccount = !empty($c['course_cpa_hour']) ? number_format((float)$c['course_cpa_hour'], 2) : '0.00';
    $cpaEthics = !empty($c['course_cpa_ethics']) ? number_format((float)$c['course_cpa_ethics'], 2) : '0.00';
    $cpaOther = !empty($c['course_cpa_other']) ? number_format((float)$c['course_cpa_other'], 2) : '0.00';
?>
    <?php $encryptedKey = \App\Utility\Cipher::encrypt($c['course_id']); ?>
    <div class="course-card new-design" onclick="goToDetail('<?php echo $encryptedKey; ?>')" data-key="<?php echo htmlspecialchars($encryptedKey); ?>" data-course="<?php echo htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="course-card-img-wrap">
            <?php echo $imgHtml; ?>
        </div>
        <div class="course-card-body">
            <div class="badge-tag">เก็บชั่วโมง</div>
            <h5 class="course-title"><?php echo htmlspecialchars($c['course_name']); ?></h5>
            <div class="course-instructor">
                <div class="instructor-avatar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span><?php echo $instructor; ?></span>
            </div>
            <div class="hours-table">
                <div class="row m-0 ht-header">
                    <div class="col-6 ht-col">ประเภท</div>
                    <div class="col-3 ht-col text-center cpd-text">CPD</div>
                    <div class="col-3 ht-col text-center cpa-text">CPA</div>
                </div>
                <div class="row m-0 ht-row">
                    <div class="col-6 ht-col">การบัญชี</div>
                    <div class="col-3 ht-col text-center ht-val"><?php echo $cpdAccount; ?></div>
                    <div class="col-3 ht-col text-center ht-val"><?php echo $cpaAccount; ?></div>
                </div>
                <div class="row m-0 ht-row">
                    <div class="col-6 ht-col">จรรยาบรรณ</div>
                    <div class="col-3 ht-col text-center ht-val"><?php echo $cpdEthics; ?></div>
                    <div class="col-3 ht-col text-center ht-val"><?php echo $cpaEthics; ?></div>
                </div>
                <div class="row m-0 ht-row">
                    <div class="col-6 ht-col">อื่นๆ</div>
                    <div class="col-3 ht-col text-center ht-val"><?php echo $cpdOther; ?></div>
                    <div class="col-3 ht-col text-center ht-val"><?php echo $cpaOther; ?></div>
                </div>
            </div>
            <div class="course-footer">
                <div class="price-box">
                    <?php echo $oldPriceHtml; ?>
                    <div class="price-current"><?php echo $currentPrice; ?> <span>฿</span></div>
                </div>
                <button class="btn-add-cart-new" type="button" onclick="event.stopPropagation(); addCart(<?php echo (int)$c['course_id']; ?>)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    เพิ่มเข้าตะกร้า
                </button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
