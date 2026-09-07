<?php
    // หน้าทดสอบ/ดูตัวอย่างบทเรียน (lesson preview / test player)
    // - เล่นวิดีโอ Vimeo ผ่าน Player SDK
    // - คำถามเด้งคั่นแบบสุ่มตำแหน่ง + ต้องตอบก่อนไปต่อ
    // - กันเลื่อนไปข้างหน้า (ย้อนกลับได้ถึงจุดที่ดูแล้ว)
    // - ตั้งค่า + ดู อยู่หน้าเดียว เอาไว้ทดสอบ
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $course_key = isset($_GET['course_key']) ? trim($_GET['course_key']) : '';
    $lesson_key = isset($_GET['lesson_key']) ? trim($_GET['lesson_key']) : '';
    $course_id = \App\Utility\Cipher::decrypt($course_key);
    $lesson_id = \App\Utility\Cipher::decrypt($lesson_key);
    $breadcrumbs = [
        ['label' => 'คอร์สเรียน', 'url' => 'course'],
        ['label' => 'จัดการบทเรียน', 'url' => 'lesson_manage.php?course_key=' . $course_key . '&lesson_key=' . $lesson_key],
        ['label' => 'ทดสอบบทเรียน'],
    ];
?>
<?php include "header.php"; ?>

