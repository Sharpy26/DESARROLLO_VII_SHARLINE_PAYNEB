<?php
require_once __DIR__ . '/config.php';

// Sanitización/validación mínima
function limpiar($v) {
    return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
}

// Agregar libro
function agregarLibro($titulo, $autor, $isbn, $anio, $cantidad) {
    global $mysqli;
    $sql = "INSERT INTO libros (titulo, autor, isbn, anio_publicacion, cantidad) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return ['error' => $mysqli->error];
    $stmt->bind_param('sssii', $titulo, $autor, $isbn, $anio, $cantidad);
    $ok = $stmt->execute();
    if (!$ok) return ['error' => $stmt->error];
    return ['id' => $stmt->insert_id];
}

// Listar libros con paginación y búsqueda (por título/autor/isbn)
function listarLibros($pagina = 1, $por_pagina = 10, $search = '') {
    global $mysqli;
    $offset = ($pagina - 1) * $por_pagina;
    $searchLike = '%' . $search . '%';

    if (!empty($search)) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM libros WHERE titulo LIKE ? OR autor LIKE ? OR isbn LIKE ? ORDER BY id DESC LIMIT ?, ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssii', $searchLike, $searchLike, $searchLike, $offset, $por_pagina);
    } else {
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM libros ORDER BY id DESC LIMIT ?, ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $offset, $por_pagina);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $total = $mysqli->query('SELECT FOUND_ROWS()')->fetch_row()[0];
    return ['data' => $rows, 'total' => (int)$total];
}

function obtenerLibro($id) {
    global $mysqli;
    $sql = "SELECT * FROM libros WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function actualizarLibro($id, $titulo, $autor, $isbn, $anio, $cantidad) {
    global $mysqli;
    $sql = "UPDATE libros SET titulo = ?, autor = ?, isbn = ?, anio_publicacion = ?, cantidad = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sssiii', $titulo, $autor, $isbn, $anio, $cantidad, $id);
    return $stmt->execute();
}

function eliminarLibro($id) {
    global $mysqli;
    $sql = "DELETE FROM libros WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

?>