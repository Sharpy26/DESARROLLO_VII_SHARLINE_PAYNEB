<?php
/**
 * views_mysqli.php
 * Implementaciones MySQLi para las vistas solicitadas:
 * 1) Productos con bajo stock (<5) + info de ventas
 * 2) Historial completo de un cliente
 * 3) Métricas por categoría
 * 4) Tendencias de ventas por mes
 *
 * Ajusta la conexión en config o define las credenciales abajo.
 */

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tienda';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) die('MySQLi connect error: ' . $mysqli->connect_error);
$mysqli->set_charset('utf8mb4');

// 1) Productos con bajo stock (<5) junto con info de ventas (cantidad vendida y total)
// Uso: optimizado con índices en libros(id), detalles_venta(producto_id)
$sql1 = "SELECT p.id AS producto_id, p.nombre, p.stock,
            IFNULL(SUM(dv.cantidad),0) AS total_vendido,
            IFNULL(SUM(dv.cantidad * dv.precio_unitario),0) AS monto_total
        FROM productos p
        LEFT JOIN detalles_venta dv ON dv.producto_id = p.id
        WHERE p.stock < 5
        GROUP BY p.id, p.nombre, p.stock
        ORDER BY p.stock ASC";

$res1 = $mysqli->query($sql1);
$productos_bajo_stock = [];
if ($res1) {
    while ($row = $res1->fetch_assoc()) $productos_bajo_stock[] = $row;
    $res1->free();
}

// 2) Historial completo de un cliente (incluye productos comprados y montos totales)
// Parámetro: cliente_id (ej: ?cliente_id=1)
$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$historial_cliente = [];
if ($cliente_id > 0) {
    $sql2 = "SELECT v.id AS venta_id, v.fecha_venta, dv.producto_id, pr.nombre AS producto_nombre, dv.cantidad, dv.precio_unitario, (dv.cantidad * dv.precio_unitario) AS subtotal
             FROM ventas v
             JOIN detalles_venta dv ON dv.venta_id = v.id
             JOIN productos pr ON pr.id = dv.producto_id
             WHERE v.cliente_id = ?
             ORDER BY v.fecha_venta DESC";
    $stmt = $mysqli->prepare($sql2);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $historial_cliente[] = $r;
    $stmt->close();
}

// 3) Métricas por categoría: ventas totales, cantidad de productos, productos más vendidos
// Recomienda índices: productos(categoria_id), detalles_venta(producto_id)
$sql3 = "SELECT c.id AS categoria_id, c.nombre AS categoria_nombre,
            COUNT(DISTINCT p.id) AS num_productos,
            IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS ventas_totales,
            (SELECT pr2.id FROM productos pr2
                JOIN detalles_venta dv2 ON dv2.producto_id = pr2.id
                WHERE pr2.categoria_id = c.id
                GROUP BY pr2.id
                ORDER BY SUM(dv2.cantidad) DESC
                LIMIT 1) AS producto_mas_vendido_id
        FROM categorias c
        LEFT JOIN productos p ON p.categoria_id = c.id
        LEFT JOIN detalles_venta dv ON dv.producto_id = p.id
        GROUP BY c.id, c.nombre
        ORDER BY ventas_totales DESC";

$res3 = $mysqli->query($sql3);
$metricas_categoria = [];
if ($res3) {
    while ($r = $res3->fetch_assoc()) $metricas_categoria[] = $r;
    $res3->free();
}

// 4) Tendencias de ventas por mes (total por mes y comparativa con mes anterior)
// Agrupar por YEAR-MONTH
$sql4 = "SELECT DATE_FORMAT(v.fecha_venta, '%Y-%m') AS mes, SUM(dv.cantidad * dv.precio_unitario) AS total_mes
         FROM ventas v
         JOIN detalles_venta dv ON dv.venta_id = v.id
         GROUP BY mes
         ORDER BY mes ASC";

$res4 = $mysqli->query($sql4);
$tendencias = [];
if ($res4) {
    while ($r = $res4->fetch_assoc()) $tendencias[] = $r;
    $res4->free();
}

// Calcular comparativas (mes anterior) en PHP
foreach ($tendencias as $i => $row) {
    $prev = $i > 0 ? $tendencias[$i-1]['total_mes'] : null;
    $tendencias[$i]['comparativa_prev'] = $prev !== null ? round((($row['total_mes'] - $prev) / $prev) * 100, 2) : null;
}

// Salida simple para comprobar
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'productos_bajo_stock' => $productos_bajo_stock,
    'historial_cliente' => $historial_cliente,
    'metricas_categoria' => $metricas_categoria,
    'tendencias' => $tendencias
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$mysqli->close();

?>