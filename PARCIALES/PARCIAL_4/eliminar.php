<?php
require 'database.php';

$id = $_GET['id'];

$sql = "DELETE FROM productos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);

header('Location: index.php');
exit;
