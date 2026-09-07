<?php
use App\Utility\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$pageTitle = 'ทำข้อสอบ';
include 'components/header.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
$currentUser = Auth::getUser();
if (!$currentUser) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$key = isset($_GET['key']) ? trim($_GET['key']) : '';
$decrypted = \App\Utility\Cipher::decryptString($key);
$ids = explode('-', $decrypted);
$course_id = isset($ids[0]) ? (int) $ids[0] : 0;
$enroll_id = isset($ids[1]) ? (int) $ids[1] : 0;
if ($course_id <= 0 || $enroll_id <= 0) {
    echo "<div class='container my-5'><div class='alert alert-danger text-center'>ไม่พบรหัสคอร์สเรียนหรือการลงทะเบียน</div></div>";
    include 'components/footer.php';
    exit;
}
?>

<style>
    body {
        background-color: #f8fafc;
        font-family: 'Kanit', sans-serif;
    }

    .exam-container {
        max-width: 1100px;
        margin: 40px auto 80px;
        padding: 0 15px;
    }

    .exam-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 30px;
        margin-bottom: 25px;
    }

    .exam-header {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 20px;
        margin-bottom: 25px;
    }

    .exam-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e3a8a;
    }

    .choice-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 20px;
    }

    .choice-item {
        display: flex;
        align-items: center;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #fff;
    }

    .choice-item:hover {
        border-color: #a78bfa;
        background-color: #faf5ff;
    }

    .choice-item.selected {
        border-color: #7c3aed;
        background-color: #f5f3ff;
        font-weight: 500;
    }

    .choice-radio {
        margin-right: 15px;
        transform: scale(1.2);
        accent-color: #7c3aed;
    }

    /* Question circle indicators styling */
    .q-nav-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #e9d5ff;
        /* ม่วงธรรมดา (outline) */
        background-color: #fff;
        color: #7c3aed;
        user-select: none;
    }

    .q-nav-circle.active {
        border-color: #6d28d9;
        /* ขอบม่วงเข้มแสดงข้อปัจจุบัน */
        border-width: 3px;
        font-weight: 700;
        transform: scale(1.1);
        box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.2);
    }

    .q-nav-circle.answered {
        border-color: #7c3aed;
        background-color: #7c3aed;
        /* สีม่วงเต็มวง */
        color: #fff;
    }

    .q-nav-circle:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15);
    }

    .exam-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
    }

    .timer-sidebar-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 24px;
    }

    .timer-label {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .timer-display-large {
        font-size: 2.6rem;
        font-weight: 800;
        color: #ef4444;
        margin-bottom: 12px;
        letter-spacing: 1px;
        font-family: 'Courier New', Courier, monospace;
    }

    .sidebar-circles-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 24px;
    }

    .sidebar-circles-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }
</style>

<div id="exam-content-area" class="container exam-container"></div>

