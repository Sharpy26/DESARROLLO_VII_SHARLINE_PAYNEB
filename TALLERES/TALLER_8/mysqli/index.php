<?php
// Página simple para probar la versión MySQLi
require_once 'libros.php';
require_once 'usuarios.php';
require_once 'prestamos.php';

echo "<h1>Sistema Biblioteca (MySQLi) - Pruebas rápidas</h1>";

// Ejemplos mínimos (comenta/ajusta según pruebas locales)
// Listar primeros libros
$libros = listarLibros(1, 10);
echo "<h2>Libros</h2>"; print_r($libros);

// Listar usuarios
$usuarios = listarUsuarios(1, 10);
echo "<h2>Usuarios</h2>"; print_r($usuarios);

// Listar prestamos activos
$prestamos = listarPrestamosActivos(1, 10);
echo "<h2>Préstamos activos</h2>"; print_r($prestamos);

?>