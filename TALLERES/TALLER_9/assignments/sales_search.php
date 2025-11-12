<?php
// sales_search.php
// Búsqueda de ventas por fechas, cliente, producto y montos.

require_once __DIR__ . '/../perf/query_builder.php';
require_once __DIR__ . '/../perf/config_pdo.php';

function searchSales(PDO $pdo, array $criteria, int $page = 1, int $perPage = 25) {
    $qb = new QueryBuilder($pdo);
    $qb->table('ventas v')
       ->select('v.id','v.cliente_id','v.fecha','v.total')
       ->join('clientes cl','v.cliente_id','=','cl.id');

    // Si filtrar por producto, unimos detalles_venta
    if (!empty($criteria['producto_id'])) {
        $qb->join('detalles_venta dv','v.id','=','dv.venta_id');
        $qb->where('dv.producto_id','=',(int)$criteria['producto_id']);
    }

    if (!empty($criteria['cliente_id'])) $qb->where('v.cliente_id','=',(int)$criteria['cliente_id']);
    if (!empty($criteria['fecha_desde'])) $qb->where('v.fecha','>=',$criteria['fecha_desde']);
    if (!empty($criteria['fecha_hasta'])) $qb->where('v.fecha','<=',$criteria['fecha_hasta']);
    if (isset($criteria['monto_min'])) $qb->where('v.total','>=',(float)$criteria['monto_min']);
    if (isset($criteria['monto_max'])) $qb->where('v.total','<=',(float)$criteria['monto_max']);

    // Orden y paginación
    $qb->orderBy('v.fecha','DESC');
    $perPage = max(1, min(500, $perPage));
    $page = max(1, $page);
    $qb->limit($perPage, ($page-1)*$perPage);

    // Log de depuración
    file_put_contents(__DIR__ . '/sales_debug.log', date('c') . " QUERY: " . $qb->buildQuery() . " PARAMS: " . json_encode($qb->getParameters()) . "\n", FILE_APPEND);

    $rows = $qb->execute();
    return ['meta'=>['page'=>$page,'per_page'=>$perPage,'count'=>count($rows)],'data'=>$rows];
}

// Demo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $res = searchSales($pdo, ['fecha_desde'=>date('Y-m-d', strtotime('-30 days')),'monto_min'=>20],1,20);
    echo "<pre>"; print_r($res); echo "</pre>";
}