<style>
    /* overlay คำถามคลุมทับเครื่องเล่น */
    .lp-player-wrap { position: relative; width: 100%; aspect-ratio: 16 / 9; }
    .lp-player-wrap > #lpPlayer { position: absolute; inset: 0; }
    #lpPlayer iframe, #lpPlayer video { position: absolute; inset: 0; width: 100% !important; height: 100% !important; border: 0; background:#000; }
    .lp-question-overlay {
        position: absolute; inset: 0; z-index: 20;
        background: rgba(15, 18, 34, 0.92);
        display: none; flex-direction: column;
        padding: 24px; overflow-y: auto; border-radius: 12px;
    }
    .lp-question-overlay.show { display: flex; }
    .lp-choice {
        display: block; width: 100%; text-align: left;
        background: #fff; border: 2px solid #e5e7eb; border-radius: 10px;
        padding: 12px 16px; margin-bottom: 10px; cursor: pointer; transition: all .15s;
    }
    .lp-choice:hover { border-color: var(--brand-500); }
    .lp-choice.selected { border-color: var(--brand-500); background: var(--brand-soft); }
    .lp-timer-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.15); color: #fff;
        padding: 4px 12px; border-radius: 999px; font-weight: 500;
    }
    .lp-meta { font-size: 13px; }
    /* แก้สวิตช์ (toggle) ทับข้อความ: เพิ่มแค่ระยะซ้ายให้ label พ้นตัวสวิตช์
       (ไม่ตั้ง width/height ทับ ปล่อยให้ knob ของธีมขนาดถูกต้อง) */
    .col-lg-4 .form-switch { padding-left: 3em; min-height: 1.6em; }
    .col-lg-4 .form-switch .form-check-input { margin-left: -3em; cursor: pointer; }
    .col-lg-4 .form-switch .form-check-label { cursor: pointer; }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="mb-0">ทดสอบบทเรียน <span class="text-secondary fs-14">(Preview)</span></h4>
                    <span class="text-secondary lp-meta" id="lpModeBadge">โหมด Sandbox (ข้อมูลตัวอย่าง)</span>
                </div>
                <a href="lesson_manage.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_id; ?>"
                   class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">arrow_back</span> กลับไปจัดการบทเรียน
                </a>
            </div>

            <div class="row g-4">

                <!-- ===== เครื่องเล่น ===== -->
                <div class="col-lg-8">
                    <div class="card app-card bg-white border-0 rounded-3">
                        <div class="card-body p-3">
                            <div class="lp-player-wrap rounded-3 overflow-hidden bg-dark">
                                <div id="lpPlayer"></div>

                                <!-- overlay คำถาม -->
                                <div class="lp-question-overlay" id="lpOverlay">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-white fw-medium" id="lpQNo">คำถามที่ 1</span>
                                        <span class="lp-timer-pill"><span class="material-symbols-outlined" style="font-size:18px;" aria-hidden="true">timer</span><span id="lpTimer">--</span> วิ</span>
                                    </div>
                                    <div class="text-white fs-16 mb-3" id="lpQText"></div>
                                    <div id="lpChoices" class="flex-grow-1"></div>
                                    <div class="mt-2">
                                        <div class="text-warning small mb-2" id="lpQHint" style="display:none;"></div>
                                        <button class="btn btn-primary w-100" id="lpSubmitBtn" onclick="LpSubmitAnswer()">ตอบ &amp; ดูต่อ</button>
                                    </div>
                                </div>
                            </div>

                            <!-- แถบความคืบหน้า -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between lp-meta text-secondary mb-1">
                                    <span>ดูแล้วถึง: <span id="lpWatched">0:00</span> / <span id="lpDuration">0:00</span></span>
                                    <span><span class="material-symbols-outlined align-middle" style="font-size:16px;" aria-hidden="true">lock</span> เลื่อนไปข้างหน้าไม่ได้ (ย้อนกลับได้)</span>
                                </div>
                                <div class="progress" style="height:6px;border-radius:6px;">
                                    <div class="progress-bar" id="lpWatchedBar" style="width:0%;background:var(--brand-500);"></div>
                                </div>
                                <div class="lp-meta mt-1" id="lpSaveInfo"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== แผงตั้งค่า (ทดสอบสด) ===== -->
                <div class="col-lg-4">
                    <div class="card app-card bg-white border-0 rounded-3 mb-4">
                        <div class="card-body p-4">
                            <h6 class="mb-3">ตั้งค่าการทดสอบ</h6>

                            <div class="mb-3">
                                <label for="lpVideoUrl" class="form-label fw-medium lp-meta">ลิงก์วิดีโอ Vimeo</label>
                                <input type="text" class="form-control form-control-sm" id="lpVideoUrl"
                                       placeholder="https://vimeo.com/76979871">
                                <div class="form-text">วางลิงก์ Vimeo หรือใส่เฉพาะรหัสตัวเลขก็ได้</div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="lpEnableQuestion" checked>
                                <label class="form-check-label lp-meta" for="lpEnableQuestion">เปิดใช้งานคำถามระหว่างรับชม</label>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="lpQuestionLimit" class="form-label fw-medium lp-meta">จำนวนครั้งที่จะเด้ง (คำถาม/OTP)</label>
                                    <input type="number" min="0" class="form-control form-control-sm" id="lpQuestionLimit" value="2">
                                </div>
                                <div class="col-6">
                                    <label for="lpQuestionTime" class="form-label fw-medium lp-meta">เวลาตอบ (วินาที)</label>
                                    <input type="number" min="0" class="form-control form-control-sm" id="lpQuestionTime" value="20">
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="lpMustAnswer" checked>
                                <label class="form-check-label lp-meta" for="lpMustAnswer">บังคับตอบก่อนไปต่อ (ห้ามข้าม)</label>
                            </div>

                            <button class="btn btn-primary w-100 mb-2" onclick="LpRestart()">
                                <span class="material-symbols-outlined align-middle" style="font-size:18px;" aria-hidden="true">restart_alt</span> เริ่มทดสอบใหม่
                            </button>
                            <?php if ($lesson_id > 0): ?>
                            <button class="btn btn-outline-primary w-100" onclick="LpLoadReal()">
                                <span class="material-symbols-outlined align-middle" style="font-size:18px;" aria-hidden="true">cloud_download</span> โหลดข้อมูลจริงจากบทเรียน
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- สรุปผล -->
                    <div class="card app-card bg-white border-0 rounded-3">
                        <div class="card-body p-4">
                            <h6 class="mb-3">ผลการตอบ</h6>
                            <div id="lpResult" class="lp-meta text-secondary">ยังไม่มีการตอบคำถาม</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<?php include "script.php"; ?>
