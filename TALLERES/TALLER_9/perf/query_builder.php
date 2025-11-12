<?php
// query_builder.php
// Builders para consultas dinámicas y seguras (PDO). Ajusta según convenciones de tu proyecto.

require_once __DIR__ . '/config_pdo.php';

class QueryBuilder {
    private $pdo;
    private $table;
    private $conditions = [];
    private $parameters = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $joins = [];
    private $groupBy = [];
    private $having = [];
    private $fields = ['*'];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function table($table) {
        $this->table = $table;
        return $this;
    }

    public function select($fields) {
        $this->fields = is_array($fields) ? $fields : func_get_args();
        return $this;
    }

    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = ':p' . count($this->parameters);
        $this->conditions[] = "$column $operator $placeholder";
        $this->parameters[$placeholder] = $value;

        return $this;
    }

    public function whereIn($column, array $values) {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = ':p' . count($this->parameters);
            $placeholders[] = $placeholder;
            $this->parameters[$placeholder] = $value;
        }

        $this->conditions[] = "$column IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    public function join($table, $first, $operator, $second, $type = 'INNER') {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'conditions' => "$first $operator $second"
        ];
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $dir";
        return $this;
    }

    public function groupBy($columns) {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function having($condition, $value) {
        $placeholder = ':p' . count($this->parameters);
        $this->having[] = "$condition $placeholder";
        $this->parameters[$placeholder] = $value;
        return $this;
    }

    public function limit($limit, $offset = null) {
        $this->limit = (int)$limit;
        $this->offset = $offset !== null ? (int)$offset : null;
        return $this;
    }

    public function buildQuery() {
        if (!$this->table) throw new Exception('Table not set');

        $sql = "SELECT " . implode(', ', $this->fields) . " FROM " . $this->table;

        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['conditions']}";
        }

        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
            if ($this->offset !== null) $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    public function execute() {
        $sql = $this->buildQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->parameters);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParameters() {
        return $this->parameters;
    }
}

class InsertBuilder {
    private $pdo;
    private $table;
    private $data = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function into($table) {
        $this->table = $table;
        return $this;
    }

    public function values(array $data) {
        $this->data = $data;
        return $this;
    }

    public function execute() {
        if (!$this->table) throw new Exception('Table not set');
        if (empty($this->data)) throw new Exception('No data provided');

        $columns = array_keys($this->data);
        $placeholders = array_map(function($col) { return ':' . $col; }, $columns);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($columns as $col) $params[':' . $col] = $this->data[$col];
        $ok = $stmt->execute($params);
        if ($ok) return $this->pdo->lastInsertId();
        return false;
    }
}

class UpdateBuilder {
    private $pdo;
    private $table;
    private $data = [];
    private $conditions = [];
    private $parameters = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function table($table) {
        $this->table = $table;
        return $this;
    }

    public function set(array $data) {
        $this->data = $data;
        return $this;
    }

    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = ':w' . count($this->parameters);
        $this->conditions[] = "$column $operator $placeholder";
        $this->parameters[$placeholder] = $value;
        return $this;
    }

    public function execute() {
        if (!$this->table) throw new Exception('Table not set');
        if (empty($this->data)) throw new Exception('No data for update');

        $setParts = [];
        foreach ($this->data as $column => $value) {
            $ph = ':s' . count($this->parameters);
            $setParts[] = "$column = $ph";
            $this->parameters[$ph] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        if (!empty($this->conditions)) $sql .= " WHERE " . implode(' AND ', $this->conditions);

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->parameters);
    }
}

// Ejemplo de uso (solo demo, ajustar paths y entorno)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    // Query demo
    $qb = new QueryBuilder($pdo);
    $results = $qb->table('productos p')
        ->select('p.id','p.nombre','p.precio')
        ->where('p.precio','>',100)
        ->limit(10)
        ->execute();

    echo "<pre>QueryBuilder results:\n";
    print_r($results);

    // Insert demo
    $ins = new InsertBuilder($pdo);
    $newId = $ins->into('productos')->values(['nombre'=>'QB Product','precio'=>12.5,'categoria_id'=>1])->execute();
    echo "Inserted new producto id: $newId\n";

    // Update demo
    $up = new UpdateBuilder($pdo);
    $ok = $up->table('productos')->set(['precio'=>99.9])->where('id','=',$newId)->execute();
    echo "Update ok: " . ($ok ? 'yes' : 'no') . "\n</pre>";
}
