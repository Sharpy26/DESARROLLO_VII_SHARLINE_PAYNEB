<?php
require_once __DIR__ . '/config.php';

function registrarPrestamoPDO($usuario_id, $libro_id) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        // FOR UPDATE equivalent: select for update
        $stmt = $pdo->prepare('SELECT cantidad FROM libros WHERE id = :id FOR UPDATE');
        $stmt->execute([':id'=>$libro_id]);
        $row = $stmt->fetch();
        if (!$row || $row['cantidad'] <= 0) {
            $pdo->rollBack();
            return ['error' => 'Libro no disponible'];
        }
        $stmt2 = $pdo->prepare('INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo) VALUES (:u, :l, NOW())');
        $stmt2->execute([':u'=>$usuario_id, ':l'=>$libro_id]);
        $prestamo_id = $pdo->lastInsertId();
        $stmt3 = $pdo->prepare('UPDATE libros SET cantidad = cantidad - 1 WHERE id = :id');
        $stmt3->execute([':id'=>$libro_id]);
        $pdo->commit();
        return ['id' => $prestamo_id];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['error' => $e->getMessage()];
    }
}

function listarPrestamosActivosPDO($pagina = 1, $por_pagina = 10) {
    global $pdo;
    $offset = ($pagina - 1) * $por_pagina;
    $stmt = $pdo->prepare('SELECT p.id, p.usuario_id, u.nombre AS usuario_nombre, p.libro_id, l.titulo AS libro_titulo, p.fecha_prestamo
        FROM prestamos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN libros l ON p.libro_id = l.id
        WHERE p.fecha_devolucion IS NULL
        ORDER BY p.fecha_prestamo DESC
        LIMIT :offset, :limit');
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function registrarDevolucionPDO($prestamo_id) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT libro_id FROM prestamos WHERE id = :id AND fecha_devolucion IS NULL FOR UPDATE');
        $stmt->execute([':id'=>$prestamo_id]);
        $row = $stmt->fetch();
        if (!$row) { $pdo->rollBack(); return ['error'=>'Préstamo no encontrado o ya devuelto']; }
        $libro_id = $row['libro_id'];
        $stmt2 = $pdo->prepare('UPDATE prestamos SET fecha_devolucion = NOW() WHERE id = :id');
        $stmt2->execute([':id'=>$prestamo_id]);
        $stmt3 = $pdo->prepare('UPDATE libros SET cantidad = cantidad + 1 WHERE id = :id');
        $stmt3->execute([':id'=>$libro_id]);
        $pdo->commit();
        return ['ok' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['error' => $e->getMessage()];
    }
}

function historialPorUsuarioPDO($usuario_id, $pagina = 1, $por_pagina = 20) {
    global $pdo;
    $offset = ($pagina - 1) * $por_pagina;
    $stmt = $pdo->prepare('SELECT p.id, p.libro_id, l.titulo, p.fecha_prestamo, p.fecha_devolucion
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        WHERE p.usuario_id = :u
        ORDER BY p.fecha_prestamo DESC
        LIMIT :offset, :limit');
    $stmt->bindValue(':u', $usuario_id, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

?>