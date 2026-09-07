<?php
    // หน้าพรีวิวเอกสารประกอบการสอน (ฝังในเบราว์เซอร์) — ให้เปิดดูเหมือนใบรับรองผล
    // โหลดไฟล์เป็น base64 ผ่าน JSON (core.php) แล้วประกอบเป็น blob ฝั่ง client ฝังใน <iframe>
    // (ไม่มี request ที่หน้าตาเป็นไฟล์ -> กัน download manager จับไฟล์ เหมือน pdf_preview.php)
    // ไฟล์ที่เบราว์เซอร์เปิดไม่ได้ (doc/xls/zip ฯลฯ) จะ fallback เป็นปุ่มดาวน์โหลด
    // query: ?id=<lesson_file_id>
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    $id = \App\Utility\Cipher::decrypt($key);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เอกสารประกอบการสอน</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; font-family: "K2D", Tahoma, sans-serif; background: #525659; }
        #pvFrame { width: 100%; height: 100%; border: 0; display: block; }
        .pv-msg { color: #fff; text-align: center; padding-top: 80px; font-size: 16px; }
        .pv-spin { width: 34px; height: 34px; border: 4px solid #888; border-top-color: #8b89ff; border-radius: 50%; margin: 0 auto 16px; animation: pvspin 0.8s linear infinite; }
        @keyframes pvspin { to { transform: rotate(360deg); } }
        .pv-dl { display: inline-block; margin-top: 18px; padding: 10px 22px; background: #8b89ff; color: #fff; border-radius: 8px; text-decoration: none; font-size: 15px; }
    </style>
</head>
<body>
    <div class="pv-msg" id="pvMsg"><div class="pv-spin"></div>กำลังเปิดเอกสาร...</div>
    <iframe id="pvFrame" style="display:none;"></iframe>

    <script>
        var DOC_ID = <?php echo $id; ?>;

        function showError(msg) {
            document.getElementById("pvMsg").innerHTML = '<div style="color:#ff8a80;">' + (msg || "เกิดข้อผิดพลาด") + '</div>';
        }

        function showDownload(blobUrl, filename) {
            document.getElementById("pvMsg").innerHTML =
                'ไฟล์นี้ไม่สามารถแสดงในเบราว์เซอร์ได้' +
                '<br><a class="pv-dl" href="' + blobUrl + '" download="' + (filename || "document") + '">ดาวน์โหลดเอกสาร</a>';
        }

        function b64ToBlob(b64, mime) {
            var bin = atob(b64), len = bin.length, bytes = new Uint8Array(len);
            for (var i = 0; i < len; i++) { bytes[i] = bin.charCodeAt(i); }
            return new Blob([bytes], { type: mime || "application/octet-stream" });
        }

        (function () {
            if (!DOC_ID) { showError("ไม่พบเอกสารที่ต้องการ"); return; }
            var token = localStorage.getItem("bo_access_token") || "";
            if (!token) { showError("กรุณาเข้าสู่ระบบใหม่"); return; }

            var body = new URLSearchParams();
            body.append("request_state", "lesson_file");
            body.append("request_function", "export_lesson_file");
            body.append("lesson_file_id", DOC_ID);
            body.append("access_token", token);

            fetch("core.php", { method: "POST", headers: { "Authorization": "Bearer " + token }, body: body })
                .then(function (res) { return res.json(); })
                .then(function (j) {
                    if (!j || j.result != 1 || !j.data || !j.data.file) {
                        throw new Error((j && j.msg) ? j.msg : "เปิดเอกสารไม่สำเร็จ");
                    }
                    var mime = (j.data.mime || "").toLowerCase();
                    var blobUrl = URL.createObjectURL(b64ToBlob(j.data.file, mime));
                    // pdf / รูป / ข้อความ = ฝังในกรอบเหมือนใบรับรอง; ชนิดอื่น = ปุ่มดาวน์โหลด
                    var canEmbed = mime.indexOf("pdf") !== -1
                        || mime.indexOf("image/") !== -1
                        || mime.indexOf("text/") !== -1;
                    if (canEmbed) {
                        var fr = document.getElementById("pvFrame");
                        fr.src = blobUrl;
                        fr.style.display = "block";
                        document.getElementById("pvMsg").style.display = "none";
                    } else {
                        showDownload(blobUrl, j.data.filename);
                    }
                })
                .catch(function (err) { showError(err && err.message ? err.message : "เปิดเอกสารไม่สำเร็จ"); });
        })();
    </script>
</body>
</html>
