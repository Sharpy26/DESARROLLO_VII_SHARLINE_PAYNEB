<?php
// deadlock_manager.php
// Manejo de deadlocks con reintentos y prevención por ordenamiento de locks.
// Requiere: config_pdo.php

require_once __DIR__ . '/config_pdo.php';

class DeadlockManager {
    private $pdo;
    private $maxRetries = 3;
    private $retryDelay = 1; // segundos

    public function __construct(PDO $pdo, int $maxRetries = 3, int $retryDelay = 1) {
        $this->pdo = $pdo;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    public function executeWithDeadlockRetry(callable $operation) {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                $this->pdo->beginTransaction();

                $result = $operation($this->pdo);

                $this->pdo->commit();
                return $result;

            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    try { $this->pdo->rollBack(); } catch (Exception $_) {}
                }

                // Verificar si es un deadlock
                if ($this->isDeadlock($e) && $attempts < $this->maxRetries - 1) {
                    $attempts++;
                    echo "Deadlock detectado, reintentando (intento $attempts)...<br>\n";
                    // Exponential backoff básico
                    sleep($this->retryDelay * $attempts);
                    continue;
                }

                // No es deadlock o se acabaron reintentos: re-lanzar
                throw $e;
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    try { $this->pdo->rollBack(); } catch (Exception $_) {}
                }
                throw $e;
            }
        }
    }

    private function isDeadlock(PDOException $e) {
        // MySQL deadlock error code 1213, SQLSTATE '40001' (serialization failure) can also indicate locking issues
        $codeNum = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : null;
        $sqlState = isset($e->errorInfo[0]) ? $e->errorInfo[0] : null;
        return $codeNum === 1213 || $sqlState === '40001';
    }

    public function transferirStock(int $origen_id, int $destino_id, int $cantidad) {
        return $this->executeWithDeadlockRetry(function(PDO $pdo) use ($origen_id, $destino_id, $cantidad) {
            // Obtener los IDs en orden para prevenir deadlocks
            $ids = [$origen_id, $destino_id];
            sort($ids, SORT_NUMERIC);

            // Bloquear las filas en un orden específico
            $selectForUpdate = $pdo->prepare("SELECT id, stock FROM productos WHERE id = ? FOR UPDATE");
            foreach ($ids as $id) {
                $selectForUpdate->execute([$id]);
                $row = $selectForUpdate->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    throw new Exception("Producto $id no encontrado");
                }
            }

            // Verificar stock disponible en origen
            $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->execute([$origen_id]);
            $stock_origen = (int)$stmt->fetchColumn();

            if ($stock_origen < $cantidad) {
                throw new Exception("Stock insuficiente en producto $origen_id");
            }

            // Realizar la transferencia
            $upd = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $upd->execute([$cantidad, $origen_id]);

            $upd2 = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $upd2->execute([$cantidad, $destino_id]);

            return true;
        });
    }
}

// Ejemplo de uso
if (php_sapi_name() !== 'cli') echo "<pre>";

$dm = new DeadlockManager($pdo, 4, 1);
try {
    $ok = $dm->transferirStock(1, 2, 5);
    if ($ok) echo "Transferencia exitosa\n";
} catch (Exception $e) {
    echo "Error en la transferencia: " . $e->getMessage() . "\n";
}

if (php_sapi_name() !== 'cli') echo "</pre>";
