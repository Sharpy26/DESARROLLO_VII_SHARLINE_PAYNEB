<?php
/**
 * procedures_mysqli_wrapper.php
 * Wrapper PHP (MySQLi) para llamar a los procedimientos definidos en procedures.sql
 */

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tienda';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) die('MySQLi connect error: ' . $mysqli->connect_error);
$mysqli->set_charset('utf8mb4');

function procesarDevolucionMySQLi($venta_id, $producto_id, $cantidad) {
    global $mysqli;
    $sql = "CALL sp_procesar_devolucion(?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return ['error' => $mysqli->error];
    $stmt->bind_param('iii', $venta_id, $producto_id, $cantidad);
    if (!$stmt->execute()) return ['error' => $stmt->error];
    $stmt->close();
    return ['ok' => true];
}

function aplicarDescuentoMySQLi($cliente_id) {
    global $mysqli;
    $mysqli->query("SET @p_descuento = 0");
    $sql = "CALL sp_aplicar_descuento_cliente(?, @p_descuento)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return ['error' => $mysqli->error];
    $stmt->bind_param('i', $cliente_id);
    if (!$stmt->execute()) return ['error' => $stmt->error];
    $stmt->close();
    $res = $mysqli->query("SELECT @p_descuento AS descuento");
    $row = $res->fetch_assoc();
    return ['descuento' => (float)$row['descuento']];
}

function reporteBajoStockMySQLi() {
    global $mysqli;
    $sql = "CALL sp_reporte_bajo_stock()";
    $res = $mysqli->query($sql);
    $out = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        while ($mysqli->more_results() && $mysqli->next_result()) {
            $extra = $mysqli->use_result();
            if ($extra instanceof mysqli_result) $extra->free();
        }
    }
    return $out;
}

function calcularComisionesMySQLi($fecha_inicio, $fecha_fin) {
    global $mysqli;
    $sql = "CALL sp_calcular_comisiones(?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return ['error' => $mysqli->error];
    $stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
    if (!$stmt->execute()) return ['error' => $stmt->error];
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    $stmt->close();
    return $out;
}

// Ejemplo de uso (comentar en producción)
// $r = procesarDevolucionMySQLi(10, 5, 1); print_r($r);
// $d = aplicarDescuentoMySQLi(3); print_r($d);
// $b = reporteBajoStockMySQLi(); print_r($b);
// $c = calcularComisionesMySQLi('2025-01-01', '2025-12-31'); print_r($c);

$mysqli->close();

?>