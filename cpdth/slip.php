<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Utility\Auth;
use App\Database\Connection;

$currentUser = Auth::getUser();

if (!$currentUser) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='bg-light'><div class='container mt-5'><div class='alert alert-danger'>ไม่พบรหัสคำสั่งซื้อ</div></div></body></html>";
    exit;
}

$db = (new App\Database\Connection())->getPdo();

// ดึงข้อมูลคำสั่งซื้อและตรวจสิทธิ์
$stmt = $db->prepare("SELECT * FROM tbl_orders WHERE order_id = :id AND user_id = :uid LIMIT 1");
$stmt->execute([':id' => $order_id, ':uid' => $currentUser->user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='bg-light'><div class='container mt-5'><div class='alert alert-danger'>ไม่พบข้อมูล หรือคุณไม่มีสิทธิ์เข้าถึง</div></div></body></html>";
    exit;
}

// ดึงข้อมูลที่อยู่/ข้อมูลลูกค้า
$ad = $db->prepare(
    "SELECT addr_type, addr_name, addr_tax_id, addr_branch, addr_branch_name, addr_phone, addr_detail,
            addr_subdistrict, addr_district, addr_province, addr_zipcode
     FROM tbl_user_address
     WHERE addr_user_id = :uid AND delete_at IS NULL
     ORDER BY addr_is_default DESC, addr_id DESC LIMIT 1"
);
$ad->execute([':uid' => $order['user_id']]);
$addr = $ad->fetch(PDO::FETCH_ASSOC);

$cust_type_str = 'บุคคลธรรมดา';
$cust_name = $addr ? ($addr['addr_name'] ?: '-') : '-';
$cust_tax  = $addr ? ($addr['addr_tax_id'] ?: '-') : '-';
$addr_text = '-';

if ($addr) {
    if ((isset($addr['addr_type']) && $addr['addr_type'] == 2) || !empty($addr['addr_branch']) || !empty($addr['addr_branch_name'])) {
        $cust_type_str = 'นิติบุคคล';
        $branch_code = $addr['addr_branch'] ?: '-';
        $branch_name = $addr['addr_branch_name'] ?: '-';
        $cust_name .= " (สาขา: $branch_code $branch_name)";
    }

    $arr = array_filter([$addr['addr_detail'] ?? '', $addr['addr_subdistrict'] ?? '', $addr['addr_district'] ?? '', $addr['addr_province'] ?? '', $addr['addr_zipcode'] ?? '']);
    if (!empty($arr)) $addr_text = implode(' ', $arr);
}

// ดึงรายการคอร์ส
$it = $db->prepare(
    "SELECT od.price_at_purchase, c.course_name
     FROM tbl_order_detail od
     LEFT JOIN tbl_course c ON c.course_id = od.course_id
     WHERE od.order_id = :id
     ORDER BY od.list_order ASC, od.detail_id ASC"
);
$it->execute([':id' => $order_id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

// เช็คคูปอง
$discount = 0;
$coupon_desc = 'ส่วนลด';
$stmt_cl = $db->prepare("
    SELECT cl.*, c.* 
    FROM tbl_coupon_logs cl 
    JOIN tbl_coupon c ON c.coupon_id = cl.coupon_id 
    WHERE cl.order_id = :id 
    LIMIT 1
");
$stmt_cl->execute([':id' => $order_id]);
$cop_log = $stmt_cl->fetch(PDO::FETCH_ASSOC);
if ($cop_log) {
    $discount = (float)($cop_log['discount'] ?? $cop_log['discount_amount'] ?? $cop_log['amount'] ?? $cop_log['price'] ?? $cop_log['coupon_discount'] ?? 0);
}

$total  = (float)($order['total_price'] ?? 0);
$before = round($total / 1.07, 2);
$vat    = round($total - $before, 2);
$ts = $order['created_at'] ? strtotime($order['created_at']) : time();
$doc_no = 'ET' . date('ym', $ts) . str_pad((string)$order['order_id'], 7, '0', STR_PAD_LEFT);
$doc_date = date('d/m/Y', $ts); 

// รหัสผ่าน PDF (ใช้เลขบัตรประชาชนของ User เสมอ)
$user_citizen = $currentUser->user_citizen_id ?? '';
$tax_digits = preg_replace('/\D/', '', (string)$user_citizen);
$pwd = strlen($tax_digits) >= 4 ? substr($tax_digits, -4) : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลใบกำกับภาษี (E-TAX)</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
    .slip-container { max-width: 1100px; margin: 40px auto; padding: 0 15px; width: 100%; }
    .slip-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 40px; max-width: 100%; overflow: hidden; }
    .slip-title { font-size: 1.5rem; font-weight: 600; color: #1e293b; margin-bottom: 30px; }
    .btn-orange { background-color: #f59e0b; color: white; border: none; font-weight: 500; padding: 10px 20px; border-radius: 6px; }
    .btn-orange:hover { background-color: #d97706; color: white; }
    .btn-green { background-color: #10b981; color: white; border: none; font-weight: 500; padding: 10px 20px; border-radius: 6px; }
    .btn-green:hover { background-color: #059669; color: white; }
    
    .info-row { display: flex; margin-bottom: 15px; }
    .info-label { width: 180px; font-weight: 600; color: #475569; }
    .info-value { flex: 1; color: #334155; }
    
    .status-badge { background-color: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
    
    .table-slip { width: 100%; min-width: 800px; margin-top: 30px; border-bottom: 1px solid #e2e8f0; }
    .table-slip th { padding: 15px 10px; border-bottom: 2px solid #e2e8f0; border-top: 1px solid #e2e8f0; color: #1e293b; font-weight: 600; font-size: 0.95rem; white-space: nowrap; }
    .table-slip td { padding: 15px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.95rem; white-space: nowrap; }
    
    .summary-table { width: 300px; margin-left: auto; margin-top: 20px; }
    .summary-table td { padding: 8px 10px; text-align: right; font-size: 0.95rem; }
    .summary-table .bold-row td { font-weight: 600; font-size: 1.05rem; color: #1e293b; }
</style>
</head>
<body>

<div class="slip-container">
    <div class="slip-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <h3 class="slip-title m-0">ข้อมูลใบกำกับภาษี (E-TAX)</h3>
            
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="info-row"><div class="info-label">เลขที่เอกสาร:</div><div class="info-value"><?php echo $doc_no; ?></div></div>
                <div class="info-row"><div class="info-label">ประเภทลูกค้า:</div><div class="info-value"><?php echo htmlspecialchars($cust_type_str); ?></div></div>
                <div class="info-row"><div class="info-label">หมายเลขผู้เสียภาษี:</div><div class="info-value"><?php echo htmlspecialchars($cust_tax); ?></div></div>
                <div class="info-row"><div class="info-label">สถานะ:</div><div class="info-value"><span class="status-badge" style="white-space: nowrap;">ออกใบกำกับภาษีแล้ว</span></div></div>
            </div>
            <div class="col-md-6">
                <div class="info-row"><div class="info-label">วันที่ในเอกสาร:</div><div class="info-value"><?php echo $doc_date; ?></div></div>
                <div class="info-row"><div class="info-label">ชื่อลูกค้า:</div><div class="info-value"><?php echo htmlspecialchars($cust_name); ?></div></div>
                <div class="info-row"><div class="info-label">ที่อยู่:</div><div class="info-value"><?php echo htmlspecialchars($addr_text); ?></div></div>
            </div>
        </div>

        <h5 class="mt-4 mb-3" style="font-weight: 600; color: #1e293b;">รายการสินค้า:</h5>
        <div class="table-responsive" style="display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table-slip text-center">
                <thead>
                    <tr>
                        <th class="text-center">ลำดับ</th>
                        <th class="text-start">ชื่อสินค้า/บริการ</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-end">ราคาสินค้า</th>
                        <th class="text-end">ส่วนลด</th>
                        <th class="text-end">VAT</th>
                        <th class="text-end">รวม</th>
                        <th class="text-start">ประเภทภาษี</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    foreach ($items as $x) {
                        $price = (float)$x['price_at_purchase'];
                        $item_before = round($price / 1.07, 2);
                        $item_vat = round($price - $item_before, 2);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td class="text-start"><?php echo htmlspecialchars($x['course_name']); ?></td>
                        <td class="text-center">1</td>
                        <td class="text-end"><?php echo number_format($price, 2); ?> ฿</td>
                        <td class="text-end">0.00 ฿</td>
                        <td class="text-end"><?php echo number_format($item_vat, 2); ?> ฿</td>
                        <td class="text-end"><?php echo number_format($price, 2); ?> ฿</td>
                        <td class="text-start">ภาษีมูลค่าเพิ่ม 7%</td>
                    </tr>
                    <?php } ?>
                    <?php if ($discount > 0) { ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td class="text-start">ส่วนลด</td>
                        <td class="text-center">1</td>
                        <td class="text-end">-<?php echo number_format($discount, 2); ?> ฿</td>
                        <td class="text-end">-<?php echo number_format($discount, 2); ?> ฿</td>
                        <td class="text-end">0.00 ฿</td>
                        <td class="text-end">-<?php echo number_format($discount, 2); ?> ฿</td>
                        <td class="text-start">ไม่มีภาษี</td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <table class="summary-table">
            <tr>
                <td>รวม</td>
                <td><?php echo number_format($before, 2); ?> ฿</td>
            </tr>
            <tr>
                <td>VAT</td>
                <td><?php echo number_format($vat, 2); ?> ฿</td>
            </tr>
            <tr class="bold-row">
                <td>รวมทั้งสิ้น</td>
                <td><span style="color: #6366f1;"><?php echo number_format($total, 2); ?> ฿</span></td>
            </tr>
        </table>
    </div>
</div>

<script>

function downloadEtax(orderId, pwd) {
    let displayPwd = pwd ? pwd : 'ไม่ได้ตั้งค่าไว้';
    let targetUrl = 'print/print_etax_certificate.php?type=etax&id=' + orderId;
    
    // 1. เปิดเอกสารใบกำกับภาษี etax ในแท็บใหม่
    let win = window.open(targetUrl, '_blank');
    if (win) {
        try { win.blur(); } catch (e) {}
    }

    // 2. แสดง Swal และดึงโฟกัสกลับมาที่หน้าเดิมและตัว Swal อย่างต่อเนื่อง
    Swal.fire({
        icon: 'success',
        title: 'ดาวน์โหลดใบกำกับภาษี',
        html: 'รหัสผ่านใบกำกับภาษีของคุณคือ <b>' + displayPwd + '</b>',
        confirmButtonText: 'คัดลอก',
        confirmButtonColor: '#10b981',
        showCloseButton: true,
        allowOutsideClick: true,
        didOpen: () => {
            const pullFocus = () => {
                if (win && !win.closed) {
                    try { win.blur(); } catch (e) {}
                }
                window.focus();
                const confirmBtn = Swal.getConfirmButton();
                if (confirmBtn) confirmBtn.focus();
            };

            pullFocus();
            [50, 100, 200, 400, 700].forEach(delay => {
                setTimeout(pullFocus, delay);
            });
        },
        preConfirm: () => {
            if (pwd) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(pwd);
                } else {
                    let textArea = document.createElement("textarea");
                    textArea.value = pwd;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
            }

            // เล่น animation ตรงส่วน icon ของ swal ใหม่
            const icon = Swal.getIcon();
            if (icon && icon.parentNode) {
                const newIcon = icon.cloneNode(true);
                icon.parentNode.replaceChild(newIcon, icon);
            }

            // ไม่ปิด modal
            return false;
        }
    });
}

function resendEmail(orderId) {
    Swal.fire({
        title: 'กำลังส่งอีเมล',
        text: 'กรุณารอสักครู่...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    // จำลองการส่งอีเมล หรือสามารถเรียก API ส่งอีเมลจริงๆ ตรงนี้
    setTimeout(() => {
        Swal.fire('สำเร็จ!', 'ส่งอีเมลไปยังที่อยู่ของคุณเรียบร้อยแล้ว', 'success');
    }, 1500);
}
</script>
</body>
</html>
