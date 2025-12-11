<?php
require 'database.php';
$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){
  $nombre = $_POST['nombre']; $tel = $_POST['telefono']; $fecha = $_POST['fecha']; $hora = $_POST['hora']; $personas = (int)$_POST['personas'];
  if(!$nombre || !$fecha || !$hora) $err='Faltan datos';
  else{ $s = $conexion->prepare('INSERT INTO reservas (nombre_cliente,telefono,fecha,hora,personas) VALUES (?,?,?,?,?)'); $s->execute([$nombre,$tel,$fecha,$hora,$personas]); header('Location: reservas.php'); exit; }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Nueva Reserva</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-image: url('https://img.freepik.com/foto-gratis/vista-superior-arreglo-rodajas-tomate_23-2148643013.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: #333;
      margin: 0;
      padding: 10px;
      position: relative;
    }
    
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.5);
      pointer-events: none;
      z-index: -1;
    }

    .header {
      background: #f5e6d3;
      padding: 15px;
      text-align: center;
      border-bottom: 2px solid #ddd;
      margin-bottom: 20px;
    }
    
    .logo {
      height: 60px;
      margin-bottom: 5px;
    }
    
    .brand-title {
      font-size: 20px;
      font-weight: bold;
      color: #333;
      margin: 5px 0;
    }
    
    .brand-subtitle {
      font-size: 12px;
      color: #666;
    }
    
    .nav-bar {
      padding: 10px;
      background: #f5e6d3;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
      margin-bottom: 20px;
    }
    
    button {
      padding: 8px 12px;
      background: #e8d4b8;
      color: #333;
      border: 1px solid #ddd;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }
    
    button:hover {
      background: #dcc4a8;
    }
    
    h1 {
      text-align: center;
      color: #333;
      font-size: 24px;
      margin: 20px 0;
    }
    
    .form-container {
      max-width: 400px;
      margin: 0 auto;
      padding: 15px;
    }
    
    form {
      background: #fafaf8;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
      margin-top: 10px;
      font-size: 14px;
    }
    
    input, select, textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
      box-sizing: border-box;
      font-size: 14px;
      margin-bottom: 10px;
    }
    
    button[type="submit"] {
      background: #e8d4b8;
      width: 100%;
      padding: 10px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 15px;
    }
    
    button[type="submit"]:hover {
      background: #dcc4a8;
    }
    
    .error {
      color: #c00;
      background: #ffebee;
      padding: 10px;
      border-radius: 3px;
      margin-bottom: 12px;
      border: 1px solid #ff9999;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="brand-title">Sharpy Britt Kitchen</div>
    <div class="brand-subtitle">✦ Auténtica Cocina Italiana ✦</div>
  </div>
  
  <div class="nav-bar">
    <button onclick="location.href='menu.php'">🍽️ Menú</button>
    <button onclick="location.href='crear_menu.php'">➕ Nuevo Plato</button>
    <button onclick="location.href='reservas.php'">📅 Reservas</button>
    <button onclick="location.href='pedidos.php'">🛒 Pedidos</button>
    <button onclick="location.href='inventario.php'">📦 Inventario</button>
    <button onclick="location.href='reportes.php'">📊 Reportes</button>
  </div>
  
  <h1>📅 Nueva Reserva</h1>
  
  <div class="form-container">
    <?php if($err) echo "<div class='error'>".$err."</div>"; ?>
    <form method="post">
      <label>👤 Nombre del Cliente</label>
      <input name="nombre" required>
      
      <label>☎️ Teléfono</label>
      <input name="telefono" placeholder="Ej: +1-234-567-8900">
      
      <label>📅 Fecha</label>
      <input name="fecha" type="date" required>
      
      <label>🕐 Hora</label>
      <input name="hora" type="time" required>
      
      <label>👥 Cantidad de Personas</label>
      <input name="personas" type="number" value="2" required>
      
      <button type="submit">💾 Crear Reserva</button>
    </form>
  </div>
</body>
</html>