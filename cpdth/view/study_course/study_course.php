<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
// อ่านข้อมูล JSON ที่ถูกส่งมาจาก POST
$data = json_decode(file_get_contents('php://input'), true);
$course = $data['course'] ?? null;
$lessons = $data['lessons'] ?? [];
$total_lessons = $data['total_lessons'] ?? 0;
$expiry_text = $data['expiry_text'] ?? '';
$course_id = (int) ($data['course_id'] ?? 0);
$is_expired = $data['is_expired'] ?? false;
$can_take_exam = $data['can_take_exam'] ?? false;
$attempts = $data['attempts'] ?? [];
$is_approved = $data['is_approved'] ?? false;
$is_locked = $data['is_locked'] ?? false;
$enroll_id = (int) ($data['enroll_id'] ?? 0);
$enroll_key = $enroll_id > 0 ? \App\Utility\Cipher::encrypt((string) $enroll_id) : '';
$pass_percent = isset($data['pass_percent']) ? (int) $data['pass_percent'] : (isset($course['course_minimum_score']) ? (int) $course['course_minimum_score'] : null);
$currentUser = \App\Utility\Auth::getUser();
if (!$currentUser) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Unauthorized</div></div>";
    exit;
}

$has_passed = false;
foreach ($attempts as $attempt) {
    if ($attempt['attempt_pass'] == '1' || $attempt['attempt_pass'] == 1) {
        $has_passed = true;
        break;
    }
}

if (!$course) {
    echo "<div class='container my-5'><div class='alert alert-danger'>ไม่พบข้อมูลคอร์สเรียน</div></div>";
    exit;
}

if ($is_expired) {
    echo "<div class='container my-5'><div class='alert alert-warning'>คอร์สเรียนนี้หมดอายุสิทธิ์การเข้าเรียนแล้ว</div></div>";
    exit;
}

