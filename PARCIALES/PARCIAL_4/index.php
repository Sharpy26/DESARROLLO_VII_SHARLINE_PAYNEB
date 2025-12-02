<?php
require 'database.php';

$sql = "SELECT * FROM productos ORDER BY id DESC";
$resultado = $conexion->query($sql);
$productos = $resultado->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Productos - TechParts</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #230c63ff; color: white; }
        a { color: blue; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Productos TechParts</h1>
    <a href="crear.php">Agregar producto</a> 
    <br><br>

    <?php if(count($productos) > 0) { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Categoria</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Acción</th>
            </tr>
            <?php foreach($productos as $p) { ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><?php echo $p['nombre']; ?></td>
                    <td><?php echo $p['categoria']; ?></td>
                    <td>$<?php echo $p['precio']; ?></td>
                    <td><?php echo $p['cantidad']; ?></td>
                    <td>
                        <a href="editar.php?id=<?php echo $p['id']; ?>">Editar</a> | 
                        <a href="eliminar.php?id=<?php echo $p['id']; ?>" onclick="return confirm('Eliminar?')">Eliminar</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>No hay productos</p>
    <?php } ?>
</body>
</html>
