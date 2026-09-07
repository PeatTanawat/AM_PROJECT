<?php
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    use App\Utility\Auth;
    use App\Database\Connection;

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

    $firstname         = $user['user_firstname'] ?? '';
    $lastname          = $user['user_lastname'] ?? '';
    $fullname          = trim($firstname . ' ' . $lastname);
    $phone             = ! empty($user['user_phone']) ? $user['user_phone'] : '-';
    $cpd_no            = ! empty($user['user_cpd_no']) ? $user['user_cpd_no'] : '-';
    $citizen_id        = ! empty($user['user_citizen_id']) ? $user['user_citizen_id'] : '-';
    $email             = $user['user_email'] ?? '-';
    $email_status      = $user['email_status'] ?? 0;
    $identity_verified = $user['identity_verified'] ?? 0;
    $approver_citizen  = $user['approver_citizen'] ?? 0;
    $remark            = $user['remark'] ?? '-';
    $id_card_image     = $user['id_card_image'] ?? '';

    // เช็ค id_card_expiry_date
    $id_card_expiry_date = $user['id_card_expiry_date'] ?? '';
    if (! empty($id_card_expiry_date)) {
    $today = date('Y-m-d');
    if ($id_card_expiry_date < $today && $identity_verified != 0) {
        $identity_verified = 0;
        $approver_citizen  = 0;
        $remark            = 'บัตรประจำตัวประชาชนของคุณหมดอายุแล้ว กรุณาทำการยืนยันตัวตนใหม่อีกครั้ง';
    }
    }
