<?php
/**
 * consultas_optimizadas.php
 *
 * Implementa las consultas solicitadas por el enunciado del taller,
 * tanto con PDO como con MySQLi. Cada consulta incluye explicación
 * y manejo de casos NULL cuando aplica.
 *
 * NOTA: Este archivo asume que existen las tablas: productos, categorias,
 * ventas, detalles_venta, clientes. Ajusta nombres de columnas según tu esquema.
 */

// --- CONFIGURACIÓN DE CONEXIÓN (ajusta según tu entorno) ---
// Para PDO: crea un archivo config_pdo.php o sustituye las variables aquí.
$dbHost = '127.0.0.1';
$dbName = 'tu_base_datos';
$dbUser = 'root';
$dbPass = 'root';

// Conexión PDO
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Error PDO: ' . $e->getMessage());
}

// Conexión MySQLi
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die('Error MySQLi: ' . $mysqli->connect_error);
}

// ----------------------
// 1) Productos que nunca se han vendido
// ----------------------
/*
 Propósito: obtener los productos que no aparecen en la tabla de detalles de venta.
 Manejo NULL: se usa LEFT JOIN y se filtra por dv.producto_id IS NULL.
 Rendimiento: índice sobre detalles_venta.producto_id y productos.id ayuda.
*/

$sql_pdo_1 = <<<'SQL'
SELECT p.id AS producto_id, p.nombre AS producto_nombre
FROM productos p
LEFT JOIN detalles_venta dv ON p.id = dv.producto_id
WHERE dv.producto_id IS NULL
ORDER BY p.id;
SQL;

$stmt = $pdo->query($sql_pdo_1);
$productos_nunca_vendidos_pdo = $stmt->fetchAll();

// MySQLi version
$result = $mysqli->query($sql_pdo_1);
$productos_nunca_vendidos_mysqli = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $productos_nunca_vendidos_mysqli[] = $row;
    $result->free();
}

// ----------------------
// 2) Categorías con número de productos y valor total del inventario
// ----------------------
/*
 Propósito: para cada categoría, contar productos y sumar (precio * stock).
 Manejo NULL: usamos IFNULL para evitar NULL en la suma cuando no hay productos.
 Rendimiento: índice en productos.categoria_id.
*/

$sql_pdo_2 = <<<'SQL'
SELECT c.id AS categoria_id,
       c.nombre AS categoria_nombre,
       COUNT(p.id) AS num_productos,
       IFNULL(SUM(p.precio * p.stock), 0) AS valor_inventario
FROM categorias c
LEFT JOIN productos p ON p.categoria_id = c.id
GROUP BY c.id, c.nombre
ORDER BY valor_inventario DESC;
SQL;

$categorias_valor_pdo = $pdo->query($sql_pdo_2)->fetchAll();

$result = $mysqli->query($sql_pdo_2);
$categorias_valor_mysqli = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $categorias_valor_mysqli[] = $row;
    $result->free();
}

// ----------------------
// 3) Clientes que han comprado todos los productos de una categoría específica
// ----------------------
/*
 Propósito: dado category_id, encontrar clientes que han comprado cada producto
 de esa categoría al menos una vez.

 Estrategia segura y eficiente (cuando hay índices adecuados):
 - Contar el número total de productos distintos en la categoría.
 - Para cada cliente, contar los productos distintos comprados en esa categoría
   y comparar los dos números.

 Manejo NULL: si la categoría no tiene productos, devolvemos lista vacía (evitar división/0).
*/

function clientes_que_compraron_todos_pdo($pdo, $categoria_id) {
    // Contar productos en la categoría
    $sql_count = 'SELECT COUNT(*) FROM productos WHERE categoria_id = :cat';
    $total = (int) $pdo->prepare($sql_count)->execute([':cat' => $categoria_id]) ? (int)$pdo->prepare($sql_count)->fetchColumn() : 0;

    // Better: perform single query that groups by cliente
    $sql = <<<'SQL'
SELECT cl.id AS cliente_id, cl.nombre AS cliente_nombre, COUNT(DISTINCT dv.producto_id) AS comprados
FROM clientes cl
JOIN ventas v ON v.cliente_id = cl.id
JOIN detalles_venta dv ON dv.venta_id = v.id
JOIN productos p ON p.id = dv.producto_id AND p.categoria_id = :cat
GROUP BY cl.id, cl.nombre
HAVING comprados = (
    SELECT COUNT(*) FROM productos WHERE categoria_id = :cat
)
ORDER BY cl.nombre;
SQL;

    // Preparar y ejecutar
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cat' => $categoria_id]);
    return $stmt->fetchAll();
}

