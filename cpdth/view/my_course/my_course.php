<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
// อ่านข้อมูล JSON ที่ถูกส่งมาจาก POST
$data = json_decode(file_get_contents('php://input'), true);
$enrollment = $data['enrollment'] ?? [];

if (!function_exists('formatThaiDate')) {
    function formatThaiDate($dateStr) {
        if (empty($dateStr) || $dateStr === '0000-00-00 00:00:00') return '';
        $time = strtotime($dateStr);
        if ($time === false) return '';
        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
        ];
        $d = date('j', $time);
        $m = $months[(int)date('n', $time)] ?? '';
        $y = date('Y', $time);
        $hm = date('H:i', $time);
        return "$d $m $y $hm";
    }
}
?>
<div class="col-12" style="width: 100%; max-width: 100%;">
    <div class="content-card">
        <div class="tab-content" id="mainTabContent">
            <div class="tab-pane show active" id="tab-course">
                <h3 class="tab-title"><i class="bi bi-play-btn-fill"></i> คอร์สเรียนของฉัน</h3>

                <?php if (empty($enrollment)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-journal-x" style="font-size: 3rem;"></i>    
                        <p class="mt-3">คุณยังไม่มีคอร์สเรียนในขณะนี้</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($enrollment as $item): 
                        // เช็คการหมดอายุ
                        date_default_timezone_set('Asia/Bangkok');
                        $now = time();
                        $expiryTime = !empty($item['enroll_expiry_date']) ? strtotime($item['enroll_expiry_date']) : null;
                        $isExpired = $expiryTime !== null && $expiryTime < $now;
                        
                        if (!empty($item['course_cover_image'])) {
                            $imgRaw = trim($item['course_cover_image']);
                            if (strpos($imgRaw, 'http') === 0 || strpos($imgRaw, 'data:') === 0) {
                                $imgSrc = $imgRaw;
                            } else {
                                $imgPath = basename($imgRaw);
                                $imgSrc = 'course_image.php?img=' . urlencode($imgPath);
                            }
                        } else {
                            $imgSrc = '';
                        }
                    ?>
                        <div class="my-course-card">
                            <?php if (!empty($imgSrc)): ?>
                                <img src="<?php echo $imgSrc; ?>" alt="Course Image" class="my-course-img">
                            <?php else: ?>
                                <div class="course-img-box bg-placeholder-gray">
                                    <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="my-course-info">
                                <div class="my-course-title <?php echo $isExpired ? 'title-strike' : ''; ?>">
                                    <?php echo htmlspecialchars($item['course_name'] ?? ''); ?>
                                </div>
                                <div class="my-course-meta">โดย <?php echo htmlspecialchars($item['course_instructor'] ?? 'ไม่ระบุ'); ?></div>
                                <div class="my-course-meta">
                                    CPD บัญชี <?php echo number_format((float)($item['course_cpd_hour'] ?? 0), 2); ?> 
                                    จรรยาบรรณ <?php echo number_format((float)($item['course_cpd_ethics'] ?? 0), 2); ?> 
                                    อื่นๆ <?php echo number_format((float)($item['course_cpd_other'] ?? 0), 2); ?>
                                </div>
                                <div class="my-course-meta">
                                    CPA บัญชี <?php echo number_format((float)($item['course_cpa_hour'] ?? 0), 2); ?> 
                                    จรรยาบรรณ <?php echo number_format((float)($item['course_cpa_ethics'] ?? 0), 2); ?> 
                                    อื่นๆ <?php echo number_format((float)($item['course_cpa_other'] ?? 0), 2); ?>
                                </div>
                                
                                <?php if (!empty($item['enroll_date'])): ?>
                                    <div class="my-course-meta">
                                        วันที่อบรม <?php echo formatThaiDate($item['enroll_date']); ?> 
                                        <?php if (!empty($item['enroll_expiry_date'])): ?>
                                            ถึง <?php echo formatThaiDate($item['enroll_expiry_date']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($item['enroll_period']) && $item['enroll_period'] > 0): ?>
                                    <div class="my-course-meta">ระยะเวลาอบรม <?php echo (int)$item['enroll_period']; ?> วัน</div>
                                <?php elseif ($item['course_period'] > 0): ?>
                                    <div class="my-course-meta">ระยะเวลาอบรม <?php echo (int)$item['course_period']; ?> วัน</div>
                                <?php endif; ?>

                                 <?php if (!$isExpired && !empty($item['enroll_expiry_date'])): ?>
                                    <div class="my-course-meta text-danger" style="font-weight: 500;">
                                        <i class="bi bi-clock-history"></i> เรียนได้ถึงวันที่: <?php echo formatThaiDate($item['enroll_expiry_date']); ?> น.
                                    </div>
                       <?php endif; ?>

                                <?php if (isset($item['enroll_payment_status']) && $item['enroll_payment_status'] === 'reject'): ?>
                                    <div class="mt-auto d-flex flex-column align-items-end gap-2 w-100 pt-2">
                                        <div class="text-danger" style="font-size: 1rem; font-weight: 500;">
                                             คำสั่งซื้อไม่สำเร็จ
                                        </div>
                                        <?php if (!empty($item['latest_order_id'])): ?>
                                        <button class="btn btn-sm btn-danger" style="border-radius: 6px; padding: 0.375rem 1rem;" onclick="location.href='profile-menu?tab=payment_history&order_id=<?php echo $item['latest_order_id']; ?>'">
                                            ดูรายละเอียด
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (isset($item['enroll_payment_status']) && ($item['enroll_payment_status'] === '0' || $item['enroll_payment_status'] === 'pending')): ?>
                                    <div class="text-warning mt-auto pt-2 align-self-end" style="font-size: 1rem; font-weight: 500;"><i class="bi bi-hourglass-split"></i> รอเจ้าหน้าที่อนุมัติคำสั่งซื้อ</div>
                                <?php elseif (isset($item['enroll_access']) && $item['enroll_access'] == 0): ?>
                                    <div class="text-danger mt-auto pt-2 align-self-end" style="font-size: 1rem; font-weight: 500;"> คุณไม่มีสิทธิ์เข้าสู่บทเรียน</div>
                                <?php elseif ($isExpired): ?>
                                    <div class="text-expired"><i class="bi bi-exclamation-circle-fill"></i> คอร์สเรียนหมดอายุแล้ว</div>
                                <?php else: ?>
                                    <div class="mt-auto d-flex flex-column align-items-end gap-2 w-100">
                                        <?php if (!empty($item['has_passed_exam']) && empty($item['enroll_is_completed'])): ?>
                                            <div class="text-warning fw-bold" style="font-size: 0.95rem;">
                                                <i class="bi bi-hourglass-split"></i> รออนุมัติใบประกาศ
                                            </div>
                                        <?php endif; ?>
                                        <button class="btn-enter-course" onclick="location.href='study_course?key=<?php echo \App\Utility\Cipher::encrypt($item['enroll_id']); ?>'">
                                            <i class="bi bi-mortarboard-fill"></i> เข้าสู่บทเรียน
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination Controls -->
                    <div id="course-pagination" class="d-flex justify-content-center mt-4 pb-3"></div>

                    <script>
                        $(document).ready(function() {
                            const itemsPerPage = 5;
                            const $items = $('.my-course-card');
                            const totalItems = $items.length;
                            const totalPages = Math.ceil(totalItems / itemsPerPage);
                            
                            window.showCoursePage = function(page, e) {
                                if(e) e.preventDefault();
                                
                                $items.hide();
                                $items.slice((page - 1) * itemsPerPage, page * itemsPerPage).fadeIn(200);
                                
                                let paginationHtml = '<ul class="pagination">';
                                
                                // Prev Button
                                if (page > 1) {
                                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showCoursePage(${page - 1}, event)"><i class="bi bi-chevron-left"></i></a></li>`;
                                }
                                
                                // Page Numbers
                                for (let i = 1; i <= totalPages; i++) {
                                    if (i === page) {
                                        paginationHtml += `<li class="page-item active"><a class="page-link" href="#" onclick="showCoursePage(${i}, event)">${i}</a></li>`;
                                    } else {
                                        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showCoursePage(${i}, event)">${i}</a></li>`;
                                    }
                                }
                                
                                // Next Button
                                if (page < totalPages) {
                                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="showCoursePage(${page + 1}, event)"><i class="bi bi-chevron-right"></i></a></li>`;
                                }
                                
                                paginationHtml += '</ul>';
                                
                                $('#course-pagination').html(paginationHtml);
                            };
                            
                            if (totalItems > itemsPerPage) {
                                showCoursePage(1);
                            }
                        });
                    </script>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
