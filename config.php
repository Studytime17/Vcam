<?php
/**
 * High-End Point of Sale (POS) System Configuration
 * Version: 7.0
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vcam_pos');

// Application constants
define('APP_VERSION', '7.0');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('BACKUP_DIR', __DIR__ . '/backups/');

// Set timezone
date_default_timezone_set('UTC');

// Create required directories
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0755, true);

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}