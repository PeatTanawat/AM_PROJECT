<?php
// หน้าแรก (dashboard) — สถิติ + ยอดขายรายวัน ตามช่วงวันที่
// สมาชิกใหม่: tbl_user.create_at / คำสั่งซื้อใหม่: tbl_orders.created_at
// ยอดเงิน + กราฟ: tbl_orders ที่ payment_status='1' (จ่ายแล้ว)

use App\Database\Connection;
use App\Utility\Auth;
use App\Utility\Response;

$access_token = Auth::requireUserToken();
$admin_id     = $access_token->user_id ?? null;
if (! $admin_id) {
    Response::json(0, 'Unauthorized', null);
}

$db_instance = new Connection();
$pdo_connect = $db_instance->getPdo();
if (! $pdo_connect) {
    Response::json(0, 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้', null);
}

// รับค่าจาก Filter ช่วงวันที่ (รูปแบบ Y-m-d)
$from = $_POST['from'] ?? '';
$to   = $_POST['to'] ?? '';

if (empty($from)) {
    $from = date('Y-m-01'); // เริ่มต้นวันแรกของเดือนนี้
}
if (empty($to)) {
    $to = date('Y-m-d'); // สิ้นสุดวันนี้
}

$from_prev = date('Y-m-d', strtotime($from . ' -1 year'));
$to_prev   = date('Y-m-d', strtotime($to . ' -1 year'));
$today     = date('Y-m-d');

$diff_seconds = strtotime($to) - strtotime($from);
$diff_days    = round($diff_seconds / (60 * 60 * 24));

// กำหนดป้ายชื่อกราฟ และวิธีกรองข้อมูลกราฟ
if ($diff_days <= 31) {
    // รายวัน
    $chart_labels = [];
    $chart_keys   = [];
    $current      = strtotime($from);
    $to_time      = strtotime($to);
    while ($current <= $to_time) {
        $chart_labels[] = date('d/m', $current);
        $chart_keys[]   = date('Y-m-d', $current);
        $current        = strtotime('+1 day', $current);
    }
    $series_name_this_year = "ช่วงปีที่เลือก (" . date('Y', strtotime($from)) . ")";
    $series_name_last_year = "ช่วงปีก่อนหน้า (" . date('Y', strtotime($from_prev)) . ")";
} else {
    // รายเดือน
    $chart_labels = [];
    $chart_keys   = [];
    $current      = strtotime($from);
    $to_time      = strtotime($to);
    while ($current <= $to_time) {
        $ym = date('Y-m', $current);
        if (! in_array($ym, $chart_keys, true)) {
            $chart_keys[]   = $ym;
            $chart_labels[] = date('m/Y', $current);
        }
        $current = strtotime('+1 month', $current);
    }
    $series_name_this_year = "ช่วงปีที่เลือก";
    $series_name_last_year = "ช่วงปีก่อนหน้า";
}

try {
    // ผู้ใช้ออนไลน์ (ความเคลื่อนไหวใน 5 นาทีล่าสุด)
    $st = $pdo_connect->prepare("SELECT COUNT(DISTINCT user_id)
                                FROM tbl_login_token
                                WHERE last_active_at >= NOW() - INTERVAL 5 MINUTE");
    $st->execute();
    $online_users = (int) $st->fetchColumn();
    $st->closeCursor();

    // ตัวแปรฟังก์ชันช่วยดึงสถิติรายช่วงเวลา
    $get_members = function ($f, $t) use ($pdo_connect) {
        $st = $pdo_connect->prepare("SELECT COUNT(*)
                                    FROM tbl_user
                                    WHERE delete_at IS NULL
                                    AND email_status = '1'
                                    AND identity_verified = '2'
                                    AND DATE(create_at) BETWEEN :f AND :t");
        $st->execute([':f' => $f, ':t' => $t]);
        $c = (int) $st->fetchColumn();
        $st->closeCursor();
        return $c;
    };

    $get_orders = function ($f, $t) use ($pdo_connect) {
        $st = $pdo_connect->prepare("SELECT COUNT(*)
                                     FROM tbl_orders
                                     WHERE DATE(created_at) BETWEEN :f AND :t");
        $st->execute([':f' => $f, ':t' => $t]);
        $c = (int) $st->fetchColumn();
        $st->closeCursor();
        return $c;
    };

    $get_revenue = function ($f, $t) use ($pdo_connect) {
        $st = $pdo_connect->prepare("SELECT COALESCE(SUM(total_price),0)
                                     FROM tbl_orders
                                     WHERE payment_status = '1'
                                     AND DATE(created_at) BETWEEN :f AND :t");
        $st->execute([':f' => $f, ':t' => $t]);
        $c = (float) $st->fetchColumn();
        $st->closeCursor();
        return $c;
    };

    // ดึงข้อมูล 3 มิติ (ปีที่เลือก, ปีก่อนหน้า, วันนี้)
    $members_this  = $get_members($from, $to);
    $members_prev  = $get_members($from_prev, $to_prev);
    $members_today = $get_members($today, $today);

    $orders_this  = $get_orders($from, $to);
    $orders_prev  = $get_orders($from_prev, $to_prev);
    $orders_today = $get_orders($today, $today);

    $revenue_this  = $get_revenue($from, $to);
    $revenue_prev  = $get_revenue($from_prev, $to_prev);
    $revenue_today = $get_revenue($today, $today);

    // ---- ข้อมูลจัดอันดับหลักสูตรยอดฮิต (Top Selling Courses) ----
    $st = $pdo_connect->prepare("
        SELECT
            c.course_id,
            c.course_name,
            COUNT(od.detail_id) as total_sold_this,
            COALESCE(SUM(od.price_at_purchase), 0) as total_revenue_this
        FROM tbl_orders o
        JOIN tbl_order_detail od ON o.order_id = od.order_id
        LEFT JOIN tbl_course c ON c.course_id = od.course_id
        WHERE o.payment_status = '1' AND DATE(o.created_at) BETWEEN :f AND :t
        GROUP BY c.course_id, c.course_name
        ORDER BY total_sold_this DESC, total_revenue_this DESC
        LIMIT 10
    ");
    $st->execute([':f' => $from, ':t' => $to]);
    $top_courses = $st->fetchAll(PDO::FETCH_ASSOC);
    $st->closeCursor();

    // ดึงยอดปีที่แล้วสำหรับแต่ละคอร์สที่ติดอันดับ
    foreach ($top_courses as &$course) {
        $cid = (int) $course['course_id'];
        
        $st_prev = $pdo_connect->prepare("
            SELECT
                COUNT(od.detail_id) as total_sold_prev,
                COALESCE(SUM(od.price_at_purchase), 0) as total_revenue_prev
            FROM tbl_orders o
            JOIN tbl_order_detail od ON o.order_id = od.order_id
            WHERE o.payment_status = '1' 
              AND od.course_id = :cid 
              AND DATE(o.created_at) BETWEEN :f_prev AND :t_prev
        ");
        $st_prev->execute([
            ':cid' => $cid,
            ':f_prev' => $from_prev,
            ':t_prev' => $to_prev
        ]);
        $prev_data = $st_prev->fetch(PDO::FETCH_ASSOC);
        $st_prev->closeCursor();
        
        $course['total_sold_prev'] = (int) ($prev_data['total_sold_prev'] ?? 0);
        $course['total_revenue_prev'] = (float) ($prev_data['total_revenue_prev'] ?? 0);
        
        $course['total_sold_this'] = (int) $course['total_sold_this'];
        $course['total_revenue_this'] = (float) $course['total_revenue_this'];
    }
    unset($course);

    // ---- เตรียมข้อมูลสำหรับกราฟแท่ง (Chart Data) ----
    $sales_this_year = array_fill_keys($chart_keys, 0);
    $sales_last_year = [];

    if ($diff_days <= 31) {
        // รายวัน: ดึงยอดขายของช่วงปีนี้
        $st = $pdo_connect->prepare("SELECT DATE(created_at) AS k, COALESCE(SUM(total_price),0) AS s
                                     FROM tbl_orders
                                     WHERE payment_status = '1' AND DATE(created_at) BETWEEN :f AND :t
                                     GROUP BY DATE(created_at)");
        $st->execute([':f' => $from, ':t' => $to]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sales_this_year[$r['k']] = (float) $r['s'];
        }
        $st->closeCursor();

        // รายวัน: ดึงยอดขายของช่วงปีก่อนหน้า
        $last_year_keys = [];
        foreach ($chart_keys as $k) {
            $prev_date                  = date('Y-m-d', strtotime($k . ' -1 year'));
            $last_year_keys[$prev_date] = 0;
        }
        $st = $pdo_connect->prepare("SELECT DATE(created_at) AS k, COALESCE(SUM(total_price),0) AS s
                                     FROM tbl_orders
                                     WHERE payment_status = '1' AND DATE(created_at) BETWEEN :f AND :t
                                     GROUP BY DATE(created_at)");
        $st->execute([':f' => $from_prev, ':t' => $to_prev]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $last_year_keys[$r['k']] = (float) $r['s'];
        }
        $st->closeCursor();
        $sales_last_year = array_values($last_year_keys);
    } else {
        // รายเดือน: ดึงยอดขายของช่วงปีนี้
        $st = $pdo_connect->prepare("SELECT CONCAT(YEAR(created_at), '-', LPAD(MONTH(created_at), 2, '0')) AS k, COALESCE(SUM(total_price),0) AS s
                                     FROM tbl_orders
                                     WHERE payment_status = '1' AND DATE(created_at) BETWEEN :f AND :t
                                     GROUP BY YEAR(created_at), MONTH(created_at)");
        $st->execute([':f' => $from, ':t' => $to]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sales_this_year[$r['k']] = (float) $r['s'];
        }
        $st->closeCursor();

        // รายเดือน: ดึงยอดขายของช่วงปีก่อนหน้า
        $last_year_keys = [];
        foreach ($chart_keys as $k) {
            $parts                    = explode('-', $k);
            $prev_y                   = (int) $parts[0] - 1;
            $prev_ym                  = sprintf("%04d-%02d", $prev_y, (int) $parts[1]);
            $last_year_keys[$prev_ym] = 0;
        }
        $st = $pdo_connect->prepare("SELECT CONCAT(YEAR(created_at), '-', LPAD(MONTH(created_at), 2, '0')) AS k, COALESCE(SUM(total_price),0) AS s
                                     FROM tbl_orders
                                     WHERE payment_status = '1' AND DATE(created_at) BETWEEN :f AND :t
                                     GROUP BY YEAR(created_at), MONTH(created_at)");
        $st->execute([':f' => $from_prev, ':t' => $to_prev]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $last_year_keys[$r['k']] = (float) $r['s'];
        }
        $st->closeCursor();
        $sales_last_year = array_values($last_year_keys);
    }

    Response::json(1, 'สำเร็จ', [
        'online_users'  => $online_users,
        'members_this'  => $members_this,
        'members_prev'  => $members_prev,
        'members_today' => $members_today,
        'orders_this'   => $orders_this,
        'orders_prev'   => $orders_prev,
        'orders_today'  => $orders_today,
        'revenue_this'  => $revenue_this,
        'revenue_prev'  => $revenue_prev,
        'revenue_today' => $revenue_today,
        'top_courses'   => $top_courses,
        'chart'         => [
            'labels'          => $chart_labels,
            'sales_this_year' => array_values($sales_this_year),
            'sales_last_year' => $sales_last_year,
            'name_this_year'  => $series_name_this_year,
            'name_last_year'  => $series_name_last_year,
        ],
        'period'        => [
            'from'      => date('d/m/Y', strtotime($from)),
            'to'        => date('d/m/Y', strtotime($to)),
            'prev_from' => date('d/m/Y', strtotime($from_prev)),
            'prev_to'   => date('d/m/Y', strtotime($to_prev)),
            'today'     => date('d/m/Y', strtotime($today)),
            'year_this_be' => date('Y', strtotime($from)) + 543,
            'year_prev_be' => date('Y', strtotime($from_prev)) + 543,
            'year_this_ce' => date('Y', strtotime($from)),
            'year_prev_ce' => date('Y', strtotime($from_prev)),
        ],
    ]);

} catch (\Throwable $e) {
    error_log('GetDashboard Error: ' . $e->getMessage());
    Response::json(0, 'เกิดข้อผิดพลาด', null);
}
