<?php
require_once(__DIR__ .'/../config/constants.php');

class Message{

    public static function error($error_code, $error_message = ""): string
    {
        return "<div class='col-12 col-lg-6 alert alert-danger alert-dismissible fade show' role='alert'>
        <strong><i class='bi bi-exclamation-octagon-fill'></i> An error occurred!</strong><br>".
            ERROR_CODES[$error_code] . "<br>" . $error_message .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }

    public static function success($success_code, $success_message = ""): string
    {
        return "<div class='col-12 col-lg-6 alert alert-success alert-dismissible fade show' role='alert'>
        <strong><i class='bi bi-check-circle-fill'></i> Success!</strong><br>".
            SUCCESS_CODES[$success_code] . " " . $success_message .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }

    /**
     * Display all flash messages from session and clear them
     * @return string HTML output of all flash messages
     */
    public static function displayFlashMessages(): string
    {
        if (empty($_SESSION['flash_messages'])) {
            return '';
        }

        $output = '';
        foreach ($_SESSION['flash_messages'] as $flash) {
            $type = $flash['type'] ?? 'info';
            $code = $flash['code'] ?? 0;
            $message = $flash['message'] ?? '';

            if ($type === 'error') {
                $output .= self::error($code, $message);
            } elseif ($type === 'success') {
                $output .= self::success($code, $message);
            }
        }

        // Clear flash messages after displaying
        unset($_SESSION['flash_messages']);

        return $output;
    }
}