// แนบ access token จาก localStorage ไปกับทุก request ของ jQuery
// ใช้ ajaxSend เพราะจะทำงานเสมอ แม้ request นั้นจะกำหนด beforeSend ของตัวเองไว้
$(document).ajaxSend(function (event, jqXHR, settings) {
    const token = localStorage.getItem("bo_access_token");
    if (token) {
        jqXHR.setRequestHeader("Authorization", "Bearer " + token);
    }
});

const PROFILE_CACHE_KEY = "cpdth_profile";

document.addEventListener("DOMContentLoaded", function () {

    // 1) ใช้โปรไฟล์/สิทธิ์จาก cache ทันที (ถ้ามี) -> sidebar ไม่รอ ajax, ไม่กระพริบ/ไม่เด้งขึ้นบน
    //    ไม่ guard จาก cache (กัน false-deny ถ้าสิทธิ์ใน cache เก่า) — guard ทำตอนได้ข้อมูลจริงเท่านั้น
    try {
        var cachedProfile = JSON.parse(localStorage.getItem(PROFILE_CACHE_KEY) || "null");
        if (cachedProfile) { ApplyProfile(cachedProfile, false); }
    } catch (e) { }

    // 2) ดึงโปรไฟล์ล่าสุดมา refresh cache + ตรวจสิทธิ์หน้าด้วยข้อมูลจริง (ทำเบื้องหลัง ไม่บล็อก UI)
    $.post("core.php", { "request_state": "list_user", "request_function": "user_profile", }, function (response) {
        if (response.result == 1) {
            try { localStorage.setItem(PROFILE_CACHE_KEY, JSON.stringify(response.data)); } catch (e) { }
            ApplyProfile(response.data, true);
            setInterval(KeepSessionAlive, 30000);
        } else {
            try { localStorage.removeItem(PROFILE_CACHE_KEY); } catch (e) { }
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                icon: "error",
                showConfirmButton: false,
                allowOutsideClick: false,
                timer: 2000,
                timerProgressBar: true,
                didClose: () => {
                    window.location.replace("logout.php");
                }
            });
        }
    }, "json");
});

// เขียนชื่อ/บทบาท/รูป + กรองเมนูตามสิทธิ์
// doGuard = true เฉพาะเมื่อเป็นข้อมูลจริงจาก server (กัน false-deny จาก cache เก่า)
function ApplyProfile(data, doGuard) {
    if (!data) { return; }
    $(".ShowUserFullname").html(data.full_name || "");
    $(".ShowUserRole").html(data.role_name || "");
    $(".ShowUserAvatar").attr("src", data.avatar || "../template/assets/images/administrator.jpg");
    FilterSidebarByAccess(data.access_menus, data.is_super_admin);
    if (doGuard) { GuardPageByAccess(data.access_menus, data.menu_map); }
}

// จำตำแหน่งเลื่อนของ sidebar ข้ามการเปลี่ยนหน้า -> ไม่เด้งขึ้นบนสุดเวลาเลือกเมนูล่าง ๆ
(function () {
    var KEY = "cpdth_sidebar_scroll";
    function scroller() {
        var el = document.getElementById("layout-menu");
        if (!el) { return null; }
        // SimpleBar ย้าย scroll ไปที่ wrapper ด้านใน (ถ้ายังไม่ init ใช้ตัว element เอง)
        return el.querySelector(".simplebar-content-wrapper") || el;
    }
    // เก็บตำแหน่งก่อนออกจากหน้า (ครอบคลุมทุกการนำทาง)
    window.addEventListener("beforeunload", function () {
        var s = scroller();
        if (s) { try { sessionStorage.setItem(KEY, String(s.scrollTop)); } catch (e) { } }
    });
    // คืนตำแหน่งหลังโหลด; ถ้าไม่มีค่าเก็บไว้ เลื่อนให้เมนูที่เลือกอยู่กลางมุมมอง
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
    // เผื่อ SimpleBar init/รีเซ็ตหลัง DOMContentLoaded -> คืนตำแหน่งอีกครั้งตอน load เสร็จ
    window.addEventListener("load", schedule);
})();

