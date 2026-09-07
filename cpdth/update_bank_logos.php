<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;
use Aws\S3\S3Client;
use Dotenv\Dotenv;

// โหลด Env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$msg = '';
$error = '';

try {
    $db_instance = new Connection();
    $pdo = $db_instance->getPdo();
    if (!$pdo) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    // ตรวจสอบการกดบันทึกฟอร์ม
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bank_id'])) {
        $bank_id = (int)$_POST['bank_id'];
        
        // ดึงข้อมูลธนาคาร
        $stmt_bank = $pdo->prepare("SELECT * FROM tbl_bank_mapping WHERE bank_id = :id LIMIT 1");
        $stmt_bank->execute([':id' => $bank_id]);
        $bank = $stmt_bank->fetch(PDO::FETCH_ASSOC);
        $stmt_bank->closeCursor();

        if (!$bank) {
            throw new Exception("ไม่พบข้อมูลธนาคารที่เลือก");
        }

        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("กรุณาเลือกไฟล์ภาพที่ถูกต้อง");
        }

        $file = $_FILES['logo_file'];
        $tmpPath = $file['tmp_name'];
        
        // ตรวจสอบชนิดไฟล์รูปภาพ
        $imageInfo = getimagesize($tmpPath);
        if (!$imageInfo) {
            throw new Exception("ไฟล์ที่อัปโหลดไม่ใช่ไฟล์รูปภาพที่ถูกต้อง");
        }
        
        $mime = $imageInfo['mime'];
        
        // โหลดรูปภาพเข้าสู่ GD Resource ตามชนิดไฟล์ต้นฉบับ
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($tmpPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($tmpPath);
                // ตั้งค่าเพื่อให้โปร่งใส (Transparency) ของ PNG ทำงานร่วมกับ WebP ได้
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($tmpPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($tmpPath);
                break;
            default:
                throw new Exception("รองรับเฉพาะรูปภาพประเภท JPG, JPEG, PNG, WEBP และ GIF เท่านั้น");
        }

        if (!$image) {
            throw new Exception("ไม่สามารถประมวลผลไฟล์รูปภาพได้");
        }

        // สร้างไฟล์ชั่วคราวเป็น WebP
        $tempWebpPath = tempnam(sys_get_temp_dir(), 'bank_logo_') . '.webp';
        
        // แปลงเป็น WebP และบันทึกลงไฟล์ชั่วคราว ความละเอียด 90%
        if (!imagewebp($image, $tempWebpPath, 90)) {
            imagedestroy($image);
            throw new Exception("เกิดข้อผิดพลาดในการแปลงรูปภาพเป็น WebP");
        }
        
        // คืนหน่วยความจำ GD
        imagedestroy($image);

        // เชื่อมต่อ AWS S3 Client
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['AWS_DEFAULT_REGION'] ?? 'ap-southeast-2',
            'credentials' => [
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '',
            ]
        ]);

        $bucket = $_ENV['AWS_BUCKET'] ?? 'cpdth-storage';
        $s3Key = 'bank_image/' . $bank['bank_abbreviation'] . '.webp';

        // ทำการอัปโหลดไฟล์ WebP ที่แปลงเสร็จแล้วไปที่ S3
        $result = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $s3Key,
            'SourceFile' => $tempWebpPath,
            'ContentType' => 'image/webp'
        ]);

        // ลบไฟล์ WebP ชั่วคราวออกจากเซิร์ฟเวอร์
        if (file_exists($tempWebpPath)) {
            unlink($tempWebpPath);
        }


        // บันทึกที่อยู่รูปภาพลงฟิลด์ logo_image ของตาราง tbl_bank_mapping
        $stmt_up = $pdo->prepare("UPDATE tbl_bank_mapping SET logo_image = :logo WHERE bank_id = :id");
        $stmt_up->execute([
            ':logo' => $s3Key,
            ':id' => $bank_id
        ]);
        $stmt_up->closeCursor();

        $msg = "อัปโหลดและบันทึกโลโก้สำหรับ {$bank['bank_name']} ({$bank['bank_abbreviation']}) ไปยัง S3 สำเร็จเรียบร้อยแล้ว!<br>S3 Path: <strong>{$s3Key}</strong>";
    }

    // ดึงรายชื่อธนาคารมาแสดงผลใน Dropdown
    $stmt = $pdo->prepare("SELECT * FROM tbl_bank_mapping ORDER BY bank_id ASC");
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดโลโก้ธนาคารไป S3 & อัปเดตฐานข้อมูล</title>
    <!-- BootStrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f1f5f9; padding-top: 50px; }
        .upload-card { border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container" style="max-width: 600px;">
    <div class="card upload-card bg-white p-4">
        <h3 class="text-center fw-bold mb-4 text-primary">อัปโหลดโลโก้ธนาคารไปยัง S3</h3>
        
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="bank_id" class="form-label fw-bold">เลือกธนาคาร</label>
                <select name="bank_id" id="bank_id" class="form-select" required>
                    <option value="">-- กรุณาเลือกธนาคาร --</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?php echo $b['bank_id']; ?>">
                            <?php echo htmlspecialchars($b['bank_code'] . ' - ' . $b['bank_name'] . ' (' . $b['bank_abbreviation'] . ')'); ?>
                            <?php echo !empty($b['logo_image']) ? ' (มีรูปแล้ว: ' . htmlspecialchars($b['logo_image']) . ')' : ' (ยังไม่มีรูป)'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="logo_file" class="form-label fw-bold">เลือกไฟล์โลโก้ (รองรับ JPG, PNG, GIF, WEBP)</label>
                <input class="form-control" type="file" name="logo_file" id="logo_file" accept="image/*" required>
                <div class="form-text text-muted">ระบบจะแปลงเป็นฟอร์แมต <code>.webp</code> อัตโนมัติก่อนอัปโหลดขึ้น S3 ใน Path: <code>bank_image/[ชื่อย่อธนาคาร].webp</code> และบันทึกตำแหน่งไฟล์ลงฐานข้อมูล</div>
            </div>


            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold" style="border-radius: 8px;">อัปโหลดและบันทึกข้อมูล</button>
        </form>
    </div>
</div>
</body>
</html>
