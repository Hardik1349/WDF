<?php
declare(strict_types=1);

// StudentHub database connection for XAMPP's default MySQL settings.
// Default XAMPP often uses username "root" with an empty password.
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'studenthub';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Check that MySQL is running and the studenthub database has been imported in phpMyAdmin.');
}
