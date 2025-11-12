<?php
/**
 * triggers_test_pdo.php
 * Script de prueba para verificar triggers creados en triggers.sql usando PDO.
 */

$dbHost = '127.0.0.1';
$dbName = 'tienda';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('PDO connect error: ' . $e->getMessage());
}

// Preparar datos mínimos
$pdo->exec("INSERT IGNORE INTO clientes (id, nombre, estado) VALUES (1, 'Cliente Test', 'activo')");
$pdo->exec("INSERT IGNORE INTO categorias (id, nombre) VALUES (1, 'Cat Test')");
$pdo->exec("INSERT IGNORE INTO productos (id, nombre, categoria_id, stock) VALUES (1, 'Producto Test', 1, 10)");

$pdo->exec("INSERT INTO ventas (cliente_id, fecha, total) VALUES (1, NOW(), 0)");
$venta_id = $pdo->lastInsertId();
$pdo->exec("INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES ({$venta_id}, 1, 3, 10.00)");

// Forzar alerta de stock
$pdo->exec("UPDATE productos SET stock = 2 WHERE id = 1");

$estad = $pdo->query('SELECT * FROM estadisticas_categoria')->fetchAll();
$alerts = $pdo->query('SELECT * FROM alertas_stock ORDER BY fecha DESC LIMIT 5')->fetchAll();

$pdo->exec("UPDATE clientes SET estado = 'inactivo' WHERE id = 1");
$hist = $pdo->query('SELECT * FROM historial_estado_cliente ORDER BY fecha DESC LIMIT 5')->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['estadisticas_categoria' => $estad, 'alertas_stock' => $alerts, 'historial_estado_cliente' => $hist], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

$pdo = null;
?>