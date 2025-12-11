<?php
require 'database.php';
$id = (int)($_GET['id'] ?? 0);
if(!$id) die('id invalido');
$del = $conexion->prepare('DELETE FROM menu WHERE id=?');
$del->execute([$id]);
header('Location: menu.php');
exit;
