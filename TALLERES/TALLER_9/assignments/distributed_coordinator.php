<?php
// distributed_coordinator.php
// Ejemplo simple de coordinador de transacciones (2-phase like) que usa la tabla tx_intents
// para marcar pasos prepare/commit entre múltiples recursos.

require_once __DIR__ . '/../perf/config_pdo.php';

class DistributedCoordinator {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Registra una intención de transacción
    public function createIntent(string $txKey, array $payload) {
        $stmt = $this->pdo->prepare('INSERT INTO tx_intents (tx_key, payload, state) VALUES (?, ?, ?)');
        $stmt->execute([$txKey, json_encode($payload), 'pending']);
        return $this->pdo->lastInsertId();
    }

    // Simula la fase prepare: ejecutar pasos locales y marcar prepared
    public function prepareIntent(int $id) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM tx_intents WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Intent no encontrado');

            // Aquí ejecutaríamos los checks/acciones locales (p.ej. reservar stock)
            // Para demo solo marcamos prepared
            $u = $this->pdo->prepare('UPDATE tx_intents SET state = ? WHERE id = ?');
            $u->execute(['prepared', $id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    // Commit final: marca committed
    public function commitIntent(int $id) {
        $stmt = $this->pdo->prepare('UPDATE tx_intents SET state = ? WHERE id = ?');
        return $stmt->execute(['committed', $id]);
    }

    // Abort: marca aborted
    public function abortIntent(int $id) {
        $stmt = $this->pdo->prepare('UPDATE tx_intents SET state = ? WHERE id = ?');
        return $stmt->execute(['aborted', $id]);
    }
}

// Demo quick
if (php_sapi_name() !== 'cli') echo "<pre>";

$c = new DistributedCoordinator($pdo);
$id = $c->createIntent('tx-' . uniqid(), ['note'=>'demo']);
echo "Created intent id=$id\n";
$c->prepareIntent($id);
echo "Prepared intent id=$id\n";
$c->commitIntent($id);
echo "Committed intent id=$id\n";

if (php_sapi_name() !== 'cli') echo "</pre>";
