<?php
class Response {
    public static function success($data = null, $message = "Success", $code = 200) {
        http_response_code($code);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public static function error($message = "Error", $code = 400, $data = null) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }

    public static function unauthorized($message = "Unauthorized") {
        self::error($message, 401);
    }

    public static function forbidden($message = "Forbidden") {
        self::error($message, 403);
    }

    public static function validation($errors) {
        self::error("Validation failed", 422, $errors);
    }
}
?>