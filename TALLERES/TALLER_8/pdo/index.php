<?php
// Página simple para probar la versión PDO
require_once 'libros.php';
require_once 'usuarios.php';
require_once 'prestamos.php';

echo "<h1>Sistema Biblioteca (PDO) - Pruebas rápidas</h1>";

$libros = listarLibrosPDO(1, 10);
echo "<h2>Libros</h2>"; print_r($libros);

$usuarios = listarUsuariosPDO(1, 10);
echo "<h2>Usuarios</h2>"; print_r($usuarios);

$prestamos = listarPrestamosActivosPDO(1, 10);
echo "<h2>Préstamos activos</h2>"; print_r($prestamos);

?>