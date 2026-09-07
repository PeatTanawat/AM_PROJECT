<?php
$breadcrumbs = [['label' => 'หน้าแรก']];
// ช่วงวันที่เริ่มต้น = 30 วันล่าสุด
$dash_from_ymd = date('Y-m-d', strtotime('-29 days'));
$dash_to_ymd = date('Y-m-d');
$dash_from_disp = date('d/m/Y', strtotime('-29 days'));
$dash_to_disp = date('d/m/Y');
?>
<?php include "header.php"; ?>

<style>
    /* ไอคอน info บนหัวการ์ดสถิติ: จาง ๆ ไม่แย่งสายตา แต่ hover แล้วชัด */
    .stat-info {
        color: var(--text-muted);
        cursor: help;
        line-height: 1;
        opacity: .7;
        transition: opacity .15s ease;
    }

    .stat-info:hover,
    .stat-info:focus {
        opacity: 1;
        outline: none;
    }

    .stat-info .material-symbols-outlined {
        font-size: 16px;
        vertical-align: middle;
    }

    /* ข้อความบอกช่วงก่อนหน้าที่ใช้เทียบ */
    .stat-compare {
        font-size: 11px;
        line-height: 1.3;
    }

    @keyframes ping {

        75%,
        100% {
            transform: scale(2.5);
            opacity: 0;
        }
    }
</style>

