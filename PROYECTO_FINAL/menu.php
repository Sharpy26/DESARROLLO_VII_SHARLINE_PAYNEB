<?php
require 'database.php';
$stmt = $conexion->query('SELECT * FROM menu ORDER BY id DESC');
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Menú - Sharpy Britt Kitchen</title>
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
      background: #fef4e6ff;
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
      background: #fff5e8ff;
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

    .menu-container {
      max-width: 980px;
      margin: 0 auto;
      padding: 0 12px 30px;
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

    .actions a {
      color: #0066cc;
      text-decoration: none;
      margin-right: 10px;
      font-size: 13px;
    }

    .actions a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="brand-title">Bienvenido a Sharpy Kitchen</div>
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
  
  <h1>🍝 Nuestro Menú 🍝</h1>
  
  <div class="menu-container">
    <table>
      <thead>
        <tr>
          <th>🍴 Nombre del Plato</th>
          <th>🏷️ Categoría</th>
          <th>💰 Precio (USD)</th>
          <th>⚙️ Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          foreach($items as $it){
            $nombre = htmlspecialchars($it['nombre']);
            $categoria = htmlspecialchars($it['categoria']);
            $precio = number_format($it['precio'],2);
            $id = $it['id'];
        ?>
          <tr>
            <td><?php print $nombre; ?></td>
            <td><?php print $categoria; ?></td>
            <td>$<?php print $precio; ?></td>
            <td class="actions">
              <a href="editar_menu.php?id=<?php print $id; ?>">✏️ Editar</a> | 
              <a href="eliminar_menu.php?id=<?php print $id; ?>" onclick="return confirm('¿Eliminar este plato?')">🗑️ Eliminar</a>
            </td>
          </tr>
        <?php 
          }
        ?>
      </tbody>
    </table>
  </div>
</body>
</html>
