<?php
date_default_timezone_set('Asia/Manila');
$host = "localhost";
$username = "root";
$password = "";
$database = "supremedent_db";
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) { throw new RuntimeException("Database connection failed. Please start MySQL in XAMPP."); }
$conn->set_charset("utf8mb4");
// Match OTP timestamps to the PHP Asia/Manila clock.
$conn->query("SET time_zone = '+08:00'");
require_once __DIR__ . '/schema.php';
ensure_schema($conn);
?>
