<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use App\Utility\Auth;
use App\Database\Connection;

Auth::requireUserToken();

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$id = isset($data['id']) ? (int) $data['id'] : (int) ($_POST['id'] ?? ($_POST['bank_id'] ?? 0));

$company_bank = null;

try {
    $pdo = (new Connection())->getPdo();
    if ($pdo && $id > 0) {
        $stmt = $pdo->prepare("SELECT id, bank_id, account_name, account_no, active_status FROM tbl_company_banks WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $company_bank = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    }
} catch (\Throwable $e) {
    error_log("GetModalEdit Bank Error: " . $e->getMessage());
}

$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$bank_id = (int) ($company_bank['bank_id'] ?? 0);
$account_name = (string) ($company_bank['account_name'] ?? '');
$account_no = (string) ($company_bank['account_no'] ?? '');
$active_status = (string) ($company_bank['active_status'] ?? '1');
?>
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 0.75rem !important;
        color: var(--bs-body-color) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<div class="modal-header">
    <h5 class="modal-title" id="myModalLabel">แก้ไขข้อมูลบัญชีธนาคาร</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <form id="formEditBank" method="POST">
        <input type="hidden" id="edit_id" name="id" value="<?php echo (int) $id; ?>">

        <div class="mb-3">
            <label for="edit_bank_id" class="form-label fw-semibold">เลือกธนาคาร</label>
            <select class="form-select" id="edit_bank_id" name="bank_id">
                <option value="">กำลังโหลดรายชื่อธนาคาร...</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="edit_account_name" class="form-label fw-semibold">กรอกชื่อบัญชี</label>
            <input type="text" class="form-control" id="edit_account_name" name="account_name" value="<?php echo $esc($account_name); ?>" placeholder="กรอกชื่อบัญชีธนาคาร">
        </div>
        <div class="mb-3">
            <label for="edit_account_no" class="form-label fw-semibold">กรอกเลขที่บัญชี</label>
            <input type="text" class="form-control" id="edit_account_no" name="account_no" value="<?php echo $esc($account_no); ?>" placeholder="กรอกเลขที่บัญชี">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold d-block">สถานะ</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="active_status" id="edit_status_active" value="1" <?php echo $active_status === '1' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="edit_status_active">เปิดใช้งาน</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="active_status" id="edit_status_inactive" value="0" <?php echo $active_status === '0' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="edit_status_inactive">ปิดใช้งาน</label>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer p-3">
    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
    <button type="button" class="btn btn-primary px-4 BtnSubmitForm" onclick="UpdateBank()">
        บันทึกข้อมูล
    </button>
</div>

<script>
    (function () {
        if (typeof GetBankMapping === 'function') {
            GetBankMapping('#edit_bank_id', '<?php echo (int) $bank_id; ?>');
        }
    })();

    function UpdateBank() {
        let bank_id = $('#edit_bank_id').val();
        let account_name = $('#edit_account_name').val().trim();
        let account_no = $('#edit_account_no').val().trim();

        if (!bank_id) {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณาเลือกธนาคาร</span>',
                icon: "error",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        if (account_name === "") {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณากรอกชื่อ account</span>',
                icon: "error",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        if (account_no === "") {
            Swal.fire({
                title: "แจ้งเตือน",
                html: '<span class="fw-bold text-danger">กรุณากรอกเลขที่บัญชี</span>',
                icon: "error",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
            return;
        }

        var formData = new FormData($('#formEditBank')[0]);
        formData.append('request_state', 'list_bank_setting');
        formData.append('request_function', 'update_bank');

        $.ajax({
            type: "POST",
            url: "core.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function () {
                if (typeof ShowLoadingButton === 'function') {
                    ShowLoadingButton(".BtnSubmitForm");
                }
            },
            success: function (response) {
                if (response.result == 1) {
                    $("#myModal").modal('hide');
                    Swal.fire({
                        title: "สำเร็จ",
                        html: '<span class="fw-bold text-success">' + response.msg + '</span>',
                        icon: "success",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    }).then(() => {
                        LoadData();
                    });
                } else {
                    Swal.fire({
                        title: "แจ้งเตือน",
                        html: '<span class="fw-bold text-danger">' + response.msg + '</span>',
                        icon: "error",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            },
            complete: function () {
                if (typeof HideLoadingButton === 'function') {
                    HideLoadingButton(".BtnSubmitForm");
                }
            },
            error: function (jqXHR, exception) {
                ShowErrorAjax(jqXHR, exception);
            }
        });
    }
</script>