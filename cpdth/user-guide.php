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
        <img class="step-image" src="assets/images/usermanualnew/1.png" alt="ข้อ 1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 1.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>2.</strong> เมื่อกดลงทะเบียน (มุมขวาบน) แล้ว ระบบจะแจ้งให้กรอกข้อมูลสำหรับการลงทะเบียนสมาชิกใหม่โดยให้ผู้เข้าอบรมกรอกข้อมูลให้ครบถ้วน เช่น ชื่อ นามสกุล เลขบัตรประชาชน เบอร์โทร และอีเมล เป็นต้น โดยผู้เข้าอบรมสามารถกรอกเลขที่ผู้ทำบัญชี และเลขที่ผู้สอบบัญชีได้ในขั้นตอนนี้ (เลขที่ผู้ทำบัญชีและเลขที่ผู้สอบบัญชีสามารถเว้นว่างไว้ได้แต่หากต้องการเพิ่มข้อมูลต้องแจ้งกับทางเจ้าหน้าที่เท่านั้น ไม่สามารถเพิ่มเองได้) เมื่อกรอกข้อมูลครบถ้วนแล้วกดปุ่มยืนยันการสมัครสมาชิกเพื่อทำการลงทะเบียน โดยขอให้ผู้เข้าอบรมกรอกเบอร์โทรศัพท์ที่สามารถรับรหัส OTP ได้ เนื่องจากใช้เพื่อยืนยันตัวตนในระหว่างการอบรม</div>
        <img class="step-image" src="assets/images/usermanualnew/2.png" alt="ข้อ 2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 2.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>3.1</strong> หลังจากที่ลงทะเบียนเสร็จแล้วระบบจะส่งอีเมลไปยังอีเมลของผู้ใช้งานที่ได้ทำการลงทะเบียนไว้ เพื่อให้กดยืนยันความถูกต้องของที่อยู่อีเมล</div>
        <img class="step-image" src="assets/images/usermanualnew/3.png" alt="ข้อ 3.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 3.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>3.2</strong> ในอีเมลจะแสดงข้อความ ยืนยันอีเมล เมื่อผู้ใช้งานกดแล้วระบบจะทำการยืนยันอีเมลของผู้ใช้งาน</div>
        <img class="step-image" src="assets/images/usermanualnew/4.png" alt="ข้อ 3.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 4.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>4.</strong> หลังจากผู้ใช้งานยืนยันอีเมลเรียบร้อยแล้ว ระบบจะเด้งมาหน้าเว็บไซต์ และให้ผู้ใช้งานเข้าไปที่เมนูข้อมูลผู้ใช้งาน เพื่อเช็กสถานะการลงทะเบียน และตรวจสอบความถูกต้องของข้อมูลอีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanualnew/5.png" alt="ข้อ 4" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 5.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>5.</strong> เมื่อเข้ามาหน้านี้แล้ว ผู้ใช้สามารถผูกบัญชีกับ Line ได้โดยกดปุ่มเชื่อมต่อบัญชี Line เพื่อในครั้งถัดไปสามารถกดเข้าสู่ระบบผ่าน Line ได้ และสามารถยกเลิการเชื่อต่อกับ Line ได้โดยกดยกเลิกการเชื่อมต่อ</div>
        <img class="step-image" src="assets/images/usermanualnew/6.png" alt="ข้อ 5" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 6.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/7.png" alt="ข้อ 5" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 7.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/8.png" alt="ข้อ 5" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 8.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>6.</strong> เมนูข้อมูลผู้ใช้งานจะแสดงข้อมูลที่ผู้ใช้งานได้ทำการลงทะเบียนไปในขั้นตอนแรก หลังจากนั้นให้ผู้ใช้งานทำการยืนยันตัวตนเพื่อให้เจ้าหน้าที่ตรวจสอบข้อมูลการลงทะเบียน โดยกดที่ปุ่มยืนยันตัวตน (มุมขวาล่าง) เพื่อส่งเอกสารยืนยันตัวตน โดยจะต้องส่งภาพถ่ายบัตรประชาชนที่ชัดเจนไม่มีแสงสะท้อน และรูปถ่ายปัจจุบัน</div>
        <img class="step-image" src="assets/images/usermanualnew/9.png" alt="ข้อ 6" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 9.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>7.</strong> ให้ผู้ใช้งานกรอกเลขประจำตัวประชาชน วันหมดอายุของบัตรประจำตัวประชาชน อัปโหลดภาพถ่ายบัตรประจำตัวประชาชนที่ชัดเจน ไม่มีแสงสะท้อน และภาพถ่ายปัจจุบันของผู้ใช้งาน เพื่อทำการยืนยันตัวตน จากนั้นกดปุ่มบันทึกเพื่อส่งข้อมูล</div>
        <img class="step-image" src="assets/images/usermanualnew/10.png" alt="ข้อ 7" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 10.png</div>'">
    </div>

     <div class="manual-step">
        <div class="step-text"><strong>8.</strong> เมื่อผู้ใช้งานส่งข้อมูลการยืนยันตัวตนแล้ว ระบบจะแจ้งสถานะของการยืนยันตัวตน และผู้ใช้งานจะได้รับอีเมลแจ้งเตือนสถานะการยืนยันตัวตนว่าสำเร็จหรือไม่ เมื่อเจ้าหน้าที่ของบริษัทได้รับแจ้งการยืนยันตัวตนแล้วจะตรวจสอบความถูกต้องครบถ้วนของข้อมูลสำหรับการยืนยันตัวตน และดำเนินการอนุมัติการยืนยันตัวตนแก่ผู้ใช้งาน</div>
        <img class="step-image" src="assets/images/usermanualnew/11.png" alt="ข้อ 8" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 11.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>8.1</strong> ปุ่มดาวน์โหลดบัตรประจำตัวผู้อบรม จะแสดงขึ้นเมื่อสถานะการตรวจสอบข้อมูลเป็นอนุมัติ ซึ่งผู้ใช้งานสามารถดาวน์โหลดบัตรประจำตัวผู้อบรมได้โดยกดปุ่มดาวน์โหลดบัตรประจำตัวผู้อบรม</div>
        <img class="step-image" src="assets/images/usermanualnew/12.png" alt="ข้อ 8.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 12.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text">อีกทั้งกรณีที่เจ้าหน้าที่ไม่อนุมัติการยืนยันตัวตน หน้าเว็บไซต์จะแสดงสาเหตุที่เจ้าหน้าที่ไม่อนุมัติการยืนยันตัวตนในช่องหมายเหตุ โดยให้ผู้ใช้งานแก้ไขและให้ทำการกดยืนยันตัวตนใหม่อีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanualnew/13.png" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 12.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>8.2</strong> ในกรณีที่ยืนยันตัวตนสำเร็จแล้ว ผู้ใช้งานจะได้รับอีเมลแจ้งว่ายืนยันตัวตนสมาชิกสำเร็จแล้ว</div>
        <img class="step-image" src="assets/images/usermanualnew/14.png" alt="ข้อ 8.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 14.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text">อีกทั้งสถานะในหน้าข้อมูลผู้ใช้งานก็จะเปลี่ยนเป็นยืนยันตัวตนสำเร็จ</div>
        <img class="step-image" src="assets/images/usermanualnew/15.png" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 15.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>8.3</strong> กรณีที่ผู้ใช้งานต้องการเปลี่ยนข้อมูลเบอร์โทรศัพท์ สามารถเลือกที่ข้อความ “เปลี่ยน” ที่แสดงอยู่ด้านหลังเบอร์โทรศัพท์</div>
        <img class="step-image" src="assets/images/usermanualnew/16.png" alt="ข้อ 8.3" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 16.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>9.</strong> สำหรับการจัดการเรื่องใบเสร็จรับเงินและใบกำกับภาษี ผู้ใช้งานสามารถเลือกเมนูที่อยู่สำหรับเพิ่มที่อยู่ในการออกใบเสร็จรับเงินและใบกำกับภาษี โดยเลือกที่ข้อความ “เพิ่มที่อยู่” ที่แสดงอยู่มุมขวาบน</div>
        <img class="step-image" src="assets/images/usermanualnew/17.png" alt="ข้อ 9" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 17.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>9.1</strong> ระบบจะให้ผู้ใช้งานเลือกเพิ่มที่อยู่โดยแยกส่วนออกระหว่างของบุคคลธรรมดาและนิติบุคคล และให้ผู้ใช้งานกรอกข้อมูลให้ครบถ้วน จากนั้นกดยืนยันเพื่อทำการบันทึก</div>
        <img class="step-image" src="assets/images/usermanualnew/18.png" alt="ข้อ 9.1" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 18.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>9.2</strong> เมื่อเพิ่มข้อมูลแล้วที่อยู่ครั้งแรกจะถูกตั้งค่าให้เป็นค่าเริ่มต้นเสมอ ถ้าผู้ใช้งานมีหลายที่อยู่ สามารถกดที่ข้อความ “ตั้งเป็นค่าเริ่มต้น” แสดงอยู่ที่ด้านหลังที่อยู่ เพื่อเปลี่ยนที่อยู่เริ่มต้นได้ หรือทำการลบที่อยู่ที่ไม่ต้องการใช้งานออกได้</div>
        <img class="step-image" src="assets/images/usermanualnew/19.png" alt="ข้อ 9.2" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 19.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>10.</strong> เมื่อผู้ใช้งานดำเนินการตั้งค่าข้อมูลส่วนบุคคลเรียบร้อยแล้ว สามารถทำการซื้อคอร์สเรียนที่ผู้ใช้งานต้องการเข้าอบรม โดยการกดที่ข้อความ “ซื้อคอร์สเรียน” ซึ่งแสดงอยู่ด้านล่างคอร์สที่ผู้เข้าอบรมต้องการอบรม</div>
        <img class="step-image" src="assets/images/usermanualnew/20.png" alt="ข้อ 10" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 20.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> โดยระบบจะแสดงรายละเอียดของคอร์สอบรมนั้นๆ ทั้งชื่อคอร์สเรียน วิทยากรผู้บรรยาย เนื้อหาคอร์สโดยย่อและแบบละเอียด หมวดหมู่คอร์ส รหัสหลักสูตร การนับชั่วโมง ระยะเวลาการอบรม และราคาคอร์สเรียน ให้ผู้เข้าอบรมกดที่ข้อความซื้อคอร์สเรียนเพื่อทำการดำเนินการชำระเงินต่อไป</div>
        <img class="step-image" src="assets/images/usermanualnew/21.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 21.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>11.</strong> เมื่อตัดสินใจว่าต้องการที่จะวื้อคอร์สเรียนแล้วผู้ใช้งานสามารถ กดปุ่มเอาเข้าไปในตะกร้าสินค้าได้เพื่อเลือกที่จะชำระทีเดียว และสามารถนำรายการสินค้าออกจากตะกร้าได้หากไม่ประสงค์เรียนคอร์สนั้น</div>
        <img class="step-image" src="assets/images/usermanualnew/22.png" alt="ข้อ 11" onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 22.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> จากนั้นระบบจะแสดงรายละเอียดคำสั่งซื้อของคอร์สอบรมที่ผู้ใช้งานเลือกอีกครั้ง กรณีที่ผู้ใช้งานมีคูปองส่วนลดจากการส่งเสริมการขาย สามารถกรอกคูปองเพื่อรับสิทธิ์ส่วนลดได้ในขั้นตอนนี้ โดยการกรอกรหัสคูปอง และกดปุ่มตรวจสอบเพื่อเช็กสิทธิ์คูปอง</div>
        <img class="step-image" src="assets/images/usermanualnew/23.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 23.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> เมื่อกรอกคูปองส่วนลดแล้ว ในหน้าคำสั่งซื้อจะแสดงราคาสุทธิที่ต้องชำระเงินหลังจากหักยอดส่วนลดแล้ว และผู้ใช้งานสามารถกดเพิ่มหมายเหตุในคำสั่งซื้อได้ โดยการกดปุ่มเพิ่มหมายเหตุด้านขวามือหากต้องการแจ้งข้อความถึงเจ้าหน้าที่</div>
        <img class="step-image" src="assets/images/usermanualnew/24.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 24.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> เมื่อกดข้อความ “เพิ่มหมายเหตุ” ระบบจะแสดงหน้าต่างให้กรอกหมายเหตุที่ต้องการ เมื่อกรอกแล้วกดปุ่มตกลงเพื่อทำการบันทึกข้อมูล และระบบจะส่งข้อความให้กับเจ้าหน้าที่ของบริษัท</div>
        <img class="step-image" src="assets/images/usermanualnew/25.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 25.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> โดยผู้ใช้งานสามารถเลือกช่องทางการชำระเงินได้ ซึ่งบริษัทกำหนดช่องทางไว้ในระบบจำนวน 3 ช่องทางคือ 1. ชำระผ่านบัตรเครดิต/เดบิต 2. ชำระผ่านพร้อมเพย์ 3.ชำระโดยการโอนเงิน ซึ่งมีความปลอดภัยของข้อมูลเนื่องจากบริษัทได้เชื่อมต่อระบบกับผู้ให้บริการที่มีความน่าเชื่อถือ</div>
        <img class="step-image" src="assets/images/usermanualnew/26.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 26.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> เมื่อผู้ใช้งานชำระเงินเสร็จแล้ว ระบบจะแจ้งสถานะการชำระเงินดังภาพด้านล่าง โดยหากชำระผ่านบัตรเดครดิต บัตรเดบิต และ QR Code สามารถเข้าเรียนได้ทันที แต่หากชำระผ่านช่องทางการโอนเงินต้องรอเจ้าหน้าที่อนุมัติหลักสูตรก่อนเริ่มเรียน</div>
        <img class="step-image" src="assets/images/usermanualnew/27.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 27.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> นอกจากนั้นผู้ใช้งานจะได้รับอีเมลแจ้งเตือนสถานะการชำระเงินของคำสั่งซื้อนั้นๆ</div>
        <img class="step-image" src="assets/images/usermanualnew/28.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 28.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> ในกรณีที่ผู้ใช้ชำระผ่านช่องทางธนาคารแล้วแนบสลิปการโอนเงิน แล้วเป็นสลิปที่ถูกต้องระบบจะรับทราบและทำงานขั้นตอนที่กล่าวมาคือ ยืนยันการชำระและส่ง Email ไปให้ผู้ใช้แต่ถ้าหากผู้ใช้งานแนบ สลิปการชำระเงินมาผิดจะต้องรอผู้ดูแลระบบตรวจสอบสลิปการโอนเงินเสียก่อนจากนั้นจะมี Email ส่งกลับมาจากผู้ดูแลระบบ</div>
        <img class="step-image" src="assets/images/usermanualnew/29.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 29.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/30.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 30.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/31.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 31.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> หากผู้ใช้บริการชำระเงินเสร็จสิ้นแล้ว หน้าเมนูประวัติการชำระเงิน (ในระบบสมาชิก) จะแสดงรายละเอียดการชำระเงินทั้งหมดของผู้ใช้งาน สามารถกดดูรายละเอียดคำสั่งซื้อของแต่ละคอร์สได้</div>
        <img class="step-image" src="assets/images/usermanualnew/32.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 32.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> เมื่อเลือกที่ข้อความ “ดูรายละเอียด” ของแต่ละคำสั่งซื้อ ระบบจะแสดงข้อมูลทั้งหมดที่เกี่ยวข้องกับคำสั่งซื้อนั้นๆ ทั้งหมายเลขคำสั่งซื้อ ชื่อคอร์ส ส่วนลด สถานะคำสั่งซื้อ วันที่ทำรายการ จำนวนคอร์ส และราคา</div>
        <img class="step-image" src="assets/images/usermanualnew/33.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 33.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"> ผู้ใช้งานสามารถพิมพ์ใบเสร็จรับเงินและใบกำกับภาษีได้ด้วยตนเองผ่านระบบ โดยเลือกเมนู e-tax แล้วกดดาวน์โหลด จากนั้นจะได้รหัส 4 ตัวมาเมื่อเปิดดูไฟล์ PDF ใบกำกับภาษี</div>
        <img class="step-image" src="assets/images/usermanualnew/34.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 34.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/35.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 35.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/36.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 36.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text">ระบบจะแสดงใบเสร็จรับเงิน ผู้ใช้งานสามารถดำเนินการบันทึกเป็น File หรือสั่งพิมพ์ได้ทันที</div>
        <img class="step-image" src="assets/images/usermanualnew/37.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 37.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text">ในกรณีที่ผู้ใช้ชำระเงินไม่ผ่าน จะมี email แจ้งมาให้ผู้ใช้ทราบและจะมีแจ้งบอกในเมนูประวัติการชำระเงิน จะมีราบการที่ชำระเงินไม่สำเร็จผู้ใช้สามารถกดเข้าไปดูรายละเอียดและทราบถึงเหตุผลว่าเพราะอะไรถึงชำระไม่ผ่านและแนบภาพใหม่ไปได้</div>
        <img class="step-image" src="assets/images/usermanualnew/38.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 38.png</div>'">
        <img class="step-image" src="assets/images/usermanualnew/39.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 39.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>12.</strong> หลังจากที่ผู้ใช้งานดำเนินการซื้อคอร์สอบรมเรียบร้อยแล้ว คอร์สที่ผู้ใช้งานเลือกซื้อจะอยู่ในเมนูคอร์สเรียนของฉัน โดยผู้ใช้งานสามาถเลือกที่ข้อความ “เข้าสู่บทเรียน” เพื่อทำการเข้าอบรมได้ทันที</div>
        <img class="step-image" src="assets/images/usermanualnew/40.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 40.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>13.</strong> เมื่อผู้ใช้งานเข้ามาในหน้าคอร์สอบรมแล้วระบบจะแสดงรายละเอียดชื่อคอร์ส ชื่อวิทยากรผู้อบรม จำนวนบทเรียน และเวลาที่ใช้ในการอบรมรวมทั้งหมด ฯลฯ โดยผู้เข้าอบรมสามารถสอบถามเกี่ยวกับบทเรียนได้ตลอดเวลาการอบรม โดยผู้ใช้งานจะไม่สามารถเริ่มทำข้อสอบได้ ถ้ายังไม่ได้อบรมให้ครบทุกบทเรียน และจะต้องอบรมทีละบทเรียนตามลำดับ ไม่สามารถอบรมข้ามบทเรียนได้ รวมถึงไม่สามารถเลื่อนความเร็ววีดีโอได้ หากผู้เข้าอบรมเลื่อนความเร็ววีดีโอระบบจะตัดออกจากการรับชมทันที ถ้าต้องการเริ่มอบรมให้เลือกที่ข้อความ “พร้อมรับชม” เพื่อรับชมวิดีโอการสอน</div>
        <img class="step-image" src="assets/images/usermanualnew/41.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 41.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>14.</strong> ในระหว่างที่อบรม ระบบจะสุ่มคำถามระหว่างรับชมวิดีโอขึ้นมา เพื่อให้ผู้ใช้งานตอบคำถามระหว่างอบรม รวมถึงการใส่รหัสผ่าน OTP ที่ได้รับทางเบอร์โทรศัพท์ที่ใช้ในการลงทะเบียนเพื่อยืนยันตัวตนผู้เข้าอบรม และผู้เข้าอบรมไม่สามารถเลื่อนความเร็วของวีดีโอได้หากกดเลื่อนความเร็วระบบจะแจ้งเตือนและให้ออกจากบทเรียนทันที</div>
        <img class="step-image" src="assets/images/usermanualnew/42.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 42.png</div>'">
        <div class="step-text">โดยหากผู้เข้าอบรมตอบคำถามระหว่างรับชมหรือรหัส OTP ผิดในครั้งที่ 3 ระบบจะออกจากบทเรียนทันที หากผู้ใช้งานกรอกรหัส OTP ผิดติดต่อกันหลายครั้งระบบทำแจ้งเตือนว่าผู้เข้าอบรมมีการใส่รหัส OTP ผิดจำนวนหลายครั้งติดต่อกัน กรุณารอสักครู่ (โดยประมาณ 15 นาที) ระบบจะกลับมาให้บริการ OTP ตามปกติ</div>
        <img class="step-image" src="assets/images/usermanualnew/43.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 43.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>15.</strong> เมื่อรับชมวิดีโอจบแล้ว กดปุ่มกลับไปหน้าบทเรียนเพื่อรับชมวิดีโอถัดไป หรือเพื่อเริ่มทำข้อสอบ (ในกรณีที่อบรมครบทุกบทเรียนแล้ว)</div>
        <div class="step-text">ในแต่ละบทเรียนหากผู้ใช้งานยังรับชมวิดีโอไม่จบ สถานะของบทเรียนนั้นจะแสดงเป็นสถานะ “ระหว่างรับชม” โดยผู้ใช้งานสามารถกดรับชมวีดีโอต่อจากเดิมได้ ถ้ารับชมจนจบบทเรียนนั้นแล้ว ชื่อบทเรียนจะถูกขีดฆ่าออก และสถานะจะเปลี่ยนเป็น “รับชมแล้ว”</div>
        <img class="step-image" src="assets/images/usermanualnew/44.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 44.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>16.</strong> เมื่อผู้ใช้งานอบรมครบทุกบทเรียนแล้ว ระบบจะปลดล็อกหน้าจอและแสดงข้อความ “เริ่มทำข้อสอบ” ให้ผู้ใช้งานคลิกเพื่อเริ่มทำข้อสอบ</div>
        <img class="step-image" src="assets/images/usermanualnew/45.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 45.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>17.</strong> เมื่อผู้ใช้งานคลิกเริ่มทำข้อสอบ ระบบจะแสดงข้อสอบของคอร์สนั้นๆให้ผู้ใช้งานได้เริ่มทำข้อสอบ โดยแต่ละคอร์สจะกำหนดระยะเวลาในการทำข้อสอบไว้ ผู้เข้าอบรมสามารถทำข้อสอบได้ 2 ครั้งโดยไม่ต้องเรียนใหม่ (หากยังไม่ผ่านให้ติดต่อเจ้าหน้าที่เพื่อปลดล็อกและเริ่มเรียนบทเรียนนั้นอีกครั้ง)</div>
        <img class="step-image" src="assets/images/usermanualnew/46.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 46.png</div>'">
        <div class="step-text">เมื่อตอบข้อสอบเสร็จครบทุกข้อแล้ว ให้ผู้ใช้งานกดที่ข้อความ “ส่งข้อสอบ” เพื่อทำการส่งข้อสอบที่ได้ตอบคำถามไว้</div>
        <img class="step-image" src="assets/images/usermanualnew/47.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 47.png</div>'">
        <div class="step-text">ระบบจะแสดงคะแนนผลการสอบของผู้ใช้งานว่าผ่านการทดสอบหรือไม่ ในกรณีที่ไม่ผ่านจะแสดงรายละเอียดดังภาพด้านล่าง ให้ผู้เข้าอบรมเลือกที่ข้อความ “ตกลง” เพื่อกลับไปทำข้อสอบใหม่อีกครั้ง</div>
        <img class="step-image" src="assets/images/usermanualnew/48.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 48.png</div>'">
        <div class="step-text">ผู้ใช้งานสามารถทำข้อสอบตามจำนวนครั้งที่ผู้ดูแลระบบกำหนดไว้เท่านั้น (2 ครั้ง) ถ้าไม่ผ่านเกินจำนวนครั้งที่ผู้ดูแลระบบกำหนดไว้ จะไม่สามารถทำข้อสอบได้อีก (หากยังไม่ผ่านให้ติดต่อเจ้าหน้าที่เพื่อปลดล็อกและเริ่มเรียนบทเรียนนั้นอีกครั้ง)</div>
        <img class="step-image" src="assets/images/usermanualnew/49.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 49.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>18.</strong> เมื่อผู้ใช้งานสอบผ่านตามเกณฑ์ที่กำหนดแล้ว ระบบจะขึ้นสถานะว่ารออนุมัติใบประกาศ เพื่อรอเจ้าหน้าที่ของบริษัทตรวจสอบข้อมูลการอบรม และข้อมูลที่เกี่ยวข้องเช่นบัตรประชาชน จากนั้นจะดำเนินการอนุมัติใบประกาศให้กับผู้ใช้งาน</div>
        <img class="step-image" src="assets/images/usermanualnew/50.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 50.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>19.</strong> ในแต่ละบทเรียนผู้ใช้งานสามารถสนทนาถาม-ตอบกับเจ้าหน้าที่ได้ ผ่านกล่องแชททางมุมขวาล่างของจอผู้ใช้งานได้ตลอดเวลา</div>
        <img class="step-image" src="assets/images/usermanualnew/51.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 51.png</div>'">
        <div class="step-text">มื่อผู้ใช้งานกดที่กล่องสนทนาแล้วระบบจะแสดงหน้าต่างให้กรอกข้อความเพื่อเริ่มสนทนากับผู้ดูแลระบบดังภาพ โดยข้อความดังกล่าวจะถูกเก็บไว้ และได้รับการตอบกลับโดยเจ้าหน้าที่</div>
        <img class="step-image" src="assets/images/usermanualnew/52.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 52.png</div>'">
    </div>

    <div class="manual-step">
        <div class="step-text"><strong>20.</strong> เมื่อเจ้าหน้าที่ตรวจสอบข้อมูลเรียบร้อยแล้ว และกดยืนยันการอนุมัติใบประกาศให้เรียบร้อยแล้ว ระบบจะแสดงข้อความ “พิมพ์ใบรับรอง” เพิ่มเติมเพื่อให้ผู้ใช้งานจัดพิมพ์ไว้เป็นหลักฐาน</div>
        <img class="step-image" src="assets/images/usermanualnew/53.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 53.png</div>'">
        <div class="step-text">เมื่อผู้ใช้งานคลิกที่ข้อความ “พิมพ์ใบรับรอง” ระบบจะสร้างไฟล์ PDF ใบรับรองให้ผู้ใช้งาน</div>
        <div class="step-text">ตัวอย่างใบรับรองการอบรม ผู้ใช้งานสามารถบันทึกหรือพิมพ์ใบรับรองได้</div>
        <img class="step-image" src="assets/images/usermanualnew/54.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 54.png</div>'">
        <div class="step-text">ในระบบสมาชิกที่เมนูใบรับรองการสอบ จะแสดงประวัติการสอบทั้งหมดที่ผู้ใช้งานสอบผ่าน โดยผู้ใช้งานสามารถดาวน์โหลดใบรับรองได้ โดยกดปุ่มใบรับรองการสอบ</div>
        <img class="step-image" src="assets/images/usermanualnew/55.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 55.png</div>'">
    </div>

      <div class="manual-step">
        <div class="step-text"><strong>21.</strong> หากผู้ใช้งานต้องการเปลี่ยนรหัสผ่าน ให้เข้าที่เมนูแก้ไขรหัสผ่าน และดำเนินการกรอกรหัสผ่านใหม่ และยืนยันรหัสผ่านใหม่ที่ต้องการ จากนั้นกดที่ข้อความ “บันทึก” เพื่อยืนยันการแก้ไขข้อมูล</div>
        <img class="step-image" src="assets/images/usermanualnew/56.png"  onerror="this.outerHTML='<div class=\'img-placeholder\'>ไม่พบไฟล์ 56.png</div>'">
    </div>
   

    

</main>

<?php include 'components/footer.php'; ?>