<div class="container-fluid">
    <div class="main-content d-flex flex-column">

        <?php include "navbar.php"; ?>

        <div class="px-2">

            <!-- การ์ดต้อนรับ + ช่วงวันที่ + ปุ่มเลือกวันที่ -->
            <div class="card bg-primary border-0 rounded-3 welcome-box mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h3 class="text-white fw-semibold mb-1">ยินดีต้อนรับ <span
                                     class="ShowUserFullname">Admin</span></h3>
                            <p id="DashHeaderText" class="text-light mb-0">
                                ภาพรวมข้อมูล ตั้งแต่ <span id="DashDateFrom"><?php echo $dash_from_disp; ?></span> ถึง
                                <span id="DashDateTo"><?php echo $dash_to_disp; ?></span>
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="DashFilterFrom" class="form-control form-control-sm text-center bg-white bg-opacity-25 text-white placeholder-light border-0 shadow-none" style="width: 125px; border-radius: 8px;" placeholder="จากวันที่">
                            <span class="text-white small">ถึง</span>
                            <input type="text" id="DashFilterTo" class="form-control form-control-sm text-center bg-white bg-opacity-25 text-white placeholder-light border-0 shadow-none" style="width: 125px; border-radius: 8px;" placeholder="ถึงวันที่">
                        </div>
                    </div>
                </div>
            </div>

            <!-- การ์ดสถิติ (ตัวเลข + แนวโน้มเทียบช่วงก่อนหน้า โหลดจาก API จริง) -->
            <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-4 mb-4">
                <!-- 1. สมาชิกใหม่ -->
                <div class="col">
                    <div class="card stat-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="text-secondary fs-14 mb-0 d-inline-flex align-items-center gap-1">สมาชิกใหม่
                                    <span class="stat-info" tabindex="0" role="button" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="นับจากจำนวนสมาชิกที่ยืนยันตัวตนทั้งหมดแล้ว">
                                        <span class="material-symbols-outlined" aria-hidden="true"
                                            style="font-size: 14px;">info</span>
                                    </span>
                                </p>
                                <div class="stat-icon stat-icon--brand"
                                    style="width: 32px; height: 32px; font-size: 16px;">
                                    <span class="material-symbols-outlined" aria-hidden="true"
                                        style="font-size: 18px;">group</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-3 p-2 mt-2 gap-1 text-center">
                                <div class="flex-fill border-end" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelMembers_2025">2025</span>
                                    <strong class="fs-6 text-dark" id="StatMembers_2025">-</strong>
                                </div>
                                <div class="flex-fill border-end" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelMembers_2026">2026</span>
                                    <strong class="fs-6 text-primary" id="StatMembers_2026">-</strong>
                                </div>
                                <div class="flex-fill" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelMembers_Today">วันนี้</span>
                                    <strong class="fs-6 text-success" id="StatMembers_Today">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. คำสั่งซื้อใหม่ -->
                <div class="col">
                    <div class="card stat-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="text-secondary fs-14 mb-0 d-inline-flex align-items-center gap-1">
                                    คำสั่งซื้อใหม่
                                    <span class="stat-info" tabindex="0" role="button" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="นับจำนวนคำสั่งซื้อทั้งหมดที่สร้างภายในช่วงที่เลือก">
                                        <span class="material-symbols-outlined" aria-hidden="true"
                                            style="font-size: 14px;">info</span>
                                    </span>
                                </p>
                                <div class="stat-icon stat-icon--success"
                                    style="width: 32px; height: 32px; font-size: 16px;">
                                    <span class="material-symbols-outlined" aria-hidden="true"
                                        style="font-size: 18px;">shopping_cart</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-3 p-2 mt-2 gap-1 text-center">
                                <div class="flex-fill border-end" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelOrders_2025">2025</span>
                                    <strong class="fs-6 text-dark" id="StatOrders_2025">-</strong>
                                </div>
                                <div class="flex-fill border-end" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelOrders_2026">2026</span>
                                    <strong class="fs-6 text-primary" id="StatOrders_2026">-</strong>
                                </div>
                                <div class="flex-fill" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelOrders_Today">วันนี้</span>
                                    <strong class="fs-6 text-success" id="StatOrders_Today">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. ยอดเงินคำสั่งซื้อ -->
                <div class="col">
                    <div class="card stat-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="text-secondary fs-14 mb-0 d-inline-flex align-items-center gap-1">ยอดเงิน
                                    <span class="stat-info" tabindex="0" role="button" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="ผลรวมยอดเงินของคำสั่งซื้อที่ชำระเงินแล้ว ภายในช่วงที่เลือก">
                                        <span class="material-symbols-outlined" aria-hidden="true"
                                            style="font-size: 14px;">info</span>
                                    </span>
                                </p>
                                <div class="stat-icon stat-icon--warning"
                                    style="width: 32px; height: 32px; font-size: 16px;">
                                    <span class="material-symbols-outlined" aria-hidden="true"
                                        style="font-size: 18px;">payments</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-3 p-2 mt-2 gap-1 text-center">
                                <div class="flex-fill border-end" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelRevenue_2025">2025</span>
                                    <strong class="fs-6 text-dark" id="StatRevenue_2025">-</strong>
                                </div>
                                <div class="flex-fill border-end" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelRevenue_2026">2026</span>
                                    <strong class="fs-6 text-primary" id="StatRevenue_2026">-</strong>
                                </div>
                                <div class="flex-fill" style="width: 33%;">
                                    <span class="d-block text-secondary mb-1 fw-medium" style="font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="LabelRevenue_Today">วันนี้</span>
                                    <strong class="fs-6 text-success" id="StatRevenue_Today">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. เครดิต OTP / STANDARD SMS -->
                <div class="col">
                    <div class="card stat-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="text-secondary fs-14 mb-0 d-inline-flex align-items-center gap-1">OTP คงเหลือ
                                    <span class="stat-info" tabindex="0" role="button" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="จำนวนเครดิต STANDARD SMS / OTP คงเหลือจาก ThaiBulkSMS">
                                        <span class="material-symbols-outlined" aria-hidden="true"
                                            style="font-size: 14px;">info</span>
                                    </span>
                                </p>
                                <div class="stat-icon stat-icon--info"
                                    style="width: 32px; height: 32px; font-size: 16px;">
                                    <span class="material-symbols-outlined" aria-hidden="true"
                                        style="font-size: 18px;">sms</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <h4 class="mb-0 mt-2">
                                    <span id="StatOtpBalance" class="stat-value text-primary">–</span>
                                    <small id="StatOtpUnit" class="fs-14 fw-normal text-secondary">เครดิต</small>
                                </h4>
                                <span id="StatOtpSubtext" class="empty-state mt-2 d-block text-secondary"
                                    style="font-size:12px;">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"
                                        aria-hidden="true"></span> กำลังดึงข้อมูล...
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. ออนไลน์ขณะนี้ (ใหม่) -->
                <div class="col">
                    <div class="card stat-card bg-white border-0 rounded-3 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="text-secondary fs-14 mb-0 d-inline-flex align-items-center gap-1">
                                    ใช้งานอยู่ตอนนี้</p>
                                <div class="stat-icon stat-icon--success"
                                    style="width: 32px; height: 32px; font-size: 16px;">
                                    <span class="material-symbols-outlined" aria-hidden="true"
                                        style="font-size: 18px;">sensors</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <h4 class="mb-0 text-success d-flex align-items-center gap-2">
                                    <!-- <span class="position-relative d-flex" style="width: 10px; height: 10px;">
                                        <span class="animate-ping position-absolute h-100 w-100 rounded-circle bg-success opacity-75" style="animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
                                        <span class="position-relative rounded-circle h-100 w-100 bg-success"></span>
                                    </span> -->
                                    <span id="StatOnlineUsers" class="stat-value">14</span> <small
                                        class="fs-14 fw-normal text-secondary">คน</small>
                                </h4>
                                <span class="stat-compare text-secondary d-block mt-3">ข้อมูลเรียลไทม์
                                    (Real-time)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- กราฟยอดขายรายวัน (ข้อมูลจริงจาก get_dashboard) -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div
                    class="card-header bg-white border-0 p-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1" id="ChartTitle">ยอดขายแยกเป็นรายวัน</h5>
                        <p class="text-secondary fs-14 mb-0" id="ChartSubtitle">
                            สรุปยอดขายเฉพาะคำสั่งซื้อที่สำเร็จแล้วแยกเป็นรายวัน</p>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary fs-12 px-3 py-2 rounded-pill">หน่วย: บาท
                        (฿)</span>
                </div>
                <div class="card-body p-4">
                    <div id="DashSalesChart"></div>
                </div>
            </div>

            <!-- ตารางจัดอันดับหลักสูตรยอดฮิต -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div
                    class="card-header bg-white border-0 p-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <!-- <h5 class="mb-1">10 อันดับหลักสูตรยอดฮิต</h5> -->
                        <p class="text-secondary fs-14 mb-0">สรุปการสั่งซื้อหลักสูตรที่มีคนซื้อมากที่สุด
                            (เฉพาะออเดอร์ที่ชำระเงินสำเร็จแล้ว)</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary text-nowrap">
                                <tr>
                                    <th scope="col" rowspan="2" class="py-3 px-3 rounded-start border-0 align-middle" style="width: 80px;">อันดับ</th>
                                    <th scope="col" rowspan="2" class="py-3 px-3 border-0 align-middle">ชื่อหลักสูตร</th>
                                    <th scope="col" colspan="2" class="py-2 px-3 border-0 text-center">ขายได้ (Order)</th>
                                    <th scope="col" colspan="2" class="py-2 px-3 border-0 text-center rounded-end">ยอดขาย (บาท)</th>
                                </tr>
                                <tr>
                                    <th scope="col" class="py-2 px-3 border-0 text-center small fw-semibold" style="min-width: 90px;" id="lblTopCourseYearThis1">ปีนี้</th>
                                    <th scope="col" class="py-2 px-3 border-0 text-center small text-secondary" style="min-width: 90px;" id="lblTopCourseYearPrev1">ปีที่แล้ว</th>
                                    <th scope="col" class="py-2 px-3 border-0 text-end small fw-semibold" style="min-width: 110px;" id="lblTopCourseYearThis2">ปีนี้</th>
                                    <th scope="col" class="py-2 px-3 border-0 text-end small text-secondary" style="min-width: 110px;" id="lblTopCourseYearPrev2">ปีที่แล้ว</th>
                                </tr>
                            </thead>
                            <tbody id="TopCoursesTable">
                                <!-- ข้อมูลจะถูกดึงมาใส่ตรงนี้ผ่าน JS -->
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-secondary">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <?php include "footer.php"; ?>
    </div>