// ตกแต่งป้ายรูปภาพปก
if (!empty($course['course_cover_image'])) {
    $imgRaw = trim($course['course_cover_image']);
    if (filter_var($imgRaw, FILTER_VALIDATE_URL) || strpos($imgRaw, 'data:') === 0) {
        $imgSrc = $imgRaw;
    } else {
        $imgPath = basename($imgRaw);
        $imgSrc = '../backoffice/upload/course/' . $imgPath;
    }
} else {
    $imgSrc = '';
}
?>
<div class="container study-container">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="profile-menu.php?tab=course" class="text-decoration-none text-secondary"
            style="font-size: 0.95rem; display: inline-flex; align-items: center; gap: 6px;">
            <i class="bi bi-arrow-left" style="font-size: 1.1rem;"></i> ย้อนกลับไปหน้าคอร์สเรียนของฉัน
        </a>
    </div>

    <div>
        <!-- Course Title & Instructor -->
        <h2 class="course-title-header"><?php echo htmlspecialchars($course['course_name'] ?? ''); ?></h2>
        <div class="course-instructor-text">โดย
            <?php echo htmlspecialchars($course['course_instructor'] ?? 'ไม่ระบุ'); ?>
        </div>

        <!-- Course Summary Box -->
        <div class="course-summary-card">
            <?php if (!empty($imgSrc)): ?>
                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Course Image" class="course-cover-img" onerror="this.onerror=null; this.src='course_image.php?img=<?php echo urlencode(basename($imgSrc)); ?>';">
            <?php else: ?>
                <div class="course-cover-placeholder">
                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                </div>
            <?php endif; ?>

            <div class="summary-stats">
                <div class="stat-row">บทเรียนทั้งหมด : <?php echo $total_lessons; ?> บทเรียน</div>
                <div class="stat-row">เวลาทั้งหมด :
                    <?php echo !empty($course['course_period']) ? (int) $course['course_period'] . ' วัน' : 'ไม่จำกัดระยะเวลา'; ?>
                </div>
                <?php if ($pass_percent !== null && $pass_percent !== ''): ?>
                    <div class="stat-row text-danger fw-bold">เกณฑ์การผ่าน : <?php echo (int) $pass_percent; ?> ข้อ</div>
                <?php endif; ?>
                <?php if (isset($course['course_number_time']) && $course['course_number_time'] !== null && $course['course_number_time'] !== ''): ?>
                    <div class="stat-row text-danger fw-bold">จำนวนครั้งที่สอบได้ : <?php echo (int) $course['course_number_time']; ?> ครั้ง</div>
                <?php endif; ?>
                <?php if (!empty($expiry_text)): ?>
                    <div class="stat-row text-danger">เรียนได้ถึงวันที่ : <?php echo $expiry_text; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Exam History Section -->
        <?php if (!empty($attempts)): ?>
            <style>
                .exam-attempt-list {
                    display: flex;
                    flex-direction: column;
                    gap: 16px;
                    margin-bottom: 30px;
                    margin-top: 20px;
                }

                .exam-attempt-card {
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 20px 24px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
                }

                .exam-attempt-info {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .exam-attempt-status {
                    font-size: 1.05rem;
                    font-weight: 600;
                    color: #475569;
                }

                .exam-attempt-status .status-fail {
                    color: #ef4444;
                    /* red-500 */
                }

                .exam-attempt-status .status-pass {
                    color: #22c55e;
                    /* green-500 */
                }

                .exam-attempt-meta {
                    font-size: 0.9rem;
                    color: #64748b;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .exam-attempt-meta i {
                    font-size: 1.05rem;
                    color: #94a3b8;
                }

                .exam-attempt-badge {
                    background-color: #e0e7ff;
                    /* light purple/blue */
                    color: #3b5998;
                    /* theme deep blue */
                    border-radius: 12px;
                    width: 64px;
                    height: 64px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Kanit', sans-serif;
                    flex-shrink: 0;
                }

                .exam-attempt-badge .badge-num {
                    font-size: 1.6rem;
                    font-weight: 600;
                    line-height: 1.1;
                }

                .exam-attempt-badge .badge-txt {
                    font-size: 0.75rem;
                    font-weight: 500;
                }
            </style>

            <h4 class="section-title">ประวัติการสอบ</h4>
            <div class="exam-attempt-list">
                <?php
                $exam_time = (int) ($course['course_exam_time'] ?? 0);
                $hours = floor($exam_time / 60);
                $minutes = $exam_time % 60;
                $duration_text = $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' ชั่วโมง';

                foreach ($attempts as $idx => $attempt):
                    $attempt_num = $idx + 1;
                    $is_passed = ($attempt['attempt_pass'] == '1' || $attempt['attempt_pass'] == 1);
                    $date_text = !empty($attempt['create_at']) ? date('d/m/Y H:i', strtotime($attempt['create_at'])) : '-';
                    ?>
                    <div class="exam-attempt-card">
                        <div class="exam-attempt-info">
                            <div class="exam-attempt-status">
                                ผลการสอบ :
                                <?php if ($is_passed): ?>
                                    <span class="status-pass">ผ่าน</span>
                                <?php else: ?>
                                    <span class="status-fail">ไม่ผ่าน</span>
                                <?php endif; ?>
                            </div>
                            <div class="exam-attempt-meta">
                                <i class="bi bi-clock"></i>
                                <span>ระยะเวลาในการทำข้อสอบ <?php echo $duration_text; ?></span>
                            </div>
                            <div class="exam-attempt-meta">
                                <i class="bi bi-calendar3"></i>
                                <span>วันที่เริ่มทำข้อสอบ <?php echo $date_text; ?></span>
                            </div>
                        </div>
                        <div class="exam-attempt-badge">
                            <span class="badge-num"><?php echo $attempt_num; ?></span>
                            <span class="badge-txt">ครั้ง</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <!-- Exam Section -->
        <?php
        $has_passed = false;
        $failed_attempts = 0;
        foreach ($attempts as $attempt) {
            if ($attempt['attempt_pass'] == '1' || $attempt['attempt_pass'] == 1) {
                $has_passed = true;
            }
        }

        if ($has_passed):
            ?>
            <!-- Dark Locked Card (Passed - Waiting Approval or Approved) -->
            <div class="exam-section-card exam-locked">
                <div class="exam-lock-overlay">
                    <div class="lock-icon">
                        <?php if ($is_approved && $enroll_id > 0): ?>
                            <span class="material-symbols-outlined" style="color: #22c55e; font-size: 2rem; font-weight: bold;">check</span>
                        <?php else: ?>
                            <i class="bi bi-lock-fill"></i>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_approved && $enroll_id > 0): ?>
                        <button type="button" class="btn btn-primary px-4 py-2 mt-2"
                            style="pointer-events: auto; border-radius: 6px; font-weight: 500; font-size: 0.95rem; background-color: #3b5998; border-color: #3b5998; box-shadow: 0 4px 6px -1px rgba(59, 89, 152, 0.2);"
                            onclick="chooseCertType('<?php echo htmlspecialchars($enroll_key); ?>', <?php echo $enroll_id; ?>)">
                            พิมพ์ใบรับรอง
                        </button>
                    <?php else: ?>
                        <div class="lock-text">รออนุมัติใบประกาศ</div>
                    <?php endif; ?>
                </div>

                <h4 class="exam-section-title">ข้อสอบของคอร์สนี้</h4>

                <div class="exam-info-box-custom">
                    <div class="exam-info-row-custom">
                        <div class="exam-info-label-custom">
                            <i class="bi bi-clock"></i>
                            <span>ระยะเวลาทำข้อสอบ</span>
                        </div>
                        <div class="exam-info-value-custom">
                            <?php
                            $exam_time = (int) ($course['course_exam_time'] ?? 0);
                            echo $exam_time > 0 ? ($exam_time >= 60 ? (int) ($exam_time / 60) . ':00 ชั่วโมง' : $exam_time . ' นาที') : '0:00 ชั่วโมง';
                            ?>
                        </div>
                    </div>
                    <div class="exam-info-row-custom">
                        <div class="exam-info-label-custom">
                            <i class="bi bi-clipboard-check"></i>
                            <span>จำนวนข้อสอบ</span>
                        </div>
                        <div class="exam-info-value-custom">
                            <?php echo (int) ($course['course_number_exam'] ?? 0); ?> ข้อ
                        </div>
                    </div>
                </div>

                <button class="btn-start-exam-custom" disabled>
                    เริ่มทำข้อสอบ
                </button>
            </div>
        <?php elseif ($is_locked): ?>
            <!-- Dark Locked Card (Max Failed Attempts) -->
            <div class="exam-section-card exam-locked">
                <div class="exam-lock-overlay">
                    <div class="lock-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div class="lock-text">ท่านสอบไม่ผ่านเกินจำนวนของคอร์สเรียนนี้ กรุณาติดต่อผู้ดูแลระบบ</div>
                </div>

                <h4 class="exam-section-title">ข้อสอบของคอร์สนี้</h4>

                <div class="exam-info-box-custom">
                    <div class="exam-info-row-custom">
                        <div class="exam-info-label-custom">
                            <i class="bi bi-clock"></i>
                            <span>ระยะเวลาทำข้อสอบ</span>
                        </div>
                        <div class="exam-info-value-custom">
                            <?php
                            $exam_time = (int) ($course['course_exam_time'] ?? 0);
                            echo $exam_time > 0 ? ($exam_time >= 60 ? (int) ($exam_time / 60) . ':00 ชั่วโมง' : $exam_time . ' นาที') : '0:00 ชั่วโมง';
                            ?>
                        </div>
                    </div>
                    <div class="exam-info-row-custom">
                        <div class="exam-info-label-custom">
                            <i class="bi bi-clipboard-check"></i>
                            <span>จำนวนข้อสอบ</span>
                        </div>
                        <div class="exam-info-value-custom">
                            <?php echo (int) ($course['course_number_exam'] ?? 0); ?> ข้อ
                        </div>
                    </div>
                </div>

                <button class="btn-start-exam-custom" disabled>
                    เริ่มทำข้อสอบ
                </button>
            </div>
        <?php elseif (!$can_take_exam): ?>
            <!-- Dark Locked Card (Still studying) -->
            <div class="exam-section-card exam-locked">
                <div class="exam-lock-overlay">
                    <div class="lock-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div class="lock-text">กรุณาเรียนให้ครบทุกบทเรียน เพื่อปลดล็อกการทำข้อสอบ</div>
                </div>

                <h4 class="exam-section-title">ข้อสอบของคอร์สนี้</h4>

                <div class="exam-info-box-custom">
                    <div class="exam-info-row-custom">
                        <div class="exam-info-label-custom">
                            <i class="bi bi-clock"></i>
                            <span>ระยะเวลาทำข้อสอบ</span>
                        </div>
                        <div class="exam-info-value-custom">
                            <?php
                            $exam_time = (int) ($course['course_exam_time'] ?? 0);
                            echo $exam_time > 0 ? ($exam_time >= 60 ? (int) ($exam_time / 60) . ':00 ชั่วโมง' : $exam_time . ' นาที') : '0:00 ชั่วโมง';
                            ?>
                        </div>
                    </div>
                    <div class="exam-info-row-custom">
                        <div class="exam-info-label-custom">
                            <i class="bi bi-clipboard-check"></i>
                            <span>จำนวนข้อสอบ</span>
                        </div>
                        <div class="exam-info-value-custom">
                            <?php echo (int) ($course['course_number_exam'] ?? 0); ?> ข้อ
                        </div>
                    </div>
                </div>

                <button class="btn-start-exam-custom" disabled>
                    เริ่มทำข้อสอบ
                </button>
            </div>
        <?php else: ?>
            <!-- Normal White Card (Unlocked) -->
            <h4 class="section-title">ข้อสอบของคอร์สนี้</h4>
            <div class="exam-info-box">
                <div class="exam-info-row">
                    <div class="exam-info-label">
                        <i class="bi bi-clock"></i>
                        <span>ระยะเวลาทำข้อสอบ</span>
                    </div>
                    <div class="exam-info-value">
                        <?php
                        $exam_time = (int) ($course['course_exam_time'] ?? 0);
                        echo $exam_time > 0 ? ($exam_time >= 60 ? (int) ($exam_time / 60) . ':00 ชั่วโมง' : $exam_time . ' นาที') : '0:00 ชั่วโมง';
                        ?>
                    </div>
                </div>
                <div class="exam-info-row">
                    <div class="exam-info-label">
                        <i class="bi bi-clipboard-check"></i>
                        <span>จำนวนข้อสอบ</span>
                    </div>
                    <div class="exam-info-value">
                        <?php echo (int) ($course['course_number_exam'] ?? 0); ?> ข้อ
                    </div>
                </div>
            </div>

            <button class="btn-start-exam"
                onclick="startExam('<?php echo \App\Utility\Cipher::encrypt($course_id . '-' . $enroll_id); ?>')">
                เริ่มทำข้อสอบ
            </button>
        <?php endif; ?>


        <h4 class="section-title">เอกสารประกอบบทเรียน</h4>
        <?php if (empty($lessons)): ?>
            <div class="alert alert-light border text-center py-4 text-muted">ไม่มีเอกสารประกอบบทเรียน</div>
        <?php else: ?>
            <?php
            $has_docs = false;
            foreach ($lessons as $index => $lesson):
                if (!empty($lesson['lesson_file_id'])):
                    $has_docs = true;
                    ?>
                    <div class="doc-item-card">
                        <div class="doc-item-main">
                            <div class="doc-icon-box">
                                <i class="bi bi-folder-fill"></i>
                            </div>
                            <div>
                                <div class="doc-title-text"><?php echo htmlspecialchars($lesson['lesson_file_name'] ?? ''); ?></div>
                                <div class="doc-type-text"><?php echo htmlspecialchars($lesson['lesson_file_type'] ?? ''); ?></div>
                            </div>
                        </div>
                        <a href="download_file?key=<?php echo \App\Utility\Cipher::encrypt($lesson['lesson_file_id']); ?>"
                            class="btn-download-doc" target="_blank">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$has_docs): ?>
                <div class="alert alert-light border text-center py-4 text-muted">ไม่มีเอกสารประกอบบทเรียน</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Lessons Section -->
        <h4 class="section-title">
            เนื้อหาของคอร์สนี้
        </h4>
        <?php if (empty($lessons)): ?>
            <div class="alert alert-light border text-center py-4 text-muted">ยังไม่มีบทเรียนในคอร์สนี้</div>
        <?php else: ?>
            <?php
            $prev_completed = true;
            foreach ($lessons as $index => $lesson):
                $is_accessible = ($index === 0 || $prev_completed);
                $prev_completed = (($lesson['progress_status'] ?? '0') === '1');
                ?>
                <div class="lesson-item-card" id="lesson-<?php echo $lesson['lesson_id']; ?>">
                    <div class="doc-item-main">
                        <div class="lesson-icon-box">
                            <i class="bi bi-play-circle-fill"></i>
                        </div>
                        <div>
                            <div class="lesson-title-text">บทเรียนที่
                                <?php echo htmlspecialchars($lesson['lesson_order'] ?? ($index + 1)); ?>
                            </div>
                            <div class="lesson-subtitle-text"><?php echo htmlspecialchars($lesson['lesson_name'] ?? ''); ?>
                            </div>
                            <div class="lesson-duration-text">วิดีโอบทเรียน</div>
                        </div>
                    </div>


                    <!-- เช็คความก้าวหน้าการเรียน -->
                    <?php
                    $progress = $lesson['progress_status'] ?? null;
                    if ($progress === '1'):
                        ?>
                        <button class="btn-watch"
                            data-video-url="<?php echo htmlspecialchars($lesson['lesson_video'] ?? ''); ?>"
                            style="background-color: #22c55e; border-color: #22c55e; display: inline-flex; align-items: center; gap: 6px;"
                            onclick="location.href='lesson_study?key=<?php echo \App\Utility\Cipher::encryptLessonKey($lesson['lesson_id'], $currentUser->user_id); ?>&eid=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>'">
                            <i class="bi bi-check-lg"></i>
                            <span>รับชมแล้ว</span>
                        </button>
                    <?php elseif ($progress === '0'): ?>
                        <button class="btn-watch"
                            data-video-url="<?php echo htmlspecialchars($lesson['lesson_video'] ?? ''); ?>"
                            style="background-color: #f59e0b; border-color: #f59e0b; display: inline-flex; align-items: center; gap: 6px;"
                            onclick="location.href='lesson_study?key=<?php echo \App\Utility\Cipher::encryptLessonKey($lesson['lesson_id'], $currentUser->user_id); ?>&eid=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>'">
                            <i class="bi bi-clock-history"></i>
                            <span>อยู่ระหว่างรับชม</span>
                        </button>
                    <?php elseif ($is_accessible): ?>
                        <button class="btn-watch"
                            data-video-url="<?php echo htmlspecialchars($lesson['lesson_video'] ?? ''); ?>"
                            onclick="location.href='lesson_study?key=<?php echo \App\Utility\Cipher::encryptLessonKey($lesson['lesson_id'], $currentUser->user_id); ?>&eid=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>'">
                            พร้อมรับชม
                        </button>
                    <?php else: ?>
                        <button class="btn-watch" disabled
                            style="background-color: #94a3b8; cursor: not-allowed; border-color: #94a3b8; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="bi bi-lock-fill"></i> เข้าเรียน
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>



    </div>