<script>
    const COURSE_ID = <?php echo $course_id; ?>;
    const ENROLL_ID = <?php echo $enroll_id; ?>;
    let questions = [];
    let totalQuestions = 0;
    let currentIndex = 0;
    const userAnswers = {}; // Map of questionIndex -> selected choiceIndex
    let timeRemaining = 0;
    let timerInterval = null;
    let minimumScore = 0;

    function loadExamData() {
        Swal.fire({
            title: "กำลังเตรียมข้อสอบ...",
            allowOutsideClick: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "exam",
                request_function: "get_exam",
                course_id: COURSE_ID
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    renderExamView(response.data);
                } else {
                    Swal.close();
                    $("#exam-content-area").html(
                        `<div class="alert alert-danger text-center">${response.msg || 'ไม่สามารถโหลดข้อสอบได้'}</div>`
                    );
                }
            },
            error: function () {
                Swal.close();
                $("#exam-content-area").html(
                    '<div class="alert alert-danger text-center">ระบบขัดข้อง ไม่สามารถดำเนินการได้</div>'
                );
            }
        });
    }

    function renderExamView(examData) {
        $.ajax({
            type: "POST",
            url: "view/exam/let_exam.php",
            data: JSON.stringify(examData),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (responseHtml) {
                Swal.close();
                $("#exam-content-area").html(responseHtml);

                // ตั้งค่าข้อมูลข้อสอบ
                questions = examData.questions || [];
                totalQuestions = questions.length;
                timeRemaining = (examData.exam_time_minutes || 30) * 60;
                minimumScore = examData.minimum_score || 0;
                currentIndex = 0;

                updateTimerDisplay();
                startTimer();
                renderQuestion();
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถแสดงผลหน้าข้อสอบได้", "error");
            }
        });
    }

    function startTimer() {
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            timeRemaining--;
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                Swal.fire({
                    title: 'หมดเวลาทำข้อสอบ!',
                    text: 'หมดเวลาทำข้อสอบแล้ว ระบบจะส่งคำตอบของคุณโดยอัตโนมัติ',
                    icon: 'warning',
                    confirmButtonText: 'ตกลง',
                    allowOutsideClick: false
                }).then(() => {
                    submitExam();
                });
            } else {
                updateTimerDisplay();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const mins = Math.floor(timeRemaining / 60);
        const secs = timeRemaining % 60;
        const timerTextEl = document.getElementById('timerText');
        if (timerTextEl) {
            timerTextEl.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
    }

    function renderQuestion() {
        if (totalQuestions === 0) return;
        const q = questions[currentIndex];

        // คำนวณจำนวนข้อที่ตอบแล้ว
        const answeredCount = Object.keys(userAnswers).length;
        const allAnswered = (answeredCount === totalQuestions);

        // อัปเดตหัวข้อและหมายเลขข้อ
        document.getElementById('questionNumberText').textContent = `กำลังทำข้อที่ ${currentIndex + 1} / ${totalQuestions} (ตอบแล้ว ${answeredCount} ข้อ)`;
        document.getElementById('questionText').textContent = q.question_text;

        // อัปเดต Progress Bar
        const progressPct = (answeredCount / totalQuestions) * 100;
        document.getElementById('examProgressBar').style.width = `${progressPct}%`;

        // แสดงตัวเลือก
        const container = document.getElementById('choicesContainer');
        container.innerHTML = '';

        q.choices.forEach((choice, index) => {
            const isSelected = userAnswers[currentIndex] === index;
            const choiceItem = document.createElement('div');
            choiceItem.className = `choice-item ${isSelected ? 'selected' : ''}`;
            choiceItem.onclick = () => selectChoice(index);

            choiceItem.innerHTML = `
                <input type="radio" class="choice-radio" name="choice" ${isSelected ? 'checked' : ''}>
                <span>${choice.choice_text}</span>
            `;
            container.appendChild(choiceItem);
        });

        // อัปเดตสถานะปุ่มนำทาง
        document.getElementById('btnPrev').disabled = (currentIndex === 0);

        const btnNext = document.getElementById('btnNext');
        if (currentIndex === totalQuestions - 1) {
            btnNext.style.display = "inline-block";
            btnNext.innerHTML = `ส่งข้อสอบ <i class="bi bi-send ms-1"></i>`;
            btnNext.className = "btn btn-success px-4 py-2";
        } else {
            btnNext.style.display = "inline-block";
            btnNext.innerHTML = `ถัดไป <i class="bi bi-arrow-right ms-1"></i>`;
            btnNext.className = "btn btn-primary px-4 py-2";
        }

        // แสดงปุ่มนำทางเลขข้อ (Circles)
        const circlesContainer = document.getElementById('questionNavCircles');
        if (circlesContainer) {
            circlesContainer.innerHTML = '';
            for (let i = 0; i < totalQuestions; i++) {
                const circle = document.createElement('div');
                const isCurrent = (i === currentIndex);
                const isAnswered = (userAnswers[i] !== undefined);

                circle.className = `q-nav-circle ${isCurrent ? 'active' : ''} ${isAnswered ? 'answered' : ''}`;
                circle.textContent = i + 1;
                circle.onclick = () => jumpToQuestion(i);
                circlesContainer.appendChild(circle);
            }
        }
    }

    function jumpToQuestion(index) {
        if (index >= 0 && index < totalQuestions) {
            currentIndex = index;
            renderQuestion();
        }
    }

    function selectChoice(index) {
        userAnswers[currentIndex] = index;
        renderQuestion();
    }

    function nextQuestion() {
        if (currentIndex < totalQuestions - 1) {
            currentIndex++;
            renderQuestion();
        } else {
            confirmSubmit();
        }
    }

    function prevQuestion() {
        if (currentIndex > 0) {
            currentIndex--;
            renderQuestion();
        }
    }

    function confirmSubmit() {
        let unansweredList = [];
        for (let i = 0; i < totalQuestions; i++) {
            if (userAnswers[i] === undefined) {
                unansweredList.push(i + 1);
            }
        }

        if (unansweredList.length > 0) {
            Swal.fire({
                title: 'ยังทำข้อสอบไม่ครบ!',
                text: `คุณยังไม่ได้ตอบคำถามข้อที่: ${unansweredList.join(', ')} (กรุณาตอบคำถามให้ครบทุกข้อก่อนส่งข้อสอบ)`,
                icon: 'warning',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#7c3aed'
            });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการส่งข้อสอบ?',
            text: "คุณต้องการส่งข้อสอบใช่หรือไม่?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ส่งข้อสอบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                submitExam();
            }
        });
    }

    function submitExam() {
        clearInterval(timerInterval);

        const formattedAnswers = {};
        questions.forEach((q, idx) => {
            const choiceIdx = userAnswers[idx];
            if (choiceIdx !== undefined) {
                const examId = q.question_id;
                const choiceId = q.choices[choiceIdx].choice_id;
                formattedAnswers[examId] = choiceId;
            }
        });

        Swal.fire({
            title: 'กำลังส่งข้อสอบ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "exam",
                request_function: "save_exam_result",
                course_id: COURSE_ID,
                enroll_id: <?php echo $enroll_id; ?>,
                answers: formattedAnswers
            },
            dataType: "json",
            success: function (response) {
                Swal.close();
                if (response.result == 1) {
                    const data = response.data;
                    Swal.fire({
                        title: data.is_passed ? 'ยินดีด้วย คุณสอบผ่าน!' : 'เสียใจด้วย คุณสอบไม่ผ่าน',
                        text: `คุณได้คะแนน ${data.score} จากทั้งหมด ${data.total_questions} คะแนน (เกณฑ์ผ่าน: ${data.minimum_score} คะแนน)`,
                        icon: data.is_passed ? 'success' : 'error',
                        confirmButtonText: 'ตกลง',
                        allowOutsideClick: false
                    }).then(() => {
                        location.href = 'study_course?key=<?php echo \App\Utility\Cipher::encrypt($enroll_id); ?>';
                    });
                } else {
                    Swal.fire("เกิดข้อผิดพลาด", response.msg || "ไม่สามารถส่งข้อสอบได้", "error");
                }
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ระบบขัดข้อง ไม่สามารถดำเนินการได้", "error");
            }
        });
    }

    $(document).ready(() => {
        loadExamData();
    });
/*
    // 1. ดักจับการสลับ Tab หรือพับหน้าจอ (Visibility Change)
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === 'hidden') {
            Swal.fire({
                icon: 'warning',
                title: 'ตรวจพบการสลับหน้าจอ',
                text: 'กรุณากดเพื่อเริ่มการสอบใหม่',
                confirmButtonText: 'เริ่มการสอบใหม่',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.reload();
            });
        }
    });
*/
    // 2. ป้องกันการกด Back / Forward (Undo/Redo ของ Browser)
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };

    // 3. ป้องกันการคลิกขวา (ป้องกัน Inspect เบื้องต้น)
    document.addEventListener('contextmenu', event => event.preventDefault());
</script>

<?php include 'components/footer.php'; ?>