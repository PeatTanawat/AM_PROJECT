<?php
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    use App\Utility\Auth;
    use App\Database\Connection;
    use App\Utility\AwsS3;

    $input = json_decode(file_get_contents('php://input'), true);
    $user  = $input['user'] ?? [];

    if (empty($user)) {
        $currentUser = Auth::getUser();
        if ($currentUser && !empty($currentUser->user_id)) {
            $db_instance = new Connection();
            $pdo_connect = $db_instance->getPdo();
            if ($pdo_connect) {
                $stmt = $pdo_connect->prepare("SELECT * FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1");
                $stmt->execute([':id' => $currentUser->user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $stmt->closeCursor();
            }
        }
    }

    $citizen_id    = $user['user_citizen_id'] ?? '';
    $expire_date   = $user['id_card_expiry_date'] ?? '';
    $id_card_image = $user['id_card_image'] ?? '';
    $current_photo = $user['current_photo'] ?? '';

    $id_card_url   = !empty($id_card_image) ? AwsS3::getFileUrl($id_card_image) : '';
    $current_photo_url = !empty($current_photo) ? AwsS3::getFileUrl($current_photo) : '';

    $citizen_label = 'อัพโหลดภาพถ่ายบัตรประชาชน (ใช้ไฟล์รูปภาพ ไม่รองรับ PDF)';
    if (! empty($id_card_url)) {
        $citizen_label = 'อัพโหลดภาพถ่ายบัตรประชาชนใหม่ (มีภาพเดิมอยู่ในระบบแล้ว)';
    }

    $avatar_label = 'อัพโหลดภาพภาพถ่ายปัจจุบัน (ใช้ไฟล์รูปภาพ ไม่รองรับ PDF)';
    if (! empty($current_photo_url)) {
        $avatar_label = 'อัพโหลดภาพถ่ายปัจจุบันใหม่ (มีภาพเดิมอยู่ในระบบแล้ว)';
    }

    $has_expire = ! empty($expire_date);

    $is_expired = false;
    if ($has_expire) {
    $today = date('Y-m-d');
    if ($expire_date < $today) {
        $is_expired = true;
    }
    }
?>

<!-- Header -->
<div class="modal-header border-0 pb-0">
    <h5 class="modal-title fw-bold px-3 pt-3" style="font-size: 1.35rem; color: #1e293b; font-family: 'Prompt', sans-serif;">
        ยืนยันตัวตนด้วยบัตรประจำตัวประชาชน
    </h5>
    <button type="button" class="btn-close me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- Body -->
<div class="modal-body px-4 pb-4" style="font-family: 'Prompt', sans-serif;">

    <!-- Info Warning Box -->
    <div class="mb-4 p-3" style="background-color: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
        <p class="m-0" style="font-size: 0.9rem; color: #1d4ed8; line-height: 1.6; font-weight: 400;">
            ข้อมูลเลขบัตรประจำตัวประชาชน ไฟล์ภาพบัตรประจำตัวประชาชน และไฟล์ภาพถ่ายปัจจุบันจะถูกบันทึกเอาไว้เพื่อการยืนยันตัวตนผู้เรียน/สอบเท่านั้น และข้อมูลจะถูกเก็บเอาไว้เป็นความลับไม่มีการเปิดเผยต่อสาธารณะ
        </p>
    </div>

    <!-- Form -->
    <form id="formIdentityVerify" enctype="multipart/form-data">

        <!-- เลขประจำตัวประชาชน -->
        <div class="d-flex align-items-center mb-3">
            <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                <i class="bi bi-person-vcard-fill"></i>
            </div>
            <div class="flex-grow-1">
                <input type="text" name="citizen_id" id="citizen_id" class="form-control" placeholder="เลขประจำตัวประชาชน"
                       value="<?php echo htmlspecialchars($citizen_id); ?>"
                       style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 12px 20px; font-size: 0.95rem; outline: none; box-shadow: none;" required>
            </div>
        </div>

        <!-- วันหมดอายุ -->
        <div class="d-flex align-items-center mb-3">
            <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                <i class="bi bi-calendar-event-fill"></i>
            </div>
            <div class="flex-grow-1">
                <input type="text" name="expire_date" id="expire_date" class="form-control custom-rounded-input <?php echo $is_expired ? 'is-expired-border' : ''; ?>" placeholder="วันหมดอายุ (วว/ดด/ปปปป)"
                       value="<?php echo htmlspecialchars($expire_date); ?>" required>
                <?php if ($is_expired): ?>
                    <div class="text-danger mt-1 ms-2" style="font-size: 0.85rem;">
                        <i class="bi bi-exclamation-circle-fill"></i> บัตรประจำตัวประชาชนหมดอายุแล้ว
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- อัพโหลดภาพถ่ายบัตรประชาชน -->
        <div class="mb-3">
            <div class="d-flex align-items-center">
                <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                    <i class="bi bi-paperclip"></i>
                </div>
                <div class="flex-grow-1 position-relative">
                    <input type="file" name="file_citizen" id="file_citizen" accept="image/*" class="form-control d-none" <?php echo empty($id_card_url) ? 'required' : ''; ?>>
                    <label id="label_file_citizen" for="file_citizen" class="form-control m-0 text-muted"
                           style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 12px 20px; font-size: 0.95rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                        <?php echo $citizen_label; ?>
                    </label>
                </div>
            </div>
            <!-- Image Preview -->
            <div class="mt-2 text-center" style="display: <?php echo !empty($id_card_url) ? 'block' : 'none'; ?>;" id="preview_citizen_container">
                <div class="fw-bold mb-1" style="color: #334155; font-size: 0.95rem;">รูปภาพบัตรประจำตัวประชาชน</div>
                <div id="status_citizen" class="small mb-1 fw-bold text-secondary"><?php echo !empty($id_card_url) ? 'ภาพเดิมในระบบ' : ''; ?></div>
                <img id="preview_citizen" data-original-src="<?php echo !empty($id_card_url) ? htmlspecialchars($id_card_url) : ''; ?>" src="<?php echo !empty($id_card_url) ? htmlspecialchars($id_card_url) : ''; ?>" alt="ภาพบัตรประชาชน" class="img-fluid rounded border" style="max-height: 140px; object-fit: contain; padding: 4px;">
            </div>
        </div>

        <!-- อัพโหลดภาพภาพถ่ายปัจจุบัน -->
        <div class="mb-4">
            <div class="d-flex align-items-center">
                <div class="me-3 text-center" style="width: 32px; color: #64748b; font-size: 1.4rem;">
                    <i class="bi bi-paperclip"></i>
                </div>
                <div class="flex-grow-1 position-relative">
                    <input type="file" name="file_avatar" id="file_avatar" accept="image/*" class="form-control d-none" <?php echo empty($current_photo_url) ? 'required' : ''; ?>>
                    <label id="label_file_avatar" for="file_avatar" class="form-control m-0 text-muted"
                           style="background-color: #f1f5f9; border: none; border-radius: 50px; padding: 12px 20px; font-size: 0.95rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                        <?php echo $avatar_label; ?>
                    </label>
                </div>
            </div>
            <!-- Image Preview -->
            <div class="mt-2 text-center" style="display: <?php echo !empty($current_photo_url) ? 'block' : 'none'; ?>;" id="preview_avatar_container">
                <div class="fw-bold mb-1" style="color: #334155; font-size: 0.95rem;">รูปภาพถ่ายปัจจุบัน</div>
                <div id="status_avatar" class="small mb-1 fw-bold text-secondary"><?php echo !empty($current_photo_url) ? 'ภาพเดิมในระบบ' : ''; ?></div>
                <img id="preview_avatar" data-original-src="<?php echo !empty($current_photo_url) ? htmlspecialchars($current_photo_url) : ''; ?>" src="<?php echo !empty($current_photo_url) ? htmlspecialchars($current_photo_url) : ''; ?>" alt="ภาพถ่ายปัจจุบัน" class="img-fluid rounded border" style="max-height: 140px; object-fit: contain; padding: 4px;">
            </div>
        </div>

        <!-- Action Buttons -->
         <div class="d-flex justify-content-end align-items-center gap-3">
            <button type="button" class="btn border-0" data-bs-dismiss="modal" style="color: #4a66ac; font-weight: 500; font-size: 1rem; background: none; font-family: 'Prompt', sans-serif;">
                ยกเลิก
            </button>
            <button type="button" class="btn text-white px-4 py-2"
            style="background-color: #3b5998; border-radius: 8px; font-weight: 500; font-size: 1rem; font-family: 'Prompt', sans-serif; min-width: 110px;"
            onclick="editIdentityVerify();">
                บันทึก
            </button>
        </div>

    </form>
</div>

<style>
    /* แก้ไขปัญหา Modal ไม่ Scroll เนื่องจาก div #showModal ขวางโครงสร้าง */
    #showModal, #showModal2 {
        display: flex;
        flex-direction: column;
        max-height: 100%;
        height: 100%;
    }
    #showModal .modal-body, #showModal2 .modal-body {
        overflow-y: auto;
        max-height: calc(85vh - 120px);
    }
    
    .flatpickr-calendar {
        z-index: 1000000 !important;
    }
    .flatpickr-wrapper {
        width: 100%;
        display: block;
    }
    .custom-rounded-input {
        background-color: #f1f5f9 !important;
        border: none !important;
        border-radius: 50px !important;
        padding: 12px 20px !important;
        font-size: 0.95rem !important;
        outline: none !important;
        box-shadow: none !important;
        width: 100% !important;
    }
    .custom-rounded-input.is-expired-border {
        border: 2px solid #dc3545 !important;
    }
