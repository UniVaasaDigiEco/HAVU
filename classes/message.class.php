<?php
require_once(__DIR__ .'/../config/constants.php');

class Message{

    public static function error($error_code): string
    {
        return "<div class='col-12 col-lg-3 alert alert-danger alert-dismissible fade show' role='alert'>
        <strong><i class='bi bi-exclamation-octagon-fill'></i> An error occurred!</strong><br>".
            ERROR_CODES[$error_code] .
        "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}