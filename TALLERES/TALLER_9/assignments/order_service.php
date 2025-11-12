<?php
// order_service.php
// Procesamiento de pedidos con SAVEPOINTs, control de concurrencia y auditoría.
// Uso: php order_service.php (o incluir desde un endpoint web)

require_once __DIR__ . '/../../TALLERES/TALLER_9/perf/config_pdo.php';
require_once __DIR__ . '/audit_logger.php';

class OrderProcessor {
    private $pdo;
    private $audit;
    private $maxDeadlockRetries = 4;

    public function __construct(PDO $pdo, AuditLogger $audit) {
        $this->pdo = $pdo;
        $this->audit = $audit;
    }

    /**
     * Procesa un pedido: items = [ ['producto_id'=>int,'cantidad'=>int], ... ]
     * - Crea la venta
     * - Usa SAVEPOINT por item: si falla el item se revierte solo ese item
     * - Reintenta ante deadlocks hasta $maxDeadlockRetries
     */
    public function processOrder(int $cliente_id, array $items) {
        $attempt = 0;
        $contextDetails = ['cliente_id'=>$cliente_id, 'items'=>$items];

        while ($attempt < $this->maxDeadlockRetries) {
            try {
                $this->pdo->beginTransaction();

                // Crear venta temporal
                $stmt = $this->pdo->prepare('INSERT INTO ventas (cliente_id, fecha, total) VALUES (?, CURDATE(), 0)');
                $stmt->execute([$cliente_id]);
                $venta_id = $this->pdo->lastInsertId();
                $this->pdo->exec('SAVEPOINT venta_creada');

                $total = 0.0;
                $processed = 0;

                foreach ($items as $i => $it) {
                    $sp = 'item_' . $i;
                    $this->pdo->exec('SAVEPOINT ' . $sp);

                    try {
                        $this->processItem($venta_id, $it);
                        $processed++;
                        // actualizar subtotal (si existe se ha insertado en detalles_venta)
                        $stmt = $this->pdo->prepare('SELECT SUM(cantidad*precio_unitario) FROM detalles_venta WHERE venta_id = ?');
                        $stmt->execute([$venta_id]);
                        $s = $stmt->fetchColumn();
                        $total = (float)$s;
                    } catch (Exception $e) {
                        // revertir solo este item
                        $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . $sp);
                        $this->audit->logFailure('item_failed', ['venta_id'=>$venta_id,'item'=>$it], $e->getMessage());
                        // continuar con siguiente item
                        continue;
                    }
                }

                // actualizar total y commit
                $stmt = $this->pdo->prepare('UPDATE ventas SET total = ? WHERE id = ?');
                $stmt->execute([$total, $venta_id]);

                $this->pdo->commit();
                return ['venta_id'=>$venta_id,'processed'=>$processed,'total'=>$total];

            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    try { $this->pdo->rollBack(); } catch (Exception $_) {}
                }

                // Si es deadlock, reintentar con backoff
                $isDeadlock = isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1213 || (isset($e->errorInfo[0]) && $e->errorInfo[0] === '40001');
                $attempt++;
                $this->audit->logFailure('order_deadlock', $contextDetails, $e->getMessage());
                if ($isDeadlock && $attempt < $this->maxDeadlockRetries) {
                    // backoff
                    sleep($attempt);
                    continue;
                }
                throw $e;
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    try { $this->pdo->rollBack(); } catch (Exception $_) {}
                }
                $this->audit->logFailure('order_failed', $contextDetails, $e->getMessage());
                throw $e;
            }
        }

        throw new Exception('Máximo reintentos alcanzado');
    }

    private function processItem(int $venta_id, array $item) {
        $productId = (int)($item['producto_id'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);
        if ($productId <= 0 || $cantidad <= 0) throw new Exception('Item inválido');

        // Bloquear fila
        $stmt = $this->pdo->prepare('SELECT stock, precio FROM productos WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) throw new Exception('Producto no existe');
        if ((int)$p['stock'] < $cantidad) throw new Exception('Stock insuficiente');

        // Actualizar stock
        $upd = $this->pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
        $upd->execute([$cantidad, $productId]);

        // Insertar detalle
        // Si existe columna subtotal, insertamos, si no solo el resto
        $cols = $this->pdo->query("SHOW COLUMNS FROM detalles_venta LIKE 'subtotal'")->fetchAll();
        if (count($cols) > 0) {
            $subtotal = round($p['precio'] * $cantidad, 2);
            $ins = $this->pdo->prepare('INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
            $ins->execute([$venta_id, $productId, $cantidad, $p['precio'], $subtotal]);
        } else {
            $ins = $this->pdo->prepare('INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            $ins->execute([$venta_id, $productId, $cantidad, $p['precio']]);
        }
    }
}

// Script de prueba rápido
if (php_sapi_name() !== 'cli') echo "<pre>";

$audit = new AuditLogger($pdo);
$op = new OrderProcessor($pdo, $audit);

try {
    $items = [
        ['producto_id'=>1,'cantidad'=>2],
        ['producto_id'=>2,'cantidad'=>1],
        ['producto_id'=>9999,'cantidad'=>1], // producto inexistente para forzar rollback parcial
    ];
    $res = $op->processOrder(1, $items);
    echo "Resultado: "; print_r($res);
} catch (Exception $e) {
    echo "Error procesando pedido: " . $e->getMessage();
}

if (php_sapi_name() !== 'cli') echo "</pre>";
