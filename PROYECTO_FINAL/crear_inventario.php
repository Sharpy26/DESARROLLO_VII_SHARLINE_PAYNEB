<?php
require 'database.php';
$err = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $ingrediente = trim($_POST['ingrediente'] ?? '');
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? '');
    if($ingrediente === '' || $cantidad <= 0){
        $err = 'Ingrediente y cantidad deben tener un valor válido.';
    } else {
        $ins = $conexion->prepare('INSERT INTO inventario (ingrediente,cantidad,unidad) VALUES (?,?,?)');
        $ins->execute([$ingrediente,$cantidad,$unidad]);
        header('Location: inventario.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Agregar Ingrediente</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-image: url('https://img.freepik.com/foto-gratis/vista-superior-arreglo-rodajas-tomate_23-2148643013.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: #222;
      margin: 0;
      padding: 12px;
      line-height: 1.4;
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
      padding: 12px 8px;
      text-align: center;
      border-bottom: 1px solid #eee;
      margin-bottom: 16px;
    }

    .brand-title { font-size: 18px; font-weight: bold; color: #222; margin: 4px 0 }
    .brand-subtitle { font-size: 12px; color: #666; margin-bottom: 6px }

    .form-container { max-width: 420px; margin: 0 auto; padding: 8px }

    form {
      background: #ffffff;
      padding: 14px;
      border: 1px solid #eee;
      border-radius: 3px;
    }

    label { display: block; margin-top: 8px; font-weight: bold; font-size: 13px }

    input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 2px;
      margin-top: 6px;
      font-size: 14px;
    }

    .error { color: #900; background: #fff0f0; padding: 6px; border: 1px solid #f1caca; border-radius: 3px; margin-bottom: 10px }

    button {
      padding: 8px 12px;
      background: #f0f0f0;
      border: 1px solid #ddd;
      border-radius: 3px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 10px;
    }

    button:hover { background: #e9e9e9 }
  </style>
</head>
<body>
  <div class="header">
    <div class="brand-title">Sharpy Britt Kitchen</div>
    <div class="brand-subtitle">✦ Auténtica Cocina Italiana ✦</div>
  </div>
  <div class="form-container">
    <h2>➕ Agregar Ingrediente</h2>
    <?php if($err){ ?><div class="error"><?php echo htmlspecialchars($err); ?></div><?php } ?>
    <form method="post">
      <label>🥘 Ingrediente</label>
      <input name="ingrediente" required placeholder="Ej: Harina, Tomate" value="<?php echo htmlspecialchars($_POST['ingrediente'] ?? ''); ?>">

      <label>📏 Cantidad</label>
      <input name="cantidad" type="number" min="1" required value="<?php echo htmlspecialchars($_POST['cantidad'] ?? ''); ?>">

      <label>📊 Unidad</label>
      <input name="unidad" placeholder="Ej: kg, L, pz" value="<?php echo htmlspecialchars($_POST['unidad'] ?? ''); ?>">

      <button type="submit">💾 Agregar</button>
    </form>
    <button onclick="location.href='inventario.php'" style="width:100%;margin-top:8px">← Volver al Inventario</button>
  </div>
</body>
</html>
