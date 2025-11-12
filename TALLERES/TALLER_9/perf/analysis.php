<?php
/**
 * analysis.php
 *
 * Ejecuta EXPLAIN / EXPLAIN FORMAT=JSON para un conjunto de consultas
 * y devuelve los resultados para análisis. Ajusta las credenciales y
 * añade o quita consultas en el arreglo $queries.
 *
 * Uso: php analysis.php  (o abrir en navegador si está en un servidor)
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
    die('Connection error: ' . $e->getMessage());
}

// Conjunto de consultas a analizar (puedes añadir más)
$queries = [
    'productos_bajo_stock' => "SELECT p.id, p.nombre, p.stock FROM productos p WHERE p.stock < 5",
    'ventas_por_mes' => "SELECT DATE_FORMAT(v.fecha_venta, '%Y-%m') AS mes, SUM(dv.cantidad * dv.precio_unitario) AS total_mes FROM ventas v JOIN detalles_venta dv ON dv.venta_id = v.id GROUP BY mes ORDER BY mes DESC",
    'metricas_por_categoria' => "SELECT c.id, c.nombre, SUM(dv.cantidad * dv.precio_unitario) AS ventas_totales FROM categorias c JOIN productos p ON p.categoria_id = c.id JOIN detalles_venta dv ON dv.producto_id = p.id GROUP BY c.id",
    // Ejemplo de consulta compleja heredada: historial de cliente
    'historial_cliente' => "SELECT v.id AS venta_id, v.fecha_venta, dv.producto_id, pr.nombre AS producto_nombre, dv.cantidad, dv.precio_unitario FROM ventas v JOIN detalles_venta dv ON dv.venta_id = v.id JOIN productos pr ON pr.id = dv.producto_id WHERE v.cliente_id = 123 ORDER BY v.fecha_venta DESC",
];

// Detectar si el servidor soporta EXPLAIN FORMAT=JSON (MySQL 5.6+)
$supportsJson = true;
try {
    $pdo->query("EXPLAIN FORMAT=JSON SELECT 1");
} catch (Exception $e) {
    $supportsJson = false;
}

$results = [];
foreach ($queries as $name => $sql) {
    $entry = ['sql' => $sql];
    try {
        // EXPLAIN FORMAT=JSON si está disponible
        if ($supportsJson) {
            $stmt = $pdo->query("EXPLAIN FORMAT=JSON " . $sql);
            $explain = $stmt->fetchColumn();
            $entry['explain_format_json'] = json_decode($explain, true);
        } else {
            $stmt = $pdo->query("EXPLAIN " . $sql);
            $entry['explain'] = $stmt->fetchAll();
        }

        // Medir tiempo de ejecución real (ejecutar la consulta una vez)
        $t0 = microtime(true);
        $stmt2 = $pdo->query($sql);
        $rows = $stmt2->fetchAll();
        $t1 = microtime(true);
        $entry['rows_sample'] = count($rows);
        $entry['execution_time_ms'] = round(($t1 - $t0) * 1000, 3);
    } catch (Exception $e) {
        $entry['error'] = $e->getMessage();
    }
    $results[$name] = $entry;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['supports_json' => $supportsJson, 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Nota: Este script es una herramienta de análisis. No aplicar índices automáticamente sin
// revisar el impacto en escrituras y almacenamiento. Ver README_perf.md para pasos siguientes.

?>
