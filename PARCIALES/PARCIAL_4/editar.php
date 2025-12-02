<?php
require 'database.php';

$id = $_GET['id'];

$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);
$producto = $stmt->fetch();

if(!$producto) {
    echo "Producto no encontrado";
    exit;
}

$error = '';

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
        $sql = "UPDATE productos SET nombre=?, categoria=?, precio=?, cantidad=? WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$nombre, $categoria, $precio, $cantidad, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Editar Producto</title>
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
    <h1>Editar Producto</h1>
    <a href="index.php">Volver</a>

    <?php if($error) { ?>
        <p class="error"><?php echo $error; ?></p>
    <?php } ?>

    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?php echo $producto['nombre']; ?>" required>

        <label>Categoria:</label>
        <input type="text" name="categoria" value="<?php echo $producto['categoria']; ?>" required>

        <label>Precio:</label>
        <input type="number" name="precio" step="0.01" value="<?php echo $producto['precio']; ?>" required>

        <label>Cantidad:</label>
        <input type="number" name="cantidad" value="<?php echo $producto['cantidad']; ?>" required>

        <button type="submit">Actualizar</button>
    </form>
</body>
</html>
