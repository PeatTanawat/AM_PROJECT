<?php
    namespace App\String;

    class Random 
    {
        public static function randomCode(int $length): string 
        {
            $possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz";
            $str = "";
            while (strlen($str) < $length) {
                $str .= substr($possible, (rand() % strlen($possible)), 1);
            }
            return $str;
        }

        public static function randomNumericCode(int $length): string 
        {
            $possible = "0123456789";
            $str = "";
            while (strlen($str) < $length) {
                $str .= substr($possible, (rand() % strlen($possible)), 1);
            }
            return $str;
        }
    }
?>