// ซ่อนเมนู sidebar ที่ผู้ใช้ไม่มีสิทธิ์ (จับคู่ด้วยข้อความ .title = menu_name)
// fail-open: ถ้าไม่มีข้อมูลสิทธิ์ (ว่าง) จะไม่กรอง (โชว์ครบ กันล็อกผู้ใช้)
function FilterSidebarByAccess(allowed, isSuperAdmin) {
    if (!allowed || !allowed.length) { return; }
    $(".sidebar-area .menu-item").each(function () {
        var title = $(this).find(".title").first().text().trim();
        if (title === "หน้าแรก") {
            if (isSuperAdmin === 1) { $(this).show(); }
            else { $(this).hide(); }
        } else if (title && allowed.indexOf(title) === -1) {
            $(this).hide();
        }
    });
    // ซ่อนหัวข้อหมวดที่ไม่เหลือเมนูที่มองเห็น
    $(".sidebar-area .menu-title").each(function () {
        var categoryText = $(this).find(".menu-title-text").text().trim();
        if (categoryText === "หน้าหลัก") {
            if (isSuperAdmin === 1) {
                $(this).show();
            } else {
                $(this).hide();
            }
            return;
        }
        if ($(this).nextUntil(".menu-title", ".menu-item:visible").length === 0) {
            $(this).hide();
        }
    });
}

// บล็อกระดับหน้า: ถ้าผู้ใช้ไม่มีสิทธิ์เมนูของหน้านี้ -> เด้งกลับหน้าแรก
// page->menu สร้างจาก tbl_slidebar.url_path (คั่นด้วย ,) ที่ส่งมาจาก server -> ไม่ต้อง hardcode
// (หน้าแรก/หน้าที่ไม่อยู่ในแมป = เข้าได้เสมอ กัน loop; fail-open ถ้าไม่มีข้อมูลสิทธิ์)
function GuardPageByAccess(allowed, menuMap) {
    if (!allowed || !allowed.length || !menuMap || !menuMap.length) { return; } // fail-open

    // สร้าง page -> menu_name จาก url_path ใน DB
    var pageMenu = {};
    menuMap.forEach(function (m) {
        (m.url_path || "").split(",").forEach(function (p) {
            p = p.trim();
            if (p) { pageMenu[p] = m.menu_name; }
        });
    });

    var page = (location.pathname.split("/").pop() || "").replace(".php", "");
    var required = pageMenu[page];
    if (!required) { return; } // ไม่อยู่ในแมป -> เข้าได้
    if (allowed.indexOf(required) === -1) {
        Swal.fire({
            title: "ไม่มีสิทธิ์เข้าถึง",
            html: '<span class="text-secondary">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</span>',
            icon: "warning", confirmButtonText: "กลับหน้าแรก", allowOutsideClick: false
        }).then(function () { window.location.replace("home"); });
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
            if (data.result === 0) {
                Swal.fire({
                    title: "แจ้งเตือน",
                    html: '<span class="fw-bold text-danger">Access Token Expired</span>',
                    icon: "error",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didClose: () => {
                        window.location.replace("logout.php");
                    },
                });
            }
        } else {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">' + response.status + "</span>",
                icon: "error",
                showConfirmButton: false,
                allowOutsideClick: false,
                timer: 2000,
                timerProgressBar: true,
                didClose: () => {
                    window.location.replace("logout.php");
                },
            });
        }
    } catch (error) {
        Swal.fire({
            title: "แจ้งเตือน",
            html: '<span class="fw-bold text-danger">' + error + "</span>",
            icon: "error",
            showConfirmButton: false,
            allowOutsideClick: false,
            timer: 2000,
            timerProgressBar: true,
            didClose: () => {
                window.location.replace("logout.php");
            },
        });
    }
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
