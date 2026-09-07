<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Database\Connection;
use App\Utility\Auth;

$input = json_decode(file_get_contents('php://input'), true);
$user = $input['user'] ?? [];

if (empty($user)) {
    $currentUser = Auth::getUser();
    if ($currentUser && !empty($currentUser->user_id)) {
        $db_instance = new Connection();
        $pdo_connect = $db_instance->getPdo();
        if ($pdo_connect) {
            $stmt = $pdo_connect->prepare('SELECT * FROM tbl_user WHERE user_id = :id AND delete_at IS NULL LIMIT 1');
            $stmt->execute([':id' => $currentUser->user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $stmt->closeCursor();
        }
    }
}

$firstname = $user['user_firstname'] ?? '';
$lastname = $user['user_lastname'] ?? '';
$fullname = trim($firstname . ' ' . $lastname);
$phone = !empty($user['user_phone']) ? $user['user_phone'] : '-';
$cpd_no = !empty($user['user_cpd_no']) ? $user['user_cpd_no'] : '-';
$cpa_no = !empty($user['user_cpa_no']) ? $user['user_cpa_no'] : '-';
$citizen_id = !empty($user['user_citizen_id']) ? $user['user_citizen_id'] : '-';
$email = $user['user_email'] ?? '-';
$email_status = $user['email_status'] ?? 0;
$identity_verified = $user['identity_verified'] ?? 0;
$approver_citizen = $user['approver_citizen'] ?? 0;
$remark = $user['remark'] ?? '-';
$line_token = $user['line_token'] ?? '';
$id_card_image_raw = $user['id_card_image'] ?? '';
$id_card_image = !empty($id_card_image_raw) ? \App\Utility\AwsS3::getFileUrl($id_card_image_raw) : '';

// เช็ค id_card_expiry_date
$id_card_expiry_date = $user['id_card_expiry_date'] ?? '';
if (!empty($id_card_expiry_date)) {
    $today = date('Y-m-d');
    if ($id_card_expiry_date < $today && $identity_verified != 0) {
        $identity_verified = 0;
        $approver_citizen = 0;
        $remark = 'บัตรประจำตัวประชาชนของคุณหมดอายุแล้ว กรุณาทำการยืนยันตัวตนใหม่อีกครั้ง';
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
                <?php if ($cpd_no !== '-' && $cpa_no !== '-'): ?>
                <div class="info-row">
                    <div class="info-label">เลขที่ผู้ทำบัญชี :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($cpd_no) ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">เลขที่ผู้สอบบัญชี :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($cpa_no) ?></span></div>
                </div>
                <?php elseif ($cpa_no !== '-'): ?>
                <div class="info-row">
                    <div class="info-label">เลขที่ผู้สอบบัญชี :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($cpa_no) ?></span></div>
                </div>
                <?php else: ?>
                <div class="info-row">
                    <div class="info-label">เลขที่ผู้ทำบัญชี :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($cpd_no) ?></span></div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="info-label">เลขประจำตัวประชาชน :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($citizen_id) ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">อีเมล :</div>
                    <div class="info-value"><span><?php echo htmlspecialchars($email) ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">บัญชี Line :</div>
                    <div class="info-value">
                        <?php if (empty($line_token)): ?>
                            <a href="javascript:void(0)" onclick="linkLineAccount()" class="btn text-white btn-sm" style="background-color: #00B900; border-radius: 50px; padding: 5px 15px;"><i class="bi bi-line"></i> เชื่อมต่อบัญชี LINE</a>
                        <?php else: ?>
                            <span class="text-success" style="font-weight: 500; margin-right: 10px;"><i class="bi bi-check-circle-fill"></i> เชื่อมต่อแล้ว</span>
                            <a href="javascript:void(0)" onclick="unlinkLineAccount()" style="border-radius: 50px; padding: 5px 15px; color: #ff0000ff;">ยกเลิกเชื่อมต่อ</a>
                        <?php endif; ?>
                    </div>
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
                                <?php if ($remark != '-' && !empty($remark)): ?>
                                    <div class="verify-title" style="color: #ef4444;">การยืนยันตัวตนไม่สำเร็จ</div>
                                    <p class="verify-text">
                                        การยืนยันตัวตนของคุณไม่ถูกต้อง กรุณาทำการยืนยันตัวตนใหม่
                                    </p>
                                <?php else: ?>
                                    <div class="verify-title" style="color: #ef4444;">คุณยังไม่ได้ทำการยืนยันตัวตน</div>
                                    <p class="verify-text">
                                        กรุณาทำการยืนยันตัวตนเพื่อเข้าถึงฟีเจอร์ต่างๆ
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn text-decoration-none d-inline-block text-white"
                         style="background-color: #3b5998; border-radius: 50px; padding: 10px 24px; font-weight: 500; font-size: 0.95rem; white-space: nowrap;"
                         type="button" onclick="Getmodal_editverifly()">ยืนยันตัวตน</button>
                    <?php endif; ?>
                    <?php if ($identity_verified == 2 && !empty($id_card_image)): ?>
                        <button class="btn text-decoration-none d-inline-block text-white ms-auto"
                         style="background-color: #64748b; border-radius: 50px; padding: 10px 24px; font-weight: 500; font-size: 0.95rem; white-space: nowrap;"
                         type="button" onclick="showIdCardModal('<?php echo htmlspecialchars($id_card_image); ?>')"><i class="bi bi-image"></i> แสดงรูปบัตรประชาชน</button>
                    <?php endif; ?>
                </div>
                
                <?php if ($identity_verified == 0 && $remark != '-' && !empty($remark)): ?>
                    <div class="mt-3 px-3 py-2 text-danger" style="font-size: 0.95rem; background-color: #fff;">
                        หมายเหตุ : <?php echo htmlspecialchars($remark); ?>
                    </div>
                <?php endif; ?>
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

<!-- โหลด LIFF SDK สำหรับการผูกบัญชี LINE -->
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
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

function linkLineAccount() {
    liff.init({
        liffId: "2011178285-krDl5as1" // ใช้ liffId ล่าสุดของคุณ
    }, () => {
        if (liff.isLoggedIn()) {
            performLinkLine();
        } else {
            liff.login();
        }
    }, err => {
        console.error("LIFF Init Error: ", err);
        Swal.fire('แจ้งเตือน', 'เกิดข้อผิดพลาดในการเชื่อมต่อระบบ LINE', 'error');
    });
}

function performLinkLine() {
    liff.getProfile().then(profile => {
        const line_token = profile.userId;
        if (!line_token) {
            Swal.fire('แจ้งเตือน', 'ไม่สามารถดึงข้อมูล User ID จาก Line ได้', 'error');
            return;
        }

        Swal.fire({
            title: 'กำลังเชื่อมต่อ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "line_auth",
                request_function: "link_line",
                line_token: line_token
            },
            dataType: "json",
            success: function (response) {
                if (response.result == 1) {
                    Swal.fire({
                        title: "สำเร็จ",
                        text: response.msg,
                        icon: "success",
                        confirmButtonText: "ตกลง"
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: "ไม่สำเร็จ",
                        text: response.msg,
                        icon: "error",
                        confirmButtonText: "ตกลง"
                    });
                }
            },
            error: function () {
                Swal.fire('แจ้งเตือน', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        });
    }).catch(err => {
        console.error("LIFF getProfile failed:", err);
        Swal.fire('แจ้งเตือน', 'ไม่สามารถดึงข้อมูลบัญชี LINE ได้', 'error');
    });
}

function unlinkLineAccount() {
    Swal.fire({
        title: 'ยืนยันการยกเลิกเชื่อมต่อ?',
        text: "คุณต้องการยกเลิกการเชื่อมต่อบัญชี LINE หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ตกลง, ยกเลิกการเชื่อมต่อ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังดำเนินการ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "core.php",
                data: {
                    request_state: "line_auth",
                    request_function: "unlink_line"
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 1) {
                        Swal.fire({
                            title: "สำเร็จ",
                            text: response.msg,
                            icon: "success",
                            confirmButtonText: "ตกลง"
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: "ไม่สำเร็จ",
                            text: response.msg,
                            icon: "error",
                            confirmButtonText: "ตกลง"
                        });
                    }
                },
                error: function () {
                    Swal.fire('แจ้งเตือน', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
                }
            });
        }
    });
}
</script>
