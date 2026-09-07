<?php
    namespace App\Date;

    use App\Date\HelperDate;

    class ThaiDate
    {
        private const MONTH_TH_FULL = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม'
        ];

        private const MONTH_TH_SHORT = [
            1 => 'ม.ค.',
            2 => 'ก.พ.',
            3 => 'มี.ค.',
            4 => 'เม.ย.',
            5 => 'พ.ค.',
            6 => 'มิ.ย.',
            7 => 'ก.ค.',
            8 => 'ส.ค.',
            9 => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.'
        ];

        /**
         * ThaiDate
         * -----------------------------
         * @param string $datetime  'YYYY-MM-DD HH:ii:ss' หรือ 'YYYY-MM-DD'
         * @param string $format    รูปแบบวันที่ (ดูรายการด้านล่าง)
         * @return string           วันที่ภาษาไทย หรือ "รูปแบบวันที่ไม่ถูกต้อง"
         *
         * FORMAT OPTIONS:
         *  - full_date_and_time      => 19 ธันวาคม 2568 เวลา 10:10
         *  - full_date_short_time    => 19 ธันวาคม 2568 - 10:10
         *  - short_date_short_time   => 19 ธ.ค. 2568 - 10:10
         *  - full_date               => 19 ธันวาคม 2568
         *  - no_date_full_month      => ธันวาคม 2568
         *  - no_date_short_month     => ธ.ค. 2568
         *  - short_date              => 19 ธ.ค. 2568
         *  - number_date_thai        => 19/12/2568
         *  - number_date_eng         => 19/12/2025
         *  - thai_excel_dot          => 19.12.2568
         */
        public static function toThaiDate(string $datetime, string $format): string
        {
            $datetime = trim($datetime);
            $date = HelperDate::parseDate($datetime);

            if ($date === null) {
                return HelperDate::ERROR;
            }

            $hasTime = strlen($datetime) === 19;
            if (!$hasTime && in_array($format, HelperDate::FORMATS_REQUIRING_TIME, true)) {
                return HelperDate::ERROR;
            }

            $day = (int) $date->format('j');
            $month = (int) $date->format('n');
            $year = (int) $date->format('Y');
            $buddhistYear = $year + 543;
            $time = $date->format('H:i');
            $dayPadded = $date->format('d');
            $monthPadded = $date->format('m');

            switch ($format) {
                case 'full_date_and_time':
                    return "{$day} " . self::MONTH_TH_FULL[$month] . " {$buddhistYear} เวลา {$time}";
                case 'full_date_short_time':
                    return "{$day} " . self::MONTH_TH_FULL[$month] . " {$buddhistYear} - {$time}";
                case 'short_date_short_time':
                    return "{$day} " . self::MONTH_TH_SHORT[$month] . " {$buddhistYear} - {$time}";
                case 'full_date':
                    return "{$day} " . self::MONTH_TH_FULL[$month] . " {$buddhistYear}";
                case 'short_date':
                    return "{$day} " . self::MONTH_TH_SHORT[$month] . " {$buddhistYear}";
                case 'no_date_full_month':
                    return self::MONTH_TH_FULL[$month] . " {$buddhistYear}";
                case 'no_date_short_month':
                    return self::MONTH_TH_SHORT[$month] . " {$buddhistYear}";
                case 'number_date_thai':
                    return "{$dayPadded}/{$monthPadded}/{$buddhistYear}";
                case 'number_date_eng':
                    return "{$dayPadded}/{$monthPadded}/{$year}";
                case 'thai_excel_dot':
                    return "{$dayPadded}.{$monthPadded}.{$buddhistYear}";
                default:
                    return HelperDate::ERROR;
            }
        }
    }