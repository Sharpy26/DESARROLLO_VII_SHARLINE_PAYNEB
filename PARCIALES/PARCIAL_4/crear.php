<?php
require 'database.php';

$error = '';
$exito = false;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $categoria = $_POST['categoria'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];

    if(empty($nombre) || empty($categoria) || empty($precio) || empty($cantidad)) {
        $error = 'Completa todos los campos';
    } else if(!is_numeric($precio) || $precio <= 0) {
        $error = 'Precio debe ser un numero positivo';
    } else if(!is_numeric($cantidad) || $cantidad < 0) {
        $error = 'Cantidad debe ser un numero';
    } else {
        $sql = "INSERT INTO productos (nombre, categoria, precio, cantidad) VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$nombre, $categoria, $precio, $cantidad]);
        $exito = true;
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Crear Producto</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { max-width: 400px; }
        input, select { display: block; margin: 10px 0; width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; }
        button:hover { background: #555; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Crear Producto</h1>
    <a href="index.php">Volver</a>

    <?php if($error) { ?>
        <p class="error"><?php echo $error; ?></p>
    <?php } ?>

    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>

        <label>Categoria:</label>
        <input type="text" name="categoria" required>

        <label>Precio:</label>
        <input type="number" name="precio" step="0.01" required>

        <label>Cantidad:</label>
        <input type="number" name="cantidad" required>

        <button type="submit">Guardar</button>
    </form>
</body>
</html>