</div>

<div class="modal fade" id="mainModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 1200px;">
        <div class="modal-content animated fadeIn" id="LoadingMainModal">
            <div id="showMainModal"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content animated fadeIn" id="LoadingMyModal">
            <div id="showModal"></div>
        </div>
    </div>
</div>

<div class="modal" id="subModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content animated fadeIn" id="LoadingSubModal">
            <div id="showSubModal"></div>
        </div>
    </div>
</div>

<?php include "script.php"; ?>

</body>

</html>

<script>
    var salesChart = null;
    var currentCompareMode = 'period'; // 'period' หรือ 'year'


    // สร้าง/อัปเดตกราฟยอดขายรายวัน (กราฟแท่ง)
    function RenderSalesChart(labels, salesThisYear, salesLastYear, nameThisYear, nameLastYear) {
        if (typeof ApexCharts === "undefined" || !document.getElementById("DashSalesChart")) { return; }

        nameThisYear = nameThisYear || "ช่วงปีที่เลือก";
        nameLastYear = nameLastYear || "ช่วงปีก่อนหน้า";

        var seriesData = [
            { name: nameThisYear, data: salesThisYear },
            { name: nameLastYear, data: salesLastYear }
        ];

        if (salesChart) {
            salesChart.updateOptions({ series: seriesData, xaxis: { categories: labels } });
            return;
        }
        salesChart = new ApexCharts(document.getElementById("DashSalesChart"), {
            chart: { type: "bar", height: 360, fontFamily: "'Kanit', sans-serif", toolbar: { show: false }, zoom: { enabled: false } },
            series: seriesData,
            colors: ["#605DFF", "#facc15"],
            plotOptions: {
                bar: { horizontal: false, columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: labels,
                labels: { style: { fontSize: "12px", colors: "#64748b" } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                min: 0,
                tickAmount: 4,
                labels: { style: { colors: "#64748b" }, formatter: function (v) { return (typeof NumberFormat === "function") ? NumberFormat(Math.round(v)) : Math.round(v); } }
            },
            fill: { opacity: 1 },
            tooltip: { y: { formatter: function (v) { return ((typeof NumberFormat === "function") ? NumberFormat(v) : v) + " ฿"; } } },
            legend: { position: 'top', horizontalAlign: 'right' }
        });
        salesChart.render();
    }

    // เปิดใช้งาน Bootstrap tooltip สำหรับไอคอน info บนการ์ดสถิติ
    function InitStatTooltips() {
        if (typeof bootstrap === "undefined" || !bootstrap.Tooltip) { return; }
        document.querySelectorAll('.stat-info[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) { new bootstrap.Tooltip(el); }
        });
    }

    // โหลดข้อมูลจริง
    function LoadDashboard() {
        var nf = function (v) { return (typeof NumberFormat === "function") ? NumberFormat(v) : v; };

        var fromVal = $("#DashFilterFrom").val();
        var toVal   = $("#DashFilterTo").val();

        // แปลงฟอร์แมตวันที่จาก d/m/Y เป็น Y-m-d สำหรับส่งให้ PHP backend
        var convertDate = function(dStr) {
            if (!dStr) return "";
            var parts = dStr.split("/");
            if (parts.length === 3) {
                return parts[2] + "-" + parts[1] + "-" + parts[0];
            }
            return dStr;
        };

        var fromYmd = convertDate(fromVal);
        var toYmd   = convertDate(toVal);

        $.ajax({
            type: "POST", url: "core.php",
            data: {
                request_state: "dashboard",
                request_function: "get_dashboard",
                from: fromYmd,
                to: toYmd
            },
            dataType: "json",
            success: function (r) {
                if (r.result != 1) { return; }
                var d = r.data;

                // อัปเดตช่วงวันที่บน Header
                var dateFrom = (d.period && d.period.from) ? d.period.from : "";
                var dateTo = (d.period && d.period.to) ? d.period.to : "";
                $("#DashDateFrom").text(dateFrom);
                $("#DashDateTo").text(dateTo);
                $("#DashHeaderText").html('ภาพรวมข้อมูล ตั้งแต่ <span id="DashDateFrom">' + dateFrom + '</span> ถึง <span id="DashDateTo">' + dateTo + '</span>');

                // อัปเดตปีของหัวตารางหลักสูตรยอดฮิต (ใช้ ค.ศ.)
                var yearThis = d.period.year_this_ce ? 'ปี ' + d.period.year_this_ce : 'ปีนี้';
                var yearPrev = d.period.year_prev_ce ? 'ปี ' + d.period.year_prev_ce : 'ปีที่แล้ว';
                $("#lblTopCourseYearThis1").text(yearThis);
                $("#lblTopCourseYearPrev1").text(yearPrev);
                $("#lblTopCourseYearThis2").text(yearThis);
                $("#lblTopCourseYearPrev2").text(yearPrev);

                // อัปเดตช่วงวันใต้คอลัมน์เปรียบเทียบ 3 ช่อง
                var prevRange = d.period.prev_from + " - " + d.period.prev_to;
                var currentRange = d.period.from + " - " + d.period.to;
                var todayRange = "วันนี้ (" + d.period.today + ")";

                // 1. สมาชิกใหม่
                $("#LabelMembers_2025").text(prevRange).attr("title", prevRange);
                $("#LabelMembers_2026").text(currentRange).attr("title", currentRange);
                $("#LabelMembers_Today").text(todayRange).attr("title", todayRange);

                $("#StatMembers_2025").text(nf(d.members_prev || 0));
                $("#StatMembers_2026").text(nf(d.members_this || 0));
                $("#StatMembers_Today").text(nf(d.members_today || 0));

                // 2. คำสั่งซื้อใหม่
                $("#LabelOrders_2025").text(prevRange).attr("title", prevRange);
                $("#LabelOrders_2026").text(currentRange).attr("title", currentRange);
                $("#LabelOrders_Today").text(todayRange).attr("title", todayRange);

                $("#StatOrders_2025").text(nf(d.orders_prev || 0));
                $("#StatOrders_2026").text(nf(d.orders_this || 0));
                $("#StatOrders_Today").text(nf(d.orders_today || 0));

                // 3. ยอดเงิน
                $("#LabelRevenue_2025").text(prevRange).attr("title", prevRange);
                $("#LabelRevenue_2026").text(currentRange).attr("title", currentRange);
                $("#LabelRevenue_Today").text(todayRange).attr("title", todayRange);

                $("#StatRevenue_2025").text(nf(Math.round(d.revenue_prev || 0)));
                $("#StatRevenue_2026").text(nf(Math.round(d.revenue_this || 0)));
                $("#StatRevenue_Today").text(nf(Math.round(d.revenue_today || 0)));

                // ออนไลน์ & SMS
                $("#StatOnlineUsers").text(nf(d.online_users || 0));

                // อัปเดตหัวข้อกราฟตามช่วงวันที่
                var tFrom = new Date(fromVal);
                var tTo = new Date(toVal);
                var diffDays = Math.round((tTo - tFrom) / (1000 * 60 * 60 * 24));
                if (diffDays <= 31) {
                    $("#ChartTitle").text("ยอดขายแยกเป็นรายวัน");
                    $("#ChartSubtitle").text("สรุปยอดขายเฉพาะคำสั่งซื้อที่สำเร็จแล้วแยกเป็นรายวัน");
                } else {
                    $("#ChartTitle").text("ยอดขายแยกเป็นรายเดือน");
                    $("#ChartSubtitle").text("สรุปยอดขายเฉพาะคำสั่งซื้อที่สำเร็จแล้วแยกเป็นรายเดือน");
                }

                // เรนเดอร์ตารางหลักสูตรยอดฮิต
                var htmlCourses = '';
                if (d.top_courses && d.top_courses.length > 0) {
                    $.each(d.top_courses, function (index, course) {
                        var rank = index + 1;
                        htmlCourses += `
                            <tr>
                                <td class="px-3 text-center fw-bold text-dark">${rank}</td>
                                <td class="px-3 fw-medium text-dark">${course.course_name || 'ไม่ระบุชื่อหลักสูตร'}</td>
                                <td class="px-3 text-center fw-semibold text-dark">${nf(course.total_sold_this)}</td>
                                <td class="px-3 text-center text-secondary">${nf(course.total_sold_prev)}</td>
                                <td class="px-3 text-end fw-semibold text-dark">${nf(course.total_revenue_this)}</td>
                                <td class="px-3 text-end text-secondary">${nf(course.total_revenue_prev)}</td>
                            </tr>
                        `;
                    });
                } else {
                    htmlCourses = `
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <span class="material-symbols-outlined fs-1  opacity-50 mb-2">shopping_bag</span>
                                <p class="mb-0">ไม่มีข้อมูลการสั่งซื้อในช่วงเวลานี้</p>
                            </td>
                        </tr>
                    `;
                }
                $("#TopCoursesTable").html(htmlCourses);

                if (d.chart) {
                    RenderSalesChart(d.chart.labels, d.chart.sales_this_year, d.chart.sales_last_year, d.chart.name_this_year, d.chart.name_last_year);
                }
            },
            error: function (j, e) { if (typeof ShowErrorAjax === "function") { ShowErrorAjax(j, e); } }
        });
    }

    function LoadSmsCredit() {
        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "dashboard",
                request_function: "get_sms_credit"
            },
            dataType: "json",
            success: function (r) {
                var nf = function (v) { return (typeof NumberFormat === "function") ? NumberFormat(v) : v; };
                if (r && r.result == 1 && r.data) {
                    var formatted = r.data.formatted || nf(r.data.standard_sms || 0);
                    var creditType = r.data.credit_type || 'STANDARD SMS';
                    $("#StatOtpBalance").text(formatted).removeClass("text-secondary").addClass("text-primary");
                    $("#StatOtpUnit").text("เครดิต");
                    $("#StatOtpSubtext").html('<span class="material-symbols-outlined text-success" style="font-size:14px; vertical-align: middle;" aria-hidden="true">check_circle</span> ' + creditType + ' (ThaiBulkSMS)');
                } else {
                    $("#StatOtpBalance").text("0").addClass("text-secondary");
                    $("#StatOtpUnit").text("เครดิต");
                    $("#StatOtpSubtext").html('<span class="material-symbols-outlined text-danger" style="font-size:14px; vertical-align: middle;" aria-hidden="true">error</span> ' + (r && r.msg ? r.msg : "ไม่สามารถเชื่อมต่อได้"));
                }
            },
            error: function () {
                $("#StatOtpBalance").text("—").addClass("text-secondary");
                $("#StatOtpSubtext").html('<span class="material-symbols-outlined text-warning" style="font-size:14px; vertical-align: middle;" aria-hidden="true">warning</span> เชื่อมต่อไม่สำเร็จ');
            }
        });
    }

    $(document).ready(function () {
        InitStatTooltips();
        
        flatpickr("#DashFilterFrom", {
            dateFormat: "d/m/Y",
            allowInput: true,
            defaultDate: "<?php echo date('01/m/Y'); ?>",
            onChange: function() { LoadDashboard(); }
        });
        
        flatpickr("#DashFilterTo", {
            dateFormat: "d/m/Y",
            allowInput: true,
            defaultDate: "<?php echo date('d/m/Y'); ?>",
            onChange: function() { LoadDashboard(); }
        });

        LoadDashboard();
        LoadSmsCredit();
    });
</script>