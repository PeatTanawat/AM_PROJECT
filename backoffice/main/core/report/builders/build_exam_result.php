<?php
// builder: ใบรับรองผลสอบแบบ PDF (ดึงข้อมูลจริงทั้งหมดตรงจาก tbl_certificate_snapshot ตามฟิลเตอร์ พร้อมระบบ Local Image Cache)
// ใช้วิธี WriteHTML ทีละหน้า (Chunk) เพื่อหลีกเลี่ยงข้อจำกัดของ PCRE Backtrack Limit สำหรับเอกสารหลายหน้า

ini_set('memory_limit', '1024M'); // ขยายหน่วยความจำ
set_time_limit(300); // ขยายเวลาทำงาน

use Mpdf\Mpdf;
use App\Utility\Pdf;
use App\Utility\Response;
use App\Utility\AwsS3;

$workspace_root = dirname(__DIR__, 4);
$cache_dir = $workspace_root . '/tmp/img_cache';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}

// ข้อมูลจำลองพื้นฐาน/ทั่วไป
$company = 'บริษัท เอ เอ็ม ซีพีดี จำกัด';
$tax_id  = '0105565002221';
$agency  = '06-330';

// ดึงภาพโลโก้และลายเซ็นแบบเดียวกับ ExportCertificate.php
$logo_uri = Pdf::fileToDataUri($workspace_root . '/assets/images/am-group-logo.png');
if ($logo_uri === '') {
    $logo_uri = Pdf::fileToDataUri(dirname($workspace_root) . '/cpdth/assets/images/logo/am-group-logo.png');
}
$logo_html = $logo_uri !== '' ? '<img src="' . $logo_uri . '" style="width:115px;">' : 'AM GROUP';

$sign_uri  = Pdf::fileToDataUri($workspace_root . '/assets/images/signature-amgroup.jpg');
$sign_html = $sign_uri !== '' ? '<img src="' . $sign_uri . '" style="width:100px;height:80px;">' : '';

// 1) ดึงข้อมูลจริงทั้งหมดจากฟิลเตอร์ของ tbl_certificate_snapshot
$course_id = (int) ($_POST['course_id'] ?? 0);
// $from, $to, $norm_date, $fmt_date จากไฟล์ ExportReport.php ที่ include เข้ามา
$from_val = isset($_POST['from']) ? $norm_date((string)$_POST['from']) : '';
$to_val   = isset($_POST['to']) ? $norm_date((string)$_POST['to']) : '';
$license_type = trim((string) ($_POST['license_type'] ?? 'both'));

$where  = ["s.cert_id IS NOT NULL"];
$params = [];
if ($course_id > 0) { $where[] = 's.course_id = :course_id'; $params[':course_id'] = $course_id; }
if ($from_val !== '') { $where[] = 'DATE(s.issued_at) >= :from'; $params[':from'] = $from_val; }
if ($to_val !== '')   { $where[] = 'DATE(s.issued_at) <= :to';   $params[':to']   = $to_val; }