</div>

<script>
    function chooseCertType(enrollKey, enrollId) {
        var param = enrollKey ? ('key=' + encodeURIComponent(enrollKey)) : ('id=' + (enrollId || 0));
        window.open('print/print_etax_certificate.php?type=certificate&' + param + '&cert_type=cpd&t=' + new Date().getTime(), '_blank');
    }

    $(document).ready(function() {
        function getVimeoId(url) {
            if (!url) return null;
            url = url.trim();
            if (/^\d+$/.test(url)) return url;
            var m = url.match(/vimeo\.com\/(?:video\/)?(\d+)/) || url.match(/player\.vimeo\.com\/video\/(\d+)/);
            return m ? m[1] : null;
        }

        $(".btn-watch").each(function() {
            var btn = $(this);
            if (btn.is(":disabled")) return;
            var videoUrl = btn.attr("data-video-url");
            var vid = getVimeoId(videoUrl);
            if (!vid) return; // ไม่ใช่วิดีโอ Vimeo

            var oembedUrl = "https://vimeo.com/api/oembed.json?url=https%3A%2F%2Fvimeo.com%2F" + vid;

            fetch(oembedUrl)
                .then(function(response) {
                    if (response.status === 404) {
                        // วิดีโอยังไม่พร้อม หรือยัง Encoding อยู่บน Vimeo
                        btn.prop("disabled", true)
                           .css({
                               "background-color": "#94a3b8",
                               "border-color": "#94a3b8",
                               "cursor": "not-allowed",
                               "pointer-events": "none"
                           })
                           .removeAttr("onclick")
                           .html('<i class="bi bi-exclamation-triangle-fill me-1"></i> วิดีโอยังไม่พร้อมรับชม');
                    }
                })
                .catch(function(err) {
                    console.error("Error checking Vimeo status:", err);
                });
        });
    });
</script>