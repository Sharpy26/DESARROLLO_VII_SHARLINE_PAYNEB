<?php
/**
 * procedures_pdo_wrapper.php
 * Wrapper PHP (PDO) para llamar a los procedimientos definidos en procedures.sql
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

function procesarDevolucionPDO($venta_id, $producto_id, $cantidad) {
    global $pdo;
    try {
        $stmt = $pdo->prepare('CALL sp_procesar_devolucion(:v, :p, :c)');
        $stmt->execute([':v'=>$venta_id, ':p'=>$producto_id, ':c'=>$cantidad]);
        return ['ok' => true];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function aplicarDescuentoPDO($cliente_id) {
    global $pdo;
    try {
        // Usamos variable de sesión para recuperar OUT
        $pdo->exec('SET @p_descuento = 0');
        $stmt = $pdo->prepare('CALL sp_aplicar_descuento_cliente(:cid, @p_descuento)');
        $stmt->execute([':cid'=>$cliente_id]);
        $res = $pdo->query('SELECT @p_descuento AS descuento')->fetch();
        return ['descuento' => isset($res['descuento']) ? (float)$res['descuento'] : 0.0];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function reporteBajoStockPDO() {
    global $pdo;
    try {
        $stmt = $pdo->query('CALL sp_reporte_bajo_stock()');
        $rows = $stmt->fetchAll();
        // Limpiar posibles resultados adicionales
        while ($pdo->nextRowset()) { /* consume */ }
        return $rows;
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function calcularComisionesPDO($fecha_inicio, $fecha_fin) {
    global $pdo;
    try {
        $stmt = $pdo->prepare('CALL sp_calcular_comisiones(:f1, :f2)');
        $stmt->execute([':f1'=>$fecha_inicio, ':f2'=>$fecha_fin]);
        $rows = $stmt->fetchAll();
        while ($pdo->nextRowset()) { /* consume */ }
        return $rows;
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Ejemplos de uso (comentar en producción)
// print_r(procesarDevolucionPDO(10,5,1));
// print_r(aplicarDescuentoPDO(3));
// print_r(reporteBajoStockPDO());
// print_r(calcularComisionesPDO('2025-01-01','2025-12-31'));

$pdo = null;
?>