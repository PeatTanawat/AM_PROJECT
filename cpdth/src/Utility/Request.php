<?php
    namespace App\Utility;

    class Request {
        public static function handleRequest($request_function, $function_list, $file){
            if (in_array($request_function, $function_list)) {
                include $file;
            } else {
                Response::json(9, "Not found function");
            }
        }
    }
?>