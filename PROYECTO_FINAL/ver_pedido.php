<?php
require 'database.php';
$id = (int)($_GET['id'] ?? 0); if(!$id) die('id');
$stmt = $conexion->prepare('SELECT * FROM pedidos WHERE id=?'); $stmt->execute([$id]); $p = $stmt->fetch(); if(!$p) die('no existe');
$it = $conexion->prepare('SELECT pi.*, m.nombre FROM pedido_items pi LEFT JOIN menu m ON m.id=pi.menu_id WHERE pi.pedido_id=?'); $it->execute([$id]); $items = $it->fetchAll();
?>
<?php
require 'database.php';
$id = (int)($_GET['id'] ?? 0); if(!$id) die('id');
$stmt = $conexion->prepare('SELECT * FROM pedidos WHERE id=?'); $stmt->execute([$id]); $p = $stmt->fetch(); if(!$p) die('no existe');
$it = $conexion->prepare('SELECT pi.*, m.nombre FROM pedido_items pi LEFT JOIN menu m ON m.id=pi.menu_id WHERE pi.pedido_id=?'); $it->execute([$id]); $items = $it->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ver Pedido</title>
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
      background: #f8eee1ff;
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
      background: #fff4e6ff;
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
    
    .container {
      max-width: 700px;
      margin: 0 auto;
      padding: 0 10px 30px;
    }
    
    .info-box {
      background: #fafaf8;
      padding: 15px;
      border-radius: 5px;
      border: 1px solid #ddd;
      margin-bottom: 20px;
    }
    
    .info-box p {
      margin: 8px 0;
      font-size: 15px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fafaf8;
      border-radius: 5px;
      overflow: hidden;
      border: 1px solid #ddd;
    }
    
    thead {
      background: #e8d4b8;
      color: #333;
    }
    
    th {
      padding: 12px;
      text-align: left;
      font-size: 14px;
    }
    
    td {
      padding: 10px 12px;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }
    
    tbody tr:hover {
      background: #f9f6f0;
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
  
  <h1>🛒 Detalle del Pedido #<?php echo $p['id']; ?></h1>
  
  <div class="container">
    <div class="info-box">
      <p><strong>🪑 Mesa:</strong> <?php echo htmlspecialchars($p['mesa']); ?></p>
      <p><strong>💰 Total:</strong> $<?php echo number_format($p['total'],2); ?></p>
      <p><strong>📊 Estado:</strong> <?php echo htmlspecialchars($p['estado']); ?></p>
    </div>
    
    <table>
      <thead>
        <tr>
          <th>🍴 Plato</th>
          <th>📏 Cantidad</th>
          <th>💵 Precio Unitario</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $it){ ?>
        <tr>
          <td><?php echo htmlspecialchars($it['nombre']); ?></td>
          <td><?php echo $it['cantidad']; ?></td>
          <td>$<?php echo number_format($it['precio'],2); ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    
    <button onclick="location.href='pedidos.php'" style="margin-top:20px">← Volver a Pedidos</button>
  </div>
</body>
</html>