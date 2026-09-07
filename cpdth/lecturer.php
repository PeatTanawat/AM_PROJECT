<?php
$pageTitle = 'แนะนำวิทยากร';
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

    .lecturer-container { display: flex; max-width: 1200px; margin: 50px auto; gap: 30px; padding: 0 20px; align-items: stretch; }
    .lecturer-card { flex: 1; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 50px 40px; }

    .lecturer-header { text-align: center; margin-bottom: 40px; }
    .lecturer-name { font-size: 1.5rem; font-weight: 600; color: #3b5998; margin: 0 0 8px 0; }
    .lecturer-title { font-size: 1rem; color: #64748b; margin: 0; }

    .timeline-section { margin-bottom: 40px; }
    .timeline-section:last-child { margin-bottom: 0; }
    .section-title { font-size: 1.15rem; font-weight: 600; color: #374151; margin-bottom: 25px; }

    .timeline { list-style: none; padding: 0; margin: 0; }
    .timeline-item { position: relative; padding-left: 35px; padding-bottom: 30px; }
    .timeline-item:last-child { padding-bottom: 0; }
    
    .timeline-item::before { content: ''; position: absolute; left: 8px; top: 6px; bottom: -6px; width: 2px; background-color: #e2e8f0; }
    .timeline-item:last-child::before { display: none; }
    .timeline-item::after { content: ''; position: absolute; left: 0; top: 6px; width: 18px; height: 18px; border-radius: 50%; }

    .timeline-edu .timeline-item::after { background-color: #62c3ba; }
    .timeline-work .timeline-item::after { background-color: #85c2ed; }
    .timeline-train .timeline-item::after { background-color: #fac06d; }

    .timeline-content { display: flex; align-items: flex-start; gap: 20px; }
    
    /* 🛑 แก้ไขจุดนี้: ขยายพื้นที่ความกว้างเป็น 130px และบังคับให้อยู่บรรทัดเดียว */
    .timeline-year { flex: 0 0 130px; white-space: nowrap; font-weight: 700; color: #374151; font-size: 0.95rem; line-height: 1.5; }
    
    .timeline-desc { flex: 1; color: #4b5563; font-size: 0.95rem; line-height: 1.5; }

    @media (max-width: 992px) {
        .lecturer-container { flex-direction: column; padding: 0 15px; }
        .lecturer-card { padding: 40px 25px; }
        .timeline-content { flex-direction: column; gap: 5px; }
        .timeline-year { flex: auto; white-space: normal; } /* บนมือถือปล่อยให้ตัดคำปกติได้ถ้าพื้นที่ไม่พอ */
    }

    @media (max-width: 768px) {
        .lecturer-card { padding: 30px 20px; }
        .lecturer-name { font-size: 1.3rem; }
    }
</style>

<div class="page-header">
    <h1 class="page-title">แนะนำวิทยากร</h1>
</div>

<main class="lecturer-container">

    <div class="lecturer-card">
        <div class="lecturer-header">
            <h2 class="lecturer-name">นายสุรพงษ์ ลักษณานุกูล</h2>
            <p class="lecturer-title">ผู้สอบบัญชีรับอนุญาต (CPA)</p>
        </div>
        <div class="timeline-section">
            <h3 class="section-title">ประวัติการศึกษา</h3>
            <ul class="timeline timeline-edu">
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2551 - 2555</span><span class="timeline-desc">บัญชีบัณฑิต มหาวิทยาลัยแม่โจ้</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2564</span><span class="timeline-desc">กำลังศึกษา บริหารธุรกิจมหาบัณฑิต มหาวิทยาลัยหอการค้าไทย</span></div></li>
            </ul>
        </div>
        <div class="timeline-section">
            <h3 class="section-title">ประสบการณ์การทำงาน</h3>
            <ul class="timeline timeline-work">
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2555 - 2558</span><span class="timeline-desc">บริษัท สอบบัญชี ดี ไอ เอ อินเตอร์เนชั่นแนล จำกัด (DIA)</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2558 - 2559</span><span class="timeline-desc">บริษัท ไพร้ซวอเตอร์เฮาส์คูเปอร์ส เอบีเอเอส จำกัด (PwC Thailand)</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2559 - 2564</span><span class="timeline-desc">บริษัทที่จดทะเบียนในตลาดหลักทรัพย์แห่งประเทศไทย (IT service and Food product)</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2560 - 2564</span><span class="timeline-desc">บริษัท สำนักงานสอบบัญชี เอ เอ็ม จำกัด</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2563 - 2564</span><span class="timeline-desc">บริษัท สำนักงานบัญชี เอ เอ็ม จำกัด</span></div></li>
            </ul>
        </div>
        <div class="timeline-section">
            <h3 class="section-title">ประวัติการอบรม</h3>
            <ul class="timeline timeline-train">
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-desc">Mini Master of Business administration (Mini MBA) - สถาบันบัณฑิตพัฒนบริหารศาสตร์ (นิด้า) NIDA</span></div></li>
            </ul>
        </div>
    </div>

    <div class="lecturer-card">
        <div class="lecturer-header">
            <h2 class="lecturer-name">นางสาวดาราวรรณ หอยทอง</h2>
            <p class="lecturer-title">ผู้สอบบัญชีรับอนุญาต (CPA)</p>
        </div>
        <div class="timeline-section">
            <h3 class="section-title">ประวัติการศึกษา</h3>
            <ul class="timeline timeline-edu">
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2552 - 2556</span><span class="timeline-desc">บัญชีบัณฑิต มหาวิทยาลัยราชภัฏสวนดุสิต</span></div></li>
            </ul>
        </div>
        <div class="timeline-section">
            <h3 class="section-title">ประสบการณ์การทำงาน</h3>
            <ul class="timeline timeline-work">
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2556 - 2559</span><span class="timeline-desc">บริษัท สอบบัญชี ดี ไอ เอ อินเตอร์เนชั่นแนล จำกัด (DIA)</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2559 - 2560</span><span class="timeline-desc">บริษัท แกรนท์ ธอนตัน จำกัด</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2560 - 2562</span><span class="timeline-desc">บริษัทที่จดทะเบียนในตลาดหลักทรัพย์แห่งประเทศไทย<br>(ธุรกิจเสื้อผ้า)</span></div></li>
                <li class="timeline-item"><div class="timeline-content"><span class="timeline-year">ปี 2560 - 2564</span><span class="timeline-desc">บริษัท เอ็ม เอ แอคเคาท์ติ้ง จำกัด</span></div></li>
            </ul>
        </div>
    </div>

</main>

<?php include 'components/footer.php'; ?>