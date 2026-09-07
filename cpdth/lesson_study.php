<?php
use App\Utility\Auth;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

require_once __DIR__ . '/vendor/autoload.php';
include 'components/header.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
$currentUser = Auth::getUser();
if (!$currentUser) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

use App\Database\Connection;

$key = isset($_GET['key']) ? trim($_GET['key']) : '';
$lesson_id = \App\Utility\Cipher::decryptLessonKey($key, $currentUser->user_id);
$course_id = 0;
$enroll_id = 0;
$is_enrolled = false;

if ($lesson_id > 0) {
    try {
        $db = (new Connection())->getPdo();
        $stmt = $db->prepare("SELECT course_id FROM tbl_lesson WHERE lesson_id = :lid AND delete_at IS NULL LIMIT 1");
        $stmt->execute([':lid' => $lesson_id]);
        $course_id = (int) $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($course_id > 0 && $currentUser) {
            $enroll_id_param = 0;
            if (isset($_GET['eid'])) {
                $eid_raw = trim($_GET['eid']);
                $enroll_id_param = \App\Utility\Cipher::decrypt($eid_raw);
                error_log("lesson_study: eid_raw=$eid_raw => decrypt=$enroll_id_param");
            }

            if ($enroll_id_param > 0) {
                $stmt = $db->prepare("SELECT enroll_id, enroll_user_id FROM tbl_course_enrollment WHERE enroll_id = :eid AND enroll_user_id = :uid AND delete_at IS NULL LIMIT 1");
                $stmt->execute([':eid' => $enroll_id_param, ':uid' => $currentUser->user_id]);
            } else {
                $dbg_eid = isset($_GET['eid']) ? htmlspecialchars($_GET['eid']) : 'none';
                $dbg_dec = isset($enroll_id_param) ? $enroll_id_param : 'none';
                echo "<div class='container my-5'><div class='alert alert-danger'>ไม่พบรหัสการลงทะเบียนเรียน กรุณาเข้าสู่บทเรียนใหม่อีกครั้ง <br> <small>DEBUG: eid=$dbg_eid, dec=$dbg_dec</small></div></div>";
                exit;
            }

            $enroll = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($enroll && (int) $enroll['enroll_user_id'] === (int) $currentUser->user_id) {
                $is_enrolled = true;
                $enroll_id = (int) $enroll['enroll_id'];
            }
            error_log("lesson_study: final enroll_id=$enroll_id");
        }
    } catch (Exception $e) {
        error_log("lesson_study EXCEPTION: " . $e->getMessage());
        $is_enrolled = false;
    }
}

if ($course_id <= 0 || $lesson_id <= 0 || !$is_enrolled) {
    echo "<div class='container my-5'><div class='alert alert-danger text-center'>คุณไม่มีสิทธิ์เข้าถึงบทเรียนนี้ หรือไม่พบข้อมูลบทเรียน</div></div>";
    include 'components/footer.php';
    exit;
}
?>
<!-- Vimeo SDK -->
<script src="https://player.vimeo.com/api/player.js"></script>

