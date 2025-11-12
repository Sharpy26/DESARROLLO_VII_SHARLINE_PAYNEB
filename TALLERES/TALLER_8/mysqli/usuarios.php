<?php
require_once __DIR__ . '/config.php';

function limpiar($v) { return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

function crearUsuario($nombre, $email, $password) {
    global $mysqli;
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $sql = "INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return ['error' => $mysqli->error];
    $stmt->bind_param('sss', $nombre, $email, $hash);
    if (!$stmt->execute()) return ['error' => $stmt->error];
    return ['id' => $stmt->insert_id];
}

function listarUsuarios($pagina = 1, $por_pagina = 10, $search = '') {
    global $mysqli;
    $offset = ($pagina - 1) * $por_pagina;
    $searchLike = '%' . $search . '%';
    if (!empty($search)) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS id, nombre, email FROM usuarios WHERE nombre LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ?, ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssii', $searchLike, $searchLike, $offset, $por_pagina);
    } else {
        $sql = "SELECT SQL_CALC_FOUND_ROWS id, nombre, email FROM usuarios ORDER BY id DESC LIMIT ?, ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $offset, $por_pagina);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $total = $mysqli->query('SELECT FOUND_ROWS()')->fetch_row()[0];
    return ['data' => $rows, 'total' => (int)$total];
}

function obtenerUsuario($id) {
    global $mysqli;
    $sql = "SELECT id, nombre, email FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function actualizarUsuario($id, $nombre, $email, $password = null) {
    global $mysqli;
    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios SET nombre = ?, email = ?, password_hash = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssi', $nombre, $email, $hash, $id);
    } else {
        $sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $nombre, $email, $id);
    }
    return $stmt->execute();
}

function eliminarUsuario($id) {
    global $mysqli;
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

?>