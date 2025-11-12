<?php
// pagination.php
// Implementa Paginator, CursorPaginator, simple cache, infinite scroll JSON endpoint y export CSV.
// Usage: adjust DB creds in perf/config_pdo.php. Open in browser for HTML UI or call with ?ajax=1 (infinite scroll) or ?export=1 to download CSV.

require_once __DIR__ . '/config_pdo.php';
require_once __DIR__ . '/query_builder.php';

class Paginator {
    protected $pdo;
    protected $table;
    protected $perPage;
    protected $currentPage;
    protected $conditions = [];
    protected $params = [];
    protected $orderBy = '';
    protected $joins = [];
    protected $fields = ['*'];

    public function __construct(PDO $pdo, $table, $perPage = 10) {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->perPage = max(1,(int)$perPage);
        $this->currentPage = 1;
    }

    public function select($fields) {
        $this->fields = is_array($fields) ? $fields : func_get_args();
        return $this;
    }

    public function where($condition, $params = []) {
        $this->conditions[] = $condition;
        $this->params = array_merge($this->params, (array)$params);
        return $this;
    }

    public function join($join) {
        $this->joins[] = $join;
        return $this;
    }

    public function orderBy($orderBy) {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function setPage($page) {
        $this->currentPage = max(1, (int)$page);
        return $this;
    }

    public function setPerPage($n) {
        $this->perPage = max(1, (int)$n);
        return $this;
    }

    public function getTotalRecords() {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($this->joins)) $sql .= ' ' . implode(' ', $this->joins);
        if (!empty($this->conditions)) $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return (int)$stmt->fetchColumn();
    }

    public function getResults() {
        $offset = ($this->currentPage - 1) * $this->perPage;
        $sql = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table}";
        if (!empty($this->joins)) $sql .= ' ' . implode(' ', $this->joins);
        if (!empty($this->conditions)) $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        if ($this->orderBy) $sql .= " ORDER BY {$this->orderBy}";
        $sql .= " LIMIT {$this->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPageInfo() {
        $totalRecords = $this->getTotalRecords();
        $totalPages = (int)ceil($totalRecords / $this->perPage);
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_previous' => $this->currentPage > 1,
            'has_next' => $this->currentPage < $totalPages,
            'previous_page' => max(1, $this->currentPage - 1),
            'next_page' => min($totalPages, $this->currentPage + 1),
            'first_page' => 1,
            'last_page' => $totalPages,
        ];
    }
}

class CursorPaginator extends Paginator {
    private $cursorField;
    private $cursorValue = null;
    private $direction = 'next';

    public function __construct(PDO $pdo, $table, $cursorField, $perPage = 10) {
        parent::__construct($pdo, $table, $perPage);
        $this->cursorField = $cursorField;
    }

    public function setCursor($value, $direction = 'next') {
        $this->cursorValue = $value;
        $this->direction = $direction === 'prev' ? 'prev' : 'next';
        return $this;
    }

    public function getResults() {
        $sql = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table}";
        if (!empty($this->joins)) $sql .= ' ' . implode(' ', $this->joins);
        $conds = $this->conditions;
        $params = $this->params;
        if ($this->cursorValue !== null) {
            $op = $this->direction === 'next' ? '>' : '<';
            $conds[] = "{$this->cursorField} {$op} :cursor";
            $params[':cursor'] = $this->cursorValue;
        }
        if (!empty($conds)) $sql .= ' WHERE ' . implode(' AND ', $conds);
        if ($this->orderBy) $sql .= " ORDER BY {$this->orderBy}";
        else {
            $dir = $this->direction === 'next' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY {$this->cursorField} {$dir}";
        }
        $limit = $this->perPage + 1; // +1 to detect more
        $sql .= " LIMIT {$limit}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = count($rows) > $this->perPage;
        if ($hasMore) array_pop($rows);
        $nextCursor = $hasMore && !empty($rows) ? end($rows)[$this->cursorField] : null;
        return ['results'=>$rows,'has_more'=>$hasMore,'next_cursor'=>$nextCursor];
    }
}

// Simple file cache for paged responses
class SimpleCache {
    private $dir;
    private $ttl;
    public function __construct($dir = __DIR__ . '/cache', $ttl = 60) {
        $this->dir = $dir; $this->ttl = $ttl;
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    }

    private function keyToFile($key) { return $this->dir . '/' . preg_replace('/[^A-Za-z0-9_\-]/','_', $key) . '.cache'; }

    public function get($key) {
        $file = $this->keyToFile($key);
        if (!file_exists($file)) return null;
        if (filemtime($file) + $this->ttl < time()) { @unlink($file); return null; }
        return unserialize(file_get_contents($file));
    }