?>
<div class="col-12" style="width: 100%; max-width: 100%;">
    <div class="content-card">
        <div class="tab-content" id="mainTabContent">

            <div class="tab-pane show active" id="tab-info">
                <h3 class="tab-title"><i class="bi bi-person-vcard-fill"></i> ข้อมูลผู้ใช้</h3>

                <div class="info-row">
                    <div class="info-label">ชื่อ-นามสกุล :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($fullname) ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">เบอร์โทรศัพท์ :</div>
                    <div class="info-value">
                        <span class="d-flex"><?php echo htmlspecialchars($phone) ?><p style="cursor: pointer;margin-left: 10px; color: #4a66ac;" id="btn-change-phone" onclick="cheangephone()">เปลี่ยน</p></span>
                        <!-- <button class="btn btn-primary" id="btn-change-phone" onclick="cheangephone()">เปลี่ยน</button> -->
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">เลขที่ผู้ทำบัญชี :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($cpd_no) ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">เลขประจำตัวประชาชน :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($citizen_id) ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">อีเมล :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($email) ?></span></div>
                </div>

                <hr>

                <h3 class="tab-title"><i class="bi bi-shield-check"></i> การยืนยันตัวตน</h3>
                <div class="verify-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <?php if ($identity_verified == 2 && $approver_citizen == 2): ?>
                        <div class="d-flex align-items-center gap-3">
                            <svg class="verify-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                                <rect x="16" y="12" width="32" height="40" rx="4" fill="#3b82f6" />
                                <rect x="24" y="8" width="16" height="8" rx="2" fill="#93c5fd" />
                                <line x1="24" y1="28" x2="40" y2="28" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
                                <line x1="24" y1="36" x2="40" y2="36" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
                                <circle cx="44" cy="44" r="14" fill="#22c55e" />
                                <polyline points="38 44 42 48 50 38" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div>
                                <div class="verify-title" style="color: #22c55e;">ยืนยันตัวตนสำเร็จ</div>
                                <p class="verify-text">
                                    คุณสามารถเริ่มเรียนคอร์สเรียนและดำเนินการสอบได้ทันที
                                </p>
                            </div>
                        </div>
                    <?php elseif ($identity_verified == 1 && $approver_citizen == 0): ?>
                        <div class="d-flex align-items-center gap-3">
                            <svg class="verify-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 10h20l10 10v34a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V14a4 4 0 0 1 4-4z" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="2"/>
                                <path d="M38 10v10h10" fill="none" stroke="#cbd5e1" stroke-width="2"/>
                                <line x1="22" y1="26" x2="42" y2="26" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"/>
                                <line x1="22" y1="34" x2="42" y2="34" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"/>
                                <line x1="22" y1="42" x2="32" y2="42" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"/>
                                <circle cx="20" cy="46" r="10" fill="#ffffff" stroke="#f97316" stroke-width="3" />
                                <polyline points="20 41 20 46 25 46" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div>
                                <div class="verify-title" style="color: #f59e0b;">รอดำเนินการ</div>
                                <p class="verify-text">
                                    การยืนยันตัวตนของคุณ อยู่ในระหว่างการดำเนินการตรวจสอบเอกสารจากเจ้าหน้าที่ซึ่งปกติจะใช้เวลาประมาณ 24 ชั่วโมง คุณจะได้รับอีเมลแจ้งเมื่อเจ้าหน้าที่ยืนยันเอกสารสำเร็จ
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-3">
                            <svg class="verify-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                                <rect x="16" y="12" width="32" height="40" rx="4" fill="#b45309" />
                                <rect x="24" y="8" width="16" height="8" rx="2" fill="#d97706" />
                                <line x1="24" y1="42" x2="40" y2="42" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
                                <path d="M32 18 L43 36 H21 Z" fill="#ef4444" stroke="#ef4444" stroke-width="1" stroke-linejoin="round" />
                                <path d="M32 18 L43 36 H21 Z" fill="#ef4444" stroke="#ef4444" stroke-width="1" stroke-linejoin="round" />
                                <text x="32" y="32" fill="#ffffff" font-size="10" font-weight="bold" font-family="Arial, sans-serif" text-anchor="middle">!</text>
                            </svg>
                            <div>
                                <div class="verify-title" style="color: #ef4444;">คุณยังไม่ได้ทำการยืนยันตัวตน</div>
                                <p class="verify-text">
                                <?php echo $remark; ?>
                                </p>
                            </div>
                        </div>
                        <button class="btn text-decoration-none d-inline-block text-white"
                         style="background-color: #3b5998; border-radius: 50px; padding: 10px 24px; font-weight: 500; font-size: 0.95rem; white-space: nowrap;"
                         type="button" onclick="Getmodal_editverifly()">ยืนยันตัวตน</button>
                    <?php endif; ?>
                    <?php if ($identity_verified > 0 || ! empty($id_card_image)): ?>
                        <button class="btn text-decoration-none d-inline-block text-white ms-auto"
                         style="background-color: #64748b; border-radius: 50px; padding: 10px 24px; font-weight: 500; font-size: 0.95rem; white-space: nowrap;"
                         type="button" onclick="showIdCardModal('<?php echo htmlspecialchars($id_card_image); ?>')"><i class="bi bi-image"></i> แสดงรูปบัตรประชาชน</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ดูรูปบัตรประชาชน -->
<div class="modal fade" id="idCardModal" tabindex="-1" aria-labelledby="idCardModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="idCardModalLabel">รูปบัตรประชาชน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="idCardImagePreview" src="" alt="ID Card" class="img-fluid rounded" style="max-height: 70vh;">
      </div>
    </div>
  </div>
</div>

<script>
function showIdCardModal(imageUrl) {
    if (!imageUrl) {
        Swal.fire('แจ้งเตือน', 'ไม่พบรูปบัตรประชาชนในระบบ', 'warning');
        return;
    }
    document.getElementById('idCardImagePreview').src = imageUrl;

    // รองรับทั้ง Bootstrap 5 และ 4
    if (typeof bootstrap !== 'undefined') {
        var myModal = new bootstrap.Modal(document.getElementById('idCardModal'));
        myModal.show();
    } else {
        $('#idCardModal').modal('show');
    }
}
</script>
