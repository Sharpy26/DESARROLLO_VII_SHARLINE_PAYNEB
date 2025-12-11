<?php
require 'database.php';
$id = (int)($_GET['id'] ?? 0); if(!$id) die('id');
// cambiar estado secuencialmente
$s = $conexion->prepare('SELECT estado FROM pedidos WHERE id=?'); $s->execute([$id]); $p = $s->fetch(); if(!$p) die('no');
$est = $p['estado'];
$next = 'pendiente';
if($est=='pendiente') $next='en_preparacion'; else if($est=='en_preparacion') $next='listo';
$u = $conexion->prepare('UPDATE pedidos SET estado=? WHERE id=?'); $u->execute([$next,$id]);
header('Location: pedidos.php'); exit;
