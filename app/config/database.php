<?php
require_once __DIR__ . '/config.php';

class Database {
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            // Log error in production
            error_log("Database connection failed: " . $e->getMessage());
            
            // Show error only in development
            if (APP_DEBUG) {
                die("Database connection error: " . $e->getMessage());
            } else {
                die("Database connection error");
            }
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}