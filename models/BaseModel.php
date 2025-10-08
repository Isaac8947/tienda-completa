<?php
require_once __DIR__ . '/../config/database.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function findAll($conditions = [], $orderBy = null, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Handle new array format with 'where', 'order_by', 'limit', 'offset' keys
        if (isset($conditions['where']) || isset($conditions['order_by']) || isset($conditions['limit']) || isset($conditions['offset'])) {
            $whereCondition = isset($conditions['where']) ? $conditions['where'] : '';
            $orderBy = isset($conditions['order_by']) ? $conditions['order_by'] : $orderBy;
            $limit = isset($conditions['limit']) ? $conditions['limit'] : $limit;
            $offset = isset($conditions['offset']) ? $conditions['offset'] : $offset;
            
            if (!empty($whereCondition)) {
                $sql .= " WHERE " . $whereCondition;
            }
        } else {
            // Handle old format with key-value pairs
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $key => $value) {
                    if (is_array($value)) {
                        $placeholders = str_repeat('?,', count($value) - 1) . '?';
                        $whereClause[] = "$key IN ($placeholders)";
                        $params = array_merge($params, $value);
                    } else {
                        $whereClause[] = "$key = ?";
                        $params[] = $value;
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
        }
        
        if ($orderBy) {
            $orderByClause = is_array($orderBy) ? implode(', ', $orderBy) : $orderBy;
            $sql .= " ORDER BY $orderByClause";
        } elseif ($this->timestamps) {
            $sql .= " ORDER BY created_at DESC";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll();
        return $this->hideFields($results);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $result = $stmt->fetch();
        return $result ? $this->hideFields($result) : null;
    }
    
    public function getById($id) {
        return $this->findById($id);
    }
    
    public function findBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        
        $result = $stmt->fetch();
        return $result ? $this->hideFields($result) : null;
    }
    
    public function findOne($conditions = []) {
        $results = $this->findAll($conditions, null, 1);
        return !empty($results) ? $results[0] : null;
    }
    
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':id', $id);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $whereClause[] = "$key IN ($placeholders)";
                    $params = array_merge($params, $value);
                } else {
                    $whereClause[] = "$key = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int) $result['total'];
    }
    
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $data = $this->findAll($conditions, $orderBy, $perPage, $offset);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    public function search($query, $fields = [], $conditions = [], $limit = 20) {
        if (empty($fields)) {
            return [];
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $params = [];
        
        // Condiciones de búsqueda
        $searchConditions = [];
        foreach ($fields as $field) {
            $searchConditions[] = "$field LIKE ?";
            $params[] = "%$query%";
        }
        $sql .= "(" . implode(' OR ', $searchConditions) . ")";
        
        // Condiciones adicionales
        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                $sql .= " AND $key = ?";
                $params[] = $value;
            }
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll();
        return $this->hideFields($results);
    }
    
    public function increment($id, $field, $amount = 1) {
        $sql = "UPDATE {$this->table} SET $field = $field + ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $id]);
    }
    
    public function decrement($id, $field, $amount = 1) {
        $sql = "UPDATE {$this->table} SET $field = $field - ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $id]);
    }
    
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    protected function hideFields($data) {
        if (empty($this->hidden)) {
            return $data;
        }
        
        if (isset($data[0])) {
            // Array de resultados
            foreach ($data as &$row) {
                foreach ($this->hidden as $field) {
                    unset($row[$field]);
                }
            }
        } else {
            // Un solo resultado
            foreach ($this->hidden as $field) {
                unset($data[$field]);
            }
        }
        
        return $data;
    }
    
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    public function commit() {
        return $this->db->commit();
    }
    
    public function rollback() {
        return $this->db->rollback();
    }
    
    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
?>
