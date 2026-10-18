<?php
mysqli_report(MYSQLI_REPORT_OFF);

$env = is_readable(__DIR__ . '/.env') ? parse_ini_file(__DIR__ . '/.env') : [];

$servername = $env['DB_HOST'] ?? 'localhost';
$username   = $env['DB_USER'] ?? 'root';
$password   = $env['DB_PASS'] ?? '';
$dbname     = $env['DB_NAME'] ?? 'skill_connect';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Database connection failed.");
}
?>