</style>
<script>
    // Use setTimeout so the DOM is fully ready inside the modal,
    // and use static: true so the calendar is appended directly next to the input
    // bypassing all modal z-index and focus trap issues.
    setTimeout(function() {
        if (typeof InitThaiDatepicker === "function") {
            InitThaiDatepicker("#expire_date", { static: true });
        } else if (typeof flatpickr !== "undefined") {
            flatpickr("#expire_date", { 
                dateFormat: "Y-m-d", 
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: true,
                static: true
            });
        }
    }, 200);

    window.previewImageEdit = function(input, labelId, previewId, statusId, defaultText) {
        const label = document.getElementById(labelId);
        const preview = document.getElementById(previewId);
        const container = document.getElementById(previewId + '_container');
        const statusEl = document.getElementById(statusId);
        const originalSrc = preview.getAttribute('data-original-src');

        if (input.files && input.files[0]) {
            label.innerText = input.files[0].name;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                if (statusEl) {
                    if (originalSrc) {
                        statusEl.innerText = 'ภาพใหม่';
                        statusEl.className = 'small mb-1 fw-bold text-primary';
                    } else {
                        statusEl.innerText = '';
                    }
                }
                container.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            label.innerText = defaultText;
            if (originalSrc) {
                preview.src = originalSrc;
                if (statusEl) {
                    statusEl.innerText = 'ภาพเดิมในระบบ';
                    statusEl.className = 'small mb-1 fw-bold text-secondary';
                }
                container.style.display = 'block';
            } else {
                preview.src = '';
                if (statusEl) {
                    statusEl.innerText = '';
                }
                container.style.display = 'none';
            }
        }
    };
    
    // ผูก Event แบบ Delegation ป้องกันปัญหาจาก Modal ที่โหลดผ่าน AJAX
    $(document).off('change', '#file_citizen').on('change', '#file_citizen', function() {
        window.previewImageEdit(this, 'label_file_citizen', 'preview_citizen', 'status_citizen', '<?php echo htmlspecialchars($citizen_label); ?>');
    });

    $(document).off('change', '#file_avatar').on('change', '#file_avatar', function() {
        window.previewImageEdit(this, 'label_file_avatar', 'preview_avatar', 'status_avatar', '<?php echo htmlspecialchars($avatar_label); ?>');
    });

    // ป้องกันปัญหา SweetAlert2 ปิดแล้วเคลียร์ style ออก
    setTimeout(function() {
        if ($('.modal.show').length > 0) {
            document.body.style.setProperty('overflow-y', 'hidden', 'important');
            document.body.style.setProperty('overflow', 'hidden', 'important');
            document.documentElement.style.setProperty('overflow-y', 'hidden', 'important');
            document.documentElement.style.setProperty('overflow', 'hidden', 'important');
        }
    }, 300);

    $('.modal').on('hidden.bs.modal', function () {
        if ($('.modal.show').length === 0) {
            document.body.style.removeProperty('overflow-y');
            document.body.style.removeProperty('overflow');
            document.documentElement.style.removeProperty('overflow-y');
            document.documentElement.style.removeProperty('overflow');
        }
    });
</script>
