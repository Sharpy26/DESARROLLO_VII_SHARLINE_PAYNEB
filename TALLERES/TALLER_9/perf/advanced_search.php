<?php
// advanced_search.php
// Funcion para realizar búsquedas avanzadas y seguras usando QueryBuilder

require_once __DIR__ . '/query_builder.php';

function busquedaAvanzada(PDO $pdo, array $criterios) {
    $qb = new QueryBuilder($pdo);
    $qb->table('productos p')
       ->select('p.*', 'c.nombre as categoria')
       ->join('categorias c', 'p.categoria_id', '=', 'c.id');

    if (!empty($criterios['nombre'])) {
        $qb->where('p.nombre', 'LIKE', '%' . $criterios['nombre'] . '%');
    }

    if (isset($criterios['precio_min'])) {
        $qb->where('p.precio', '>=', $criterios['precio_min']);
    }

    if (isset($criterios['precio_max'])) {
        $qb->where('p.precio', '<=', $criterios['precio_max']);
    }

    if (!empty($criterios['categorias']) && is_array($criterios['categorias'])) {
        $qb->whereIn('c.id', $criterios['categorias']);
    }

    if (!empty($criterios['ordenar_por'])) {
        $qb->orderBy($criterios['ordenar_por'], $criterios['orden'] ?? 'ASC');
    }

    if (!empty($criterios['limite'])) {
        $qb->limit((int)$criterios['limite'], (int)($criterios['offset'] ?? 0));
    }

    return $qb->execute();
}

// Demo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $criterios = [
        'nombre' => 'laptop',
        'precio_min' => 500,
        'precio_max' => 2000,
        'categorias' => [1,2],
        'ordenar_por' => 'p.precio',
        'orden' => 'DESC',
        'limite' => 10
    ];

    $resultados = busquedaAvanzada($pdo, $criterios);
    echo "<pre>Resultados:\n"; print_r($resultados); echo "</pre>";
}
