<?php
/**
 * triggers_test_mysqli.php
 * Script de prueba para verificar triggers creados en triggers.sql usando MySQLi.
 */

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tienda';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) die('MySQLi connect error: ' . $mysqli->connect_error);
$mysqli->set_charset('utf8mb4');

// 1) Insertar venta de prueba (crear cliente, producto, detalles si no existen) - simplificado
$mysqli->query("INSERT IGNORE INTO clientes (id, nombre, estado) VALUES (1, 'Cliente Test', 'activo')");
$mysqli->query("INSERT IGNORE INTO categorias (id, nombre) VALUES (1, 'Cat Test')");
$mysqli->query("INSERT IGNORE INTO productos (id, nombre, categoria_id, stock) VALUES (1, 'Producto Test', 1, 10)");

// Insertar venta principal
$mysqli->query("INSERT INTO ventas (id, cliente_id, fecha, total) VALUES (NULL, 1, NOW(), 0)");
$venta_id = $mysqli->insert_id;
$mysqli->query("INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES ({$venta_id}, 1, 3, 10.00)");

// Ejecutar actualización de producto para bajar stock y activar trigger de alerta
$mysqli->query("UPDATE productos SET stock = 2 WHERE id = 1");

// Consultar tablas creadas por triggers
$res = $mysqli->query("SELECT * FROM estadisticas_categoria");
$estad = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$res2 = $mysqli->query("SELECT * FROM alertas_stock ORDER BY fecha DESC LIMIT 5");
$alerts = $res2 ? $res2->fetch_all(MYSQLI_ASSOC) : [];

// Registrar cambio de estado del cliente para probar historial
$mysqli->query("UPDATE clientes SET estado = 'inactivo' WHERE id = 1");
$res3 = $mysqli->query("SELECT * FROM historial_estado_cliente ORDER BY fecha DESC LIMIT 5");
$hist = $res3 ? $res3->fetch_all(MYSQLI_ASSOC) : [];

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['estadisticas_categoria' => $estad, 'alertas_stock' => $alerts, 'historial_estado_cliente' => $hist], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

$mysqli->close();
?>