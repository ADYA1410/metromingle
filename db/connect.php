<?php

$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'metromingle';
$port = 3307;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    error_log('PlotSphere DB connection failed: ' . $ex->getMessage());
    http_response_code(500);
    die('Database connection failed. Please make sure MySQL is running on port ' . $port . '.');
}
