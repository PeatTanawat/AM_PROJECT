<?php
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    $list_data = $data["list_data"] ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">บทเรียน</h4>
   <button type="button" 
        class="btn btn-primary" 
        onclick="OpenAddLesson()" 
        style="display: inline-flex; align-items: center; gap: 5px;">
  
  <span class="material-symbols-outlined" aria-hidden="true" style="font-size: 20px;">add</span>
  <span>เพิ่มบทเรียนใหม่</span>

</button>
</div>

<div class="default-table-area">
<div class="table-responsive">
    <table class="table align-middle w-100">
        <thead>
            <tr>
                <th scope="col" class="text-center" style="width: 80px;">บทที่</th>
                <th scope="col">ชื่อบทเรียน</th>
                <th scope="col" class="text-center" style="width: 140px;">สถานะวิดีโอ</th>
                <th scope="col" class="text-center" style="width: 140px;">สถานะคำถาม</th>
                <th scope="col" class="text-center" style="width: 180px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list_data) > 0): ?>
                <?php foreach ($list_data as $row): ?>
                    <?php
                        $lesson_id   = (int)($row['lesson_id'] ?? 0);
                        $order       = $row['lesson_order'] ?? '';
                        $has_video   = trim((string)($row['lesson_video'] ?? '')) !== '';
                        $has_question = (string)($row['lesson_question'] ?? '0') === '1';
                    ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars((string)$order); ?></td>
                        <td><?php echo htmlspecialchars($row['lesson_name'] ?? ''); ?></td>
                        <td class="text-center">
                            <?php if ($has_video): ?>
                                <span class="badge bg-success">พร้อมใช้งาน</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">ยังไม่มี</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($has_question): ?>
                                <span class="badge bg-success">เปิดใช้งาน</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">ปิดใช้งาน</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-warning table-action-btn" onclick="GotoLessonManage('<?php echo \App\Utility\Cipher::encrypt($lesson_id); ?>')">
                                    <span class="material-symbols-outlined" aria-hidden="true">edit</span>แก้ไข
                                </button>
                                <button type="button" class="btn btn-danger table-action-btn" onclick="DeleteLesson(<?php echo $lesson_id; ?>)">
                                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>ลบ
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีบทเรียน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
