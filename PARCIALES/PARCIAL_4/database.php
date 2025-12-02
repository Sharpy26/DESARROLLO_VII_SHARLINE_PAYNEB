<?php
// Conexion a base de datos

$host = '127.0.0.1';
$usuario = 'root';
$contraseña = '';
$basedatos = 'techparts_db';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$basedatos;charset=utf8mb4", $usuario, $contraseña);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
