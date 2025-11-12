<?php
// report_generator.php
// Generador de reportes que permite seleccionar campos y aplicar filtros dinámicamente.

require_once __DIR__ . '/../perf/query_builder.php';
require_once __DIR__ . '/../perf/config_pdo.php';

function generateReport(PDO $pdo, array $fields, array $filters = [], int $page = 1, int $perPage = 50) {
    // Validar campos (permitir letras, dígitos, underscore, punto y espacio)
    $safeFields = [];
    foreach ($fields as $f) {
        if (!preg_match('/^[A-Za-z0-9_\.\s]+$/', $f)) continue;
        $safeFields[] = $f;
    }
    if (empty($safeFields)) throw new InvalidArgumentException('No hay campos válidos');

    $qb = new QueryBuilder($pdo);
    $qb->table('productos p')
       ->select($safeFields)
       ->join('categorias c','p.categoria_id','=','c.id');

    // Aplicar filtros comunes
    if (!empty($filters['categoria'])) $qb->where('c.id','=',(int)$filters['categoria']);
    if (isset($filters['precio_min'])) $qb->where('p.precio','>=',(float)$filters['precio_min']);
    if (isset($filters['precio_max'])) $qb->where('p.precio','<=',(float)$filters['precio_max']);

    // Paginación
    $perPage = max(1, min(500, $perPage));
    $page = max(1, $page);
    $qb->limit($perPage, ($page-1)*$perPage);

    // Guardar query para depuración
    $debug = ['sql'=>$qb->buildQuery(),'params'=>$qb->getParameters(),'page'=>$page,'per_page'=>$perPage];
    file_put_contents(__DIR__ . '/report_debug.log', date('c') . " " . json_encode($debug) . "\n", FILE_APPEND);

    $rows = $qb->execute();
    return ['meta'=>['page'=>$page,'per_page'=>$perPage,'count'=>count($rows)],'data'=>$rows,'debug'=>$debug];
}

// Demo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $fields = ['p.id','p.nombre','p.precio','c.nombre as categoria'];
    $res = generateReport($pdo, $fields, ['precio_min'=>10], 1, 10);
    echo "<pre>"; print_r($res); echo "</pre>";
}
