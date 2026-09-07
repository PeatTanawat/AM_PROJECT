<?php
/**
 * Pagination กลาง — ใช้ร่วมทุก view fragment ของตาราง custom (สไตล์เดียวกันทั้งเว็บ)
 * ต้องกำหนดตัวแปรก่อน include: $page, $per_page, $total
 * เรียกใช้: <?php $page=$page; $per_page=$per_page; $total=$total; include dirname(__DIR__).'/_pagination.php'; ?>
 * ปุ่มทั้งหมดเรียก JS GetData(page) ของหน้านั้น ๆ — แสดงเสมอ (แม้มีหน้าเดียว)
 */
$__per   = max(1, (int) ($per_page ?? 10));
$__total = (int) ($total ?? 0);
$__pages = max(1, (int) ceil($__total / $__per));
$__page  = max(1, min((int) ($page ?? 1), $__pages));
$__from  = $__total > 0 ? ($__page - 1) * $__per + 1 : 0;
$__to    = min($__page * $__per, $__total);

$__w_start = max(1, $__page - 2);
$__w_end   = min($__pages, $__page + 2);
?>
<div class="d-flex justify-content-between align-items-center px-1 py-3 flex-wrap gap-2">
    <span class="text-secondary">แสดง <?php echo number_format($__from) ?>-<?php echo number_format($__to) ?> จาก <?php echo number_format($__total) ?> รายการ</span>
    <nav aria-label="pagination">
        <ul class="pagination mb-0">
            <li class="page-item <?php echo $__page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0);" onclick="GetData(1)">หน้าแรก</a>
            </li>
            <li class="page-item <?php echo $__page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0);" onclick="GetData(<?php echo $__page - 1 ?>)">ก่อนหน้า</a>
            </li>

            <?php if ($__w_start > 1): ?>
                <li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="GetData(1)">1</a></li>
                <?php if ($__w_start > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($__p = $__w_start; $__p <= $__w_end; $__p++): ?>
                <li class="page-item <?php echo $__p == $__page ? 'active' : '' ?>">
                    <a class="page-link" href="javascript:void(0);" onclick="GetData(<?php echo $__p ?>)"><?php echo $__p ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($__w_end < $__pages): ?>
                <?php if ($__w_end < $__pages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="GetData(<?php echo $__pages ?>)"><?php echo $__pages ?></a></li>
            <?php endif; ?>

            <li class="page-item <?php echo $__page >= $__pages ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0);" onclick="GetData(<?php echo $__page + 1 ?>)">ถัดไป</a>
            </li>
            <li class="page-item <?php echo $__page >= $__pages ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0);" onclick="GetData(<?php echo $__pages ?>)">หน้าสุดท้าย</a>
            </li>
        </ul>
    </nav>
</div>