<script src="https://player.vimeo.com/api/player.js"></script>

</body>
</html>

<script>
    var COURSE_ID = <?php echo $course_id; ?>;
    var LESSON_ID = <?php echo $lesson_id; ?>;

    // ===== ข้อมูลตัวอย่าง (sandbox) =====
    var SAMPLE_HTML5 = "https://media.w3.org/2010/05/sintel/trailer.mp4"; // วิดีโอตัวอย่าง HTML5 (เล่นได้ทุกที่ ไม่ติด embed)
    var MOCK_QUESTIONS = [
        { text: "การอบรม CPD ย่อมาจากข้อใด?", choices: ["Continuing Professional Development", "Certified Public Data", "Central Profit Database", "Corporate Policy Document"], correct: 1 },
        { text: "ผู้ทำบัญชีต้องเก็บชั่วโมง CPD ขั้นต่ำกี่ชั่วโมงต่อปี (ตัวอย่าง)?", choices: ["6 ชั่วโมง", "12 ชั่วโมง", "20 ชั่วโมง", "ไม่มีกำหนด"], correct: 1 },
        { text: "ข้อใดคือหน้าที่ของผู้สอบบัญชี?", choices: ["ออกแบบเว็บไซต์", "ตรวจสอบและแสดงความเห็นต่องบการเงิน", "ขายสินค้า", "เขียนโปรแกรม"], correct: 2 },
        { text: "จรรยาบรรณวิชาชีพบัญชีเน้นเรื่องใดเป็นหลัก?", choices: ["ความซื่อสัตย์และความเที่ยงธรรม", "ความเร็ว", "ราคาถูก", "การตลาด"], correct: 1 }
    ];

    // ===== สถานะเครื่องเล่น =====
    var duration = 0;
    var maxWatched = 0;          // วินาทีที่ดูถึงมากสุด (กันเลื่อนข้าม)
    var schedule = [];           // [{time, asked, question}]
    var pausedForQuestion = false;
    var seeking = false;         // กัน loop ตอน setCurrentTime
    var currentQ = null;
    var selectedChoice = null;
    var timerInterval = null;
    var answered = 0, correctCount = 0;
    var REAL_MODE = false;       // true = โหลดข้อมูลจริงจากบทเรียน (เปิดบันทึก/จำจุดดูค้าง)
    var RESUME_SEC = 0;          // จุดที่ดูค้างไว้ (วินาที) จาก tbl_lesson_progress
    var lastSaveSec = 0;         // บันทึกล่าสุดกี่วินาที (ใช้ throttle)

    function fmt(s) {
        s = Math.max(0, Math.floor(s || 0));
        var m = Math.floor(s / 60), ss = s % 60;
        return m + ":" + (ss < 10 ? "0" : "") + ss;
    }

    // ดึงรหัสวิดีโอจากลิงก์ Vimeo หรือ player URL
    function parseVimeoId(input) {
        input = (input || "").trim();
        if (/^\d+$/.test(input)) { return input; }
        var m = input.match(/vimeo\.com\/(?:video\/)?(\d+)/) || input.match(/player\.vimeo\.com\/video\/(\d+)/);
        return m ? m[1] : "";
    }

    // ===== Player adapter: รองรับทั้ง Vimeo (บทเรียนจริง) และ HTML5 (sandbox/ไฟล์ mp4) =====
    var LPlayer = {
        kind: null, vimeo: null, video: null, _h: {},
        build: function (source, handlers) {
            this.destroy();
            this._h = handlers || {};
            var self = this;
            var box = document.getElementById("lpPlayer");
            box.innerHTML = "";
            var vid = parseVimeoId(source);
            if (vid) {
                this.kind = "vimeo";
                // ถ้าเป็น URL เต็ม -> ส่ง url (เก็บ ?h=hash ของวิดีโอ unlisted ไว้), ถ้าเป็นเลขล้วน -> ส่ง id
                var vopt = /vimeo\.com/.test(source) ? { url: source.trim() } : { id: vid };
                vopt.responsive = false; vopt.keyboard = false;
                this.vimeo = new Vimeo.Player(box, vopt);
                this.vimeo.on("timeupdate", function (d) { if (self._h.onTime) self._h.onTime(d.seconds); });
                this.vimeo.on("seeked", function (d) { if (self._h.onSeeked) self._h.onSeeked(d.seconds); });
                this.vimeo.on("ended", function () { if (self._h.onEnded) self._h.onEnded(); });
                return this.vimeo.ready().then(function () { return self.vimeo.getDuration(); });
            }
            // HTML5 video (ไฟล์ mp4/webm หรือวิดีโอตัวอย่าง sandbox)
            this.kind = "html5";
            var v = document.createElement("video");
            var cleanSrc = source;
            if (cleanSrc.indexOf('upload/') === 0) {
                cleanSrc = '../' + cleanSrc;
            }
            v.src = cleanSrc;
            v.controls = true;
            v.setAttribute("playsinline", "");
            box.appendChild(v);
            this.video = v;
            v.addEventListener("timeupdate", function () { if (self._h.onTime) self._h.onTime(v.currentTime); });
            v.addEventListener("seeked", function () { if (self._h.onSeeked) self._h.onSeeked(v.currentTime); });
            v.addEventListener("ended", function () { if (self._h.onEnded) self._h.onEnded(); });
            return new Promise(function (resolve, reject) {
                v.addEventListener("loadedmetadata", function () { resolve(v.duration); });
                v.addEventListener("error", function () { reject(new Error("ไฟล์วิดีโอโหลดไม่ได้ (ตรวจสอบที่อยู่ไฟล์)")); });
            });
        },
        play: function () { if (this.kind === "vimeo") { this.vimeo.play(); } else if (this.video) { this.video.play(); } },
        pause: function () { if (this.kind === "vimeo") { this.vimeo.pause(); } else if (this.video) { this.video.pause(); } },
        setTime: function (s) { if (this.kind === "vimeo") { this.vimeo.setCurrentTime(s); } else if (this.video) { this.video.currentTime = s; } },
        destroy: function () {
            if (this.vimeo) { try { this.vimeo.destroy(); } catch (e) {} this.vimeo = null; }
            if (this.video) { try { this.video.pause(); } catch (e) {} this.video = null; }
            this.kind = null;
            var box = document.getElementById("lpPlayer"); if (box) { box.innerHTML = ""; }
        }
    };

    // ===== เริ่ม/รีเซ็ตการทดสอบ =====
    function LpRestart() {
        // เคลียร์ของเดิม
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        LPlayer.destroy();
        $("#lpOverlay").removeClass("show");
        maxWatched = 0; duration = 0; schedule = []; pausedForQuestion = false; seeking = false;
        currentQ = null; selectedChoice = null; answered = 0; correctCount = 0; lastSaveSec = 0;
        UpdateResult();
        UpdateWatched(0);

        // ถ้าผู้ใช้ใส่ลิงก์ Vimeo -> เล่น Vimeo, ถ้าไม่ใส่ -> วิดีโอตัวอย่าง HTML5
        var src = ($("#lpVideoUrl").val() || "").trim() || SAMPLE_HTML5;

        LPlayer.build(src, {
            onTime: function (t) {
                if (pausedForQuestion) { return; }
                if (GuardForward(t)) { return; }   // กระโดดไปข้างหน้า -> ดีดกลับ (ไม่ให้ maxWatched โตตาม)
                if (t > maxWatched) { maxWatched = t; }
                UpdateWatched(t);
                SaveProgress(false);               // บันทึกจุดที่ดู (throttle ทุก ~5 วิ)
                for (var i = 0; i < schedule.length; i++) {
                    if (!schedule[i].asked && t >= schedule[i].time) { TriggerQuestion(schedule[i]); break; }
                }
            },
            onSeeked: function (sec) {
                if (seeking) { seeking = false; return; }  // เป็นการดีดกลับเอง -> ข้าม
                GuardForward(sec);
            },
            onEnded: function () {
                $("#lpResult").html('<span class="text-success fw-medium">ดูจบแล้ว</span> — ตอบ ' + answered + ' ข้อ ถูก ' + correctCount + ' ข้อ');
                SaveProgress(true); // บันทึกสถานะ "ดูจบ"
            }
        }).then(function (d) {
            duration = d;
            $("#lpDuration").text(fmt(d));
            BuildSchedule();
            // เล่นต่อจากจุดที่ดูค้างไว้ (เฉพาะโหมดข้อมูลจริง)
            if (REAL_MODE && RESUME_SEC > 1 && RESUME_SEC < d - 1) {
                maxWatched = RESUME_SEC;
                // ข้ามคำถามที่อยู่ก่อนจุด resume (กันเด้งรัวตอนเล่นต่อ)
                schedule.forEach(function (s) { if (s.time <= RESUME_SEC) { s.asked = true; } });
                UpdateWatched(RESUME_SEC);
                seeking = true;
                LPlayer.setTime(RESUME_SEC);
                LpToast("เล่นต่อจากจุดที่ดูค้างไว้ (" + fmt(RESUME_SEC) + ")");
            }
            RESUME_SEC = 0; // ใช้ครั้งเดียว
        }).catch(function (err) {
            Swal.fire({ title: "โหลดวิดีโอไม่ได้", html: '<span class="fw-bold text-danger">' + (err && err.message ? err.message : "ตรวจสอบลิงก์/ไฟล์วิดีโออีกครั้ง") + '</span>', icon: "error", showConfirmButton: true });
        });
    }

    // สร้างตารางเด้ง: จำนวนจุดทั้งหมดตามที่ตั้งค่า แต่ละจุดสุ่มเป็นคำถามหรือ OTP (เหมือนหน้าจริง)
    function BuildSchedule() {
        schedule = [];
        if (!$("#lpEnableQuestion").is(":checked")) { return; }
        var n = parseInt($("#lpQuestionLimit").val(), 10) || 0;
        // โหมดจริงใช้คำถามจริง (ว่าง = ไม่มีคำถาม -> เป็น OTP หมด), sandbox ใช้คำถามตัวอย่าง
        var pool = REAL_MODE ? (window.LP_QUESTIONS || []) : MOCK_QUESTIONS;
        if (n <= 0 || duration <= 0) { return; }

        var lo = duration * 0.1, hi = duration * 0.9;
        var times = [];
        for (var i = 0; i < n; i++) {
            times.push(lo + Math.random() * (hi - lo));
        }
        times.sort(function (a, b) { return a - b; });

        // เลือกคำถามแบบสุ่มไม่ซ้ำจาก pool
        var idxs = pool.map(function (_, i) { return i; });
        for (var j = idxs.length - 1; j > 0; j--) {
            var k = Math.floor(Math.random() * (j + 1));
            var tmp = idxs[j]; idxs[j] = idxs[k]; idxs[k] = tmp;
        }
        for (var q = 0; q < n; q++) {
            var hasQ = (pool.length > 0 && idxs[q] !== undefined);
            var isOtp = !hasQ || (Math.random() < 0.5);   // มีคำถาม -> สุ่ม 50% คำถาม/OTP, ไม่มี -> OTP
            schedule.push({ time: times[q], asked: false, type: isOtp ? 'otp' : 'question', question: hasQ ? pool[idxs[q]] : null });
        }
    }

    function TriggerQuestion(item) {
        item.asked = true;
        currentQ = item;
        selectedChoice = null;
        pausedForQuestion = true;
        LPlayer.pause();

        var idx = schedule.indexOf(item) + 1;
        $("#lpQHint").hide().text("");

        // จุดยืนยัน OTP (จำลองในหน้าทดสอบ — ของจริงเด้งกรอก OTP ฝั่งนักเรียน)
        if (item.type === 'otp') {
            $("#lpQNo").text("ยืนยันตัวตน (OTP) " + idx + " / " + schedule.length);
            $("#lpQText").html('<span class="material-symbols-outlined align-middle" style="font-size:20px;">verified_user</span> จุดยืนยันตัวตนด้วย OTP (โหมดทดสอบ — จำลอง)<div class="small text-white-50 mt-1">ของจริงระบบจะส่ง OTP ให้นักเรียนกรอกก่อนดูต่อ</div>');
            $("#lpChoices").html('');
            $("#lpSubmitBtn").text("ยืนยัน (จำลอง) & ดูต่อ");
            $("#lpOverlay").addClass("show");
            StartTimer();
            return;
        }

        $("#lpQNo").text("คำถามที่ " + idx + " / " + schedule.length);
        $("#lpQText").html(item.question.text);
        $("#lpSubmitBtn").text("ตอบ & ดูต่อ");

        var html = "";
        item.question.choices.forEach(function (c, i) {
            html += '<button type="button" class="lp-choice" data-i="' + (i + 1) + '" onclick="LpPick(this)">' +
                    '<span class="fw-medium me-1">' + (i + 1) + '.</span> ' + EscapeHTML(c) + '</button>';
        });
        $("#lpChoices").html(html);
        $("#lpOverlay").addClass("show");

        StartTimer();
    }

    function LpPick(el) {
        selectedChoice = parseInt($(el).data("i"), 10);
        $("#lpChoices .lp-choice").removeClass("selected");
        $(el).addClass("selected");
    }

    function StartTimer() {
        if (timerInterval) { clearInterval(timerInterval); }
        var t = parseInt($("#lpQuestionTime").val(), 10);
        if (!t || t <= 0) { $("#lpTimer").text("∞"); return; }
        var remain = t;
        $("#lpTimer").text(remain);
        timerInterval = setInterval(function () {
            remain--;
            $("#lpTimer").text(remain);
            if (remain <= 0) {
                clearInterval(timerInterval); timerInterval = null;
                if ($("#lpMustAnswer").is(":checked")) {
                    // บังคับตอบ: หมดเวลาแล้วยังต้องเลือก
                    $("#lpQHint").show().text("หมดเวลา — กรุณาเลือกคำตอบเพื่อดูต่อ");
                } else {
                    // ไม่บังคับ: นับเป็นไม่ตอบ แล้วไปต่อ
                    FinishQuestion(false, true);
                }
            }
        }, 1000);
    }

    function LpSubmitAnswer() {
        // จุด OTP (จำลอง): ยืนยันแล้วดูต่อได้เลย ไม่นับเป็นคำถาม
        if (currentQ && currentQ.type === 'otp') {
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
            $("#lpOverlay").removeClass("show");
            pausedForQuestion = false;
            currentQ = null;
            SaveProgress(true);
            LPlayer.play();
            return;
        }
        if (selectedChoice === null) {
            if ($("#lpMustAnswer").is(":checked")) {
                $("#lpQHint").show().text("กรุณาเลือกคำตอบก่อน");
                return;
            }
            FinishQuestion(false, true);
            return;
        }
        var isCorrect = (selectedChoice === currentQ.question.correct);
        FinishQuestion(isCorrect, false);
    }

    function FinishQuestion(isCorrect, skipped) {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        answered++;
        if (isCorrect) { correctCount++; }
        UpdateResult();
        $("#lpOverlay").removeClass("show");
        pausedForQuestion = false;
        currentQ = null;
        SaveProgress(true); // ตอบคำถามเสร็จ -> บันทึกจุดทันที
        LPlayer.play();
    }

    // ===== บันทึกจุดที่ดูค้างไว้ (เฉพาะโหมดข้อมูลจริง) =====
    function SaveProgress(force, sync) {
        if (!REAL_MODE || !LESSON_ID) {
            $("#lpSaveInfo").html('<span class="text-secondary">โหมด Sandbox — ไม่บันทึกจุดดูค้าง</span>');
            return;
        }
        var sec = Math.floor(maxWatched);
        var status = (duration > 0 && maxWatched >= duration - 1.5) ? "1" : "0";
        // throttle: บันทึกเมื่อคืบหน้า >= 3 วิ หรือสั่ง force
        if (!force && (sec - lastSaveSec) < 3) { return; }
        lastSaveSec = sec;
        $.ajax({
            type: "POST", url: "core.php",
            async: sync ? false : true, // sync = true ตอนปิด/refresh หน้า (ให้บันทึกทันก่อนออก)
            data: {
                request_state: "lesson_progress", request_function: "save_progress",
                lesson_id: LESSON_ID, last_sec: sec, status: status
            },
            dataType: "json",
            success: function (r) {
                if (r.result == 1) {
                    $("#lpSaveInfo").html('<span class="text-success">💾 จำจุดไว้ที่ ' + fmt(sec) + '</span>');
                } else {
                    $("#lpSaveInfo").html('<span class="text-danger">บันทึกไม่ได้: ' + r.msg + '</span>');
                }
            },
            error: function () {
                $("#lpSaveInfo").html('<span class="text-danger">บันทึกไม่ได้ (เชื่อมต่อ server ไม่ได้)</span>');
            }
        });
    }

    // บันทึกจุดล่าสุดตอนจะออก/refresh หน้า (กันหลุดวินาทีท้าย ๆ)
    window.addEventListener("pagehide", function () { SaveProgress(true, true); });

    function UpdateResult() {
        if (answered === 0) { $("#lpResult").text("ยังไม่มีการตอบคำถาม"); return; }
        $("#lpResult").html('ตอบไปแล้ว <b>' + answered + '</b> ข้อ — ถูก <b class="text-success">' + correctCount + '</b> ข้อ');
    }

    function UpdateWatched(t) {
        $("#lpWatched").text(fmt(maxWatched));
        var pct = duration > 0 ? Math.min(100, (t / duration) * 100) : 0;
        $("#lpWatchedBar").css("width", pct + "%");
    }

    // ตรวจการเลื่อน/กระโดดไปข้างหน้าเกินจุดที่ดูแล้ว -> ดีดกลับ (คืน true ถ้าบล็อก)
    // เผื่อ 1.5 วิ ให้การเล่นปกติ (timeupdate ปกติคืบ ~0.25 วิ)
    function GuardForward(t) {
        if (t > maxWatched + 1.5) {
            if (!seeking) { LpToast("ห้ามเลื่อนข้ามไปข้างหน้า — ดูได้ถึงจุดที่เคยดูเท่านั้น"); }
            seeking = true;
            LPlayer.setTime(maxWatched);
            return true;
        }
        return false;
    }

    function LpToast(msg) {
        Swal.fire({ toast: true, position: "top", icon: "warning", title: msg, showConfirmButton: false, timer: 1500, timerProgressBar: true });
    }

    // ===== โหลดข้อมูลจริงจากบทเรียน (ถ้า DB/Vimeo พร้อม) =====
    function LpLoadReal() {
        if (!LESSON_ID) { return; }
        Swal.fire({ title: "กำลังโหลด...", allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });

        // 1) ตั้งค่า + วิดีโอ จาก get_lesson
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "lesson", request_function: "get_lesson", lesson_id: LESSON_ID },
            dataType: "json"
        }).done(function (res) {
            if (res.result != 1) { Swal.close(); LpToast(res.msg || "โหลดบทเรียนไม่ได้"); return; }
            REAL_MODE = true; // เปิดบันทึก/จำจุดดูค้าง
            var L = res.data.lesson;
            if (L.lesson_video) { $("#lpVideoUrl").val(L.lesson_video); }
            $("#lpEnableQuestion").prop("checked", String(L.lesson_question) === "1");
            $("#lpQuestionLimit").val(L.lesson_question_limit || 0);
            $("#lpQuestionTime").val(L.lesson_question_time || 0);

            // 2) รายการคำถาม
            $.ajax({
                type: "POST", url: "core.php",
                data: { request_state: "question", request_function: "get_list_question", lesson_id: LESSON_ID },
                dataType: "json"
            }).done(function (qres) {
                if (qres.result != 1 || !qres.data.list_data.length) {
                    window.LP_QUESTIONS = [];
                    Swal.close();
                    $("#lpModeBadge").text("โหมดข้อมูลจริง (ไม่มีคำถาม)");
                    LpRealRestart();
                    return;
                }
                // 3) ดึงตัวเลือกของแต่ละคำถามผ่าน get_question
                var ids = qres.data.list_data.map(function (q) { return q.question_id; });
                var calls = ids.map(function (id) {
                    return $.ajax({
                        type: "POST", url: "core.php",
                        data: { request_state: "question", request_function: "get_question", question_id: id },
                        dataType: "json"
                    });
                });
                $.when.apply($, calls).then(function () {
                    var results = (ids.length === 1) ? [arguments] : Array.prototype.slice.call(arguments);
                    var built = [];
                    results.forEach(function (r) {
                        var d = r[0];
                        if (d.result != 1) { return; }
                        var qText = (d.data.question.question_text || "").replace(/<[^>]*>/g, "").trim();
                        var choices = d.data.choices.map(function (c) { return c.question_choice_text || ""; });
                        var correct = 0;
                        d.data.choices.forEach(function (c, i) { if (String(c.question_choice_correct) === "1") { correct = i + 1; } });
                        if (choices.length) { built.push({ text: qText, choices: choices, correct: correct }); }
                    });
                    window.LP_QUESTIONS = built;
                    Swal.close();
                    $("#lpModeBadge").text("โหมดข้อมูลจริง — " + built.length + " คำถาม");
                    LpRealRestart();
                });
            }).fail(function () { Swal.close(); LpToast("โหลดคำถามไม่ได้ — เล่นต่อโดยไม่มีคำถาม"); window.LP_QUESTIONS = []; LpRealRestart(); });
        }).fail(function () {
            // DB ต่อไม่ได้ -> ถอยไปโหมด sandbox ให้หน้ายังเล่นได้ (แต่ไม่บันทึก/ไม่เล่นต่อ)
            Swal.close();
            REAL_MODE = false;
            $("#lpModeBadge").text("โหมด Sandbox (เชื่อมต่อฐานข้อมูลไม่ได้)");
            LpToast("เชื่อมต่อฐานข้อมูลไม่ได้ — ใช้โหมด Sandbox แทน");
            LpRestart();
        });
    }

    // โหมดข้อมูลจริง: ดึงจุดที่ดูค้างไว้ก่อน แล้วค่อยเริ่มเล่น (เล่นต่อจากเดิม)
    function LpRealRestart() {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "lesson_progress", request_function: "get_progress", lesson_id: LESSON_ID },
            dataType: "json"
        }).done(function (p) {
            RESUME_SEC = (p && p.result == 1) ? (parseInt(p.data.last_sec, 10) || 0) : 0;
        }).always(function () {
            LpRestart();
        });
    }

    $(document).ready(function () {
        if (LESSON_ID > 0) {
            LpLoadReal();   // มีบทเรียน -> โหลดข้อมูลจริง + เล่นต่อจากจุดที่ดูค้าง (จำได้แม้ refresh)
        } else {
            LpRestart();    // ไม่มีบทเรียน -> โหมด sandbox
        }
    });
</script>