<style>
    body {
        font-family: 'Kanit', sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
    }

    .study-player-container {
        max-width: 1000px;
        margin: 40px auto 80px;
        padding: 0 15px;
    }

    .lp-player-wrap {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .lp-player-wrap>#lpPlayer {
        position: absolute;
        inset: 0;
    }

    #lpPlayer iframe,
    #lpPlayer video {
        position: absolute;
        inset: 0;
        width: 100% !important;
        height: 100% !important;
        border: 0;
        background: #000;
    }

    .lp-question-overlay {
        position: absolute;
        inset: 0;
        z-index: 20;
        background: rgba(15, 18, 34, 0.95);
        display: none;
        flex-direction: column;
        padding: 30px;
        overflow-y: auto;
    }

    .lp-question-overlay.show {
        display: flex;
    }

    .lp-choice {
        display: block;
        width: 100%;
        text-align: left;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 20px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 0.95rem;
        color: #334155;
        transition: all 0.2s;
    }

    .lp-choice:hover {
        border-color: #3b5998;
        background-color: #f8fafc;
    }

    .lp-choice.selected {
        border-color: #3b5998;
        background-color: #3b5998;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(59, 89, 152, 0.4);
        transform: translateY(-2px);
    }

    .lp-timer-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        padding: 6px 16px;
        border-radius: 999px;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .lesson-info-box {
        margin-top: 25px;
        background: #fff;
        padding: 24px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .lesson-title {
        font-size: 1.35rem;
        font-weight: 600;
        color: #1e3a8a;
        margin-bottom: 10px;
    }

    .lesson-desc {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .btn-back-course {
        background-color: #3b5998;
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 10px 32px;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        margin: 30px auto 0;
        box-shadow: 0 4px 12px rgba(59, 89, 152, 0.15);
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-back-course:hover {
        background-color: #2d4373;
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-prev-course {
        background-color: #64748b;
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 10px 32px;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.15);
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-prev-course:hover {
        background-color: #475569;
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-next-course {
        background-color: #10b981;
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 10px 32px;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-next-course:hover {
        background-color: #059669;
        color: #fff;
        transform: translateY(-1px);
    }

    .progress-wrap {
        margin-top: 15px;
        padding: 10px 0;
    }

    /* Custom controls styling */
    .lp-custom-controls {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(15, 23, 42, 0.85) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 18;
        /* Just below question overlay (20) */
        transition: transform 0.3s ease, opacity 0.3s ease;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Auto hide controls when not hovering player */
    .lp-player-wrap:not(:hover) .lp-custom-controls {
        transform: translateY(100%);
        opacity: 0;
    }

    .lp-player-wrap.controls-active .lp-custom-controls {
        transform: translateY(0) !important;
        opacity: 1 !important;
    }

    .ctrl-btn {
        transition: transform 0.1s ease;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    }

    .ctrl-btn:hover {
        transform: scale(1.1);
        color: #60a5fa !important;
    }

    .ctrl-time {
        font-family: monospace;
        letter-spacing: 0.5px;
        font-weight: 500;
        min-width: 90px;
        text-align: center;
    }

    /* Custom range slider */
    .custom-slider {
        cursor: pointer;
        width: 100%;
        height: 6px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 999px;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        transition: height 0.1s ease;
    }

    .custom-slider:hover {
        height: 8px;
    }

    .custom-slider::-webkit-slider-runnable-track {
        background: transparent;
        height: 100%;
    }

    .custom-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #3b5998;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        margin-top: -3px;
        transition: transform 0.1s ease;
    }

    .custom-slider:hover::-webkit-slider-thumb {
        transform: scale(1.25);
        background: #60a5fa;
    }

    .custom-slider::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #3b5998;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        transition: transform 0.1s ease;
    }
</style>

<div id="study-course-content-area"></div>

<script>
    // --- Swal Fullscreen Interceptor ---
    window.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal !== 'undefined') {
            const originalSwalFire = Swal.fire;
            Swal.fire = function (...args) {
                if (args.length === 1 && typeof args[0] === 'object') {
                    if (!args[0].target) {
                        args[0].target = document.fullscreenElement || 'body';
                    }
                } else if (args.length > 0 && typeof args[0] === 'string') {
                    let opts = {
                        title: args[0],
                        target: document.fullscreenElement || 'body'
                    };
                    if (args.length > 1) opts.text = args[1];
                    if (args.length > 2) opts.icon = args[2];
                    return originalSwalFire.call(this, opts);
                }
                return originalSwalFire.apply(this, args);
            };
        }
    });
    const COURSE_ID = <?php echo $course_id; ?>;
    const ENROLL_ID = <?php echo $enroll_id; ?>;
    const LESSON_ID = <?php echo $lesson_id; ?>;

    let LESSON_VIDEO = '';
    let LESSON_QUESTION = '0';
    let LESSON_QUESTION_LIMIT = 0;
    let LESSON_QUESTION_TIME = 0;
    let LP_QUESTIONS = [];
    let RESUME_SEC = 0;
    let NEXT_LESSON_ID = null;
    let NEXT_LESSON_KEY = null;
    let NEXT_LESSON_ORDER = null;
    let NEXT_LESSON_NAME = null;
    let PREV_LESSON_ID = null;
    let PREV_LESSON_KEY = null;
    let PREV_LESSON_ORDER = null;
    let PREV_LESSON_NAME = null;
    let isNavigatingToNext = false;
    let ENROLL_KEY = null;
    let ALREADY_COMPLETED = false;
    let CAN_SKIP = false;
    let COURSE_OTP = false;

    let duration = 0;
    let maxWatched = 0;
    let schedule = [];
    let pausedForQuestion = false;
    let seeking = false;
    let currentQ = null;
    let selectedChoice = null;
    let timerInterval = null;
    let answered = 0;
    let correctCount = 0;
    let lastSaveSec = 0;

    function fmt(s) {
        s = Math.max(0, Math.floor(s || 0));
        let m = Math.floor(s / 60);
        let ss = s % 60;
        return m + ":" + (ss < 10 ? "0" : "") + ss;
    }


    function parseVimeoId(input) {
        input = (input || "").trim();
        if (/^\d+$/.test(input)) { return input; }
        let m = input.match(/vimeo\.com\/(?:video\/)?(\d+)/) || input.match(/player\.vimeo\.com\/video\/(\d+)/);
        return m ? m[1] : "";
    }

    // Player handler
    const LPlayer = {
        kind: null, vimeo: null, video: null, _h: {},
        build: function (source, handlers) {
            this.destroy();
            this._h = handlers || {};
            let self = this;
            let box = document.getElementById("lpPlayer");
            box.innerHTML = "";
            let vid = parseVimeoId(source);
            if (vid) {
                this.kind = "vimeo";
                let cleanSource = source.trim().replace("player.vimeo.com/video/", "vimeo.com/");
                let vopt = /vimeo\.com/.test(cleanSource) ? { url: cleanSource } : { id: vid };
                vopt.responsive = false;
                vopt.keyboard = false;
                vopt.title = false;    // ซ่อนชื่อวิดีโอ
                vopt.byline = false;   // ซ่อนชื่อเจ้าของช่อง
                vopt.portrait = false; // ซ่อนรูปโปรไฟล์
                vopt.share = false;    // ซ่อนปุ่มแชร์
                vopt.watchlater = false; // ซ่อนปุ่มดูภายหลัง
                vopt.like = false;     // ซ่อนปุ่ม Like
                vopt.controls = false;  // ปิดแถบควบคุมเดิมของ Vimeo เพื่อใช้ของตนเอง
                this.vimeo = new Vimeo.Player(box, vopt);
                this.vimeo.on("timeupdate", function (d) {
                    if (d.duration && self._h.onDuration) self._h.onDuration(d.duration);
                    if (self._h.onTime) self._h.onTime(d.seconds);
                });
                this.vimeo.on("seek", function (d) { if (self._h.onSeeking) self._h.onSeeking(d.seconds); });
                this.vimeo.on("seeked", function (d) { if (self._h.onSeeked) self._h.onSeeked(d.seconds); });
                this.vimeo.on("pause", function () {
                    $("#lpPlayIcon").removeClass("bi-pause-fill").addClass("bi-play-fill");
                    if (self._h.onPause) self._h.onPause();
                });
                this.vimeo.on("play", function () {
                    $("#lpPlayIcon").removeClass("bi-play-fill").addClass("bi-pause-fill");
                });
                this.vimeo.on("volumechange", function (d) {
                    if (d.volume === 0) {
                        $("#lpMuteIcon").removeClass("bi-volume-up-fill").addClass("bi-volume-mute-fill");
                    } else {
                        $("#lpMuteIcon").removeClass("bi-volume-mute-fill").addClass("bi-volume-up-fill");
                    }
                });
                this.vimeo.on("ended", function () { if (self._h.onEnded) self._h.onEnded(); });
                return this.vimeo.ready().then(function () { return self.vimeo.getDuration(); });
            }
            this.kind = "html5";
            let v = document.createElement("video");
            let cleanSrc = source;
            if (cleanSrc && cleanSrc.indexOf('upload/') === 0) {
                cleanSrc = '../backoffice/' + cleanSrc;
            }
            v.src = cleanSrc;
            v.controls = false; // ปิดแถบควบคุมของ HTML5 เพื่อใช้ของตนเอง
            v.setAttribute("playsinline", "");
            box.appendChild(v);
            this.video = v;
            v.addEventListener("durationchange", function () { if (self._h.onDuration) self._h.onDuration(v.duration); });
            v.addEventListener("timeupdate", function () {
                if (v.duration && self._h.onDuration) self._h.onDuration(v.duration);
                if (self._h.onTime) self._h.onTime(v.currentTime);
            });
            v.addEventListener("seeking", function () { if (self._h.onSeeking) self._h.onSeeking(v.currentTime); });
            v.addEventListener("seeked", function () { if (self._h.onSeeked) self._h.onSeeked(v.currentTime); });
            v.addEventListener("pause", function () {
                $("#lpPlayIcon").removeClass("bi-pause-fill").addClass("bi-play-fill");
                if (self._h.onPause) self._h.onPause();
            });
            v.addEventListener("play", function () {
                $("#lpPlayIcon").removeClass("bi-play-fill").addClass("bi-pause-fill");
            });
            v.addEventListener("volumechange", function () {
                if (v.muted || v.volume === 0) {
                    $("#lpMuteIcon").removeClass("bi-volume-up-fill").addClass("bi-volume-mute-fill");
                } else {
                    $("#lpMuteIcon").removeClass("bi-volume-mute-fill").addClass("bi-volume-up-fill");
                }
            });
            v.addEventListener("ended", function () { if (self._h.onEnded) self._h.onEnded(); });
            return new Promise(function (resolve, reject) {
                v.addEventListener("loadedmetadata", function () { resolve(v.duration); });
                v.addEventListener("error", function () { reject(new Error("ไฟล์วิดีโอโหลดไม่ได้")); });
            });
        },
        play: function () { if (this.kind === "vimeo") { this.vimeo.play(); } else if (this.video) { this.video.play(); } },
        pause: function () { if (this.kind === "vimeo") { this.vimeo.pause(); } else if (this.video) { this.video.pause(); } },
        setTime: function (s) { if (this.kind === "vimeo") { this.vimeo.setCurrentTime(s); } else if (this.video) { this.video.currentTime = s; } },
        togglePlay: function () {
            let self = this;
            if (this.kind === "vimeo" && this.vimeo) {
                this.vimeo.getPaused().then(function (paused) {
                    if (paused) { self.vimeo.play(); } else { self.vimeo.pause(); }
                });
            } else if (this.video) {
                if (this.video.paused) { this.video.play(); } else { this.video.pause(); }
            }
        },
        toggleMute: function () {
            let self = this;
            if (this.kind === "vimeo" && this.vimeo) {
                this.vimeo.getMuted().then(function (muted) {
                    let newMuted = !muted;
                    self.vimeo.setMuted(newMuted);
                    if (newMuted) {
                        self.vimeo.setVolume(0);
                        $("#lpMuteIcon").removeClass("bi-volume-up-fill").addClass("bi-volume-mute-fill");
                    } else {
                        self.vimeo.setVolume(1);
                        $("#lpMuteIcon").removeClass("bi-volume-mute-fill").addClass("bi-volume-up-fill");
                    }
                }).catch(function () {
                    // Fallback if getMuted fails
                    self.vimeo.setVolume(0);
                    $("#lpMuteIcon").removeClass("bi-volume-up-fill").addClass("bi-volume-mute-fill");
                });
            } else if (this.video) {
                this.video.muted = !this.video.muted;
                if (this.video.muted || this.video.volume === 0) {
                    $("#lpMuteIcon").removeClass("bi-volume-up-fill").addClass("bi-volume-mute-fill");
                } else {
                    $("#lpMuteIcon").removeClass("bi-volume-mute-fill").addClass("bi-volume-up-fill");
                }
            }
        },
        destroy: function () {
            if (this.vimeo) { try { this.vimeo.destroy(); } catch (e) { } this.vimeo = null; }
            if (this.video) { try { this.video.pause(); } catch (e) { } this.video = null; }
            this.kind = null;
            let box = document.getElementById("lpPlayer");
            if (box) { box.innerHTML = ""; }
        }
    };

    function LpRestart() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        LPlayer.destroy();
        $("#lpOverlay").removeClass("show");
        maxWatched = 0; duration = 0; schedule = []; pausedForQuestion = false; seeking = false;
        currentQ = null; selectedChoice = null; answered = 0; correctCount = 0; lastSaveSec = 0;
        otpWrongCount = 0;
        UpdateWatched(0);

        // บันทึกความคืบหน้าเป็น 0 กลับไปยังฐานข้อมูลทันที เพื่อรีเซ็ตค่า
        SaveProgress(true, false, false, true);

        let src = LESSON_VIDEO;
        if (!src) {
            return;
        }

        LPlayer.build(src, {
            onDuration: function (d) {
                if (d > 0 && duration === 0) {
                    duration = d;
                    $("#lpDuration").text(fmt(d));
                    BuildSchedule();
                } else if (d > 0 && Math.abs(duration - d) > 1.0) {
                    duration = d;
                    $("#lpDuration").text(fmt(d));
                }
            },
            onTime: function (t) {
                if (pausedForQuestion) { return; }
                if (GuardForward(t)) { return; }
                if (t > maxWatched) { maxWatched = t; }
                UpdateWatched(t);
                SaveProgress(false);
                for (let i = 0; i < schedule.length; i++) {
                    if (!schedule[i].asked && t >= schedule[i].time) { TriggerCheckpoint(schedule[i]); break; }
                }
            },
            onSeeking: function (sec) {
                if (seeking) { return; }
                GuardForward(sec);
            },
            onSeeked: function (sec) {
                if (seeking) { seeking = false; return; }
                GuardForward(sec);
            },
            onPause: function () {
                SaveProgress(true);
            },
            onEnded: function () {
                if (duration > 0) {
                    maxWatched = duration;
                    UpdateWatched(duration);
                }
                SaveProgress(true, false, true);
                if (!ALREADY_COMPLETED) {
                    triggerLessonCompletion();
                }
            }
        }).then(function (d) {
            duration = d;
            $("#lpDuration").text(fmt(d));
            BuildSchedule();
            if (ALREADY_COMPLETED) {
                maxWatched = d;
                let startSec = (RESUME_SEC > 0 && RESUME_SEC < d) ? RESUME_SEC : 0;
                UpdateWatched(startSec);
                if (startSec > 0) {
                    seeking = true;
                    LPlayer.setTime(startSec);
                }
            } else if (RESUME_SEC > 0 && RESUME_SEC <= d) {
                maxWatched = RESUME_SEC;
                schedule.forEach(function (s) { if (s.time <= RESUME_SEC) { s.asked = true; } });
                UpdateWatched(RESUME_SEC);
                seeking = true;
                LPlayer.setTime(RESUME_SEC);
            } else {
                maxWatched = 0;
                UpdateWatched(0);
            }
            RESUME_SEC = 0;
        }).catch(function (err) {
            $("#lpPlayer").html('<div class="d-flex align-items-center justify-content-center h-100 text-white"><div class="text-center"><i class="bi bi-exclamation-triangle fs-1 text-danger mb-2 d-block"></i>โหลดวิดีโอไม่ได้ กรุณาตรวจสอบลิงก์วิดีโอหรืออินเทอร์เน็ตอีกครั้ง</div></div>');
        });
    }

    let otpToken = '';
    let otpTimerInterval = null;
    let otpWrongCount = 0;

    function BuildSchedule() {
        schedule = [];
        if (!(LESSON_QUESTION === "1") && !COURSE_OTP) { return; }
        let limit = LESSON_QUESTION_LIMIT;
        let pool = LP_QUESTIONS || [];
        
        // ถ้าปิดคำถาม (แต่เปิด OTP) ไม่ต้องเอาข้อสอบมาปน
        if (!(LESSON_QUESTION === "1")) {
            pool = [];
        }

        let n = limit;
        if (n <= 0 || duration <= 0) { return; }

        let lo = duration * 0.1, hi = duration * 0.9;
        let times = [];
        for (let i = 0; i < n; i++) {
            times.push(lo + Math.random() * (hi - lo));
        }
        times.sort(function (a, b) { return a - b; });

        let idxs = pool.map(function (_, i) { return i; });
        for (let j = idxs.length - 1; j > 0; j--) {
            let k = Math.floor(Math.random() * (j + 1));
            let tmp = idxs[j]; idxs[j] = idxs[k]; idxs[k] = tmp;
        }

        // คำนวณจำนวนประเภทจุดตรวจ (คำถาม vs OTP):
        // - กรณีเปิดใช้งาน OTP และมีคำถามในคลัง:
        //   - จำนวนคู่ (เช่น 6 ข้อ) -> คำถาม 3, OTP 3
        //   - จำนวนคี่ (เช่น 5 ข้อ) -> คำถาม 3, OTP 2 (ยึดคำถามเป็นหลักเพื่อประหยัด OTP SMS Credit)
        // - วางรูปแบบสลับประเภท (Interleaved) เพื่อไม่ให้ OTP เกิดติดกัน
        let types = [];
        if (!COURSE_OTP) {
            for (let i = 0; i < n; i++) types.push('question');
        } else if (pool.length === 0) {
            for (let i = 0; i < n; i++) types.push('otp');
        } else {
            let qCount = Math.ceil(n / 2);   // ถ้า n เป็นคี่ ยึดคำถามเยอะกว่า 1 ข้อ (เช่น 5 -> Q=3, O=2)
            let otpCount = Math.floor(n / 2); // ถ้า n เป็นคู่ ได้เท่ากัน (เช่น 6 -> Q=3, O=3)

            // สุ่มเริ่มต้นว่าจุดแรกเป็น คำถาม หรือ OTP ก่อน (กรณี qCount == otpCount)
            let startWithQuestion = (qCount > otpCount) ? true : (Math.random() < 0.5);

            let qLeft = qCount;
            let oLeft = otpCount;
            let currentIsQ = startWithQuestion;

            for (let i = 0; i < n; i++) {
                if (currentIsQ && qLeft > 0) {
                    types.push('question');
                    qLeft--;
                    if (oLeft > 0) currentIsQ = false;
                } else if (!currentIsQ && oLeft > 0) {
                    types.push('otp');
                    oLeft--;
                    if (qLeft > 0) currentIsQ = true;
                } else if (qLeft > 0) {
                    types.push('question');
                    qLeft--;
                } else if (oLeft > 0) {
                    types.push('otp');
                    oLeft--;
                }
            }
        }

        let qIndexInPool = 0;
        for (let q = 0; q < n; q++) {
            let isOtp = (types[q] === 'otp');
            let questionObj = null;
            if (!isOtp && pool.length > 0) {
                questionObj = pool[idxs[qIndexInPool % pool.length]];
                qIndexInPool++;
            }

            schedule.push({
                time: times[q],
                asked: false,
                type: isOtp ? 'otp' : 'question',
                question: questionObj
            });
        }
        console.log("BuildSchedule completed. Total checkpoints in schedule:", schedule.length, schedule);
    }

    function TriggerCheckpoint(item) {
        item.asked = true;
        currentQ = item;
        pausedForQuestion = true;
        LPlayer.pause();

        if (item.type === 'otp') {
            TriggerOtpCheckpoint();
        } else {
            TriggerQuestion(item);
        }
    }

    function TriggerQuestion(item) {
        selectedChoice = null;
        let qIndex = schedule.indexOf(item) + 1;
        $("#lpQNo").text("คำถามที่ " + qIndex + " / " + schedule.length);
        $("#lpQText").html(item.question.text);
        $("#lpQHint").hide().text("");

        if (item.question.image) {
            $("#lpQImage").attr("src", item.question.image);
            $(".lp-q-image-container").show();
        } else {
            $("#lpQImage").attr("src", "");
            $(".lp-q-image-container").hide();
        }

        let html = "";
        item.question.choices.forEach(function (c, i) {
            html += '<button type="button" class="lp-choice" data-i="' + (i + 1) + '" onclick="LpPick(this)">' +
                '<span class="fw-medium me-1">' + (i + 1) + '.</span> ' + EscapeHTML(c) + '</button>';
        });
        $("#lpChoices").html(html);
        $("#lpOverlay").addClass("show");

        StartTimer();
    }

    function TriggerOtpCheckpoint() {
        $("#lpOtpPin").val("");
        $("#lpOtpHint").hide().text("");
        otpWrongCount = 0;
        $("#lpOtpWarningLine").hide();
        $("#lpOtpWrongCountDisplay").text("");

        Swal.fire({
            title: 'กำลังขอรหัส OTP...',
            text: 'ระบบกำลังส่งรหัสยืนยันไปยังมือถือของคุณ',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem("access_token") || '')
            },
            data: {
                request_state: "study_course",
                request_function: "request_otp"
            },
            dataType: "json",
            success: function (response) {
                Swal.close();
                if (response.result == 1) {
                    otpToken = response.data.token;
                    $("#lpOtpPhone").text(response.data.phone || '--');
                    $("#lpOtpRef").text("Ref: " + (response.data.refno || '')).show();
                    $("#lpOtpPin").val('');
                    $("#lpOtpOverlay").addClass("show");
                    StartOtpTimer();
                } else {
                    // แยก title/body ให้ชัดเจน ไม่ใช้ข้อความ API ดิบๆ ที่ผู้ใช้อ่านไม่เข้าใจ
                    var errMsg = response.msg || '';
                    var swTitle = 'ไม่สามารถส่งรหัส OTP ได้';
                    var swBody  = 'กรุณาตรวจสอบว่าเบอร์โทรศัพท์ในโปรไฟล์ของคุณถูกต้อง<br>แล้วลองเข้าเรียนใหม่อีกครั้ง';
                    if (errMsg.indexOf('ไม่พบเบอร์') !== -1 || errMsg.indexOf('ไม่มีเบอร์') !== -1) {
                        swTitle = 'ไม่พบเบอร์โทรศัพท์';
                        swBody  = 'ระบบไม่พบเบอร์โทรศัพท์ที่ผูกกับบัญชีของคุณ<br>กรุณาอัปเดตเบอร์โทรในหน้า <b>โปรไฟล์</b> แล้วลองใหม่อีกครั้ง';
                    } else if (errMsg.indexOf('ตัวเลข 10 หลัก') !== -1 || errMsg.indexOf('ไม่ถูกต้อง') !== -1) {
                        swTitle = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
                        swBody  = 'เบอร์โทรศัพท์ในโปรไฟล์ไม่ถูกต้อง<br>กรุณาแก้ไขให้เป็นเบอร์มือถือ 10 หลัก แล้วลองใหม่';
                    }
                    Swal.fire({
                        icon: 'warning',
                        title: swTitle,
                        html: swBody,
                        confirmButtonText: 'รับทราบ'
                    }).then(() => {
                        maxWatched = 0;
                        SaveProgress(true, false, false, true);
                        LpRestart();
                    });
                }
            },
            error: function () {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อระบบขัดข้อง',
                    text: 'ระบบไม่สามารถส่งสัญญาณขอรหัส OTP ได้ในขณะนี้',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    maxWatched = 0;
                    SaveProgress(true, false, false, true);
                    LpRestart();
                });
            }
        });
    }

    function StartOtpTimer() {
        if (otpTimerInterval) { clearInterval(otpTimerInterval); }
        let remain = (LESSON_QUESTION_TIME && LESSON_QUESTION_TIME > 0) ? LESSON_QUESTION_TIME : 300;
        $("#lpOtpTimer").text(remain);
        otpTimerInterval = setInterval(function () {
            remain--;
            $("#lpOtpTimer").text(remain);
            if (remain <= 0) {
                clearInterval(otpTimerInterval); otpTimerInterval = null;
                maxWatched = 0;
                SaveProgress(true, false, false, true);
                $("#lpOtpOverlay").removeClass("show");
                Swal.fire({
                    title: "หมดเวลากรอกรหัส OTP!",
                    text: "คุณไม่ได้กรอกรหัสยืนยันภายในเวลาที่กำหนด ระบบจะเริ่มต้นเรียนบทเรียนนี้ใหม่ตั้งแต่ต้น",
                    icon: "error",
                    confirmButtonText: "ตกลง",
                    allowOutsideClick: false
                }).then(function () {
                    LpRestart();
                });
            }
        }, 1000);
    }

    function LpCloseOtp() {
        if (otpTimerInterval) { clearInterval(otpTimerInterval); otpTimerInterval = null; }
        $("#lpOtpOverlay").removeClass("show");
        maxWatched = 0;
        SaveProgress(true, false, false, true);
        LpRestart();
    }

    function LpAutoFillOtp() {
        const code = $("#lpOtpCodeDisplay").text().trim();
        if (code && code !== '----') {
            $("#lpOtpPin").val(code).focus();
        }
    }

    $(document).on("keyup", "#lpOtpPin", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            LpVerifyOtp();
        }
    });

    function LpVerifyOtp() {
        const pin = $("#lpOtpPin").val().trim();
        $("#lpOtpHint").hide().text("");

        Swal.fire({
            title: 'กำลังตรวจสอบรหัส...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem("access_token") || '')
            },
            data: {
                request_state: "study_course",
                request_function: "verify_otp",
                token: otpToken,
                pin: pin
            },
            dataType: "json",
            success: function (response) {
                Swal.close();
                if (response.result == 1) {
                    if (otpTimerInterval) { clearInterval(otpTimerInterval); otpTimerInterval = null; }
                    $("#lpOtpOverlay").removeClass("show");
                    pausedForQuestion = false;
                    currentQ = null;
                    SaveProgress(true);
                    LPlayer.play();
                } else {
                    otpWrongCount++;
                    if (otpWrongCount >= 3) {
                        if (otpTimerInterval) { clearInterval(otpTimerInterval); otpTimerInterval = null; }
                        $("#lpOtpOverlay").removeClass("show");
                        maxWatched = 0;
                        SaveProgress(true, false, false, true);
                        Swal.fire({
                            title: "รหัส OTP ไม่ถูกต้อง!",
                            text: "คุณกรอกรหัสยืนยันผิดครบ 3 ครั้ง ระบบจะเริ่มต้นเรียนบทเรียนนี้ใหม่ตั้งแต่ต้น",
                            icon: "error",
                            confirmButtonText: "ตกลง",
                            allowOutsideClick: false
                        }).then(function () {
                            LpRestart();
                        });
                    } else {
                        $("#lpOtpPin").val("");
                        $("#lpOtpWrongCountDisplay").text("(" + otpWrongCount + "/3)");
                        $("#lpOtpWarningLine").show();
                        $("#lpOtpHint").hide().text("");
                        Swal.fire({
                            title: "รหัส OTP ไม่ถูกต้อง!",
                            text: "คุณกรอกรหัสยืนยันไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง (เหลือโอกาสอีก " + (3 - otpWrongCount) + " ครั้ง)",
                            icon: "warning",
                            confirmButtonText: "ตกลง",
                            allowOutsideClick: false
                        });
                    }
                }
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถส่งสัญญาณยืนยันรหัสได้ กรุณาลองใหม่อีกครั้ง", "error");
            }
        });
    }

    function EscapeHTML(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function LpPick(el) {
        selectedChoice = parseInt($(el).data("i"), 10);
        $("#lpChoices .lp-choice").removeClass("selected");
        $(el).addClass("selected");
    }

    function StartTimer() {
        if (timerInterval) { clearInterval(timerInterval); }
        let t = LESSON_QUESTION_TIME;
        if (!t || t <= 0) { $("#lpTimer").text("∞"); return; }
        let remain = t;
        $("#lpTimer").text(remain);
        timerInterval = setInterval(function () {
            remain--;
            $("#lpTimer").text(remain);
            if (remain <= 0) {
                clearInterval(timerInterval); timerInterval = null;
                maxWatched = 0;
                SaveProgress(true, false, false, true);
                Swal.fire({
                    title: "หมดเวลาตอบคำถาม!",
                    text: "คุณไม่ได้ตอบคำถามภายในเวลาที่กำหนด ระบบจะเริ่มต้นเรียนบทเรียนนี้ใหม่ตั้งแต่ต้น",
                    icon: "error",
                    confirmButtonText: "ตกลง",
                    allowOutsideClick: false
                }).then(function () {
                    LpRestart();
                });
            }
        }, 1000);
    }

    function LpSubmitAnswer() {
        if (selectedChoice === null) {
            $("#lpQHint").show().text("กรุณาเลือกคำตอบก่อน");
            return;
        }

        Swal.fire({
            title: 'กำลังตรวจสอบคำตอบ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem("access_token") || '')
            },
            data: {
                request_state: "study_course",
                request_function: "verify_question",
                question_id: currentQ.question.question_id,
                selected_choice: selectedChoice
            },
            dataType: "json",
            success: function (response) {
                Swal.close();
                if (response.result == 1) {
                    FinishQuestion(true);
                } else {
                    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
                    maxWatched = 0;
                    SaveProgress(true, false, false, true);
                    Swal.fire({
                        title: "คำตอบไม่ถูกต้อง!",
                        text: "คุณตอบคำถามผิด ระบบจะเริ่มต้นเรียนบทเรียนนี้ใหม่ตั้งแต่ต้น",
                        icon: "error",
                        confirmButtonText: "ตกลง",
                        allowOutsideClick: false
                    }).then(function () {
                        LpRestart();
                    })
                    LpRestart();
                }
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถเชื่อมต่อระบบเพื่อตรวจสอบคำตอบได้", "error");
            }
        });
    }

    function FinishQuestion(isCorrect) {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        answered++;
        if (isCorrect) { correctCount++; }
        $("#lpOverlay").removeClass("show");
        pausedForQuestion = false;
        currentQ = null;
        SaveProgress(true);
        LPlayer.play();
    }

    function goToNextLesson(skipSwal) {
        if (!NEXT_LESSON_KEY || isNavigatingToNext) return;
        isNavigatingToNext = true;
        let eid = '<?php echo isset($_GET["eid"]) ? $_GET["eid"] : ""; ?>';

        if (skipSwal) {
            window.location.href = 'lesson_study?key=' + NEXT_LESSON_KEY + '&eid=' + eid;
            return;
        }

        let start = Date.now();
        let duration = 1500;

        Swal.fire({
            title: 'กำลังไปบทเรียนถัดไป...',
            html: '<div style="width: 100%; background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; margin-top: 15px;">' +
                  '  <div id="swalCustomProgress" style="width: 0%; height: 100%; background: #3b5998; transition: width 0.05s linear;"></div>' +
                  '</div>',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                let interval = setInterval(() => {
                    let elapsed = Date.now() - start;
                    let pct = Math.min(100, (elapsed / duration) * 100);
                    let bar = document.getElementById('swalCustomProgress');
                    if (bar) {
                        bar.style.width = pct + '%';
                    }
                    if (elapsed >= duration) {
                        clearInterval(interval);
                        window.location.href = 'lesson_study?key=' + NEXT_LESSON_KEY + '&eid=' + eid;
                    }
                }, 30);
            }
        });
    }

    function handleAllLessonsFinished() {
        if (isNavigatingToNext) return;
        isNavigatingToNext = true;
        Swal.fire({
            icon: 'success',
            title: 'ท่านอบรมครบทุกบทเรียนแล้ว',
            confirmButtonText: 'ไปทำข้อสอบ',
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            allowOutsideClick: false,
            didOpen: () => {
                const confirmBtn = Swal.getConfirmButton();
                const cancelBtn = Swal.getCancelButton();
                if (confirmBtn && cancelBtn) {
                    confirmBtn.style.minWidth = '130px';
                    cancelBtn.style.minWidth = '130px';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (typeof ENROLL_KEY !== 'undefined' && ENROLL_KEY) {
                    window.location.href = 'study_course?key=' + ENROLL_KEY;
                } else {
                    window.location.href = 'study_course';
                }
            } else {
                isNavigatingToNext = false;
            }
        });
    }

    function triggerLessonCompletion() {
        if (isNavigatingToNext) return;
        if (NEXT_LESSON_KEY) {
            goToNextLesson();
        } else {
            handleAllLessonsFinished();
        }
    }

    function goToPrevLesson() {
        if (!PREV_LESSON_KEY || isNavigatingToNext) return;
        isNavigatingToNext = true;
        let eid = '<?php echo isset($_GET["eid"]) ? $_GET["eid"] : ""; ?>';
        window.location.href = 'lesson_study?key=' + PREV_LESSON_KEY + '&eid=' + eid;
    }

    $(document).on('click', '#btn-next-lesson', function (e) {
        e.preventDefault();
        goToNextLesson(true);
    });

    $(document).on('click', '#btn-prev-lesson', function (e) {
        e.preventDefault();
        goToPrevLesson();
    });

    function SaveProgress(force, sync, isFinished, reset) {
        if (ALREADY_COMPLETED) {
            if (isFinished) {
                triggerLessonCompletion();
            }
            return;
        }
        let status = (isFinished || (duration > 0 && maxWatched >= duration - 3)) ? "1" : "0";
        let sec = (status === "1" && duration > 0) ? Math.floor(duration) : Math.floor(maxWatched);
        if (!force && (sec - lastSaveSec) < 3) { return; }
        lastSaveSec = sec;

        if (sync) {
            // ใช้ navigator.sendBeacon หรือ fetch keepalive เพื่อไม่ให้เบราว์เซอร์บล็อกตอนปิดหน้าเว็บ
            const token = localStorage.getItem("access_token");
            const headers = {
                'Content-Type': 'application/x-www-form-urlencoded'
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }
            const body = new URLSearchParams({
                request_state: "study_course",
                request_function: "save_progress",
                lesson_id: LESSON_ID,
                enroll_id: ENROLL_ID,
                last_sec: sec,
                status: status,
                reset: reset ? 1 : 0
            });
            fetch("core.php", {
                method: "POST",
                headers: headers,
                body: body,
                keepalive: true
            });
            if (isFinished) {
                triggerLessonCompletion();
            }
            return;
        }

        $.ajax({
            type: "POST", url: "core.php",
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem("access_token") || '')
            },
            data: {
                request_state: "study_course",
                request_function: "save_progress",
                lesson_id: LESSON_ID,
                enroll_id: ENROLL_ID,
                last_sec: sec,
                status: status,
                reset: reset ? 1 : 0
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    $("#lpSaveInfo").html('<span class="text-success"><i class="bi bi-cloud-check"></i> บันทึกความคืบหน้าแล้วที่ ' + fmt(sec) + '</span>');
                    if (isFinished) {
                        triggerLessonCompletion();
                    }
                } else {
                    console.error("Save progress failed:", r.msg);
                    $("#lpSaveInfo").html('<span class="text-danger"><i class="bi bi-cloud-slash"></i> บันทึกความคืบหน้าไม่สำเร็จ: ' + r.msg + '</span>');
                }
            },
            error: function (xhr, status, error) {
                console.error("Save progress AJAX error:", error);
                $("#lpSaveInfo").html('<span class="text-danger"><i class="bi bi-cloud-slash"></i> เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อบันทึก</span>');
            }
        });
    }

    function handlePageLeave() {
        if (typeof pausedForQuestion !== 'undefined' && pausedForQuestion === true) {
            if (typeof maxWatched !== 'undefined') {
                maxWatched = 0;
            }
            if (typeof SaveProgress === 'function') {
                SaveProgress(true, true, false, true);
            }
        } else {
            if (typeof SaveProgress === 'function') {
                SaveProgress(true, true);
            }
        }
    }

    window.addEventListener("pagehide", handlePageLeave);
    window.addEventListener("beforeunload", handlePageLeave);

    function UpdateWatched(t) {
        $("#lpWatched").text(fmt(maxWatched));
        let pct = duration > 0 ? Math.min(100, Math.max(0, (t / duration) * 100)) : 0;
        $("#lpWatchedBar").css("width", pct + "%");

        // Update custom timeline range slider and track background gradient
        let timeline = $("#lpTimeline");
        if (timeline.length && !timeline.is(":active")) {
            let curPct = duration > 0 ? Math.min(100, Math.max(0, (t / duration) * 100)) : 0;
            if (timeline[0]) {
                timeline[0].value = curPct;
            }
            timeline.css("background", `linear-gradient(to right, #3b5998 0%, #3b5998 ${curPct}%, rgba(255,255,255,0.2) ${curPct}%, rgba(255,255,255,0.2) 100%)`);
        }

        // Always allow pointer events to enable backward seeking
        timeline.css({ "pointer-events": "auto", "cursor": "pointer" });

        // Update current time display
        $("#lpCtrlTime").text(fmt(t) + " / " + fmt(duration));
    }

    function LpTogglePlay() {
        LPlayer.togglePlay();
    }

    function LpToggleMute() {
        LPlayer.toggleMute();
    }

    function showVolumeSlider() {
        let container = document.getElementById('lpVolumeSliderContainer');
        if (container) {
            container.style.width = '100px';
            container.style.opacity = '1';
        }
    }

    function hideVolumeSlider() {
        let container = document.getElementById('lpVolumeSliderContainer');
        if (container) {
            container.style.width = '0';
            container.style.opacity = '0';
        }
    }

    function LpChangeVolume(val) {
        if (LPlayer.kind === "vimeo" && LPlayer.vimeo) {
            LPlayer.vimeo.setVolume(val);
        } else if (LPlayer.video) {
            LPlayer.video.volume = val;
        }

        // Update icon based on volume
        if (val == 0) {
            $("#lpMuteIcon").removeClass("bi-volume-up-fill").addClass("bi-volume-mute-fill");
        } else {
            $("#lpMuteIcon").removeClass("bi-volume-mute-fill").addClass("bi-volume-up-fill");
        }
    }

    function LpToggleFullscreen() {
        let wrap = document.querySelector(".lp-player-wrap");
        if (!wrap) return;

        let isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
        
        if (LPlayer.kind === "vimeo" && LPlayer.vimeo && !isFullscreen) {
            LPlayer.vimeo.requestFullscreen().catch(function(error) {
                console.error("Vimeo Fullscreen API error:", error);
                fallbackFullscreen(wrap);
            });
        } else if (!isFullscreen) {
            fallbackFullscreen(wrap);
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) { /* Safari */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { /* IE11 */
                document.msExitFullscreen();
            }
        }
    }

    function fallbackFullscreen(wrap) {
        if (wrap.requestFullscreen) {
            wrap.requestFullscreen().catch(err => console.error("Error attempting to enable fullscreen:", err));
        } else if (wrap.webkitRequestFullscreen) { /* Safari */
            wrap.webkitRequestFullscreen();
        } else if (wrap.msRequestFullscreen) { /* IE11 */
            wrap.msRequestFullscreen();
        } else {
            // Fallback for iOS Safari which only supports fullscreen on the video element directly
            let video = wrap.querySelector('video') || wrap.querySelector('iframe');
            if (video && video.webkitEnterFullscreen) {
                video.webkitEnterFullscreen();
            } else {
                console.error("Fullscreen API is not supported.");
            }
        }
    }

    // Delegated events for custom timeline control
    $(document).on("input", "#lpTimeline", function (e) {
        let targetPct = parseFloat($(this).val());
        let targetSec = (targetPct / 100) * duration;

        let limit = maxWatched + 2.0;
        let snapTo = maxWatched;
        if (CAN_SKIP) {
            limit = Math.max(duration * 1, maxWatched + 2.0);
            snapTo = Math.max(duration * 1, maxWatched);
        }

        if (!ALREADY_COMPLETED && targetSec > limit) {
            // Hard block dragging to future progress
            let maxPct = duration > 0 ? (snapTo / duration) * 100 : 0;
            this.value = maxPct;
            $(this).css("background", `linear-gradient(to right, #3b5998 0%, #3b5998 ${maxPct}%, rgba(255,255,255,0.2) ${maxPct}%, rgba(255,255,255,0.2) 100%)`);
            GuardForward(targetSec, false);
        } else {
            // Update gradient filling color dynamically while dragging within valid range
            $(this).css("background", `linear-gradient(to right, #3b5998 0%, #3b5998 ${targetPct}%, rgba(255,255,255,0.2) ${targetPct}%, rgba(255,255,255,0.2) 100%)`);
        }
    });

    $(document).on("change", "#lpTimeline", function (e) {
        let targetPct = parseFloat($(this).val());
        let targetSec = (targetPct / 100) * duration;

        let limit = maxWatched + 2.0;
        let snapTo = maxWatched;
        if (CAN_SKIP) {
            limit = Math.max(duration * 1, maxWatched + 2.0);
            snapTo = Math.max(duration * 1, maxWatched);
        }

        if (!ALREADY_COMPLETED && targetSec > limit) {
            let maxPct = duration > 0 ? (snapTo / duration) * 100 : 0;
            this.value = maxPct;
            $(this).css("background", `linear-gradient(to right, #3b5998 0%, #3b5998 ${maxPct}%, rgba(255,255,255,0.2) ${maxPct}%, rgba(255,255,255,0.2) 100%)`);
            GuardForward(targetSec);
        } else {
            seeking = true;
            LPlayer.setTime(targetSec);
        }
    });

    // Auto-hide controls logic
    let controlsTimeout;
    $(document).on("mousemove", ".lp-player-wrap", function () {
        let wrap = $(this);
        wrap.addClass("controls-active");
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(function () {
            if (!pausedForQuestion && !Swal.isVisible()) {
                wrap.removeClass("controls-active");
            }
        }, 2500);
    });

    $(document).on("mouseleave", ".lp-player-wrap", function () {
        if (!pausedForQuestion && !Swal.isVisible()) {
            $(this).removeClass("controls-active");
        }
    });

    let lastGuardAlertTime = 0;
    function GuardForward(t, muteAlert = false) {
        if (ALREADY_COMPLETED) { return false; }

        let limit = maxWatched + 2.0;
        let snapTo = maxWatched;
        if (CAN_SKIP) {
            limit = Math.max(duration * 1, maxWatched + 2.0);
            snapTo = Math.max(duration * 1, maxWatched);
        }

        if (t > limit) {
            if (!muteAlert) {
                let now = Date.now();
                if (now - lastGuardAlertTime > 2000) {
                    lastGuardAlertTime = now;
                    Swal.fire({
                        icon: 'warning',
                        title: 'แจ้งเตือน',
                        text: 'ไม่สามารถเลื่อนวิดีโอไปข้างหน้าได้เนื่องจากยังเรียนไม่จบ',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }

            if (seeking) {
                return true;
            }
            seeking = true;
            LPlayer.setTime(snapTo);
            return true;
        }
        return false;
    }

    function LpToast(msg) {
        Swal.fire({ toast: true, position: "top", icon: "warning", title: msg, showConfirmButton: false, timer: 1500, timerProgressBar: true });
    }

    function LoadLessonStudyData() {
        Swal.fire({ title: "กำลังโหลดบทเรียน...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "study_course",
                request_function: "get_lesson",
                course_id: COURSE_ID,
                lesson_id: LESSON_ID,
                enroll_id: ENROLL_ID
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    RenderLessonStudy(response.data);
                } else {
                    Swal.close();
                    Swal.fire("เกิดข้อผิดพลาด", response.msg || 'ไม่สามารถโหลดข้อมูลบทเรียนได้', "error");
                }
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ระบบขัดข้อง ไม่สามารถดำเนินการได้", "error");
            }
        });
    }

    function RenderLessonStudy(lessonData) {
        $.ajax({
            type: "POST",
            url: "view/study_course/lesson_study.php",
            data: JSON.stringify(lessonData),
            contentType: "application/json; charset=utf-8",
            processData: false,
            dataType: "html",
            success: function (responseHtml) {
                Swal.close();
                $("#study-course-content-area").html(responseHtml);
                // console.log("RenderLessonStudy loaded lessonData:", lessonData);

                // กำหนดค่าตัวแปรเริ่มต้นของเครื่องเล่นวิดีโอจาก JSON
                let rawVideo = (lessonData.lesson.lesson_video || '').trim();
                if (rawVideo && (rawVideo.indexOf('upload/') === 0 || !/(?:player\.)?vimeo\.com/i.test(rawVideo)) && rawVideo.indexOf('http') !== 0 && rawVideo.indexOf('/') !== 0) {
                    LESSON_VIDEO = '../backoffice/' + rawVideo;
                } else {
                    LESSON_VIDEO = rawVideo;
                }
                let hasCourseQ = (lessonData.course_question !== undefined && lessonData.course_question !== null);
                let isQEnabled = hasCourseQ ? (lessonData.course_question == 1 || lessonData.course_question === true || lessonData.course_question == "1") : (lessonData.lesson.lesson_question == "1" || lessonData.lesson.lesson_question == 1);
                LESSON_QUESTION = isQEnabled ? '1' : '0';

                if (lessonData.question_limit !== undefined && lessonData.question_limit !== null && lessonData.question_limit !== "") {
                    LESSON_QUESTION_LIMIT = parseInt(lessonData.question_limit) || 0;
                } else {
                    LESSON_QUESTION_LIMIT = parseInt(lessonData.lesson.lesson_question_limit) || 0;
                }

                if (lessonData.question_time !== undefined && lessonData.question_time !== null && lessonData.question_time !== "") {
                    LESSON_QUESTION_TIME = parseInt(lessonData.question_time) || 0;
                } else {
                    LESSON_QUESTION_TIME = parseInt(lessonData.lesson.lesson_question_time) || 0;
                }
                LP_QUESTIONS = lessonData.questions || [];
                RESUME_SEC = parseInt(lessonData.resume_sec) || 0;
                NEXT_LESSON_ID = lessonData.next_lesson_id || null;
                NEXT_LESSON_KEY = lessonData.next_lesson_key || null;
                NEXT_LESSON_ORDER = lessonData.next_lesson_order || null;
                NEXT_LESSON_NAME = lessonData.next_lesson_name || null;
                PREV_LESSON_ID = lessonData.prev_lesson_id || null;
                PREV_LESSON_KEY = lessonData.prev_lesson_key || null;
                PREV_LESSON_ORDER = lessonData.prev_lesson_order || null;
                PREV_LESSON_NAME = lessonData.prev_lesson_name || null;
                isNavigatingToNext = false;
                ENROLL_KEY = lessonData.enroll_key || null;
                ALREADY_COMPLETED = (lessonData.already_completed == 1 || lessonData.already_completed === true || lessonData.already_completed == "1" || lessonData.already_completed == "true");
                CAN_SKIP = (lessonData.course_skip == 1 || lessonData.course_skip === true || lessonData.course_skip == "1" || lessonData.course_skip == "true");
                COURSE_OTP = (lessonData.course_otp == 1 || lessonData.course_otp === true || lessonData.course_otp == "1" || lessonData.course_otp == "true");

                LpRestart();
            },
            error: function () {
                Swal.close();
                Swal.fire("เกิดข้อผิดพลาด", "ไม่สามารถแสดงผลบทเรียนได้", "error");
            }
        });
    }

    $(document).ready(function () {
        LoadLessonStudyData();
    });

    /*
    // 1. ดักจับการสลับ Tab หรือพับหน้าจอ (Visibility Change)
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === 'hidden') {
            // เช็คว่ากำลังแสดงคำถามหรือ OTP อยู่หรือไม่ (ตัวแปร pausedForQuestion จะเป็น true)
            if (typeof pausedForQuestion !== 'undefined' && pausedForQuestion === true) {
                // รีเซ็ตการดูวิดีโอเป็น 0 ทันที
                if (typeof maxWatched !== 'undefined') {
                    maxWatched = 0;
                }
                if (typeof SaveProgress === 'function') {
                    SaveProgress(true, false, false, true);
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'ตรวจพบการสลับหน้าจอ',
                    text: 'ระบบจะพากลับไปยังหน้าคอร์สเรียน',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    if (typeof ENROLL_KEY !== 'undefined' && ENROLL_KEY) {
                        window.location.href = 'study_course?key=' + ENROLL_KEY + '#lesson-' + LESSON_ID;
                    } else {
                        window.location.href = 'study_course#lesson-' + LESSON_ID;
                    }
                });
            } else {
                // ถ้าสลับหน้าจอตอนดูวิดีโอปกติ จะให้วิดีโอหยุดเล่นอัตโนมัติ (ถ้าต้องการ)
                if (typeof LPlayer !== 'undefined' && typeof LPlayer.pause === 'function') {
                    LPlayer.pause();
                }
            }
        }
    });
    */

    // 2. ป้องกันการกด Back / Forward (Undo/Redo ของ Browser) เฉพาะตอนมีคำถามหรือ OTP
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        if (typeof pausedForQuestion !== 'undefined' && pausedForQuestion === true) {
            // ถ้าติดคำถามหรือ OTP อยู่ ให้ดึงกลับมาหน้าปัจจุบัน (ไม่ให้ออก)
            history.go(1);
        } else {
            // ถ้าไม่ได้ติดคำถามหรือ OTP ให้ย้อนกลับไปหน้าก่อนหน้าได้เลย
            history.back();
        }
    };

    // 3. ป้องกันการคลิกขวา (ป้องกัน Inspect แอบดูคำตอบ)
    document.addEventListener('contextmenu', event => event.preventDefault());

</script>

<?php include 'view/chatmessage/chatmessage.php'; ?>

<?php include 'components/footer.php'; ?>