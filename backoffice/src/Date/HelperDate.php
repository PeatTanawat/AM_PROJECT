<?php
    namespace App\Date;

    use DateTimeImmutable;

    class HelperDate
    {
        public const ERROR = 'รูปแบบวันที่ไม่ถูกต้อง';
        
        public const FORMATS_REQUIRING_TIME = [
            'full_date_and_time',
            'full_date_short_time',
            'short_date_short_time'
        ];

        public static function parseDate(string $value): ? DateTimeImmutable
        {
            $allowedFormats = [
                'Y-m-d H:i:s' => '!Y-m-d H:i:s',
                'Y-m-d' => '!Y-m-d'
            ];

            foreach ($allowedFormats as $outputFormat => $inputFormat) {
                $date = DateTimeImmutable::createFromFormat($inputFormat, $value);
                if ($date === false) {
                    continue;
                }

                $errors = DateTimeImmutable::getLastErrors();
                if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                    continue;
                }

                if ($date->format($outputFormat) === $value) {
                    return $date;
                }
            }

            return null;
        }
    }