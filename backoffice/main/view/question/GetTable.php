<?php
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    $list_data = $data["list_data"] ?? [];

    // ตัด HTML ของ rich text ออกให้เหลือข้อความสั้น ๆ สำหรับแสดงในตาราง
    function plain_text(string $html, int $limit = 80): string {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
        if (mb_strlen($t) > $limit) { $t = mb_substr($t, 0, $limit) . '…'; }
        return $t;
    }
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">คำถามระหว่างรับชม</h4>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-info" onclick="OpenUploadQuestion()">อัพโหลดคำถาม</button>
        <button type="button" class="btn btn-primary" onclick="OpenAddQuestion()">เพิ่มคำถาม</button>
    </div>
</div>

<div class="default-table-area">
<div class="table-responsive">
    <table class="table align-middle w-100">
        <thead>
            <tr>
                <th scope="col" class="text-center" style="width: 80px;">ลำดับ</th>
                <th scope="col">คำถาม</th>
                <th scope="col" class="text-center" style="width: 120px;">ไฟล์/ภาพ</th>
                <th scope="col" class="text-center" style="width: 140px;">คำตอบที่ถูกต้อง</th>
                <th scope="col" class="text-center" style="width: 180px;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list_data) > 0): ?>
                <?php $n = 1; ?>
                <?php foreach ($list_data as $row): ?>
                    <?php
                        $qid     = (int)($row['question_id'] ?? 0);
                        $hasFile = trim((string)($row['question_image'] ?? '')) !== '' || trim((string)($row['question_file'] ?? '')) !== '';
                        $correct = (int)($row['correct_index'] ?? 0);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $n++; ?></td>
                        <td><?php echo htmlspecialchars(plain_text((string)($row['question_text'] ?? ''))); ?></td>
                        <td class="text-center">
                            <?php if ($hasFile): ?>
                                <span class="badge bg-success">มี</span>
                            <?php else: ?>
                                <span class="text-muted">ไม่มีข้อมูล</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo $correct > 0 ? 'ข้อ ' . $correct : '<span class="text-muted">-</span>'; ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-warning table-action-btn" onclick="OpenEditQuestion(<?php echo $qid; ?>)">
                                    <span class="material-symbols-outlined" aria-hidden="true">edit</span>แก้ไข
                                </button>
                                <button type="button" class="btn btn-danger table-action-btn" onclick="DeleteQuestion(<?php echo $qid; ?>)">
                                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>ลบ
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีคำถาม</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
