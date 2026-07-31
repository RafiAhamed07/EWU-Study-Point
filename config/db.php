<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'ewu_study_point');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
	die('Database connection failed.');
}
