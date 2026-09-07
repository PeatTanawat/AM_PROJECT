<?php
$pageTitle = 'คู่มือการใช้งาน';
include 'components/header.php';
?>

<style>
    .page-header {
        background-color: #f4f5f7;
        padding: 80px 20px;
        text-align: center;
    }

    .page-title {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    /* 📱 Responsive (หัวข้อ) */
    @media (max-width: 768px) {
        .page-header {
            padding: 60px 20px !important;
        }

        .page-title {
            font-size: 1.8rem !important;
        }
    }

    .manual-container {
        max-width: 1500px;
        margin: 50px auto;
        padding: 0 20px;
    }

    .manual-step {
        margin-bottom: 50px;
        padding-bottom: 30px;
        border-bottom: 1px dashed #e2e8f0;
    }

    .manual-step:last-child {
        border-bottom: none;
    }

    .step-text {
        font-size: 1rem;
        line-height: 1.8;
        color: #374151;
        margin-bottom: 20px;
    }

    .step-note {
        font-size: 0.95rem;
        color: #dc2626;
        background-color: #fef2f2;
        padding: 10px 15px;
        border-left: 4px solid #dc2626;
        margin-bottom: 20px;
    }

    .step-image {
        width: 100%;
        max-width: 800px;
        height: auto;
        display: block;
        margin: 0 auto 25px auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        background-color: #f8fafc;
    }
    
    /* Prevent image download */
    img {
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
        user-drag: none;
        user-select: none;
        -webkit-user-select: none;
        -ms-user-select: none;
        pointer-events: none;
    }

    .img-placeholder {
        width: 100%;
        max-width: 800px;
        padding: 40px 20px;
        background-color: #f1f5f9;
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        text-align: center;
        color: #64748b;
        font-weight: 500;
        margin: 0 auto 25px auto;
        box-sizing: border-box;
    }

    @media (max-width: 768px) {
        .manual-container {
            margin: 20px auto;
            padding: 0 15px;
        }

        .manual-step {
            margin-bottom: 30px;
            padding-bottom: 20px;
        }

        .step-text {
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .step-note {
            font-size: 0.9rem;
            padding: 10px;
        }

        .img-placeholder {
            padding: 20px 10px;
            font-size: 0.85rem;
        }
    }

    /* เมื่อหน้าจอมีความกว้าง 1024px ขึ้นไป (สำหรับโน้ตบุ๊ก) */
    @media (min-width: 1024px) {
        .manual-container {
            max-width: 1200px;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">คู่มือการใช้งานระบบ</h1>
</div>

<main class="manual-container">

    <div class="manual-step">
        <div class="step-text"><strong>1.</strong> ผู้เข้าอบรมเลือกเมนูลงทะเบียนที่หน้าเว็บไซต์ (ด้านมุมขวาบน) เพื่อทำการสมัครสมาชิกก่อนเข้าเรียน</div>
        <img class="step-image" src="assets/images/usermanual/total-1.png" alt="ข้อ 1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-1.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>2.</strong> เมื่อกดลงทะเบียน (มุมขวาบน) แล้ว ระบบจะแจ้งให้กรอกข้อมูลสำหรับการลงทะเบียนสมาชิกใหม่โดยให้ผู้เข้าอบรมกรอกข้อมูลให้ครบถ้วน เช่น ชื่อ นามสกุล เลขบัตรประชาชน เบอร์โทร และอีเมล เป็นต้น โดยผู้เข้าอบรมสามารถกรอกเลขที่ผู้ทำบัญชี และเลขที่ผู้สอบบัญชีได้ในขั้นตอนนี้ (เลขที่ผู้ทำบัญชีและเลขที่ผู้สอบบัญชีสามารถเว้นว่างไว้ได้แต่หากต้องการเพิ่มข้อมูลต้องแจ้งกับทางเจ้าหน้าที่เท่านั้น ไม่สามารถเพิ่มเองได้) เมื่อกรอกข้อมูลครบถ้วนแล้วกดปุ่มยืนยันการสมัครสมาชิกเพื่อทำการลงทะเบียน โดยขอให้ผู้เข้าอบรมกรอกเบอร์โทรศัพท์ที่สามารถรับรหัส OTP ได้ เนื่องจากใช้เพื่อยืนยันตัวตนในระหว่างการอบรม</div>
        <img class="step-image" style="max-width: 200px;" src="assets/images/usermanual/total-2.png" alt="ข้อ 2" onerror="this.outerHTML='<div class=\'img-placeholder\' style=\'max-width: 200px;\'>ไม่พบไฟล์ total-2.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>3.1</strong> หลังจากที่ลงทะเบียนเสร็จแล้วระบบจะส่งอีเมลไปยังอีเมลของผู้ใช้งานที่ได้ทำการลงทะเบียนไว้ เพื่อให้กดยืนยันความถูกต้องของที่อยู่อีเมล</div>
        <img class="step-image" src="assets/images/usermanual/total-3.1.png" alt="ข้อ 3.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-3.1.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>3.2</strong> ในอีเมลจะแสดงข้อความ ยืนยันอีเมล เมื่อผู้ใช้งานกดแล้วระบบจะทำการยืนยันอีเมลของผู้ใช้งาน</div>
        <img class="step-image" src="assets/images/usermanual/total-3.2.png" alt="ข้อ 3.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-3.2.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>4.</strong> หลังจากผู้ใช้งานยืนยันอีเมลเรียบร้อยแล้ว ระบบจะเด้งมาหน้าเว็บไซต์ และให้ผู้ใช้งานเข้าไปที่เมนูข้อมูลผู้ใช้งาน เพื่อเช็กสถานะการลงทะเบียน และตรวจสอบความถูกต้องของข้อมูลอีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanual/total-4.png" alt="ข้อ 4" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-4.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>5.</strong> เมนูข้อมูลผู้ใช้งานจะแสดงข้อมูลที่ผู้ใช้งานได้ทำการลงทะเบียนไปในขั้นตอนแรก หลังจากนั้นให้ผู้ใช้งานทำการยืนยันตัวตนเพื่อให้เจ้าหน้าที่ตรวจสอบข้อมูลการลงทะเบียน โดยกดที่ปุ่มยืนยันตัวตน (มุมขวาล่าง) เพื่อส่งเอกสารยืนยันตัวตน โดยจะต้องส่งภาพถ่ายบัตรประชาชนที่ชัดเจนไม่มีแสงสะท้อน และรูปถ่ายปัจจุบัน</div>
        <img class="step-image" src="assets/images/usermanual/total-5.png" alt="ข้อ 5" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-5.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>6.</strong> ให้ผู้ใช้งานกรอกเลขประจำตัวประชาชน วันหมดอายุของบัตรประจำตัวประชาชน อัปโหลดภาพถ่ายบัตรประจำตัวประชาชนที่ชัดเจน ไม่มีแสงสะท้อน และภาพถ่ายปัจจุบันของผู้ใช้งาน เพื่อทำการยืนยันตัวตน จากนั้นกดปุ่มบันทึกเพื่อส่งข้อมูล</div>
        <img class="step-image" src="assets/images/usermanual/total-6.png" alt="ข้อ 6" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-6.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>7.</strong> เมื่อผู้ใช้งานส่งข้อมูลการยืนยันตัวตนแล้ว ระบบจะแจ้งสถานะของการยืนยันตัวตน และผู้ใช้งานจะได้รับอีเมลแจ้งเตือนสถานะการยืนยันตัวตนว่าสำเร็จหรือไม่ เมื่อเจ้าหน้าที่ของบริษัทได้รับแจ้งการยืนยันตัวตนแล้วจะตรวจสอบความถูกต้องครบถ้วนของข้อมูลสำหรับการยืนยันตัวตน และดำเนินการอนุมัติการยืนยันตัวตนแก่ผู้ใช้งาน</div>
        <img class="step-image" src="assets/images/usermanual/total-7.png" alt="ข้อ 7" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-7.png</div>'">

        <div class="step-text"><strong>7.1</strong> ในกรณีที่ยืนยันตัวตนไม่ผ่าน ผู้ใช้งานจะได้รับอีเมลจากระบบแจ้งว่าเอกสารที่ส่งยืนยันตัวตนไม่ผ่านการอนุมัติ ให้ทำการส่งเอกสารใหม่อีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanual/total-7.1.png" alt="ข้อ 7.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-7.1.png</div>'">

        <div class="step-text">อีกทั้งกรณีที่เจ้าหน้าที่ไม่อนุมัติการยืนยันตัวตน หน้าเว็บไซต์จะแสดงสาเหตุที่เจ้าหน้าที่ไม่อนุมัติการยืนยันตัวตนในช่องหมายเหตุ โดยให้ผู้ใช้งานแก้ไขและให้ทำการกดยืนยันตัวตนใหม่อีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanual/total-7.1-2.png" alt="ข้อ 7.1.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-7.1-2.png</div>'">

        <div class="step-text"><strong>7.2</strong> ในกรณีที่ยืนยันตัวตนสำเร็จแล้ว ผู้ใช้งานจะได้รับอีเมลแจ้งว่ายืนยันตัวตนสมาชิกสำเร็จแล้ว</div>
        <img class="step-image" src="assets/images/usermanual/total-7.2.png" alt="ข้อ 7.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-7.2.png</div>'">

        <div class="step-text">อีกทั้งสถานะในหน้าข้อมูลผู้ใช้งานก็จะเปลี่ยนเป็นยืนยันตัวตนสำเร็จ</div>
        <img class="step-image" src="assets/images/usermanual/total-7.2-1.png" alt="ข้อ 7.2.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-7.2-1.png</div>'">

        <div class="step-text"><strong>7.3</strong> กรณีที่ผู้ใช้งานต้องการเปลี่ยนข้อมูลเบอร์โทรศัพท์ สามารถเลือกที่ข้อความ “เปลี่ยน” ที่แสดงอยู่ด้านหลังเบอร์โทรศัพท์</div>
        <img class="step-image" src="assets/images/usermanual/total-7.3.png" alt="ข้อ 7.3" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-7.3.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>8.</strong> สำหรับการจัดการเรื่องใบเสร็จรับเงินและใบกำกับภาษี ผู้ใช้งานสามารถเลือกเมนูที่อยู่สำหรับเพิ่มที่อยู่ในการออกใบเสร็จรับเงินและใบกำกับภาษี โดยเลือกที่ข้อความ “เพิ่มที่อยู่” ที่แสดงอยู่มุมขวาบน</div>
        <img class="step-image" src="assets/images/usermanual/total-8.png" alt="ข้อ 8" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-8.png</div>'">

        <div class="step-text"><strong>8.1</strong> ระบบจะให้ผู้ใช้งานเลือกเพิ่มที่อยู่โดยแยกส่วนออกระหว่างของบุคคลธรรมดาและนิติบุคคล และให้ผู้ใช้งานกรอกข้อมูลให้ครบถ้วน จากนั้นกดยืนยันเพื่อทำการบันทึก</div>
        <img class="step-image" src="assets/images/usermanual/total-8.1.png" alt="ข้อ 8.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-8.1.png</div>'">

        <div class="step-text"><strong>8.2</strong> เมื่อเพิ่มข้อมูลแล้วที่อยู่ครั้งแรกจะถูกตั้งค่าให้เป็นค่าเริ่มต้นเสมอ ถ้าผู้ใช้งานมีหลายที่อยู่ สามารถกดที่ข้อความ “ตั้งเป็นค่าเริ่มต้น” แสดงอยู่ที่ด้านหลังที่อยู่ เพื่อเปลี่ยนที่อยู่เริ่มต้นได้ หรือทำการลบที่อยู่ที่ไม่ต้องการใช้งานออกได้</div>
        <img class="step-image" src="assets/images/usermanual/total-8.2.png" alt="ข้อ 8.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-8.2.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>9.</strong> เมื่อผู้ใช้งานดำเนินการตั้งค่าข้อมูลส่วนบุคคลเรียบร้อยแล้ว สามารถทำการซื้อคอร์สเรียนที่ผู้ใช้งานต้องการเข้าอบรม โดยการกดที่ข้อความ “ซื้อคอร์สเรียน” ซึ่งแสดงอยู่ด้านล่างคอร์สที่ผู้เข้าอบรมต้องการอบรม</div>
        <img class="step-image" src="assets/images/usermanual/total-9.png" alt="ข้อ 9" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9.png</div>'">

        <div class="step-text">โดยระบบจะแสดงรายละเอียดของคอร์สอบรมนั้นๆ ทั้งชื่อคอร์สเรียน วิทยากรผู้บรรยาย เนื้อหาคอร์สโดยย่อและแบบละเอียด หมวดหมู่คอร์ส รหัสหลักสูตร การนับชั่วโมง ระยะเวลาการอบรม และราคาคอร์สเรียน ให้ผู้เข้าอบรมกดที่ข้อความซื้อคอร์สเรียนเพื่อทำการดำเนินการชำระเงินต่อไป</div>
        <img class="step-image" src="assets/images/usermanual/total-9-1.png" alt="ข้อ 9.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-1.png</div>'">

        <div class="step-text">จากนั้นระบบจะแสดงรายละเอียดคำสั่งซื้อของคอร์สอบรมที่ผู้ใช้งานเลือกอีกครั้ง กรณีที่ผู้ใช้งานมีคูปองส่วนลดจากการส่งเสริมการขาย สามารถกรอกคูปองเพื่อรับสิทธิ์ส่วนลดได้ในขั้นตอนนี้ โดยการกรอกรหัสคูปอง และกดปุ่มตรวจสอบเพื่อเช็กสิทธิ์คูปอง</div>
        <img class="step-image" src="assets/images/usermanual/total-9-2.png" alt="ข้อ 9.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-2.png</div>'">

        <div class="step-text">เมื่อกรอกคูปองส่วนลดแล้ว ในหน้าคำสั่งซื้อจะแสดงราคาสุทธิที่ต้องชำระเงินหลังจากหักยอดส่วนลดแล้ว และผู้ใช้งานสามารถกดเพิ่มหมายเหตุในคำสั่งซื้อได้ โดยการกดปุ่มเพิ่มหมายเหตุด้านขวามือหากต้องการแจ้งข้อความถึงเจ้าหน้าที่</div>
        <img class="step-image" src="assets/images/usermanual/total-9-3.png" alt="ข้อ 9.3" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-3.png</div>'">

        <div class="step-text">เมื่อกดข้อความ “เพิ่มหมายเหตุ” ระบบจะแสดงหน้าต่างให้กรอกหมายเหตุที่ต้องการ เมื่อกรอกแล้วกดปุ่มตกลงเพื่อทำการบันทึกข้อมูล และระบบจะส่งข้อความให้กับเจ้าหน้าที่ของบริษัท</div>
        <img class="step-image" src="assets/images/usermanual/total-9-4.png" alt="ข้อ 9.4" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-4.png</div>'">

        <div class="step-text">โดยผู้ใช้งานสามารถเลือกช่องทางการชำระเงินได้ ซึ่งบริษัทกำหนดช่องทางไว้ในระบบจำนวน 3 ช่องทางคือ 1. ชำระผ่านบัตรเครดิต/เดบิต 2. ชำระผ่านพร้อมเพย์ 3.ชำระโดยการโอนเงิน ซึ่งมีความปลอดภัยของข้อมูลเนื่องจากบริษัทได้เชื่อมต่อระบบกับผู้ให้บริการที่มีความน่าเชื่อถือ</div>
        <img class="step-image" src="assets/images/usermanual/total-9-5.png" alt="ข้อ 9.5" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-5.png</div>'">

        <div class="step-text">การชำระผ่านบัตรเครดิต/เดบิต เมื่อกรอกข้อมูลบัตรเรียบร้อยแล้ว ระบบจะแสดงข้อความให้จดจำบัตรเพื่อความสะดวกในการชำระเงินครั้งต่อไป แต่หากผู้ใช้งานไม่ประสงค์จะจดจำบัตรไม่ต้องเลือกติ๊กที่กรอบสี่เหลี่ยม</div>
        <img class="step-image" src="assets/images/usermanual/total-9-6.png" alt="ข้อ 9.6" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-6.png</div>'">

        <div class="step-text">ตัวอย่างหน้าจอแสดงการชำระผ่านพร้อมเพย์</div>
        <img class="step-image" src="assets/images/usermanual/total-9-7.png" alt="ข้อ 9.7" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-7.png</div>'">

        <div class="step-text">เมื่อผู้ใช้งานชำระเงินเสร็จแล้ว ระบบจะแจ้งสถานะการชำระเงินดังภาพด้านล่าง โดยหากชำระผ่านบัตรเดครดิต บัตรเดบิต และ QR Code สามารถเข้าเรียนได้ทันที แต่หากชำระผ่านช่องทางการโอนเงินต้องรอเจ้าหน้าที่อนุมัติหลักสูตรก่อนเริ่มเรียน</div>
        <img class="step-image" src="assets/images/usermanual/total-9-8.png" alt="ข้อ 9.8" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-8.png</div>'">

        <div class="step-text">นอกจากนั้นผู้ใช้งานจะได้รับอีเมลแจ้งเตือนสถานะการชำระเงินของคำสั่งซื้อนั้นๆ</div>
        <img class="step-image" src="assets/images/usermanual/total-9-9.png" alt="ข้อ 9.9" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-9.png</div>'">

        <div class="step-text">ในกรณีที่ผู้ใช้งานไม่ได้ชำระเงินตามคำสั่งซื้อภายในระยะเวลาที่บริษัทกำหนด (48 ชม.) ระบบจะดำเนินการยกเลิกคำสั่งซื้อนั้น และส่งอีเมลเพื่อแจ้งเตือนสถานะคำสั่งซื้อที่หมดอายุแก่ผู้ใช้บริการ</div>
        <img class="step-image" src="assets/images/usermanual/total-9-10.png" alt="ข้อ 9.10" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-10.png</div>'">

        <div class="step-text">หากผู้ใช้บริการชำระเงินเสร็จสิ้นแล้ว หน้าเมนูประวัติการชำระเงิน (ในระบบสมาชิก) จะแสดงรายละเอียดการชำระเงินทั้งหมดของผู้ใช้งาน สามารถกดดูรายละเอียดคำสั่งซื้อของแต่ละคอร์สได้</div>
        <img class="step-image" src="assets/images/usermanual/total-9-11.png" alt="ข้อ 9.11" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-11.png</div>'">

        <div class="step-text">เมื่อเลือกที่ข้อความ “ดูรายละเอียด” ของแต่ละคำสั่งซื้อ ระบบจะแสดงข้อมูลทั้งหมดที่เกี่ยวข้องกับคำสั่งซื้อนั้นๆ ทั้งหมายเลขคำสั่งซื้อ ชื่อคอร์ส ส่วนลด สถานะคำสั่งซื้อ วันที่ทำรายการ จำนวนคอร์ส และราคา</div>
        <img class="step-image" src="assets/images/usermanual/total-9-12.png" alt="ข้อ 9.12" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-12.png</div>'">

        <div class="step-text">ผู้ใช้งานสามารถพิมพ์ใบเสร็จรับเงินและใบกำกับภาษีได้ด้วยตนเองผ่านระบบ โดยกดที่ปุ่มพิมพ์ใบเสร็จของคำสั่งซื้อนั้นๆ</div>
        <img class="step-image" src="assets/images/usermanual/total-9-13.png" alt="ข้อ 9.13" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-13.png</div>'">

        <div class="step-text">ระบบจะแสดงใบเสร็จรับเงิน ผู้ใช้งานสามารถดำเนินการบันทึกเป็น File หรือสั่งพิมพ์ได้ทันที</div>
        <img class="step-image" src="assets/images/usermanual/total-9-14.png" alt="ข้อ 9.14" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-9-14.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>10.</strong> หลังจากที่ผู้ใช้งานดำเนินการซื้อคอร์สอบรมเรียบร้อยแล้ว คอร์สที่ผู้ใช้งานเลือกซื้อจะอยู่ในเมนูคอร์สเรียนของฉัน โดยผู้ใช้งานสามาถเลือกที่ข้อความ “เข้าสู่บทเรียน” เพื่อทำการเข้าอบรมได้ทันที</div>
        <img class="step-image" src="assets/images/usermanual/total-10.png" alt="ข้อ 10" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-10.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>11.</strong> เมื่อผู้ใช้งานเข้ามาในหน้าคอร์สอบรมแล้วระบบจะแสดงรายละเอียดชื่อคอร์ส ชื่อวิทยากรผู้อบรม จำนวนบทเรียน และเวลาที่ใช้ในการอบรมรวมทั้งหมด ฯลฯ โดยผู้เข้าอบรมสามารถสอบถามเกี่ยวกับบทเรียนได้ตลอดเวลาการอบรม โดยผู้ใช้งานจะไม่สามารถเริ่มทำข้อสอบได้ ถ้ายังไม่ได้อบรมให้ครบทุกบทเรียน และจะต้องอบรมทีละบทเรียนตามลำดับ ไม่สามารถอบรมข้ามบทเรียนได้ รวมถึงไม่สามารถเลื่อนความเร็ววีดีโอได้ หากผู้เข้าอบรมเลื่อนความเร็ววีดีโอระบบจะตัดออกจากการรับชมทันที ถ้าต้องการเริ่มอบรมให้เลือกที่ข้อความ “พร้อมรับชม” เพื่อรับชมวิดีโอการสอน</div>
        <img class="step-image" src="assets/images/usermanual/total-11.png" alt="ข้อ 11" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-11.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>12.</strong> ในระหว่างที่อบรม ระบบจะสุ่มคำถามระหว่างรับชมวิดีโอขึ้นมา เพื่อให้ผู้ใช้งานตอบคำถามระหว่างอบรม รวมถึงการใส่รหัสผ่าน OTP ที่ได้รับทางเบอร์โทรศัพท์ที่ใช้ในการลงทะเบียนเพื่อยืนยันตัวตนผู้เข้าอบรม และผู้เข้าอบรมไม่สามารถเลื่อนความเร็วของวีดีโอได้หากกดเลื่อนความเร็วระบบจะแจ้งเตือนและให้ออกจากบทเรียนทันที</div>
        <img class="step-image" src="assets/images/usermanual/total-12.png" alt="ข้อ 12" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-12.png</div>'">

        <div class="step-text">โดยหากผู้เข้าอบรมตอบคำถามระหว่างรับชมหรือรหัส OTP ผิดในครั้งที่ 3 ระบบจะออกจากบทเรียนทันที หากผู้ใช้งานกรอกรหัส OTP ผิดติดต่อกันหลายครั้งระบบทำแจ้งเตือนว่าผู้เข้าอบรมมีการใส่รหัส OTP ผิดจำนวนหลายครั้งติดต่อกัน กรุณารอสักครู่ (โดยประมาณ 15 นาที) ระบบจะกลับมาให้บริการ OTP ตามปกติ</div>
        <img class="step-image" src="assets/images/usermanual/total-12-1.png" alt="ข้อ 12.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-12-1.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>13.</strong> เมื่อรับชมวิดีโอจบแล้ว กดปุ่มกลับไปหน้าบทเรียนเพื่อรับชมวิดีโอถัดไป หรือเพื่อเริ่มทำข้อสอบ (ในกรณีที่อบรมครบทุกบทเรียนแล้ว)<br><br>ในแต่ละบทเรียนหากผู้ใช้งานยังรับชมวิดีโอไม่จบ สถานะของบทเรียนนั้นจะแสดงเป็นสถานะ “ระหว่างรับชม” โดยผู้ใช้งานสามารถกดรับชมวีดีโอต่อจากเดิมได้ ถ้ารับชมจนจบบทเรียนนั้นแล้ว ชื่อบทเรียนจะถูกขีดฆ่าออก และสถานะจะเปลี่ยนเป็น “รับชมแล้ว”</div>
        <img class="step-image" src="assets/images/usermanual/total-13.png" alt="ข้อ 13" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-13.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>14.</strong> เมื่อผู้ใช้งานอบรมครบทุกบทเรียนแล้ว ระบบจะปลดล็อกหน้าจอและแสดงข้อความ “เริ่มทำข้อสอบ” ให้ผู้ใช้งานคลิกเพื่อเริ่มทำข้อสอบ</div>
        <img class="step-image" src="assets/images/usermanual/total-14.png" alt="ข้อ 14" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-14.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>15.</strong> เมื่อผู้ใช้งานคลิกเริ่มทำข้อสอบ ระบบจะแสดงข้อสอบของคอร์สนั้นๆให้ผู้ใช้งานได้เริ่มทำข้อสอบ โดยแต่ละคอร์สจะกำหนดระยะเวลาในการทำข้อสอบไว้ ผู้เข้าอบรมสามารถทำข้อสอบได้ 2 ครั้งโดยไม่ต้องเรียนใหม่ (หากยังไม่ผ่านให้ติดต่อเจ้าหน้าที่เพื่อปลดล็อกและเริ่มเรียนบทเรียนนั้นอีกครั้ง)</div>
        <img class="step-image" src="assets/images/usermanual/total-15.png" alt="ข้อ 15" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-15.png</div>'">

        <div class="step-text">เมื่อตอบข้อสอบเสร็จครบทุกข้อแล้ว ให้ผู้ใช้งานกดที่ข้อความ “ส่งข้อสอบ” เพื่อทำการส่งข้อสอบที่ได้ตอบคำถามไว้</div>
        <img class="step-image" src="assets/images/usermanual/total-15-1.png" alt="ข้อ 15.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-15-1.png</div>'">

        <div class="step-text">ระบบจะแสดงคะแนนผลการสอบของผู้ใช้งานว่าผ่านการทดสอบหรือไม่ ในกรณีที่ไม่ผ่านจะแสดงรายละเอียดดังภาพด้านล่าง ให้ผู้เข้าอบรมเลือกที่ข้อความ “ตกลง” เพื่อกลับไปทำข้อสอบใหม่อีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanual/total-15-2.png" alt="ข้อ 15.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-15-2.png</div>'">

        <div class="step-text">ผู้ใช้งานสามารถทำข้อสอบตามจำนวนครั้งที่ผู้ดูแลระบบกำหนดไว้เท่านั้น (2 ครั้ง) ถ้าไม่ผ่านเกินจำนวนครั้งที่ผู้ดูแลระบบกำหนดไว้ จะไม่สามารถทำข้อสอบได้อีก (หากยังไม่ผ่านให้ติดต่อเจ้าหน้าที่เพื่อปลดล็อกและเริ่มเรียนบทเรียนนั้นอีกครั้ง)</div>
        <img class="step-image" src="assets/images/usermanual/total-15-3.png" alt="ข้อ 15.3" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-15-3.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>16.</strong> เมื่อผู้ใช้งานสอบผ่านตามเกณฑ์ที่กำหนดแล้ว ระบบจะขึ้นสถานะว่ารออนุมัติใบประกาศ เพื่อรอเจ้าหน้าที่ของบริษัทตรวจสอบข้อมูลการอบรม และข้อมูลที่เกี่ยวข้องเช่นบัตรประชาชน จากนั้นจะดำเนินการอนุมัติใบประกาศให้กับผู้ใช้งาน</div>
        <img class="step-image" src="assets/images/usermanual/total-16.png" alt="ข้อ 16" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-16.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>17.</strong> ในแต่ละบทเรียนผู้ใช้งานสามารถสนทนาถาม-ตอบกับเจ้าหน้าที่ได้ ผ่านกล่องแชททางมุมขวาล่างของจอผู้ใช้งานได้ตลอดเวลา</div>
        <img class="step-image" src="assets/images/usermanual/total-17.png" alt="ข้อ 17" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-17.png</div>'">

        <div class="step-text">เมื่อผู้ใช้งานกดที่กล่องสนทนาแล้วระบบจะแสดงหน้าต่างให้กรอกข้อความเพื่อเริ่มสนทนากับผู้ดูแลระบบดังภาพ โดยข้อความดังกล่าวจะถูกเก็บไว้ และได้รับการตอบกลับโดยเจ้าหน้าที่</div>
        <img class="step-image" src="assets/images/usermanual/total-17-1.png" alt="ข้อ 17.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-17-1.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>18.</strong> เมื่อเจ้าหน้าที่ตรวจสอบข้อมูลเรียบร้อยแล้ว และกดยืนยันการอนุมัติใบประกาศให้เรียบร้อยแล้ว ระบบจะแสดงข้อความ “พิมพ์ใบรับรอง” เพิ่มเติมเพื่อให้ผู้ใช้งานจัดพิมพ์ไว้เป็นหลักฐาน</div>
        <img class="step-image" src="assets/images/usermanual/total-18.png" alt="ข้อ 18" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-18.png</div>'">

        <div class="step-text">เมื่อผู้ใช้งานคลิกที่ข้อความ “พิมพ์ใบรับรอง” ระบบจะแสดงหน้าต่างให้ผู้ใช้งานเลือกประเภทในการดาวน์โหลดใบรับรองการอบรมแยกจากกันระหว่างผู้ทำบัญชี และผู้สอบบัญชี</div>
        <img class="step-image" src="assets/images/usermanual/total-18-1.png" alt="ข้อ 18.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-18-1.png</div>'">

        <div class="step-text">ตัวอย่างใบรับรองการอบรม ผู้ใช้งานสามารถบันทึกหรือพิมพ์ใบรับรองได้</div>
        <img class="step-image" src="assets/images/usermanual/total-18-2.png" alt="ข้อ 18.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-18-2.png</div>'">

        <div class="step-text">ในระบบสมาชิกที่เมนูใบรับรองการสอบ จะแสดงประวัติการสอบทั้งหมดที่ผู้ใช้งานสอบผ่าน โดยผู้ใช้งานสามารถดาวน์โหลดใบรับรองได้ โดยกดปุ่มใบรับรองการสอบ</div>
        <img class="step-image" src="assets/images/usermanual/total-18-3.png" alt="ข้อ 18.3" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-18-3.png</div>'">

        <div class="step-text">ระบบจะแสดงหน้าต่างให้ผู้ใช้งานเลือกประเภทในการดาวน์โหลดใบรับรองการสอบ และแสดงใบรับรองการสอบให้ผู้ใช้งานสามารถบันทึกหรือพิมพ์ใบรับรองได้</div>
        <img class="step-image" src="assets/images/usermanual/total-18-4.png" alt="ข้อ 18.4" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-18-4.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>19.</strong> หากผู้ใช้งานต้องการเปลี่ยนรหัสผ่าน ให้เข้าที่เมนูแก้ไขรหัสผ่าน และดำเนินการกรอกรหัสผ่านใหม่ และยืนยันรหัสผ่านใหม่ที่ต้องการ จากนั้นกดที่ข้อความ “บันทึก” เพื่อยืนยันการแก้ไขข้อมูล</div>
        <img class="step-image" src="assets/images/usermanual/total-19.png" alt="ข้อ 19" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ total-19.png</div>'">
    </div>

</main>

<?php include 'components/footer.php'; ?>