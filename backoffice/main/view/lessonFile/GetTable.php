<?php
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    $list_data = $data["list_data"] ?? [];
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">เอกสารประกอบการสอน</h4>
    <button type="button" class="btn btn-primary"  style="display: inline-flex; align-items: center; gap: 5px;"  onclick="OpenAddLessonFile()"><span class="material-symbols-outlined" aria-hidden="true">add</span>เพิ่มเอกสารใหม่</button>
</div>

<div class="default-table-area">
<div class="table-responsive">
    <table class="table align-middle w-100">
        <thead>
            <tr>
                <th scope="col" class="text-center" style="width: 70px;">ลำดับ</th>
                <th scope="col">ชื่อเอกสาร</th>
                <th scope="col">บทเรียน</th>
                <th scope="col" style="width: 180px;">ประเภทไฟล์</th>
                <th scope="col" class="text-center" style="width: 200px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list_data) > 0): ?>
                <?php $n = 1; ?>
                <?php foreach ($list_data as $row): ?>
                    <?php
                        $fid  = (int)($row['lesson_file_id'] ?? 0);
                        $name = (string)($row['lesson_file_name'] ?? '');
                        $type = (string)($row['lesson_file_type'] ?? '');
                        $path = $row['file_path'] ?? null;
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $n++; ?></td>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td class="text-secondary"><?php echo htmlspecialchars($row['lesson_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($type !== '' ? $type : '-'); ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center align-items-center">
                                <?php if ($path): ?>
                                    <a href="lesson_file_view.php?key=<?php echo \App\Utility\Cipher::encrypt($fid); ?>" class="btn btn-secondary table-action-btn"
                                       target="_blank" rel="noopener">
                                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>เปิดดู
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">ไม่พบไฟล์</span>
                                <?php endif; ?>
                                <button type="button" class="btn btn-danger table-action-btn" onclick="DeleteLessonFile(<?php echo $fid; ?>)">
                                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>ลบ
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีเอกสารประกอบ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
