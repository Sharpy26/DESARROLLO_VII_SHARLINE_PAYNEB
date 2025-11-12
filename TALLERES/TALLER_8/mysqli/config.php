<?php
// Config MySQLi
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'biblioteca';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die('MySQLi connection error: ' . $mysqli->connect_error);
}
// Set charset
$mysqli->set_charset('utf8mb4');

?>