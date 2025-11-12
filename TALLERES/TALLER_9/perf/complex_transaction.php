<?php
// complex_transaction.php
// Manejo de transacciones complejas usando SAVEPOINTs.
// Requiere: config_pdo.php

require_once __DIR__ . '/config_pdo.php';

class ComplexTransactionManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function procesarVentaCompleja(int $cliente_id, array $items) {
        try {
            $this->pdo->beginTransaction();

            // Crear la venta con total temporal 0
            $stmt = $this->pdo->prepare("INSERT INTO ventas (cliente_id, fecha, total) VALUES (?, CURDATE(), 0)");
            $stmt->execute([$cliente_id]);
            $venta_id = $this->pdo->lastInsertId();

            // Punto de guardado después de crear la venta
            $this->pdo->exec("SAVEPOINT venta_creada");

            $total_venta = 0.0;
            $items_procesados = 0;

            foreach ($items as $index => $item) {
                // Normalizar estructura del item
                $productId = (int)($item['producto_id'] ?? 0);
                $cantidad  = (int)($item['cantidad'] ?? 0);

                if ($productId <= 0 || $cantidad <= 0) {
                    echo "Item inválido en índice $index, se omite.<br>";
                    continue;
                }

                // Crear un savepoint por item (nombre seguro)
                $sp = "item_" . $index;
                $this->pdo->exec("SAVEPOINT " . $sp);

                try {
                    // Bloquear fila del producto para evitar race conditions
                    $stmt = $this->pdo->prepare("SELECT stock, precio FROM productos WHERE id = ? FOR UPDATE");
                    $stmt->execute([$productId]);
                    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$producto) {
                        throw new Exception("Producto $productId no existe");
                    }

                    if ($producto['stock'] < $cantidad) {
                        throw new Exception("Stock insuficiente para producto $productId");
                    }

                    // Actualizar stock
                    $stmt = $this->pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$cantidad, $productId]);

                    // Registrar detalle de venta. Verificamos si la columna subtotal existe en la tabla; si no, calculamos y guardamos solo lo esencial.
                    $subtotal = round($producto['precio'] * $cantidad, 2);

                    // Intentamos insertar con columna subtotal si existe
                    $colsCheck = $this->pdo->query("SHOW COLUMNS FROM detalles_venta LIKE 'subtotal'")->fetchAll();
                    if (count($colsCheck) > 0) {
                        $stmt = $this->pdo->prepare(
                            "INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)"
                        );
                        $stmt->execute([$venta_id, $productId, $cantidad, $producto['precio'], $subtotal]);
                    } else {
                        $stmt = $this->pdo->prepare(
                            "INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)"
                        );
                        $stmt->execute([$venta_id, $productId, $cantidad, $producto['precio']]);
                    }

                    $total_venta += $subtotal;
                    $items_procesados++;

                } catch (Exception $e) {
                    // Rollback to savepoint for this item and continue with next item
                    $this->pdo->exec("ROLLBACK TO SAVEPOINT " . $sp);
                    echo "Error procesando producto $productId: " . htmlspecialchars($e->getMessage()) . "<br>";
                    continue;
                }
            }

            // Actualizar total de la venta
            $stmt = $this->pdo->prepare("UPDATE ventas SET total = ? WHERE id = ?");
            $stmt->execute([$total_venta, $venta_id]);

            // Confirmar
            $this->pdo->commit();
            echo "Venta (id=$venta_id) procesada. Items procesados: $items_procesados. Total: $total_venta<br>";

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo "Error en la transacción compleja: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
}

// Ejemplo de uso via CLI o navegador
if (php_sapi_name() !== 'cli') {
    echo "<pre>";
}

$ctm = new ComplexTransactionManager($pdo);

$items = [
    ['producto_id' => 1, 'cantidad' => 2],
    ['producto_id' => 2, 'cantidad' => 1],
    ['producto_id' => 3, 'cantidad' => 3]
];

$ctm->procesarVentaCompleja(1, $items);

if (php_sapi_name() !== 'cli') {
    echo "</pre>";
}
