<?php
// core/mainAddress/SaveAddress.php

use App\Utility\Auth;
use App\Utility\Response;
use App\Database\Connection;

try {
    // 1. ตรวจสอบสิทธิ์การเข้าสู่ระบบและดึง user_id
    $access_token = Auth::requireUserToken();
    $user_id = $access_token->user_id ?? null;

    if (!$user_id) {
        Response::json(0, 'Unauthorized', null);
    }

    // 2. รับและตรวจสอบประเภทของที่อยู่ (บุคคลธรรมดา = 1, นิติบุคคล = 2)
    $raw_type = isset($_POST['addr_type']) ? trim($_POST['addr_type']) : 'typeIndividual';
    $addr_type = 1;

    $addr_name = '';
    $addr_tax_id = '';
    $addr_branch = null;
    $addr_branch_name = null;
    $main_branch = 0;

    if ($raw_type === 'typeIndividual' || $raw_type === 'individual' || $raw_type === '1') {
        $addr_type = 1;
        $addr_name = isset($_POST['indiv_name']) ? trim($_POST['indiv_name']) : '';
        $addr_tax_id = isset($_POST['indiv_tax_id']) ? trim($_POST['indiv_tax_id']) : '';
        
        if (empty($addr_name)) {
            Response::json(0, 'กรุณาระบุชื่อ - นามสกุล', null);
        }
        if (empty($addr_tax_id)) {
            Response::json(0, 'กรุณาระบุเลขที่บัตรประชาชน', null);
        }
        if (strlen($addr_tax_id) !== 13) {
            Response::json(0, 'เลขที่บัตรประชาชนต้องมี 13 หลัก', null);
        }
    } else if ($raw_type === 'typeCorporate' || $raw_type === 'corporate' || $raw_type === '2') {
        $addr_type = 2;
        $addr_name = isset($_POST['corp_name']) ? trim($_POST['corp_name']) : '';
        $addr_tax_id = isset($_POST['corp_tax_id']) ? trim($_POST['corp_tax_id']) : '';
        $is_headoffice = isset($_POST['corp_is_headoffice']) ? (int)$_POST['corp_is_headoffice'] : 0;
        
        if (empty($addr_name)) {
            Response::json(0, 'กรุณาระบุชื่อบริษัท', null);
        }
        if (empty($addr_tax_id)) {
            Response::json(0, 'กรุณาระบุหมายเลขประจำตัวผู้เสียภาษี', null);
        }
        if (strlen($addr_tax_id) !== 13) {
            Response::json(0, 'หมายเลขประจำตัวผู้เสียภาษีต้องมี 13 หลัก', null);
        }

        $corp_branch_code = isset($_POST['corp_branch_code']) ? trim($_POST['corp_branch_code']) : '';
        if (empty($corp_branch_code)) {
            Response::json(0, 'กรุณาระบุรหัสสาขา', null);
        }

        if ($is_headoffice === 1) {
            $addr_branch = $corp_branch_code;
            $addr_branch_name = 'สำนักงานใหญ่';
            $main_branch = 1;
        } else {
            $corp_branch_name = isset($_POST['corp_branch_name']) ? trim($_POST['corp_branch_name']) : '';
            if (empty($corp_branch_name)) {
                Response::json(0, 'กรุณาระบุชื่อสาขาของบริษัท', null);
            }
            $addr_branch = $corp_branch_code;
            $addr_branch_name = $corp_branch_name;
        }
    } else {
        Response::json(0, 'ประเภทที่อยู่ไม่ถูกต้อง', null);
    }

    // 3. ดึงฟิลด์ข้อมูลที่อยู่ร่วมกัน
    $addr_phone = isset($_POST['addr_phone']) ? trim($_POST['addr_phone']) : '';
    $addr_detail = isset($_POST['addr_detail']) ? trim($_POST['addr_detail']) : '';
    $addr_province = isset($_POST['addr_province']) ? trim($_POST['addr_province']) : '';
    $addr_district = isset($_POST['addr_district']) ? trim($_POST['addr_district']) : '';
    $addr_subdistrict = isset($_POST['addr_subdistrict']) ? trim($_POST['addr_subdistrict']) : '';
    $addr_zipcode = isset($_POST['addr_zipcode']) ? trim($_POST['addr_zipcode']) : '';

    // ตรวจสอบความถูกต้องของที่อยู่หลัก
    if (empty($addr_phone)) {
        Response::json(0, 'กรุณาระบุเบอร์โทรศัพท์', null);
    }
    if (empty($addr_detail)) {
        Response::json(0, 'กรุณาระบุที่อยู่หลัก', null);
    }
    if (empty($addr_province)) {
        Response::json(0, 'กรุณาเลือกจังหวัด', null);
    }
    if (empty($addr_district)) {
        Response::json(0, 'กรุณาเลือกเขต/อำเภอ', null);
    }
    if (empty($addr_subdistrict)) {
        Response::json(0, 'กรุณาเลือกแขวง/ตำบล', null);
    }
    if (empty($addr_zipcode)) {
        Response::json(0, 'กรุณาเลือกหรือใส่รหัสไปรษณีย์', null);
    }

    // 4. เชื่อมต่อฐานข้อมูล
    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 5. ตรวจสอบเงื่อนไขว่าเป็นการแก้ไข (Update) หรือเพิ่มใหม่ (Insert)
    $addr_id = isset($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;

    if ($addr_id > 0) {
        // ตรวจสอบว่าที่อยู่นี้มีอยู่จริงและเป็นของผู้ใช้รายนี้
        $stmt_check = $pdo_connect->prepare("SELECT addr_id FROM tbl_user_address WHERE addr_id = :addr_id AND addr_user_id = :user_id AND delete_at IS NULL LIMIT 1");
        $stmt_check->execute([':addr_id' => $addr_id, ':user_id' => $user_id]);
        $existing_address = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $stmt_check->closeCursor();

        if (!$existing_address) {
            Response::json(0, 'ไม่พบที่อยู่ที่ต้องการแก้ไข', null);
        }

        // ทำการ Update ข้อมูลเดิม
        $sql = "UPDATE tbl_user_address SET 
            addr_type = :addr_type,
            addr_name = :addr_name,
            addr_tax_id = :addr_tax_id,
            addr_branch = :addr_branch,
            addr_branch_name = :addr_branch_name,
            main_branch = :main_branch,
            addr_phone = :addr_phone,
            addr_detail = :addr_detail,
            addr_subdistrict = :addr_subdistrict,
            addr_district = :addr_district,
            addr_province = :addr_province,
            addr_zipcode = :addr_zipcode
            WHERE addr_id = :addr_id AND addr_user_id = :user_id";
        
        $stmt_update = $pdo_connect->prepare($sql);
        $result = $stmt_update->execute([
            ':addr_type'        => $addr_type,
            ':addr_name'        => $addr_name,
            ':addr_tax_id'      => $addr_tax_id,
            ':addr_branch'      => $addr_branch,
            ':addr_branch_name' => $addr_branch_name,
            ':main_branch'      => $main_branch,
            ':addr_phone'       => $addr_phone,
            ':addr_detail'      => $addr_detail,
            ':addr_subdistrict' => $addr_subdistrict,
            ':addr_district'    => $addr_district,
            ':addr_province'    => $addr_province,
            ':addr_zipcode'     => $addr_zipcode,
            ':addr_id'          => $addr_id,
            ':user_id'          => $user_id
        ]);
        $stmt_update->closeCursor();
    } else {
        // ตรวจสอบว่ามีที่อยู่อื่นอยู่แล้วหรือไม่ ถ้ายังไม่มีเลยให้ตั้งค่าเป็นค่าเริ่มต้น (addr_is_default = 1)
        $stmt_check = $pdo_connect->prepare("SELECT addr_id FROM tbl_user_address WHERE addr_user_id = :user_id AND delete_at IS NULL LIMIT 1");
        $stmt_check->execute([':user_id' => $user_id]);
        $has_any = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $stmt_check->closeCursor();

        $addr_is_default = $has_any ? 0 : 1;

        // ทำการ Insert ข้อมูลใหม่
        $sql = "INSERT INTO tbl_user_address (
            addr_user_id,
            addr_type,
            addr_name,
            addr_tax_id,
            addr_branch,
            addr_branch_name,
            main_branch,
            addr_phone,
            addr_detail,
            addr_subdistrict,
            addr_district,
            addr_province,
            addr_zipcode,
            addr_is_default
        ) VALUES (
            :addr_user_id,
            :addr_type,
            :addr_name,
            :addr_tax_id,
            :addr_branch,
            :addr_branch_name,
            :main_branch,
            :addr_phone,
            :addr_detail,
            :addr_subdistrict,
            :addr_district,
            :addr_province,
            :addr_zipcode,
            :addr_is_default
        )";
        
        $stmt_insert = $pdo_connect->prepare($sql);
        $result = $stmt_insert->execute([
            ':addr_user_id'     => $user_id,
            ':addr_type'        => $addr_type,
            ':addr_name'        => $addr_name,
            ':addr_tax_id'      => $addr_tax_id,
            ':addr_branch'      => $addr_branch,
            ':addr_branch_name' => $addr_branch_name,
            ':main_branch'      => $main_branch,
            ':addr_phone'       => $addr_phone,
            ':addr_detail'      => $addr_detail,
            ':addr_subdistrict' => $addr_subdistrict,
            ':addr_district'    => $addr_district,
            ':addr_province'    => $addr_province,
            ':addr_zipcode'     => $addr_zipcode,
            ':addr_is_default'  => $addr_is_default
        ]);
        $stmt_insert->closeCursor();
    }

    if ($result) {
        Response::json(1, 'บันทึกที่อยู่ออกใบกำกับภาษีเรียบร้อยแล้ว', null);
    } else {
        Response::json(0, 'เกิดข้อผิดพลาดในการบันทึกข้อมูลที่อยู่', null);
    }

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดระบบ: ' . $e->getMessage(), null);
}
