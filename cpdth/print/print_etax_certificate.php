<?php
// หน้าพรีวิวเอกสาร PDF (ฝังในเบราว์เซอร์) — กัน download manager จับไฟล์
// โหลด PDF เป็น base64 ผ่าน JSON แล้วแปลงเป็น blob ฝังใน <iframe> เต็มหน้า
// (ปุ่มดาวน์โหลด/พิมพ์ ใช้ของ PDF viewer ในเบราว์เซอร์เอง)
// query: ?type=certificate|etax & id=<id>
$type = isset($_GET['type']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['type'])) : '';
$key  = isset($_GET['key']) ? trim($_GET['key']) : '';
$id   = 0;

if (!empty($key)) {
    $backoffice_path = dirname(__DIR__, 2) . '/backoffice';
    if (file_exists($backoffice_path . '/vendor/autoload.php')) {
        require_once $backoffice_path . '/vendor/autoload.php';
    }
    $id = (int) \App\Utility\Cipher::decrypt($key);
}

if ($id <= 0 && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
}

// map ชนิดเอกสาร -> route + ชื่อพารามิเตอร์ id
$map = [
    'certificate' => ['state' => 'print', 'func' => 'print_certificate', 'idkey' => 'enroll_id', 'title' => 'ใบรับรองผลการสอบ'],
    'etax' => ['state' => 'print', 'func' => 'print_etax', 'idkey' => 'order_id', 'title' => 'ใบกำกับภาษี'],
];
$cfg = $map[$type] ?? null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $cfg ? htmlspecialchars($cfg['title']) : 'เอกสาร PDF'; ?></title>
    <link rel="icon" type="image/png" href="../template/assets/images/favicon.png">
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            height: 100%;
            font-family: "K2D", Tahoma, sans-serif;
            background: #525659;
        }

        #pvFrame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }

        .pv-msg {
            color: #fff;
            text-align: center;
            padding-top: 80px;
            font-size: 16px;
        }

        .pv-spin {
            width: 34px;
            height: 34px;
            border: 4px solid #888;
            border-top-color: #fff;
            border-radius: 50%;
            margin: 0 auto 16px;
            animation: pvspin 0.8s linear infinite;
        }

        @keyframes pvspin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="pv-msg" id="pvMsg">
        <div class="pv-spin"></div>กำลังสร้างเอกสาร...
    </div>
    <iframe id="pvFrame" style="display:none;"></iframe>

    <script>
        var CFG = <?php echo $cfg ? json_encode($cfg, JSON_UNESCAPED_UNICODE) : 'null'; ?>;
        var DOC_ID = <?php echo $id; ?>;

        function showError(msg) {
            document.getElementById("pvMsg").innerHTML = '<div style="color:#ff8a80;">' + (msg || "เกิดข้อผิดพลาด") + '</div>';
        }

        function b64ToBlob(b64) {
            var bin = atob(b64), len = bin.length, bytes = new Uint8Array(len);
            for (var i = 0; i < len; i++) { bytes[i] = bin.charCodeAt(i); }
            return new Blob([bytes], { type: "application/pdf" });
        }

        (function () {
            if (!CFG || !DOC_ID) { showError("ไม่พบเอกสารที่ต้องการ"); return; }
            var token = localStorage.getItem("access_token") || "";
            if (!token) { showError("กรุณาเข้าสู่ระบบใหม่"); return; }

            var body = new URLSearchParams();
            body.append("request_state", CFG.state);
            body.append("request_function", CFG.func);
            body.append(CFG.idkey, DOC_ID);
            body.append("mode", "base64");
            body.append("access_token", token);
            
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has("cert_type")) {
                body.append("cert_type", urlParams.get("cert_type"));
            }
            if (urlParams.has("course_id")) {
                body.append("course_id", urlParams.get("course_id"));
            }

            fetch("../core.php", { method: "POST", headers: { "Authorization": "Bearer " + token }, body: body })
                .then(function (res) { return res.json(); })
                .then(function (j) {
                    if (!j || j.result != 1 || !j.data || !j.data.pdf) {
                        throw new Error((j && j.msg) ? j.msg : "สร้างเอกสารไม่สำเร็จ");
                    }
                    var blobUrl = URL.createObjectURL(b64ToBlob(j.data.pdf));
                    var fr = document.getElementById("pvFrame");
                    fr.src = blobUrl;
                    fr.style.display = "block";
                    document.getElementById("pvMsg").style.display = "none";
                })
                .catch(function (err) { showError(err && err.message ? err.message : "สร้างเอกสารไม่สำเร็จ"); });
        })();
    </script>
</body>

</html>
