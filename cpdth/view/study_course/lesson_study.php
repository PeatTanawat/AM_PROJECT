<?php
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $data      = json_decode(file_get_contents('php://input'), true);
    $lesson    = $data['lesson'] ?? null;
    $course_id = (int) ($data['course_id'] ?? 0);
    $enroll_id = (int) ($data['enroll_id'] ?? 0);
    $lesson_id = (int) ($data['lesson_id'] ?? 0);

    $already_completed = $data['already_completed'] ?? false;
    $next_lesson_key   = $data['next_lesson_key'] ?? null;
    $prev_lesson_key   = $data['prev_lesson_key'] ?? null;

    if (! $lesson) {
    echo "<div class='container my-5'><div class='alert alert-danger'>ไม่พบข้อมูลบทเรียน</div></div>";
    exit;
    }
?>
<div class="container study-player-container">


    <!-- Player Wrapper -->
    <div class="lp-player-wrap rounded-3 overflow-hidden bg-dark position-relative">
        <div id="lpPlayer"></div>

        <!-- Video Click Overlay for Play/Pause -->
        <div class="lp-click-overlay" onclick="LpTogglePlay()" style="position: absolute; inset: 0; z-index: 10; cursor: pointer;"></div>

        <!-- Custom Control Bar -->
        <div class="lp-custom-controls d-flex align-items-center gap-3 px-3 py-2 text-white" id="lpCustomControls">
            <button class="btn btn-link text-white p-0 ctrl-btn" id="lpPlayPauseBtn" onclick="LpTogglePlay()">
                <i class="bi bi-play-fill fs-4" id="lpPlayIcon"></i>
            </button>

            <span class="small ctrl-time" id="lpCtrlTime">0:00 / 0:00</span>

            <div class="flex-grow-1 position-relative d-flex align-items-center" id="lpTimelineContainer">
                <input type="range" class="form-range custom-slider" id="lpTimeline" min="0" max="100" value="0" step="0.01">
            </div>

            <div class="d-flex align-items-center position-relative volume-wrapper" onmouseenter="showVolumeSlider()" onmouseleave="hideVolumeSlider()">
                <button class="btn btn-link text-white p-0 ctrl-btn" id="lpMuteBtn" onclick="LpToggleMute()">
                    <i class="bi bi-volume-up-fill fs-4" id="lpMuteIcon"></i>
                </button>
                <div id="lpVolumeSliderContainer" style="width: 0; height: 32px; overflow: hidden; transition: width 0.3s ease, opacity 0.3s ease; opacity: 0; display: flex; align-items: center; margin-left: 8px;">
                    <input type="range" class="form-range custom-slider" id="lpVolumeSlider" min="0" max="1" step="0.05" value="1" style="width: 80px; margin: 0 5px; --value-percent: 100%;" oninput="LpChangeVolume(this.value)">
                </div>
            </div>

            <button class="btn btn-link text-white p-0 ctrl-btn" id="lpFullscreenBtn" onclick="LpToggleFullscreen()">
                <i class="bi bi-fullscreen fs-5"></i>
            </button>
        </div>

        <!-- Question Overlay -->
        <div class="lp-question-overlay" id="lpOverlay">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="text-white fw-medium fs-5" id="lpQNo">คำถามที่ 1</span>
                <div class="d-flex align-items-center gap-3 ms-auto">
                    <span style="color: #ef4444; font-weight: 500; font-size: 0.95rem;">หากตอบผิดระบบจะให้เริ่มอบรมใหม่</span>
                    <span class="lp-timer-pill">
                        <i class="bi bi-stopwatch"></i>
                        <span id="lpTimer">--</span> วินาที
                    </span>
                </div>
            </div>
            <div class="text-white fs-18 mb-4" id="lpQText"></div>
            <div class="text-center mb-3 lp-q-image-container" style="display: none;">
                <img id="lpQImage" src="" class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
            </div>
            <div id="lpChoices" class="flex-grow-1"></div>
            <div class="mt-3">
                <div class="text-warning small mb-3" id="lpQHint" style="display:none;"></div>
                <button class="btn btn-primary w-100 py-2" id="lpSubmitBtn" onclick="LpSubmitAnswer()">ตอบ &amp; เรียนต่อ</button>
            </div>
        </div>

        <!-- OTP Overlay -->
        <div class="lp-question-overlay" id="lpOtpOverlay" style="justify-content: center; align-items: center; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
            <div style="background: #ffffff; border-radius: 8px; padding: 25px 20px; max-width: 420px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: left;">

                <div class="d-flex align-items-start mb-4">
                    <div style="color: #64748b; font-size: 1.8rem; margin-right: 15px; line-height: 1;">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </div>
                    <div>
                        <div style="color: #334155; font-weight: 600; font-size: 1.05rem; line-height: 1.5; margin-bottom: 6px;">
                            กรุณากรอกรหัส OTP ที่ส่งไปยังเบอร์<br>
                            <span id="lpOtpPhone" style="color: #3b5998; font-weight: bold; font-size: 1.25rem; display: inline-block; margin: 4px 0; letter-spacing: 0.5px;">--</span>
                            <span id="lpOtpRef" style="background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; color: #475569; margin-left: 6px; display: inline-block;"></span>
                            <div id="lpOtpWarningLine" style="color: #dc2626; font-size: 0.9rem; white-space: nowrap; display: none;">หากตอบผิดเกิน 3 ครั้งระบบจะให้เริ่มอบรมใหม่ <span id="lpOtpWrongCountDisplay" style="font-weight: bold;"></span></div>
                        </div>
                        <div style="color: #64748b; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                            <span>(<span id="lpOtpTimer">--</span> วินาที)</span>
                        </div>
                    </div>
                </div>

                <div class="mb-4 position-relative">
                    <input type="text" id="lpOtpPin" class="form-control fw-bold" placeholder="รหัส OTP" maxlength="6" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px 16px; padding-right: 40px; font-size: 1.1rem; color: #334155; letter-spacing: 2px;">
                    <i class="bi bi-lock-fill" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 1.2rem;"></i>
                </div>

                <div class="text-danger small mb-3 fw-bold text-center" id="lpOtpHint" style="display:none;"></div>

                <button class="btn w-100 py-2 fw-semibold text-white mb-2" id="lpOtpSubmitBtn" onclick="LpVerifyOtp()" style="background-color: #3b5998; border-radius: 4px; font-size: 1rem; border: none; box-shadow: 0 2px 4px rgba(59, 89, 152, 0.2);">ยืนยัน</button>
                <!-- <button class="btn btn-light w-100 py-2 fw-medium text-muted" onclick="LpCloseOtp()" style="border-radius: 4px; font-size: 0.9rem; background-color: transparent; border: none;">ปิดหน้าต่างนี้ (เริ่มวิดีโอใหม่)</button> -->
                
                <!-- hint: ไม่ได้รับ OTP -->
                <p style="margin: 12px 0 0; color: #94a3b8; font-size: 0.78rem; text-align: center; line-height: 1.6;">
                    ไม่ได้รับรหัส? ตรวจสอบเบอร์โทรในหน้า <strong>โปรไฟล์</strong> ของคุณ
                </p>
            </div>
        </div>
    </div>

    <!-- Progress Indicator (Hidden or simplified as the custom controls now contain progress) -->
    <div class="progress-wrap d-none">
        <div class="d-flex justify-content-between text-secondary mb-2" style="font-size: 0.85rem;">
            <span>ดูแล้วถึง: <span id="lpWatched" class="fw-medium text-dark">0:00</span> / <span id="lpDuration">0:00</span></span>
            <span><i class="bi bi-lock-fill"></i> ระบบบันทึกการเรียนรู้อัตโนมัติ (ห้ามเลื่อนข้ามไปข้างหน้า)</span>
        </div>
        <div class="progress" style="height: 6px; border-radius: 6px;">
            <div class="progress-bar" id="lpWatchedBar" style="width: 0%; background: #3b5998; transition: width 0.2s;"></div>
        </div>
    </div>
    <div class="text-muted mt-2 small text-end" id="lpSaveInfo" style="display: none;"></div>

    <!-- Lesson Info -->
    <div class="lesson-info-box">
        <h3 class="lesson-title"><?php echo htmlspecialchars($lesson['lesson_name'] ?? 'ไม่มีชื่อบทเรียน'); ?></h3>
        <p class="lesson-desc"><?php echo htmlspecialchars($lesson['lesson_overview'] ?? '-'); ?></p>
    </div>

    <!-- Navigation Buttons -->
    <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mt-4">
        <?php if (! empty($prev_lesson_key)): ?>
            <a href="lesson_study?key=<?php echo htmlspecialchars($prev_lesson_key); ?>&eid=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>"
               id="btn-prev-lesson"
               class="btn-prev-course m-0">
                <i class="bi bi-arrow-left-circle"></i> กลับไปบทก่อนหน้า
            </a>
        <?php endif; ?>

        <a href="study_course?key=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>#lesson-<?php echo $lesson_id; ?>" class="btn-back-course m-0">
            <i class="bi bi-grid-fill"></i> กลับไปหน้าบทเรียน
        </a>
        <?php
            $is_completed = ($already_completed == 1 || $already_completed === true || $already_completed == "1" || $already_completed == "true");
            $show_next    = $is_completed && ! empty($next_lesson_key);
        ?>
        <a href="lesson_study?key=<?php echo htmlspecialchars($next_lesson_key ?? ''); ?>&eid=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>"
           id="btn-next-lesson"
           class="btn-next-course m-0"
           style="<?php echo $show_next ? '' : 'display: none;'; ?>">
            บทเรียนถัดไป <i class="bi bi-arrow-right-circle"></i>
        </a>
    </div>
