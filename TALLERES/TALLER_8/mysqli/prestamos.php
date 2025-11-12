<?php
require_once __DIR__ . '/config.php';

// Registrar préstamo (transacción): inserta préstamo y decrementa cantidad
function registrarPrestamo($usuario_id, $libro_id) {
    global $mysqli;

    $mysqli->begin_transaction();
    try {
        // Verificar existencia y cantidad
        $sql = "SELECT cantidad FROM libros WHERE id = ? FOR UPDATE";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $libro_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if (!$res || $res['cantidad'] <= 0) {
            $mysqli->rollback();
            return ['error' => 'Libro no disponible'];
        }

        // Insertar préstamo
        $sqlIns = "INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo) VALUES (?, ?, NOW())";
        $stmt2 = $mysqli->prepare($sqlIns);
        $stmt2->bind_param('ii', $usuario_id, $libro_id);
        $stmt2->execute();
        $prestamo_id = $stmt2->insert_id;

        // Actualizar stock
        $sqlUpd = "UPDATE libros SET cantidad = cantidad - 1 WHERE id = ?";
        $stmt3 = $mysqli->prepare($sqlUpd);
        $stmt3->bind_param('i', $libro_id);
        $stmt3->execute();

        $mysqli->commit();
        return ['id' => $prestamo_id];
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['error' => $e->getMessage()];
    }
}

function listarPrestamosActivos($pagina = 1, $por_pagina = 10) {
    global $mysqli;
    $offset = ($pagina - 1) * $por_pagina;
    $sql = "SELECT p.id, p.usuario_id, u.nombre AS usuario_nombre, p.libro_id, l.titulo AS libro_titulo, p.fecha_prestamo
            FROM prestamos p
            JOIN usuarios u ON p.usuario_id = u.id
            JOIN libros l ON p.libro_id = l.id
            WHERE p.fecha_devolucion IS NULL
            ORDER BY p.fecha_prestamo DESC
            LIMIT ?, ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $offset, $por_pagina);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return $res;
}

function registrarDevolucion($prestamo_id) {
    global $mysqli;
    $mysqli->begin_transaction();
    try {
        // Obtener registro
        $sql = "SELECT libro_id FROM prestamos WHERE id = ? AND fecha_devolucion IS NULL FOR UPDATE";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $prestamo_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            $mysqli->rollback();
            return ['error' => 'Préstamo no encontrado o ya devuelto'];
        }
        $libro_id = $row['libro_id'];

        // Actualizar préstamo
        $sqlUpd = "UPDATE prestamos SET fecha_devolucion = NOW() WHERE id = ?";
        $stmt2 = $mysqli->prepare($sqlUpd);
        $stmt2->bind_param('i', $prestamo_id);
        $stmt2->execute();

        // Incrementar stock
        $sqlInc = "UPDATE libros SET cantidad = cantidad + 1 WHERE id = ?";
        $stmt3 = $mysqli->prepare($sqlInc);
        $stmt3->bind_param('i', $libro_id);
        $stmt3->execute();

        $mysqli->commit();
        return ['ok' => true];
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['error' => $e->getMessage()];
    }
}

function historialPorUsuario($usuario_id, $pagina = 1, $por_pagina = 20) {
    global $mysqli;
    $offset = ($pagina - 1) * $por_pagina;
    $sql = "SELECT p.id, p.libro_id, l.titulo, p.fecha_prestamo, p.fecha_devolucion
            FROM prestamos p
            JOIN libros l ON p.libro_id = l.id
            WHERE p.usuario_id = ?
            ORDER BY p.fecha_prestamo DESC
            LIMIT ?, ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $usuario_id, $offset, $por_pagina);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>