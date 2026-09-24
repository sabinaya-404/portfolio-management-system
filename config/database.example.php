<?php

$host = "localhost";
$dbname = "portfolio_management";
$username = "root";
$password = "";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    die("Database service is currently unavailable. Please ensure MySQL is running and try again later.");
}