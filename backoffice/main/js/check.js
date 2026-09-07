/**
 * check.js
 * -----------------------------------------------------------------------------
 * เช็ค badge ข้างเมนู sidebar เป็นระยะ — ถ้ามีข้อมูลใหม่เข้ามา (คำสั่งซื้อรอยืนยัน /
 * คำขอยืนยันตัวตน / ข้อความแชทยังไม่อ่าน) จะอัปเดตตัวเลขบน badge ให้อัตโนมัติ
 * โดยไม่ต้องรีเฟรชหน้า
 *
 * - ค่าเริ่มต้นตอนโหลดหน้ามาจาก sidebar.php (server-render) แล้วไฟล์นี้ poll มาอัปเดตต่อ
 * - ยิงเงียบ ๆ: global:false (ไม่โชว์ spinner กลางจอ) + แนบ Bearer token เอง
 *   (เพราะ global:false ทำให้ ajaxSend ใน core.js ไม่ทำงาน จึงต้องแนบ token เอง)
 */
(function () {
    "use strict";

    var POLL_MS = 30000; // เช็คทุก 30 วินาที

    // key ที่ backend คืน -> id ของ badge span ใน sidebar.php
    var BADGE_MAP = {
        order_pending:  "sidebarOrderBadge",   // คำสั่งซื้อรอยืนยัน
        verify_pending: "sidebarVerifyBadge",  // ยืนยันตัวตนผู้ใช้งาน
        chat_unread:    "sidebarChatBadge"     // ตอบคำถามผู้เรียน
    };

    function setBadge(id, n) {
        var el = document.getElementById(id);
        if (!el) { return; }
        if (n > 0) {
            el.textContent = n > 99 ? "99+" : n;
            el.style.display = "";
        } else {
            el.style.display = "none";
        }
    }

    function checkBadges() {
        if (!window.jQuery) { return; }
        $.ajax({
            global: false, // ไม่ trigger spinner กลางจอ (poll ถี่)
            type: "POST",
            url: "core.php",
            headers: { "Authorization": "Bearer " + (localStorage.getItem("bo_access_token") || "") },
            data: { request_state: "sidebar", request_function: "get_badges" },
            dataType: "json",
            success: function (r) {
                if (!r || r.result != 1 || !r.data) { return; }
                Object.keys(BADGE_MAP).forEach(function (key) {
                    setBadge(BADGE_MAP[key], parseInt(r.data[key], 10) || 0);
                });
            }
            // error: เงียบไว้ ไม่รบกวนผู้ใช้ (เช่น token หมดอายุ core.js จัดการเอง)
        });
    }

    if (window.jQuery) {
        $(function () {
            checkBadges();
            setInterval(checkBadges, POLL_MS);
        });
    }
})();
