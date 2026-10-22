<?php

if (!function_exists('log_error')) {

    function log_error($message, $context = '') {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp]";
        if (!empty($context)) {
            $log_message .= " [$context]";
        }
        $log_message .= " $message" . PHP_EOL;


        $log_file = __DIR__ . '/../logs/app.log';

        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        error_log($log_message, 3, $log_file);
    }
}

if (!function_exists('log_warning')) {

    function log_warning($message, $context = '') {
        log_error($message, $context);
    }
}

if (!function_exists('log_info')) {

    function log_info($message, $context = '') {
        log_error($message, $context);
    }
}

if (!function_exists('validate_csrf_token')) {

    function validate_csrf_token(?string $token): bool {
        return !empty($token)
            && !empty($_SESSION["csrf_token"])
            && hash_equals((string) $_SESSION["csrf_token"], (string) $token);
    }
}
?>