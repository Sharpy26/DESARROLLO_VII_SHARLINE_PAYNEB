<?php
require_once __DIR__ . '/config.php';

function limpiar($v) { return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

function crearUsuarioPDO($nombre, $email, $password) {
    global $pdo;
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (:n, :e, :p)');
    $stmt->execute([':n'=>$nombre, ':e'=>$email, ':p'=>$hash]);
    return ['id' => $pdo->lastInsertId()];
}

function listarUsuariosPDO($pagina = 1, $por_pagina = 10, $search = '') {
    global $pdo;
    $offset = ($pagina - 1) * $por_pagina;
    if (!empty($search)) {
        $like = "%$search%";
        $stmt = $pdo->prepare('SELECT SQL_CALC_FOUND_ROWS id, nombre, email FROM usuarios WHERE nombre LIKE :s OR email LIKE :s ORDER BY id DESC LIMIT :offset, :limit');
        $stmt->bindValue(':s', $like);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare('SELECT SQL_CALC_FOUND_ROWS id, nombre, email FROM usuarios ORDER BY id DESC LIMIT :offset, :limit');
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
        $stmt->execute();
    }
    $rows = $stmt->fetchAll();
    $total = $pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    return ['data'=>$rows, 'total'=>(int)$total];
}

function obtenerUsuarioPDO($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, nombre, email FROM usuarios WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    return $stmt->fetch();
}

function actualizarUsuarioPDO($id, $nombre, $email, $password = null) {
    global $pdo;
    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = :n, email = :e, password_hash = :p WHERE id = :id');
        return $stmt->execute([':n'=>$nombre, ':e'=>$email, ':p'=>$hash, ':id'=>$id]);
    } else {
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = :n, email = :e WHERE id = :id');
        return $stmt->execute([':n'=>$nombre, ':e'=>$email, ':id'=>$id]);
    }
}

function eliminarUsuarioPDO($id) {
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
    return $stmt->execute([':id'=>$id]);
}

?>