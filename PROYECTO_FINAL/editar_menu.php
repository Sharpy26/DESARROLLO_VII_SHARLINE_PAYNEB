<?php
require 'database.php';
$id = (int)($_GET['id'] ?? 0);
if(!$id) die('Id invalido');
$stmt = $conexion->prepare('SELECT * FROM menu WHERE id=?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if(!$item) die('No existe');
$err='';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $nombre = $_POST['nombre']; $categoria = $_POST['categoria']; $precio = $_POST['precio'];
    if(!$nombre || !$precio) $err='Faltan campos';
    else{ $u = $conexion->prepare('UPDATE menu SET nombre=?,categoria=?,precio=? WHERE id=?'); $u->execute([$nombre,$categoria,$precio,$id]); header('Location: menu.php'); exit; }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Editar Plato - Sharpy Britt Kitchen</title>
        <style>
            /* versión más simple / estudiantil */
            body{font-family:Arial,Helvetica,sans-serif;margin:0;min-height:100vh;background:linear-gradient(135deg,#E8D5C4,#D9C8B8);color:#5C4A3D}
            .header{background:#FBF7F4;padding:14px;text-align:center;border-bottom:3px solid #D4C4B0}
            .logo-small{height:48px}
            .brand{font-weight:bold;margin-top:6px;color:#6B5344}
            .container{max-width:640px;margin:20px auto;padding:12px}
            .card{background:#FBF7F4;padding:16px;border-radius:8px}
            label{display:block;margin-bottom:6px;font-weight:600;color:#6B5344}
            input[type=text], input[type=number]{width:100%;padding:8px;border-radius:4px;border:1px solid #D4C4B0;margin-bottom:10px;font-size:14px}
            .row{display:flex;gap:8px}
            .row .col{flex:1}
            .btn{padding:8px 14px;border-radius:6px;border:none;cursor:pointer;font-weight:700}
            .btn-primary{background:#C9B8A0;color:#5C4A3D}
            .btn-secondary{background:#E8DCD0;color:#5C4A3D}
            .error{color:#A85444;margin-bottom:10px}
            a.back{color:#8B7355;text-decoration:none;font-weight:600}
        </style>
</head>
<body>
    <div class="header">
        
        <div class="brand">Sharpy Britt Kitchen — Editar Plato</div>
    </div>
    <div class="container">
        <div class="card">
            <a class="back" href="menu.php">← Volver al Menú</a>
            <h2 style="margin-top:10px">Editar plato</h2>
            <?php if($err) echo "<div class='error'>".htmlspecialchars($err)."</div>"; ?>
            <form method="post">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" type="text" value="<?php echo htmlspecialchars($item['nombre']); ?>">

                <label for="categoria">Categoría</label>
                <input id="categoria" name="categoria" type="text" value="<?php echo htmlspecialchars($item['categoria']); ?>">

                <div class="row">
                    <div class="col">
                        <label for="precio">Precio (USD)</label>
                        <input id="precio" name="precio" type="number" step="0.01" value="<?php echo htmlspecialchars($item['precio']); ?>">
                    </div>
                    <div class="col">
                        <label>&nbsp;</label>
                        <div style="display:flex;gap:8px">
                            <button class="btn btn-primary" type="submit">Actualizar</button>
                            <a href="menu.php" class="btn btn-secondary" style="display:inline-block;line-height:30px;text-align:center;text-decoration:none">Cancelar</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>