<?php
    use App\Database\Connection;
    use App\Utility\Auth;

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/debug.log');

    require_once __DIR__ . '/vendor/autoload.php';

    $pageTitle = 'เรียนคอร์สเรียน';
    include 'components/header.php';

    // 1. ตรวจสอบการเข้าสู่ระบบ
    $currentUser = Auth::getUser();
    if (! $currentUser) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
    }

    $key       = isset($_GET['key']) ? trim($_GET['key']) : '';
    $enroll_id = \App\Utility\Cipher::decrypt($key);
    if ($enroll_id <= 0) {
    echo "<div class='container my-5'><div class='alert alert-danger'>ไม่พบรหัสการลงทะเบียนเรียน</div></div>";
    include 'components/footer.php';
    exit;
    }

    $is_enrolled = false;
    $course_id   = 0;
    $error_msg   = "คุณไม่มีสิทธิ์เข้าถึงคอร์สเรียนนี้ หรือไม่ใช่เจ้าของสิทธิ์การลงทะเบียน";
    try {
    $db   = (new Connection())->getPdo();
    $stmt = $db->prepare("
        SELECT ce.enroll_user_id, ce.enroll_course_id, u.email_status, u.approver_citizen 
        FROM tbl_course_enrollment ce
        JOIN tbl_user u ON ce.enroll_user_id = u.user_id
        WHERE ce.enroll_id = :eid AND ce.delete_at IS NULL LIMIT 1
    ");
    $stmt->execute([':eid' => $enroll_id]);
    $enroll = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($enroll && (int) $enroll['enroll_user_id'] === (int) $currentUser->user_id) {
        if ((int) $enroll['email_status'] === 1 && (int) $enroll['approver_citizen'] === 2) {
            $is_enrolled = true;
            $course_id   = (int) $enroll['enroll_course_id'];
        } else {
            $error_msg = "คุณยังยืนยันตัวตนไม่สมบูรณ์ (รอการยืนยันอีเมลหรือรอแอดมินอนุมัติบัตรประชาชน)";
        }
    }
    } catch (Exception $e) {
    $is_enrolled = false;
    }

    if (! $is_enrolled || $course_id <= 0) {
    echo "<div class='container my-5'><div class='alert alert-danger text-center py-4'>" . htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') . "</div></div>";
    include 'components/footer.php';
    exit;
    }
?>

<style>
    body {
        font-family: 'Kanit', sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
    }

    .study-container {
        max-width: 1000px;
        margin: 40px auto 80px;
        padding: 0 15px;
    }

    /* Course Header Details */
    .course-title-header {
        font-size: 1.45rem;
        font-weight: 600;
        color: #1e3a8a;
        line-height: 1.4;
        margin-bottom: 5px;
    }

    .course-instructor-text {
        font-size: 0.95rem;
        color: #64748b;
        margin-bottom: 25px;
    }

    /* Info card layout */
    .course-summary-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 24px;
        display: flex;
        gap: 30px;
        align-items: center;
        margin-bottom: 40px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .course-cover-img {
        width: 320px;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .course-cover-placeholder {
        width: 320px;
        height: 180px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        flex-shrink: 0;
    }

    .summary-stats {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .stat-row {
        font-size: 0.95rem;
        color: #334155;
    }

    /* Sections styling */
    .section-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: #1e3a8a;
        margin-bottom: 16px;
        margin-top: 30px;
    }

    /* Exam Box styling */
    .exam-section-card {
        background-color: #334155; /* Slate dark background */
        border-radius: 16px;
        padding: 30px;
        position: relative;
        overflow: hidden;
        margin-top: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .exam-section-title {
        color: #94a3b8; /* Slate text */
        font-size: 1.1rem;
        margin-bottom: 20px;
        font-weight: 500;
        margin-top: 0;
    }
    .exam-info-box-custom {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 25px;
    }
    .exam-info-row-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .exam-info-label-custom {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #e2e8f0;
        font-size: 0.95rem;
    }
    .exam-info-label-custom i {
        font-size: 1.25rem;
        color: #94a3b8;
    }
    .exam-info-value-custom {
        color: #f1f5f9;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .btn-start-exam-custom {
        background-color: #3b5998;
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 12px 45px;
        font-size: 1rem;
        font-weight: 500;
        display: block;
        width: fit-content;
        margin: 0 auto;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .btn-start-exam-custom:hover:not(:disabled) {
        background-color: #2d4373;
        transform: translateY(-1px);
        color: #fff;
    }
    .btn-start-exam-custom:disabled {
        background-color: #1e293b;
        color: #475569;
        cursor: not-allowed;
        box-shadow: none;
        opacity: 0.6;
    }

    /* Lock Overlay */
    .exam-locked .exam-info-box-custom,
    .exam-locked .exam-section-title {
        opacity: 0.25;
    }
    .exam-lock-overlay {
        position: absolute;
        inset: 0;
        background: rgba(30, 41, 59, 0.6); /* Translucent dark cover */
        backdrop-filter: blur(2.5px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
        color: #fff;
        padding: 20px;
    }
    .exam-lock-overlay .lock-icon {
        font-size: 2.8rem;
        color: #fff;
        margin-bottom: 12px;
        animation: lockBounce 2s infinite ease-in-out;
    }
    .exam-lock-overlay .lock-text {
        font-size: 1.15rem;
        font-weight: 600;
        text-align: center;
        max-width: 85%;
        line-height: 1.5;
        text-shadow: 0 2px 8px rgba(0,0,0,0.6);
    }

    @keyframes lockBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }

    /* Original White Exam Box styling */
    .exam-info-box {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .exam-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .exam-info-row:last-child {
        border-bottom: none;
    }

    .exam-info-label {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.95rem;
        color: #475569;
    }

    .exam-info-label i {
        font-size: 1.25rem;
        color: #64748b;
    }

    .exam-info-value {
        font-weight: 500;
        color: #0f172a;
        font-size: 0.95rem;
    }

    .btn-start-exam {
        background-color: #3b5998;
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 10px 36px;
        font-size: 1rem;
        font-weight: 500;
        display: block;
        width: fit-content;
        margin: 25px auto 10px;
        box-shadow: 0 4px 12px rgba(59, 89, 152, 0.15);
        transition: all 0.2s ease;
    }

    .btn-start-exam:hover {
        background-color: #2d4373;
        color: #fff;
        transform: translateY(-1px);
    }

    /* Lesson List styling */
    .lesson-item-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.01);
    }

    .lesson-item-main {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .lesson-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        flex-shrink: 0;
    }

    .lesson-icon-box i {
        font-size: 1.4rem;
    }

    .lesson-title-text {
        font-weight: 500;
        color: #334155;
        font-size: 0.95rem;
        margin-bottom: 4px;
    }

    .lesson-subtitle-text {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 2px;
    }

    .lesson-duration-text {
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .badge-watched {
        background-color: #22c55e;
        color: #fff;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 6px 16px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-watch {
        background-color: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 6px 16px;
        transition: background 0.2s;
    }

    .btn-watch:hover {
        background-color: #2563eb;
    }

    /* Document section styling */
    .doc-item-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .doc-item-main {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .doc-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: #fffbeb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d97706;
        flex-shrink: 0;
    }

    .doc-icon-box i {
        font-size: 1.5rem;
    }

    .doc-title-text {
        font-weight: 500;
        color: #334155;
        font-size: 0.95rem;
        margin-bottom: 4px;
    }

    .doc-type-text {
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .btn-download-doc {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #3b5998;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: background-color 0.2s;
    }

    .btn-download-doc:hover {
        background-color: #2d4373;
        color: #fff;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .course-summary-card {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }

        .course-cover-img, .course-cover-placeholder {
            width: 100%;
            height: auto;
            aspect-ratio: 16/9;
        }

        .summary-stats {
            align-items: center;
        }
    }

    /* Floating Chat Button */
    .floating-chat-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 65px;
        height: 65px;
        background-color: #3b5998;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 1000;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .floating-chat-btn:hover {
        background-color: #2d4373;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        color: #fff;
    }


</style>

<div id="study-course-content-area"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const enrollId = <?php echo $enroll_id; ?>;

    function loadStudyCourse() {
        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "study_course",
                request_function: "get_study_course",
                enroll_id: enrollId
            },
            dataType: "json",
            success: function(response) {
                if (response.result == 1) {
                    RenderStudyCourse(response.data);
                } else {
                    $("#study-course-content-area").html(
                        `<div class="container my-5"><div class="alert alert-danger">${response.msg || 'เกิดข้อผิดพลาดในการโหลดข้อมูล'}</div></div>`
                    );
                }
            },
            error: function() {
                $("#study-course-content-area").html(
                    '<div class="container my-5"><div class="alert alert-danger">ระบบขัดข้อง ไม่สามารถดำเนินการได้</div></div>'
                );
            }
        });
    }

    function RenderStudyCourse(courseData) {
        $.ajax({
            type: "POST",
            url: "view/study_course/study_course.php",
            data: JSON.stringify(courseData),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function(responseHtml) {
                $("#study-course-content-area").html(responseHtml);
            },
            error: function() {
                $("#study-course-content-area").html(
                    '<div class="container my-5"><div class="alert alert-danger">ไม่สามารถโหลดหน้าแสดงผลบทเรียนได้</div></div>'
                );
            }
        });
    }

    loadStudyCourse();
});

function startExam(examKey) {
    Swal.fire({
        title: 'ยืนยันการเริ่มทำข้อสอบ?',
        text: "เมื่อกดเริ่มแล้ว เวลาในการทำข้อสอบจะเริ่มนับถอยหลังทันที",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3b5998',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            location.href = 'exam?key=' + examKey;
        }
    });
}

function downloadAttachment(lessonId) {
    Swal.fire({
        title: 'ดาวน์โหลดเอกสาร?',
        text: "คุณต้องการดาวน์โหลดเอกสารประกอบบทเรียนนี้ใช่หรือไม่?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b5998',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ดาวน์โหลด',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('สำเร็จ', 'กำลังดาวน์โหลดไฟล์...', 'success');
        }
    });
}
</script>

<?php include 'view/chatmessage/chatmessage.php'; ?>

<?php include 'components/footer.php'; ?>
