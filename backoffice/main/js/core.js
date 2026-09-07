// แนบ access token จาก localStorage ไปกับทุก request ของ jQuery
// ใช้ ajaxSend เพราะจะทำงานเสมอ แม้ request นั้นจะกำหนด beforeSend ของตัวเองไว้
$(document).ajaxSend(function (event, jqXHR, settings) {
    const token = localStorage.getItem("bo_access_token");
    if (token) {
        jqXHR.setRequestHeader("Authorization", "Bearer " + token);
        if (!document.cookie.includes("bo_access_token=")) {
            document.cookie = "bo_access_token=" + token + "; path=/; max-age=86400";
        }
    }
});

const PROFILE_CACHE_KEY = "cpdth_profile";

document.addEventListener("DOMContentLoaded", function () {
    try {
        var cachedProfile = JSON.parse(localStorage.getItem(PROFILE_CACHE_KEY) || "null");
        if (cachedProfile) { ApplyProfile(cachedProfile, false); }
    } catch (e) { }

    $.post("core.php", { "request_state": "list_user", "request_function": "user_profile" }, function (response) {
        if (response.result == 1) {
            try { localStorage.setItem(PROFILE_CACHE_KEY, JSON.stringify(response.data)); } catch (e) { }
            ApplyProfile(response.data, true);
            setInterval(KeepSessionAlive, 30000);
        } else {
            try { localStorage.removeItem(PROFILE_CACHE_KEY); } catch (e) { }
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true,
                didClose: () => { window.location.replace("logout.php"); }
            });
        }
    }, "json");
});

function ApplyProfile(data, doGuard) {
    if (!data) { return; }
    $(".ShowUserFullname").text(data.full_name || "");
    $(".ShowUserRole").text(data.role_name || "");
    $(".ShowUserAvatar").attr("src", data.avatar || "../template/assets/images/administrator.jpg");
    FilterSidebarByAccess(data.access_menus, data.is_super_admin);
    if (doGuard) { GuardPageByAccess(data.access_menus, data.menu_map); }
}

(function () {
    var KEY = "cpdth_sidebar_scroll";
    function scroller() {
        var el = document.getElementById("layout-menu");
        if (!el) { return null; }
        return el.querySelector(".simplebar-content-wrapper") || el;
    }
    window.addEventListener("beforeunload", function () {
        var s = scroller();
        if (s) { try { sessionStorage.setItem(KEY, String(s.scrollTop)); } catch (e) { } }
    });
    function restore() {
        var s = scroller();
        if (!s) { return; }
        var v = null;
        try { v = sessionStorage.getItem(KEY); } catch (e) { }
        if (v !== null) {
            s.scrollTop = parseInt(v, 10) || 0;
        } else {
            var active = document.querySelector("#layout-menu .menu-link.active");
            if (active) { s.scrollTop = Math.max(0, active.offsetTop - s.clientHeight / 2); }
        }
    }
    function schedule() {
        if (window.requestAnimationFrame) { requestAnimationFrame(restore); } else { setTimeout(restore, 0); }
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", schedule);
    } else {
        schedule();
    }
    window.addEventListener("load", schedule);
})();

function FilterSidebarByAccess(allowed, isSuperAdmin) {
    if (!allowed || !allowed.length) { return; }
    $(".sidebar-area .menu-item").each(function () {
        var title = $(this).find(".title").first().text().trim();
        if (!title) { return; }
        if (title === "หน้าแรก") {
            if (isSuperAdmin === 1) { $(this).show(); }
            else { $(this).hide(); }
        } else if (allowed.indexOf(title) === -1) { $(this).hide(); }
        else { $(this).show(); }
    });
    $(".sidebar-area .menu-title").each(function () {
        var categoryText = $(this).find(".menu-title-text").text().trim();
        if (categoryText === "หน้าหลัก") {
            if (isSuperAdmin === 1) { $(this).show(); }
            else { $(this).hide(); }
            return;
        }
        if ($(this).nextUntil(".menu-title", ".menu-item:visible").length === 0) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
}

function GuardPageByAccess(allowed, menuMap) {
    if (!allowed || !allowed.length || !menuMap || !menuMap.length) { return; }
    var pageMenu = {};
    menuMap.forEach(function (m) {
        (m.url_path || "").split(",").forEach(function (p) {
            p = p.trim().replace(/\.php$/i, "");
            if (p) { pageMenu[p] = m.menu_name; }
        });
    });

    var page = (location.pathname.split("/").pop() || "").replace(/\.php$/i, "");
    var required = pageMenu[page];
    if (!required) { return; }
    if (allowed.indexOf(required) === -1) {
        var fallbackPage = "home";
        for (var i = 0; i < menuMap.length; i++) {
            var m = menuMap[i];
            if (allowed.indexOf(m.menu_name) !== -1 && m.url_path) {
                var firstP = m.url_path.split(",")[0].trim().replace(/\.php$/i, "");
                if (firstP) { fallbackPage = firstP; break; }
            }
        }
        if (page !== fallbackPage) {
            Swal.fire({
                title: "ไม่มีสิทธิ์เข้าถึง",
                html: '<span class="text-secondary">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</span>',
                icon: "warning", confirmButtonText: "ไปยังเมนูที่มีสิทธิ์", allowOutsideClick: false
            }).then(function () { window.location.replace(fallbackPage); });
        }
    }
}

async function KeepSessionAlive() {
    try {
        const response = await fetch("core/keepSession.php", {
            method: "POST",
            headers: {
                "Cache-Control": "no-cache",
                "Authorization": "Bearer " + (localStorage.getItem("bo_access_token") || "")
            }
        });
        if (response.ok) {
            const data = await response.json();
            if (data.result === 0) showSessionExpiredMsg("Access Token Expired");
        } else {
            showSessionExpiredMsg(response.status);
        }
    } catch (error) {
        showSessionExpiredMsg(error);
    }
}

function showSessionExpiredMsg(msg) {
    Swal.fire({
        title: "แจ้งเตือน",
        html: '<span class="fw-bold text-danger">' + msg + "</span>",
        icon: "error", showConfirmButton: false, allowOutsideClick: false, timer: 2000, timerProgressBar: true,
        didClose: () => { window.location.replace("logout.php"); }
    });
}

// ============================================================
// end of core.js — บรรทัดสำรองด้านล่างกันไฟล์ถูกตัดท้ายตอน deploy
// (ถ้าปลายไฟล์หายไปบ้าง โค้ดจริงยังปิดวงเล็บครบ) — ห้ามลบ
// ============================================================
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
