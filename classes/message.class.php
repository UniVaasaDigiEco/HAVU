<?php
require_once(__DIR__ .'/../config/constants.php');

class Message{

    private static function getCodeMessage(string $type, int $code): string
    {
        if ($type === 'error') {
            return HavuLocale::translate('messages.error_codes.' . $code, [], ERROR_CODES[$code] ?? '');
        }

        if ($type === 'success') {
            return HavuLocale::translate('messages.success_codes.' . $code, [], SUCCESS_CODES[$code] ?? '');
        }

        return '';
    }

    public static function error($error_code, $error_message = ""): string
    {
        return "<div class='col-12 col-lg-6 alert alert-danger alert-dismissible fade show' role='alert'>
        <strong><i class='bi bi-exclamation-octagon-fill'></i> " . htmlspecialchars(t('messages.error_heading'), ENT_QUOTES, 'UTF-8') . "</strong><br>".
            htmlspecialchars(self::getCodeMessage('error', (int)$error_code), ENT_QUOTES, 'UTF-8') . "<br>" . htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='" . htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button></div>";
    }

    public static function success($success_code, $success_message = ""): string
    {
        return "<div class='col-12 col-lg-6 alert alert-success alert-dismissible fade show' role='alert'>
        <strong><i class='bi bi-check-circle-fill'></i> " . htmlspecialchars(t('messages.success_heading'), ENT_QUOTES, 'UTF-8') . "</strong><br>".
            htmlspecialchars(self::getCodeMessage('success', (int)$success_code), ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='" . htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button></div>";
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
            $messageKey = $flash['message_key'] ?? null;
            $messageParams = $flash['message_params'] ?? [];

            if (is_string($messageKey) && $messageKey !== '') {
                $message = HavuLocale::translate($messageKey, is_array($messageParams) ? $messageParams : []);
            }

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
