<?php
$data = json_decode(file_get_contents('php://input'), true);
$course = $data['course'] ?? null;
$minimum_score = (int)($data['minimum_score'] ?? 0);
?>
<div class="mb-4">
    <h4 class="mb-1 text-dark fw-bold"><?php echo htmlspecialchars($course['course_name'] ?? ''); ?></h4>
    <span class="text-muted">เกณฑ์ผ่านในการสอบ: <?php echo $minimum_score; ?> คะแนน</span>
</div>

<div class="row">
    <!-- Left Column: Question & Choices Card -->
    <div class="col-lg-8">
        <div class="exam-card">
            <div class="exam-header d-flex justify-content-between align-items-center">
                <span class="text-muted fw-medium" id="questionNumberText">ข้อที่ -- / --</span>
            </div>

            <div class="exam-body">
                <h5 class="fw-semibold text-dark mb-4" id="questionText">กำลังโหลดคำถาม...</h5>
                <div class="choice-container" id="choicesContainer">
                    <!-- choices populated dynamically -->
                </div>
            </div>
        </div>

        <!-- Nav Navigation Buttons -->
        <div class="exam-nav mb-4">
            <button class="btn btn-outline-secondary px-4 py-2" id="btnPrev" onclick="prevQuestion()" disabled>
                <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
            </button>
            <button class="btn btn-primary px-4 py-2" id="btnNext" onclick="nextQuestion()">
                ถัดไป <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- Right Column: Sidebar (Large Timer + Navigation Board) -->
    <div class="col-lg-4">
        <!-- Prominent Timer Card -->
        <div class="timer-sidebar-card text-center mb-4">
            <div class="timer-label"><i class="bi bi-hourglass-split"></i> เวลาที่เหลือในการทำข้อสอบ</div>
            <div class="timer-display-large" id="timerText">--:--</div>
            <div class="progress" style="height: 6px; border-radius: 4px; background-color: #f1f5f9;">
                <div class="progress-bar" id="examProgressBar" role="progressbar" style="width: 0%; background-color: #ef4444;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <!-- Question Navigation Board -->
        <div class="sidebar-circles-card">
            <div class="sidebar-circles-title mb-3"><i class="bi bi-grid-3x3-gap-fill me-1"></i> แผงควบคุมและเลือกข้อสอบ</div>
            <div class="d-grid gap-2" style="grid-template-columns: repeat(5, 1fr);" id="questionNavCircles">
                <!-- Circles rendered dynamically -->
            </div>
        </div>
    </div>
</div>

