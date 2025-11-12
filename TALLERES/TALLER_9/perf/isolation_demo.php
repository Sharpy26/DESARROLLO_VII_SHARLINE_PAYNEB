<?php
// isolation_demo.php
// Demonstrates transaction isolation behaviors using two separate PDO sessions.
// Usage: open in browser (through your local webserver) or run via CLI: php isolation_demo.php

require_once __DIR__ . '/config_pdo.php';

// Create a second PDO connection (separate session)
try {
    $pdo2 = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    echo "Second connection failed: " . $e->getMessage();
    exit(1);
}

class TransactionManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function setIsolationLevel(string $level) {
        $valid = ['READ UNCOMMITTED','READ COMMITTED','REPEATABLE READ','SERIALIZABLE'];
        if (!in_array(strtoupper($level), $valid, true)) {
            throw new InvalidArgumentException('Nivel inválido: ' . $level);
        }
        $this->pdo->exec("SET SESSION TRANSACTION ISOLATION LEVEL " . $level);
        echo "[{$this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS)}] Nivel fijado: $level<br>\n";
    }

    // Helper: read precio for a product
    public function readPrice(int $productId) {
        $stmt = $this->pdo->prepare('SELECT precio FROM productos WHERE id = ?');
        $stmt->execute([$productId]);
        $r = $stmt->fetch(PDO::FETCH_NUM);
        return $r ? $r[0] : null;
    }

    public function updatePrice(int $productId, float $newPrice) {
        $stmt = $this->pdo->prepare('UPDATE productos SET precio = ? WHERE id = ?');
        return $stmt->execute([$newPrice, $productId]);
    }
}

// Pretty helper
function hr() { echo "<hr>\n"; }

$productId = 1; // change if needed

echo "<h3>Demostración de niveles de aislamiento</h3>\n";

// Check producto exists
$check = $pdo->prepare('SELECT id, precio FROM productos WHERE id = ?');
$check->execute([$productId]);
$prod = $check->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    echo "Producto con id=$productId no encontrado. Cargue datos de prueba o cambie el id.<br>";
    exit(0);
}

echo "Producto inicial (id={$prod['id']}): precio={$prod['precio']}<br>\n";
hr();

// 1) Dirty read demo (READ UNCOMMITTED)
echo "<h4>1) Dirty read (READ UNCOMMITTED)</h4>\n";
$tmA = new TransactionManager($pdo);
$tmB = new TransactionManager($pdo2);

// Set both sessions to READ UNCOMMITTED
$tmA->setIsolationLevel('READ UNCOMMITTED');
$tmB->setIsolationLevel('READ UNCOMMITTED');

try {
    // Session A: start transaction and update but DO NOT commit yet
    $pdo->beginTransaction();
    $orig = $tmA->readPrice($productId);
    $new = round($orig * 1.1, 2);
    $tmA->updatePrice($productId, $new);
    echo "Session A: updated precio a $new (sin commit)\n";

    // Session B: read without transaction (or in its own autocommit mode)
    $priceB = $tmB->readPrice($productId);
    echo "Session B: lectura intermedia (debería ver el valor sin confirmar en READ UNCOMMITTED): $priceB<br>\n";

    // Rollback A
    $pdo->rollBack();
    echo "Session A: rollback realizado\n";

    // Session B: read again
    $priceBAfter = $tmB->readPrice($productId);
    echo "Session B: lectura después del rollback: $priceBAfter<br>\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error en dirty-read demo: " . $e->getMessage();
}

hr();

// 2) Repeatable read demo (REPEATABLE READ vs READ COMMITTED)
echo "<h4>2) Repeatable / Non-repeatable read demo</h4>\n";

// We'll demonstrate behavior under REPEATABLE READ and READ COMMITTED
foreach (['REPEATABLE READ','READ COMMITTED'] as $iso) {
    echo "<strong>Probando aislamiento: $iso</strong><br>\n";
    // Create fresh connections for isolation clarity
    $pdoA = new PDO($dsn, $dbUser, $dbPass, $options);
    $pdoB = new PDO($dsn, $dbUser, $dbPass, $options);
    $a = new TransactionManager($pdoA);
    $b = new TransactionManager($pdoB);

    $a->setIsolationLevel($iso);
    $b->setIsolationLevel($iso);

    try {
        // Session A: begin transaction and read price
        $pdoA->beginTransaction();
        $pa1 = $a->readPrice($productId);
        echo "A - primera lectura: $pa1<br>\n";

        // Session B: immediately update and commit
        $pdoB->beginTransaction();
        $b_orig = $b->readPrice($productId);
        $b_new = round($b_orig * 1.2, 2);
        $b->updatePrice($productId, $b_new);
        $pdoB->commit();
        echo "B - actualizó y commit a: $b_new<br>\n";

        // Session A: second read
        $pa2 = $a->readPrice($productId);
        echo "A - segunda lectura (dentro de la misma tx): $pa2<br>\n";

        // End A
        $pdoA->commit();
    } catch (Exception $e) {
        if ($pdoA->inTransaction()) $pdoA->rollBack();
        if ($pdoB->inTransaction()) $pdoB->rollBack();
        echo "Error en demo $iso: " . $e->getMessage() . "<br>\n";
    }

    hr();
}

echo "Demostración completada.<br>\n";

?>
