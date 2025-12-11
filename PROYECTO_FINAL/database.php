<?php
$host = '127.0.0.1';
$db   = 'restaurante_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}
