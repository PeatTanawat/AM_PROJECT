<?php $breadcrumbs = [['label' => 'ตอบคำถามจากผู้เรียน']]; ?>
<?php include "header.php"; ?>

<style>
    .chat-wrap { display: flex; gap: 0; height: calc(100vh - 210px); min-height: 460px; }
    /* ===== ซ้าย: รายการห้องแชท ===== */
    .chat-rooms { width: 340px; flex-shrink: 0; border-right: 1px solid var(--border); display: flex; flex-direction: column; }
    .chat-rooms-head { padding: 16px; border-bottom: 1px solid var(--border); }
    .chat-rooms-list { flex: 1 1 auto; overflow-y: auto; }
    .chat-room-item { display: flex; gap: 10px; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid var(--border); transition: background .12s; }
    .chat-room-item:hover { background: var(--brand-soft, #f3f2ff); }
    .chat-room-item.active { background: var(--brand-soft, #f3f2ff); border-left: 3px solid var(--brand-500); }
    .chat-avatar { width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0; background: var(--brand-500); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
    .chat-room-body { flex: 1 1 auto; min-width: 0; }
    .chat-room-name { font-weight: 600; display: flex; align-items: center; justify-content: space-between; gap: 6px; }
    .chat-room-last { font-size: 13px; color: var(--text-secondary, #6c757d); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-room-time { font-size: 11px; color: var(--text-muted, #98a2b3); flex-shrink: 0; }
    /* ===== ขวา: บทสนทนา ===== */
    .chat-thread { flex: 1 1 auto; display: flex; flex-direction: column; min-width: 0; }
    .chat-thread-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
    .chat-messages { flex: 1 1 auto; overflow-y: auto; padding: 20px; background: #f7f8fb; display: flex; flex-direction: column; gap: 10px; }
    .chat-bubble { max-width: 72%; padding: 9px 14px; border-radius: 14px; font-size: 14px; line-height: 1.5; word-wrap: break-word; white-space: pre-wrap; }
    .chat-row { display: flex; flex-direction: column; }
    .chat-row.mine { align-items: flex-end; }
    .chat-row.theirs { align-items: flex-start; }
    .chat-row.mine .chat-bubble { background: var(--brand-500); color: #fff; border-bottom-right-radius: 4px; }
    .chat-row.theirs .chat-bubble { background: #fff; color: var(--text-primary, #1a1a1a); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
    .chat-time { font-size: 11px; color: var(--text-muted, #98a2b3); margin-top: 3px; }
    .chat-day-divider { align-self: stretch; display: flex; align-items: center; gap: 12px; margin: 4px 0; }
    .chat-day-divider::before, .chat-day-divider::after { content: ""; flex: 1 1 auto; height: 1px; background: var(--border, #e5e7eb); }
    .chat-day-divider span { flex: 0 0 auto; font-size: 12px; color: var(--text-secondary, #6c757d); background: #eef0f3; padding: 2px 12px; border-radius: 999px; }
    .chat-input { padding: 14px 18px; border-top: 1px solid var(--border); display: flex; gap: 10px; align-items: flex-end; }
    .chat-input textarea { resize: none; max-height: 120px; }
    .chat-empty { flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted, #98a2b3); gap: 8px; }
    @media (max-width: 991px) {
        .chat-wrap { flex-direction: column; height: auto; }
        .chat-rooms { width: 100%; border-right: 0; border-bottom: 1px solid var(--border); max-height: 320px; }
        .chat-messages { min-height: 320px; }
    }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">
            <div class="card app-card bg-white border-0 rounded-3 mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 p-4">
                    <h2 class="mb-0">ตอบคำถามจากผู้เรียน</h2>
                </div>

                <div class="card-body p-0">
                    <div class="chat-wrap">

                        <!-- ===== ซ้าย: รายการห้องแชท ===== -->
                        <div class="chat-rooms">
                            <div class="chat-rooms-head">
                                <input type="text" class="form-control" id="roomSearch" placeholder="ค้นหาชื่อผู้เรียน" oninput="RenderRooms()">
                            </div>
                            <div class="chat-rooms-list" id="roomsList">
                                <div class="text-center text-muted py-4">กำลังโหลด...</div>
                            </div>
                        </div>

                        <!-- ===== ขวา: บทสนทนา ===== -->
                        <div class="chat-thread">
                            <div id="threadEmpty" class="chat-empty">
                                <span class="material-symbols-outlined" style="font-size:52px;">forum</span>
                                <div>เลือกบทสนทนาทางซ้ายเพื่อเริ่มตอบคำถาม</div>
                            </div>

                            <div id="threadBox" class="d-none" style="display:flex; flex-direction:column; flex:1 1 auto; min-height:0;">
                                <div class="chat-thread-head">
                                    <div class="chat-avatar" id="threadAvatar">?</div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold" id="threadName">-</div>
                                        <div class="small text-secondary text-truncate" id="threadEmail"></div>
                                    </div>
                                </div>
                                <div class="chat-messages" id="messagesBox"></div>
                                <div class="chat-input">
                                    <textarea class="form-control" id="replyText" rows="1" placeholder="พิมพ์ข้อความตอบกลับ" onkeydown="OnReplyKey(event)" oninput="AutoGrow(this)"></textarea>
                                    <button type="button" class="btn btn-primary BtnSendReply" onclick="SendReply()" style="min-width:90px;">
                                        <span class="material-symbols-outlined align-middle" style="font-size:18px;">send</span> ส่ง
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    window.CPDTH_SUPPRESS_SPINNER = true;   // หน้าแชท poll ถี่ -> ไม่โชว์ spinner กลางจอ (loading.js เช็ค flag นี้)

    var ACTIVE_ROOM = null;      // room_id ที่เปิดอยู่
    var ACTIVE_LEARNER = null;   // user_id ผู้เรียนของห้องที่เปิด
    var roomsCache = [];
    var lastRoomsSig = "";       // ลายเซ็นข้อมูลห้องล่าสุด -> ข้าม re-render ถ้าไม่เปลี่ยน (กันกระพริบ/ไม่หนัก)
    var lastMsgId = 0;           // messages_id ล่าสุดของห้องที่เปิด -> poll ดึงเฉพาะข้อความใหม่
    var lastMsgDate = null;      // วันของข้อความล่าสุดที่ render -> ขึ้นเส้นกั้นเมื่อเปลี่ยนวัน
    var pollMsgTimer = null;
    var pollRoomsTimer = null;

    $(document).ready(function () {
        LoadRooms(false);
        // รีเฟรชรายการห้องเป็นระยะ (เงียบ ๆ ไม่มี loading, re-render เฉพาะเมื่อข้อมูลเปลี่ยน)
        pollRoomsTimer = setInterval(function () { LoadRooms(true); }, 15000);
    });

    // ===== รายการห้องแชท =====
    // โหลดห้องทั้งหมด (ไม่ส่งคำค้นไป server — ค้นหาทำในเครื่อง) + ข้าม render ถ้าข้อมูลไม่เปลี่ยน
    function LoadRooms(silent) {
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "chat", request_function: "get_rooms" },
            dataType: "json",
            success: function (res) {
                if (res.result != 1) { if (!silent) { ToastResult(res); } return; }
                var list = res.data.list || [];
                var sig = JSON.stringify(list);
                if (sig === lastRoomsSig) { return; }   // ไม่เปลี่ยน -> ไม่ต้อง re-render
                lastRoomsSig = sig;
                roomsCache = list;
                RenderRooms();
                UpdateSidebarBadge(list);
            },
            error: function (jqXHR, exception) { if (!silent) { ShowErrorAjax(jqXHR, exception); } }
        });
    }

    // render รายการห้องจาก roomsCache + กรองด้วยคำค้นในเครื่อง (ค้นหาทันทีขณะพิมพ์ ไม่ยิง server)
    function RenderRooms() {
        var term = ($("#roomSearch").val() || "").trim().toLowerCase();
        var list = term ? roomsCache.filter(function (r) {
            return (r.learner || "").toLowerCase().indexOf(term) !== -1
                || (r.user_email || "").toLowerCase().indexOf(term) !== -1
                || (r.last_message || "").toLowerCase().indexOf(term) !== -1;
        }) : roomsCache;
        if (!list.length) {
            $("#roomsList").html('<div class="text-center text-muted py-4">' + (term ? 'ไม่พบผลการค้นหา' : 'ยังไม่มีบทสนทนา') + '</div>');
            return;
        }
        var html = "";
        list.forEach(function (r) {
            var initial = (r.learner || "?").trim().charAt(0).toUpperCase();
            var prefix = r.last_by_admin ? '<span class="text-secondary">คุณ: </span>' : '';
            var badge = r.unread > 0 ? '<span class="badge rounded-pill bg-danger">' + (r.unread > 99 ? '99+' : r.unread) + '</span>' : '';
            var activeCls = (ACTIVE_ROOM === r.room_id) ? ' active' : '';
            html += '<div class="chat-room-item' + activeCls + '" data-room="' + r.room_id + '" onclick="OpenRoom(' + r.room_id + ')">' +
                        '<div class="chat-avatar">' + EscapeHTML(initial) + '</div>' +
                        '<div class="chat-room-body">' +
                            '<div class="chat-room-name"><span class="text-truncate">' + EscapeHTML(r.learner) + '</span>' +
                                '<span class="chat-room-time">' + FmtTime(r.last_time) + '</span></div>' +
                            '<div class="d-flex align-items-center justify-content-between gap-2">' +
                                '<span class="chat-room-last">' + prefix + EscapeHTML(r.last_message || '') + '</span>' + badge +
                            '</div>' +
                        '</div>' +
                    '</div>';
        });
        $("#roomsList").html(html);
    }

    // อัปเดต badge จำนวนข้อความที่ยังไม่อ่านบน sidebar (รวมทุกห้อง) แบบสด
    function UpdateSidebarBadge(list) {
        var total = 0;
        list.forEach(function (r) { total += (r.unread || 0); });
        var $b = $("#sidebarChatBadge");
        if (!$b.length) { return; }
        if (total > 0) { $b.text(total > 99 ? '99+' : total).show(); } else { $b.hide(); }
    }

    // ===== เปิดบทสนทนา =====
    function OpenRoom(roomId) {
        ACTIVE_ROOM = roomId;
        $(".chat-room-item").removeClass("active");
        $('.chat-room-item[data-room="' + roomId + '"]').addClass("active");
        $("#threadEmpty").addClass("d-none");
        $("#threadBox").removeClass("d-none");
        $("#messagesBox").empty();
        lastMsgId = 0;
        lastMsgDate = null;
        LoadMessages(true);   // true = โหลดเต็ม
        if (pollMsgTimer) { clearInterval(pollMsgTimer); }
        pollMsgTimer = setInterval(function () { LoadMessages(false); }, 8000);  // false = poll เฉพาะข้อความใหม่
    }

    // isFull=true โหลดเต็ม (ตอนเปิดห้อง), false = poll ดึงเฉพาะข้อความใหม่กว่า lastMsgId
    function LoadMessages(isFull) {
        if (!ACTIVE_ROOM) { return; }
        var roomAtRequest = ACTIVE_ROOM;
        $.ajax({
            type: "POST", url: "core.php",
            data: { request_state: "chat", request_function: "get_messages", room_id: roomAtRequest, after_id: isFull ? 0 : lastMsgId },
            dataType: "json",
            success: function (res) {
                if (roomAtRequest !== ACTIVE_ROOM) { return; }  // เปลี่ยนห้องระหว่างรอ -> ทิ้ง
                if (res.result != 1) { if (isFull) { ToastResult(res); } return; }
                if (isFull && res.data.learner) {
                    ACTIVE_LEARNER = res.data.learner.user_id;
                    $("#threadName").text(res.data.learner.name);
                    $("#threadEmail").text(res.data.learner.user_email || "");
                    $("#threadAvatar").text((res.data.learner.name || "?").trim().charAt(0).toUpperCase());
                }
                var msgs = res.data.messages || [];
                if (isFull) {
                    if (msgs.length) { AppendMessages(msgs, true); }
                    else { $("#messagesBox").html('<div class="text-center text-muted my-auto chat-empty-note">ยังไม่มีข้อความ</div>'); }
                    LoadRooms(true);        // ห้องนี้ถูก mark-read -> refresh badge/list
                } else if (msgs.length) {
                    AppendMessages(msgs, false);
                    LoadRooms(true);        // มีข้อความใหม่ -> refresh badge/list
                }
            },
            error: function (jqXHR, exception) { if (isFull) { ShowErrorAjax(jqXHR, exception); } }
        });
    }

    // ต่อท้ายเฉพาะข้อความใหม่ (ไม่ล้างทั้งกล่อง -> ไม่กระพริบ ไม่เด้ง scroll)
    function AppendMessages(messages, isFull) {
        var box = $("#messagesBox");
        box.find(".chat-empty-note").remove();
        var atBottom = (box[0].scrollHeight - box[0].scrollTop - box[0].clientHeight) < 60;
        var html = "";
        messages.forEach(function (m) {
            if (m.messages_id > lastMsgId) { lastMsgId = m.messages_id; }
            var dayKey = DayKey(m.created_at);
            if (dayKey && dayKey !== lastMsgDate) {
                html += '<div class="chat-day-divider"><span>' + EscapeHTML(FmtDayLabel(m.created_at)) + '</span></div>';
                lastMsgDate = dayKey;
            }
            var side = m.is_admin ? "mine" : "theirs";
            html += '<div class="chat-row ' + side + '">' +
                        '<div class="chat-bubble">' + EscapeHTML(m.message) + '</div>' +
                        '<div class="chat-time">' + FmtClock(m.created_at) + '</div>' +
                    '</div>';
        });
        box.append(html);
        if (isFull || atBottom) { box.scrollTop(box[0].scrollHeight); }  // เลื่อนลงเฉพาะตอนเปิดใหม่/อยู่ล่างสุด
    }

    // ===== ส่งข้อความตอบกลับ =====
    function OnReplyKey(e) {
        if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); SendReply(); }
    }
    function AutoGrow(el) {
        el.style.height = "auto";
        el.style.height = Math.min(el.scrollHeight, 120) + "px";
    }
    function SendReply() {
        var text = $("#replyText").val().trim();
        if (!ACTIVE_ROOM || text === "") { return; }
        $.ajax({
            beforeSend: function () { ShowLoadingButton('.BtnSendReply'); },
            type: "POST", url: "core.php",
            data: { request_state: "chat", request_function: "send_message", room_id: ACTIVE_ROOM, message: text },
            dataType: "json",
            success: function (res) {
                if (res.result != 1) { ToastResult(res); return; }
                $("#replyText").val("").css("height", "auto");
                LoadMessages(false);   // ดึงข้อความที่เพิ่งส่ง (incremental) มาต่อท้าย
            },
            complete: function () { HideLoadingButton('.BtnSendReply'); },
            error: function (jqXHR, exception) { ShowErrorAjax(jqXHR, exception); }
        });
    }

    // ===== helper =====
    function FmtTime(ts) {
        if (!ts) { return ""; }
        var d = new Date(String(ts).replace(" ", "T"));
        if (isNaN(d.getTime())) { return ts; }
        var now = new Date();
        var hhmm = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
        if (d.toDateString() === now.toDateString()) { return hhmm; }
        return d.getDate() + "/" + (d.getMonth() + 1) + " " + hhmm;
    }
    // เวลาบนบับเบิลข้อความ: แสดงแค่ HH:MM เสมอ (บอกวันด้วยเส้นกั้นแทน)
    function ParseTs(ts) {
        if (!ts) { return null; }
        var d = new Date(String(ts).replace(" ", "T"));
        return isNaN(d.getTime()) ? null : d;
    }
    function FmtClock(ts) {
        var d = ParseTs(ts);
        if (!d) { return ts || ""; }
        return ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
    }
    // คีย์ของวัน (ตามเวลาเครื่องผู้ใช้) ใช้เทียบว่าเปลี่ยนวันหรือยัง
    function DayKey(ts) {
        var d = ParseTs(ts);
        if (!d) { return ""; }
        return d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
    }
    // ป้ายบนเส้นกั้นวัน: วันนี้ / เมื่อวาน / '6 ก.ค. 2568' (พ.ศ.)
    function FmtDayLabel(ts) {
        var d = ParseTs(ts);
        if (!d) { return ""; }
        var today = new Date();
        var yest = new Date(); yest.setDate(yest.getDate() - 1);
        if (d.toDateString() === today.toDateString()) { return "วันนี้"; }
        if (d.toDateString() === yest.toDateString()) { return "เมื่อวาน"; }
        var TH = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        return d.getDate() + " " + TH[d.getMonth()] + " " + (d.getFullYear() + 543);
    }

    function ToastResult(response) {
        Swal.fire({
            title: response.result == 1 ? "สำเร็จ" : "แจ้งเตือน",
            html: '<span class="fw-bold ' + (response.result == 1 ? 'text-success' : 'text-danger') + '">' + response.msg + '</span>',
            icon: response.result == 1 ? "success" : "error",
            showConfirmButton: false, timer: 1800, timerProgressBar: true
        });
    }
</script>