function clientes_que_compraron_todos_mysqli($mysqli, $categoria_id) {
    $categoria_id = (int)$categoria_id;
    $sql = "SELECT cl.id AS cliente_id, cl.nombre AS cliente_nombre, COUNT(DISTINCT dv.producto_id) AS comprados
            FROM clientes cl
            JOIN ventas v ON v.cliente_id = cl.id
            JOIN detalles_venta dv ON dv.venta_id = v.id
            JOIN productos p ON p.id = dv.producto_id AND p.categoria_id = {$categoria_id}
            GROUP BY cl.id, cl.nombre
            HAVING comprados = (
                SELECT COUNT(*) FROM productos WHERE categoria_id = {$categoria_id}
            )
            ORDER BY cl.nombre";

    $out = [];
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

// ----------------------
// 4) Porcentaje de ventas de cada producto respecto al total de ventas
// ----------------------
/*
 Propósito: calcular, para cada producto, el total vendido (en valor monetario)
 y el porcentaje que representa respecto al total general.

 Manejo NULL: NULLIF evita división por cero cuando el total general es 0.
 Rendimiento: índices en detalles_venta.producto_id y (si existe) en precio_unitario.
*/

$sql_pdo_4 = <<<'SQL'
SELECT
  p.id AS producto_id,
  p.nombre AS producto_nombre,
  IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS ventas_producto,
  ROUND(
    100 * IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) / NULLIF(
      (SELECT IFNULL(SUM(dv2.cantidad * dv2.precio_unitario),0) FROM detalles_venta dv2), 0
    ),
  2) AS porcentaje_total
FROM productos p
LEFT JOIN detalles_venta dv ON dv.producto_id = p.id
GROUP BY p.id, p.nombre
ORDER BY ventas_producto DESC;
SQL;

$porcentaje_ventas_pdo = $pdo->query($sql_pdo_4)->fetchAll();

$result = $mysqli->query($sql_pdo_4);
$porcentaje_ventas_mysqli = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $porcentaje_ventas_mysqli[] = $row;
    $result->free();
}

// ----------------------
// Ejemplos de uso y salida resumida (para pruebas)
// ----------------------
echo "<h2>Consultas optimizadas (PDO y MySQLi)</h2>";

echo "<h3>1) Productos que nunca se han vendido (PDO)</h3>";
print_r($productos_nunca_vendidos_pdo);

echo "<h3>1) Productos que nunca se han vendido (MySQLi)</h3>";
print_r($productos_nunca_vendidos_mysqli);

echo "<h3>2) Categorías: número de productos y valor inventario (PDO)</h3>";
print_r($categorias_valor_pdo);

echo "<h3>2) Categorías: número de productos y valor inventario (MySQLi)</h3>";
print_r($categorias_valor_mysqli);

// Ejemplo para la consulta 3: categoría = 1 (ajusta según datos)
$cat = 1;
echo "<h3>3) Clientes que compraron todos los productos de la categoría {$cat} (PDO)</h3>";
print_r(clientes_que_compraron_todos_pdo($pdo, $cat));

echo "<h3>3) Clientes que compraron todos los productos de la categoría {$cat} (MySQLi)</h3>";
print_r(clientes_que_compraron_todos_mysqli($mysqli, $cat));

echo "<h3>4) Porcentaje de ventas por producto (PDO)</h3>";
print_r($porcentaje_ventas_pdo);

echo "<h3>4) Porcentaje de ventas por producto (MySQLi)</h3>";
print_r($porcentaje_ventas_mysqli);

// Cerrar conexiones
$pdo = null;
$mysqli->close();

// Fin del archivo
?>