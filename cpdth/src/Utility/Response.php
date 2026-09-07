<?php
    namespace App\Utility;

    class Response {
        public static function json(int $result, string $msg, mixed $data = null, int $httpCode = 200): void {
            if (ob_get_length()) ob_clean();

            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'result'    => $result,
                'msg'       => $msg,
                'data'      => $data,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
?>