    public function set($key, $value) {
        $file = $this->keyToFile($key);
        file_put_contents($file, serialize($value));
    }
}

function exportToCsv(array $rows, string $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    $out = fopen('php://output', 'w');
    if (empty($rows)) { fclose($out); return; }
    // header
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out);
}

// --- Endpoint / demo handling ---
$cache = new SimpleCache(__DIR__ . '/cache', 120);

$isAjax = isset($_GET['ajax']) && $_GET['ajax'];
$isExport = isset($_GET['export']) && $_GET['export'];

// Validate per_page
$perPage = isset($_GET['per_page']) ? max(1, min(1000, (int)$_GET['per_page'])) : 20;

if ($isAjax) {
    // Infinite scroll JSON API (cursor-based)
    $cursor = isset($_GET['cursor']) ? $_GET['cursor'] : null;
    $cacheKey = 'cursor_' . ($cursor ?? 'start') . '_pp_' . $perPage;
    $cached = $cache->get($cacheKey);
    if ($cached !== null) {
        header('Content-Type: application/json'); echo json_encode($cached); exit;
    }

    $cp = new CursorPaginator($pdo, 'productos', 'id', $perPage);
    $cp->select('id','nombre','precio');
    if ($cursor) $cp->setCursor((int)$cursor, 'next');
    $data = $cp->getResults();
    $cache->set($cacheKey, $data);
    header('Content-Type: application/json'); echo json_encode($data); exit;
}

if ($isExport) {
    // Export current page to CSV (use page-based paginator for deterministic export)
    $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $p = new Paginator($pdo, 'productos', $perPage);
    $p->select('id','nombre','precio');
    $p->setPage($page);
    $rows = $p->getResults();
    exportToCsv($rows, 'productos_page_' . $page . '.csv');
    exit;
}

// Otherwise render HTML UI (basic) with per-page selection and infinite scroll JS
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$p = new Paginator($pdo, 'productos', $perPage);
$p->select('productos.id','productos.nombre','productos.precio');
$p->setPage($page);
$results = $p->getResults();
$pageInfo = $p->getPageInfo();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Paginación demo</title>
    <style>/* small styling */
    body{font-family:Arial,Helvetica,sans-serif;padding:20px}
    .controls{margin-bottom:10px}
    .item{border-bottom:1px solid #eee;padding:8px 0}
    </style>
</head>
<body>
    <div class="controls">
        <label>Per page:
            <select id="perPageSelect">
                <option value="10"<?= $perPage==10?' selected':'' ?>>10</option>
                <option value="20"<?= $perPage==20?' selected':'' ?>>20</option>
                <option value="50"<?= $perPage==50?' selected':'' ?>>50</option>
                <option value="100"<?= $perPage==100?' selected':'' ?>>100</option>
            </select>
        </label>
        <button id="exportBtn">Export current page CSV</button>
    </div>

    <div id="list">
        <?php foreach ($results as $r): ?>
            <div class="item" data-id="<?= (int)$r['id'] ?>">
                <strong><?= htmlspecialchars($r['nombre']) ?></strong>
                <div>$<?= number_format($r['precio'],2) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pageInfo['has_next']): ?>
        <button id="loadMore">Load more (page <?= $pageInfo['next_page'] ?>)</button>
    <?php else: ?>
        <div>End of results</div>
    <?php endif; ?>

    <script>
    (function(){
        const perPageEl = document.getElementById('perPageSelect');
        perPageEl.addEventListener('change', ()=>{
            const pp = perPageEl.value;
            location.search = '?per_page='+pp;
        });

        document.getElementById('exportBtn').addEventListener('click', ()=>{
            const pp = perPageEl.value;
            const page = <?= $page ?>;
            window.location = '?export=1&per_page='+pp+'&page='+page;
        });

        const loadBtn = document.getElementById('loadMore');
        if (loadBtn) loadBtn.addEventListener('click', ()=>{
            const last = document.querySelector('#list .item:last-child');
            const cursor = last ? last.getAttribute('data-id') : null;
            const pp = perPageEl.value;
            fetch('?ajax=1&cursor='+cursor+'&per_page='+pp)
            .then(r=>r.json()).then(data=>{
                data.results.forEach(function(item){
                    const div = document.createElement('div');
                    div.className='item'; div.setAttribute('data-id', item.id);
                    div.innerHTML = '<strong>'+item.nombre+'</strong><div>$'+parseFloat(item.precio).toFixed(2)+'</div>';
                    document.getElementById('list').appendChild(div);
                });
                if (!data.has_more) loadBtn.remove();
            });
        });
    })();
    </script>
</body>
</html>
