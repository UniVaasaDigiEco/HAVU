<?php
require_once(__DIR__ . '/../config/constants.php');
class Tools{
    public static function GetDB(): mysqli{
        try{
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $db->set_charset('utf8mb4');
            return $db;
        }
        catch (Exception $exception){
            throw new RuntimeException("Database connection failed: " . $exception->getMessage());
        }
    }

    public static function parseDateTime(?string $datetime): ?DateTime {
        if ($datetime === null){
            Throw new InvalidArgumentException("Datetime string is null");
        }

        try {
            return new DateTime($datetime);
        } catch (Exception $e) {
            throw new RuntimeException("Failed to parse datetime: '$datetime': " . $e->getMessage());
        }
    }
}