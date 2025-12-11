<?php
require 'database.php';
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
// total ventas por fecha
$stm = $conexion->prepare('SELECT DATE(creado) as dia, SUM(total) as ventas FROM pedidos WHERE DATE(creado) BETWEEN ? AND ? GROUP BY DATE(creado) ORDER BY DATE(creado)');
$stm->execute([$desde,$hasta]);
$rows = $stm->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reportes</title>
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
      background: #f9f1e6ff;
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
      background: #f8f0e6ff;
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
    
    .report-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 10px 30px;
    }
    
    .filter-box {
      background: #fafaf8;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      border: 1px solid #ddd;
    }
    
    .filter-box label {
      display: inline-block;
      margin-right: 10px;
      font-weight: bold;
      font-size: 14px;
    }
    
    .filter-box input {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
      margin-right: 10px;
      font-size: 14px;
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
  
  <h1>📊 Reportes de Ventas</h1>
  
  <div class="report-container">
    <div class="filter-box">
      <form method="get" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px">
        <label>📅 Desde:</label>
        <input type="date" name="desde" value="<?php echo $desde; ?>">
        
        <label>📅 Hasta:</label>
        <input type="date" name="hasta" value="<?php echo $hasta; ?>">
        
        <button type="submit">🔍 Filtrar</button>
      </form>
    </div>
    
    <table cellpadding="6">
      <thead>
        <tr>
          <th>📅 Fecha</th>
          <th>💰 Ventas (USD)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r){ ?>
        <tr>
          <td><?php echo $r['dia']; ?></td>
          <td>$<?php echo number_format($r['ventas'],2); ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</body>
</html>