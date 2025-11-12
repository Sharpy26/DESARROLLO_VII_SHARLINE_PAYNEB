<?php
/**
 * views_pdo.php
 * Implementaciones PDO para las 4 vistas solicitadas.
 * Ajusta las credenciales según tu entorno.
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

// 1) Productos con bajo stock (<5) + info de ventas
$sql1 = "SELECT p.id AS producto_id, p.nombre, p.stock,
            IFNULL(SUM(dv.cantidad),0) AS total_vendido,
            IFNULL(SUM(dv.cantidad * dv.precio_unitario),0) AS monto_total
        FROM productos p
        LEFT JOIN detalles_venta dv ON dv.producto_id = p.id
        WHERE p.stock < 5
        GROUP BY p.id, p.nombre, p.stock
        ORDER BY p.stock ASC";
$stmt = $pdo->query($sql1);
$productos_bajo_stock = $stmt->fetchAll();

// 2) Historial cliente (param cliente_id)
$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$historial_cliente = [];
if ($cliente_id > 0) {
    $sql2 = "SELECT v.id AS venta_id, v.fecha_venta, dv.producto_id, pr.nombre AS producto_nombre, dv.cantidad, dv.precio_unitario, (dv.cantidad * dv.precio_unitario) AS subtotal
             FROM ventas v
             JOIN detalles_venta dv ON dv.venta_id = v.id
             JOIN productos pr ON pr.id = dv.producto_id
             WHERE v.cliente_id = :cid
             ORDER BY v.fecha_venta DESC";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([':cid'=>$cliente_id]);
    $historial_cliente = $stmt2->fetchAll();
}

// 3) Métricas por categoría
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
$stmt3 = $pdo->query($sql3);
$metricas_categoria = $stmt3->fetchAll();

// 4) Tendencias por mes
$sql4 = "SELECT DATE_FORMAT(v.fecha_venta, '%Y-%m') AS mes, SUM(dv.cantidad * dv.precio_unitario) AS total_mes
         FROM ventas v
         JOIN detalles_venta dv ON dv.venta_id = v.id
         GROUP BY mes
         ORDER BY mes ASC";
$stmt4 = $pdo->query($sql4);
$tendencias = $stmt4->fetchAll();

// Comparativa mes anterior (calcular en PHP para máxima compatibilidad)
for ($i = 0; $i < count($tendencias); $i++) {
    $prev = $i > 0 ? $tendencias[$i-1]['total_mes'] : null;
    $tendencias[$i]['comparativa_prev'] = $prev !== null ? round((($tendencias[$i]['total_mes'] - $prev) / $prev) * 100, 2) : null;
}

header('Content-Type: application/json; charset=utf-8');
 echo json_encode([
    'productos_bajo_stock' => $productos_bajo_stock,
    'historial_cliente' => $historial_cliente,
    'metricas_categoria' => $metricas_categoria,
    'tendencias' => $tendencias
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$pdo = null;
?>