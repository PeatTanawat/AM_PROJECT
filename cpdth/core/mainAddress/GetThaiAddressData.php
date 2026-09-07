<?php
// core/mainAddress/GetThaiAddressData.php

use App\Utility\Response;
use App\Database\Connection;

try {
    $db_instance = new Connection();
    $pdo_connect = $db_instance->getPdo();

    if (!$pdo_connect) {
        Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
    }

    // 1. ดึงข้อมูลจังหวัดจาก tbl_province
    $stmtP = $pdo_connect->prepare("SELECT id, name_th FROM tbl_province WHERE deleted_at IS NULL ORDER BY name_th ASC");
    $stmtP->execute();
    $provinces = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงข้อมูลอำเภอ/เขตจาก tbl_district
    $stmtA = $pdo_connect->prepare("SELECT id, province_id, name_th FROM tbl_district WHERE deleted_at IS NULL ORDER BY name_th ASC");
    $stmtA->execute();
    $amphures = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงข้อมูลตำบล/แขวง จาก tbl_sub_district
    $stmtT = $pdo_connect->prepare("SELECT id, amphure_id, name_th, zip_code FROM tbl_sub_district WHERE deleted_at IS NULL ORDER BY name_th ASC");
    $stmtT->execute();
    $districts = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    // จัดกลุ่มข้อมูลให้อยู่ในรูปแบบ Hierarchy (จังหวัด -> อำเภอ -> ตำบล)
    $tambonByAmphure = [];
    foreach ($districts as $d) {
        $tambonByAmphure[$d['amphure_id']][] = [
            'id'       => (int)$d['id'],
            'name_th'  => $d['name_th'],
            'zip_code' => $d['zip_code']
        ];
    }

    $amphureByProvince = [];
    foreach ($amphures as $a) {
        $amphureByProvince[$a['province_id']][] = [
            'id'      => (int)$a['id'],
            'name_th' => $a['name_th'],
            'tambon'  => $tambonByAmphure[$a['id']] ?? []
        ];
    }

    $result = [];
    foreach ($provinces as $p) {
        $result[] = [
            'id'      => (int)$p['id'],
            'name_th' => $p['name_th'],
            'amphure' => $amphureByProvince[$p['id']] ?? []
        ];
    }

    Response::json(1, 'Success', $result);

} catch (Exception $e) {
    Response::json(0, 'เกิดข้อผิดพลาดในการดึงข้อมูลที่อยู่จากฐานข้อมูล: ' . $e->getMessage(), null);
}
