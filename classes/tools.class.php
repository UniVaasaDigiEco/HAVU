<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../vendor/autoload.php');
use Ramsey\Uuid\Uuid;
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

    public static function parseUuidFromBytes(string $bytes): string {
        try {
            return Uuid::fromBytes($bytes)->toString();
        } catch (Exception $exception) {
            throw new RuntimeException("Failed to parse UUID from bytes: " . $exception->getMessage());
        }
    }
}