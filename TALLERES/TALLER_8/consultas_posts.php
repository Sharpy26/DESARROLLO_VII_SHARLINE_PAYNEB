<?php
/**
 * consultas_posts.php
 *
 * Implementa consultas SQL (PDO + MySQLi) para:
 * 1) Últimas 5 publicaciones con autor y fecha
 * 2) Usuarios que no han publicado
 * 3) Promedio de publicaciones por usuario
 * 4) Publicación más reciente de cada usuario
 *
 * Recomendaciones: crear índices sobre `posts(user_id)`, `posts(published_at)` y
 * `users(id)` para mejorar el rendimiento en tablas grandes.
 */

// --- CONFIG (ajusta según tu entorno) ---
$dbHost = '127.0.0.1';
$dbName = 'tu_base_datos';
$dbUser = 'usuario';
$dbPass = 'clave';

// Conexión PDO
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Error PDO: ' . $e->getMessage());
}

// Conexión MySQLi
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die('Error MySQLi: ' . $mysqli->connect_error);
}

echo "<h2>Consultas sobre publicaciones (PDO & MySQLi)</h2>";

// 1) Últimas 5 publicaciones con autor y fecha
// --------------------------------------------------
$sql_last5 = <<<'SQL'
SELECT p.id AS post_id, p.title AS post_title, u.id AS author_id, u.name AS author_name, p.published_at
FROM posts p
JOIN users u ON p.user_id = u.id
ORDER BY p.published_at DESC
LIMIT 5;
SQL;

// PDO
$stmt = $pdo->prepare($sql_last5);
$stmt->execute();
$last5_pdo = $stmt->fetchAll();
if (empty($last5_pdo)) {
    echo "<p>No se encontraron publicaciones (PDO).</p>";
} else {
    echo "<h3>Últimas 5 publicaciones (PDO)</h3>";
    print_r($last5_pdo);
}

// MySQLi
$last5_mysqli = [];
if ($res = $mysqli->query($sql_last5)) {
    while ($row = $res->fetch_assoc()) $last5_mysqli[] = $row;
    $res->free();
}
if (empty($last5_mysqli)) {
    echo "<p>No se encontraron publicaciones (MySQLi).</p>";
} else {
    echo "<h3>Últimas 5 publicaciones (MySQLi)</h3>";
    print_r($last5_mysqli);
}

// 2) Usuarios que no han realizado ninguna publicación
// --------------------------------------------------
$sql_users_no_posts = <<<'SQL'
SELECT u.id AS user_id, u.name AS user_name
FROM users u
LEFT JOIN posts p ON p.user_id = u.id
WHERE p.id IS NULL
ORDER BY u.name;
SQL;

// PDO
$users_no_posts_pdo = $pdo->query($sql_users_no_posts)->fetchAll();
echo "<h3>Usuarios sin publicaciones (PDO)</h3>";
if (empty($users_no_posts_pdo)) echo "<p>Todos los usuarios han publicado al menos una vez.</p>";
else print_r($users_no_posts_pdo);

// MySQLi
$users_no_posts_mysqli = [];
if ($res = $mysqli->query($sql_users_no_posts)) {
    while ($row = $res->fetch_assoc()) $users_no_posts_mysqli[] = $row;
    $res->free();
}
echo "<h3>Usuarios sin publicaciones (MySQLi)</h3>";
if (empty($users_no_posts_mysqli)) echo "<p>Todos los usuarios han publicado al menos una vez.</p>";
else print_r($users_no_posts_mysqli);

// 3) Promedio de publicaciones por usuario
// --------------------------------------------------
/*
 - Interpretación: promedio total = total_posts / total_users (incluye usuarios con 0 publicaciones)
 - Usamos NULLIF para evitar división por cero cuando no hay usuarios.
*/
$sql_avg = <<<'SQL'
SELECT
  IFNULL(t.tot_posts / NULLIF(t.tot_users, 0), 0) AS promedio_por_usuario,
  t.tot_posts, t.tot_users
FROM (
  SELECT
    (SELECT COUNT(*) FROM posts) AS tot_posts,
    (SELECT COUNT(*) FROM users) AS tot_users
) AS t;
SQL;

// PDO
$avg_pdo = $pdo->query($sql_avg)->fetch();
echo "<h3>Promedio de publicaciones por usuario (PDO)</h3>";
if (!$avg_pdo) echo "<p>No hay datos disponibles.</p>";
else print_r($avg_pdo);

// MySQLi
$avg_mysqli = null;
if ($res = $mysqli->query($sql_avg)) {
    $avg_mysqli = $res->fetch_assoc();
    $res->free();
}
echo "<h3>Promedio de publicaciones por usuario (MySQLi)</h3>";
if (!$avg_mysqli) echo "<p>No hay datos disponibles.</p>";
else print_r($avg_mysqli);

// 4) Publicación más reciente de cada usuario
// --------------------------------------------------
/*
 Estrategia eficiente:
 - Obtener (user_id, MAX(published_at)) por usuario y unirse con posts para obtener los detalles.
 - Si hay muchos usuarios, asegurarse de tener índice en posts(user_id, published_at).
*/
$sql_latest_per_user = <<<'SQL'
SELECT p.user_id, u.name AS user_name, p.id AS post_id, p.title, p.published_at
FROM posts p
JOIN users u ON p.user_id = u.id
JOIN (
  SELECT user_id, MAX(published_at) AS last_pub
  FROM posts
  GROUP BY user_id
) lp ON p.user_id = lp.user_id AND p.published_at = lp.last_pub
ORDER BY u.name;
SQL;

// PDO
$latest_pdo = $pdo->query($sql_latest_per_user)->fetchAll();
echo "<h3>Publicación más reciente por usuario (PDO)</h3>";
if (empty($latest_pdo)) echo "<p>No se encontraron publicaciones.</p>";
else print_r($latest_pdo);

// MySQLi
$latest_mysqli = [];
if ($res = $mysqli->query($sql_latest_per_user)) {
    while ($row = $res->fetch_assoc()) $latest_mysqli[] = $row;
    $res->free();
}
echo "<h3>Publicación más reciente por usuario (MySQLi)</h3>";
if (empty($latest_mysqli)) echo "<p>No se encontraron publicaciones.</p>";
else print_r($latest_mysqli);

// Recomendaciones de índices (comentario):
// CREATE INDEX idx_posts_user_id ON posts(user_id);
// CREATE INDEX idx_posts_published_at ON posts(published_at);
// CREATE INDEX idx_posts_user_published ON posts(user_id, published_at);

// Cerrar conexiones
$pdo = null;
$mysqli->close();

?>
