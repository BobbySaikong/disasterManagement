<?php
// Database connection parameters

$host = 'localhost';
$port = 3306;
$dbname = 'disaster_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($host, $user, $pass, $dbname, $port);
$db->set_charset($charset);
$db->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
unset($host, $dbname, $user, $pass, $charset, $port); // we don't need them anymore
