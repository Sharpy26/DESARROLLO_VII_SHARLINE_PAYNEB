<?php
session_start();

$_SESSION['usuario'] = "Sharpy Britt";
$_SESSION['rol'] = "admin";

echo "Sesión iniciada para " . $_SESSION['usuario'];
?>