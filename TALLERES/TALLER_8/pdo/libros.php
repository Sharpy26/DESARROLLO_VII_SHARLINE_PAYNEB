<?php
require_once __DIR__ . '/config.php';

function limpiar($v) { return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

function agregarLibroPDO($titulo, $autor, $isbn, $anio, $cantidad) {
    global $pdo;
    $sql = "INSERT INTO libros (titulo, autor, isbn, anio_publicacion, cantidad) VALUES (:titulo, :autor, :isbn, :anio, :cantidad)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':titulo'=>$titulo, ':autor'=>$autor, ':isbn'=>$isbn, ':anio'=>$anio, ':cantidad'=>$cantidad]);
    return ['id' => $pdo->lastInsertId()];
}

function listarLibrosPDO($pagina = 1, $por_pagina = 10, $search = '') {
    global $pdo;
    $offset = ($pagina - 1) * $por_pagina;
    if (!empty($search)) {
        $like = "%$search%";
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM libros WHERE titulo LIKE :s OR autor LIKE :s OR isbn LIKE :s ORDER BY id DESC LIMIT :offset, :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':s', $like);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM libros ORDER BY id DESC LIMIT :offset, :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
        $stmt->execute();
    }
    $rows = $stmt->fetchAll();
    $total = $pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    return ['data'=>$rows, 'total'=>(int)$total];
}

function obtenerLibroPDO($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM libros WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    return $stmt->fetch();
}

function actualizarLibroPDO($id, $titulo, $autor, $isbn, $anio, $cantidad) {
    global $pdo;
    $sql = "UPDATE libros SET titulo = :titulo, autor = :autor, isbn = :isbn, anio_publicacion = :anio, cantidad = :cantidad WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':titulo'=>$titulo, ':autor'=>$autor, ':isbn'=>$isbn, ':anio'=>$anio, ':cantidad'=>$cantidad, ':id'=>$id]);
}

function eliminarLibroPDO($id) {
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM libros WHERE id = :id');
    return $stmt->execute([':id'=>$id]);
}

?>