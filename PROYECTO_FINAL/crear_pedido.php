<?php
require 'database.php';
// obtener menu
$stm = $conexion->query('SELECT * FROM menu');
$menu = $stm->fetchAll();
$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $mesa = $_POST['mesa'] ?? '';
    $items = $_POST['item'] ?? [];
    if(!$mesa || empty($items)) $err='Selecciona mesa y al menos 1 plato';
    else{
        // calcular total
        $total = 0;
        foreach($items as $mid => $qty){
            $mid = (int)$mid; $qty = (int)$qty;
            if($qty<=0) continue;
            $s = $conexion->prepare('SELECT precio FROM menu WHERE id=?'); $s->execute([$mid]); $m = $s->fetch();
            if($m) $total += $m['precio'] * $qty;
        }
        // insertar pedido
        $ins = $conexion->prepare('INSERT INTO pedidos (mesa,total) VALUES (?,?)');
        $ins->execute([$mesa,$total]);
        $pedido_id = $conexion->lastInsertId();
        foreach($items as $mid=>$qty){ $mid=(int)$mid; $qty=(int)$qty; if($qty<=0) continue; $s=$conexion->prepare('SELECT precio FROM menu WHERE id=?'); $s->execute([$mid]); $m=$s->fetch(); $pr=$m['precio']; $i = $conexion->prepare('INSERT INTO pedido_items (pedido_id,menu_id,cantidad,precio) VALUES (?,?,?,?)'); $i->execute([$pedido_id,$mid,$qty,$pr]); }
        header('Location: pedidos.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Nuevo Pedido</title>
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
      max-width: 700px;
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
    
    input[type="text"], input[type="number"], select {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
      box-sizing: border-box;
      font-size: 14px;
      margin-bottom: 10px;
    }
    
    input[type="text"] {
      width: 100%;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 16px;
      background: #fafaf8;
      border: 1px solid #ddd;
    }
    
    th {
      background: #e8d4b8;
      color: #333;
      padding: 12px;
      text-align: left;
      font-size: 14px;
    }
    
    td {
      padding: 10px;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }
    
    tr:hover {
      background: #f9f6f0;
    }
    
    input[type="number"] {
      width: 80px;
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
  </style>
    .error{color:#A85444;background:#F5E5E0;padding:10px;border-radius:4px;margin-bottom:12px;border-left:4px solid #A85444}
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
  
  <h1>🛒 Nuevo Pedido</h1>
  
  <div class="form-container">
    <?php if($err) echo "<div class='error'>".$err."</div>"; ?>
    <form method="post">
      <label>🪑 Mesa #</label>
      <input name="mesa" type="text" placeholder="Ej: A1, B2, etc" required>
      
      <label>🍴 Selecciona los Platos:</label>
      <table>
        <thead>
          <tr>
            <th>Plato</th>
            <th>Precio (USD)</th>
            <th>Cantidad</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($menu as $m){ ?>
          <tr>
            <td><?php echo htmlspecialchars($m['nombre']); ?></td>
            <td>$<?php echo number_format($m['precio'],2); ?></td>
            <td><input type="number" name="item[<?php echo $m['id']; ?>]" value="0" min="0"></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      
      <button type="submit">💾 Registrar Pedido</button>
    </form>
  </div>
</body>
</html>