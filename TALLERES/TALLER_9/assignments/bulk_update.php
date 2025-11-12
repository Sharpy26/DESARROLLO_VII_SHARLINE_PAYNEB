<?php
// bulk_update.php
// Actualización masiva de productos basada en criterios usando UpdateBuilder y transacciones.

require_once __DIR__ . '/../perf/query_builder.php';
require_once __DIR__ . '/../perf/config_pdo.php';
require_once __DIR__ . '/audit_logger.php';

function bulkUpdateProducts(PDO $pdo, array $criteria, array $updates) {
    $audit = new AuditLogger($pdo);

    // Validar columnas de actualización (permitir solo un conjunto seguro)
    $allowed = ['precio','stock','categoria_id','estado'];
    foreach ($updates as $col => $val) {
        if (!in_array($col, $allowed, true)) {
            throw new InvalidArgumentException('Columna no permitida: ' . $col);
        }
    }

    try {
        $pdo->beginTransaction();

        $ub = new UpdateBuilder($pdo);
        $ub->table('productos')->set($updates);

        // Aplicar criterios
        if (isset($criteria['categoria_id'])) $ub->where('categoria_id','=',(int)$criteria['categoria_id']);
        if (isset($criteria['precio_min'])) $ub->where('precio','>=',(float)$criteria['precio_min']);
        if (isset($criteria['precio_max'])) $ub->where('precio','<=',(float)$criteria['precio_max']);
        if (isset($criteria['estado'])) $ub->where('estado','=',$criteria['estado']);

        // Registrar la consulta antes de ejecutar
        // No expone parámetros internamente, así que usamos el builder's execute result
        $ok = $ub->execute();

        $pdo->commit();

        // Log de éxito
        $audit->logFailure('bulk_update_success', ['criteria'=>$criteria,'updates'=>$updates], '');
        return $ok;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $audit->logFailure('bulk_update_failed', ['criteria'=>$criteria,'updates'=>$updates], $e->getMessage());
        throw $e;
    }
}

// Demo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $ok = bulkUpdateProducts($pdo, ['categoria_id'=>1,'precio_min'=>10], ['precio'=>19.99]);
        echo "Bulk update OK: "; var_export($ok);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
