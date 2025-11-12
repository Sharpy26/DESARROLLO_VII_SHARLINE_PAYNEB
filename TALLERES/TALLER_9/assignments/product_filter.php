<?php
// product_filter.php
// Demo de filtrado múltiple usando QueryBuilder

require_once __DIR__ . '/../perf/query_builder.php';
require_once __DIR__ . '/../perf/config_pdo.php';

function filterProducts(PDO $pdo, array $criteria) {
    $qb = new QueryBuilder($pdo);
    $qb->table('productos p')
       ->select('p.id','p.nombre','p.precio','p.stock','c.nombre as categoria')
       ->join('categorias c','p.categoria_id','=','c.id');

    if (!empty($criteria['nombre'])) $qb->where('p.nombre','LIKE','%'.$criteria['nombre'].'%');
    if (isset($criteria['precio_min'])) $qb->where('p.precio','>=',(float)$criteria['precio_min']);
    if (isset($criteria['precio_max'])) $qb->where('p.precio','<=',(float)$criteria['precio_max']);
    if (!empty($criteria['categorias']) && is_array($criteria['categorias'])) $qb->whereIn('c.id',$criteria['categorias']);
    if (isset($criteria['disponible'])) {
        if ($criteria['disponible']) $qb->where('p.stock','>',0);
        else $qb->where('p.stock','=',0);
    }

    // Paginación
    $limit = isset($criteria['limit']) ? (int)$criteria['limit'] : 20;
    $page = isset($criteria['page']) ? max(1,(int)$criteria['page']) : 1;
    $offset = ($page - 1) * $limit;
    $qb->limit($limit, $offset);

    // Registro de la consulta para depuración
    file_put_contents(__DIR__ . '/debug_query.log', date('c') . " QUERY: " . $qb->buildQuery() . " PARAMS: " . json_encode($qb->getParameters()) . "\n", FILE_APPEND);

    return $qb->execute();
}

// Demo
if (php_sapi_name() !== 'cli') echo "<pre>";
$res = filterProducts($pdo, ['nombre'=>'Producto','precio_min'=>10,'disponible'=>1,'limit'=>10,'page'=>1]);
print_r($res);
if (php_sapi_name() !== 'cli') echo "</pre>";