if ($license_type === 'cpd') {
    $where[] = "s.user_license_no IS NOT NULL AND s.user_license_no <> ''";
} elseif ($license_type === 'cpa') {
    $where[] = "s.user_licensecpa_no IS NOT NULL AND s.user_licensecpa_no <> ''";
} elseif ($license_type === 'both') {
    $where[] = "((s.user_license_no IS NOT NULL AND s.user_license_no <> '') OR (s.user_licensecpa_no IS NOT NULL AND s.user_licensecpa_no <> ''))";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT s.enroll_id, s.issued_at, s.course_id,
               s.user_firstname, s.user_lastname, s.user_citizen_id, s.user_license_no, s.user_licensecpa_no, s.id_card_image_snapshot,
               s.course_name, s.course_instructor, s.course_code_cpd, s.course_code_cpa, s.course_approval_date,
               s.hours_account, s.hours_ethics, s.hours_other,
               s.exam_score, s.exam_total, s.score_percent, s.cert_no, s.cert_type
        FROM tbl_certificate_snapshot s
        $where_sql
        ORDER BY s.issued_at DESC, s.cert_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$total_rows = count($rows);

if ($total_rows === 0) {
    Response::json(0, 'ไม่พบข้อมูลใบรับรองที่ตรงตามเงื่อนไข', null);
}

try {
    $fontDir = $workspace_root . '/assets/fonts/sarabun';
    $tempDir = $workspace_root . '/tmp';

    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs      = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData          = $defaultFontConfig['fontdata'];

    $mpdf = new Mpdf([
        'mode'              => 'utf-8',
        'format'            => 'Letter',
        'orientation'       => 'P',
        'tempDir'           => $tempDir,
        'fontDir'           => array_merge($fontDirs, [$fontDir]),
        'fontdata'          => $fontData + [
            'thsarabun' => [
                'R'  => 'THSarabunNew.ttf',
                'B'  => 'THSarabunNew Bold.ttf',
                'I'  => 'THSarabunNew Italic.ttf',
                'BI' => 'THSarabunNew BoldItalic.ttf',
            ],
        ],
        'default_font'      => 'thsarabun',
        'margin_left'       => 16,
        'margin_right'      => 16,
        'margin_top'        => 13,
        'margin_bottom'     => 12,
    ]);

    $mpdf->SetTitle('Exam_Results_Certificates_Report');

    // เขียน Style ครั้งเดียวก่อนเริ่มวนลูปเขียนหน้าใบรับรอง
    $styles = '
    <style>
        body {
            font-family: thsarabun;
            color: #222;
            font-size: 18pt;
            line-height: 1.35;
        }
        .hd { font-size: 16pt; }
        .hd-sub { font-size: 17px; }
        .logo { font-size: 20pt; font-weight: bold; color: #345585; padding-left: 20px; padding-top: 5px; }
        .company { font-size: 25px; color: #3b5998; font-weight: bold; }
        .cert-no { font-size: 17px; font-weight: normal; color: #555; }
        .title { text-align: center; font-weight: bold; font-size: 21px; margin: 16px 0 10px 0; color: #111; }
        .lead { text-indent: 100px; text-align: left; margin: 8px 0; line-height: 1.2; font-size: 17px; color: #222; }
        .detail { font-size: 18pt; margin: 0 auto; }
        .detail td { vertical-align: top; line-height: 1.0; padding: 0px 3px; }
        .lbl { text-align: right; white-space: nowrap; font-size: 17px; }
        .val { text-align: left; font-size: 18px; color: #222; }
        .sign { text-align: center; line-height: 1.4; font-size: 18px; color: #222; }
    </style>
    ';
    
    $styles = str_replace(["\xc2\xa0", "&nbsp;", "\xe2\x80\x8b"], [" ", " ", ""], $styles);
    $mpdf->WriteHTML($styles);

    // เขียนหน้าใบรับรองทีละหน้าตามแถวข้อมูลจริงที่ดึงได้
    $idx = 0;
    foreach ($rows as $row) {
        if ($idx > 0) {
            $mpdf->WriteHTML('<pagebreak />');
        }
        $idx++;

        $fullname = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
        $user_cpd_no = trim((string) ($row['user_license_no'] ?? ''));
        $user_cpa_no = trim((string) ($row['user_licensecpa_no'] ?? ''));
        
        $license_rows_html = '';
        if ($user_cpd_no !== '' && $user_cpa_no !== '') {
            $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้ทำบัญชี</td><td style="font-size:18px;">:&nbsp;' . htmlspecialchars($user_cpd_no) . '</td></tr>';
            $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้สอบบัญชี</td><td style="font-size:18px;">:&nbsp;' . htmlspecialchars($user_cpa_no) . '</td></tr>';
        } elseif ($user_cpd_no !== '') {
            $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้ทำบัญชี</td><td style="font-size:18px;">:&nbsp;' . htmlspecialchars($user_cpd_no) . '</td></tr>';
        } elseif ($user_cpa_no !== '') {
            $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้สอบบัญชี</td><td style="font-size:18px;">:&nbsp;' . htmlspecialchars($user_cpa_no) . '</td></tr>';
        } else {
            $fallback_lic = trim((string) ($row['user_citizen_id'] ?? '-'));
            $license_rows_html .= '<tr><td align="right" class="lbl">เลขที่ผู้ทำบัญชี</td><td style="font-size:18px;">:&nbsp;' . htmlspecialchars($fallback_lic) . '</td></tr>';
        }

        $course = (string) ($row['course_name'] ?? '');
        $code_cpd = trim((string) ($row['course_code_cpd'] ?? ''));
        $code_cpa = trim((string) ($row['course_code_cpa'] ?? ''));

        if ($user_cpd_no !== '' && $user_cpa_no !== '') {
            $codes = [];
            if ($code_cpd !== '') $codes[] = 'CPD: ' . $code_cpd;
            if ($code_cpa !== '') $codes[] = 'CPA: ' . $code_cpa;
            $code_disp = !empty($codes) ? implode(' / ', $codes) : '-';
        } elseif ($user_cpa_no !== '') {
            $code_disp = $code_cpa !== '' ? $code_cpa : ($code_cpd !== '' ? $code_cpd : '-');
        } else {
            $code_disp = $code_cpd !== '' ? $code_cpd : ($code_cpa !== '' ? $code_cpa : '-');
        }
        
        $approve_date = (! empty($row['course_approval_date']) && $row['course_approval_date'] !== '0000-00-00')
            ? date('d/m/Y', strtotime($row['course_approval_date'])) : '-';

        // จัดการชั่วโมงการอบรม
        $cpd_hour   = number_format((float) ($row['hours_account'] ?? 0), 2);
        $cpd_ethics = number_format((float) ($row['hours_ethics'] ?? 0), 2);
        $cpd_other  = (float) ($row['hours_other'] ?? 0);
        $who = ($user_cpd_no !== '' && $user_cpa_no !== '') ? 'ผู้ทำบัญชีและผู้สอบบัญชี' : ($user_cpa_no !== '' ? 'ผู้สอบบัญชี' : 'ผู้ทำบัญชี');
        $hours      = 'บัญชี ' . $cpd_hour . ' ชม. จรรยาบรรณ ' . $cpd_ethics . ' ชม.';
        if ($cpd_other > 0) {
            $hours .= ' อื่น ๆ ' . number_format($cpd_other, 2) . ' ชม.';
        }
        $hours .= ' สำหรับ' . $who;

        $instructor = trim((string) ($row['course_instructor'] ?? '')) ?: '-';
        $train_date = (! empty($row['issued_at']) && $row['issued_at'] !== '0000-00-00 00:00:00')
            ? date('d/m/Y', strtotime($row['issued_at'])) : date('d/m/Y');
            
        $cert_no = (string) ($row['cert_no'] ?? '');
        
        // คะแนนสอบ
        $score_percent = $row['score_percent'];
        $score_txt = ($score_percent !== null) ? number_format((float)$score_percent, 2) . ' %' : '-';

        // รูปบัตรประชาชน (ดึงผ่าน Local Cache หรือ AWS S3)
        $id_img_uri = '';
        $id_card    = trim((string) ($row['id_card_image_snapshot'] ?? ''));
        if ($id_card !== '') {
            $s3_key = $id_card;
            if (strpos($id_card, 'http://') === 0 || strpos($id_card, 'https://') === 0) {
                $s3_key = ltrim((string) parse_url($id_card, PHP_URL_PATH), '/');
            } else {
                $s3_key = ltrim($id_card, '/');
            }

            $cache_file = $cache_dir . '/' . md5($s3_key) . '.jpg';

            // ดึงภาพจาก Local Cache หากมีอยู่แล้ว
            if (is_file($cache_file) && filesize($cache_file) > 0) {
                $id_img_uri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($cache_file));
            } else {
                // หากยังไม่มีใน Cache ให้ดาวน์โหลดจาก S3
                $url = AwsS3::getFileUrl($s3_key);

                if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                    $ch_img = curl_init($url);
                    curl_setopt($ch_img, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_img, CURLOPT_TIMEOUT, 2); // timeout 2 วินาที
                    $bin = curl_exec($ch_img);
                    curl_close($ch_img);

                    if ($bin !== false && strlen($bin) > 0) {
                        $mime = 'image/jpeg';
                        if (function_exists('imagecreatefromstring')) {
                            $src_img = @imagecreatefromstring($bin);
                            if ($src_img !== false) {
                                $orig_w = imagesx($src_img);
                                $orig_h = imagesy($src_img);

                                $target_w     = 440;
                                $target_h     = 270;
                                $target_ratio = $target_w / $target_h;
                                $orig_ratio   = $orig_w / $orig_h;

                                if ($orig_ratio > $target_ratio) {
                                    $crop_h = $orig_h;
                                    $crop_w = (int) ($orig_h * $target_ratio);
                                    $crop_x = (int) (($orig_w - $crop_w) / 2);
                                    $crop_y = 0;
                                } else {
                                    $crop_w = $orig_w;
                                    $crop_h = (int) ($orig_w / $target_ratio);
                                    $crop_x = 0;
                                    $crop_y = (int) (($orig_h - $crop_h) / 2);
                                }

                                $dst_img = imagecreatetruecolor($target_w, $target_h);
                                imagecopyresampled($dst_img, $src_img, 0, 0, $crop_x, $crop_y, $target_w, $target_h, $crop_w, $crop_h);

                                ob_start();
                                imagejpeg($dst_img, null, 92);
                                $cropped_bin = ob_get_clean();

                                imagedestroy($src_img);
                                imagedestroy($dst_img);

                                if ($cropped_bin !== false && strlen($cropped_bin) > 0) {
                                    $bin = $cropped_bin;
                                    @file_put_contents($cache_file, $bin); // บันทึกลง Local Cache
                                }
                            }
                        }
                        $id_img_uri = 'data:' . $mime . ';base64,' . base64_encode($bin);
                    }
                }
            }
        }
        
        $id_img_html = $id_img_uri !== ''
            ? '<img src="' . $id_img_uri . '" style="width:240px;height:145px;border:1px solid #bbb;object-fit:cover;">'
            : '<div style="width:240px;height:145px;border:1px dashed #999;background-color:#eee;text-align:center;line-height:145px;font-size:16px;color:#666;">ไม่มีรูปภาพบัตรประชาชน</div>';

        $page_html = '
        <div style="position: relative; height: 100%;">
            <table width="100%" class="hd"><tr>
                <td width="30%" valign="middle" class="logo">' . $logo_html . '</td>
                <td width="40%" align="center" valign="middle" class="company">' . htmlspecialchars($company) . '</td>
                <td width="30%" align="right" valign="top" class="cert-no">' . htmlspecialchars($cert_no) . '</td>
            </tr></table>
            <table width="100%" class="hd-sub" style="margin-top:40px;"><tr>
                <td width="50%" align="left" style="padding-left: 20px;">CPD e-Learning</td>
                <td width="50%" align="right">วันที่ ' . htmlspecialchars($train_date) . '</td>
            </tr></table>

            <div class="title">หนังสือรับรอง</div>

            <p class="lead" style="padding-left: 20px;">ตามที่' . htmlspecialchars($company) . ' เลขประจำตัวผู้เสียภาษี ' . htmlspecialchars($tax_id) . ' รหัสหน่วยงาน ' . htmlspecialchars($agency) . '<br>
            ได้จัดฝึกอบรมและสัมมนาหลักสูตร ที่ได้รับความเห็นชอบจากสภาวิชาชีพบัญชี ในพระบรมราชูปถัมภ์ตามข้อบังคับกับสภาวิชาชีพ<br>
            เพื่อพัฒนาความรู้ต่อเนื่องทางวิชาชีพของผู้ทำบัญชี มีรายละเอียดดังนี้</p>

            <table align="center" cellpadding="3" class="detail" style="margin-top: 10px;">
                <tr><td align="right" class="lbl" style="padding-top: 10px;">ผู้เข้าสัมมนา</td><td style="font-size:18px; padding-top: 10px;">:&nbsp;' . htmlspecialchars($fullname) . '</td></tr>
                ' . $license_rows_html . '
                <tr><td align="right" class="lbl">ชื่อหลักสูตร</td><td style="font-size:18px;">:&nbsp;' . htmlspecialchars($course) . '</td></tr>
                <tr><td align="right" class="lbl" style="padding-top: 10px;">รหัสหลักสูตร</td><td style="font-size:17px; padding-top: 10px;">:&nbsp;' . htmlspecialchars($code_disp) . '</td></tr>
                <tr><td align="right" class="lbl">วันที่อนุมัติหลักสูตร</td><td style="font-size:17px;">:&nbsp;' . htmlspecialchars($approve_date) . '</td></tr>
                <tr><td align="right" class="lbl">นับชั่วโมงด้าน</td><td style="font-size:17px;">:&nbsp;' . htmlspecialchars($hours) . '</td></tr>
                <tr><td align="right" class="lbl">วิทยากรนำเสนอ</td><td style="font-size:17px;">:&nbsp;' . htmlspecialchars($instructor) . '</td></tr>
                <tr><td align="right" class="lbl">วัน เดือน ปี ที่อบรม</td><td style="font-size:17px;">:&nbsp;' . htmlspecialchars($train_date) . '</td></tr>
                <tr><td align="right" class="lbl">สถานที่</td><td style="font-size:17px;">:&nbsp;การพัฒนาความรู้ต่อเนื่องผ่านระบบเครือข่ายอินเตอร์เน็ต (e-Learning)</td></tr>
                <tr><td align="right" class="lbl">คะแนนสอบ</td><td style="font-size:17px;">:&nbsp;' . htmlspecialchars($score_txt) . '</td></tr>
            </table>

            <table width="100%" style="margin-top: 20px;"><tr>
                <td width="50%" valign="bottom" style="padding-left: 20px;">' . $id_img_html . '</td>
                <td width="50%" valign="bottom" class="sign">
                    บริษัทฯ ขอรับรองว่าท่านได้ผ่านการสัมมนาหลักสูตรดังกล่าวจริง<br>
                    ' . ($sign_html !== '' ? $sign_html . '<br>' : '<br><br><br>') . '
                    ขอแสดงความนับถือ<br>
                    ในนาม ' . htmlspecialchars($company) . '
                </td>
            </tr></table>
        </div>';

        $page_html = str_replace(["\xc2\xa0", "&nbsp;", "\xe2\x80\x8b"], [" ", " ", ""], $page_html);
        $mpdf->WriteHTML($page_html);
    }

    $license_title = 'CPD และ CPA';
    if ($license_type === 'cpd') {
        $license_title = 'CPD ผู้ทำบัญชี';
    } elseif ($license_type === 'cpa') {
        $license_title = 'CPA ผู้สอบบัญชี';
    }

    $date_suffix = date('Ymd');
    if (!empty($_POST['from']) && !empty($_POST['to'])) {
        $date_suffix = str_replace('/', '', $_POST['from']) . '_' . str_replace('/', '', $_POST['to']);
    } elseif (!empty($_POST['from'])) {
        $date_suffix = str_replace('/', '', $_POST['from']);
    } elseif (!empty($_POST['to'])) {
        $date_suffix = str_replace('/', '', $_POST['to']);
    }

    $filename = $license_title . '_' . $date_suffix . '.pdf';

    $pdf = $mpdf->Output('', 'S');

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $pdf;
    exit;

} catch (\Throwable $e) {
    error_log('ExportReport PDF 100 Error: ' . $e->getMessage());
    Response::json(0, 'สร้าง PDF ไม่สำเร็จ: ' . $e->getMessage(), null);
}
