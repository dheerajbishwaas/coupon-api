<?php

require_once __DIR__ . '/../config/database.php';

class BaseModel {
    protected $table;
    protected $conn;

    public function __construct($table, $pdo = null) {
        $this->table = $table;
        if ($pdo) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    // Common insert method
    public function insert(array $data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $stmt = $this->conn->prepare($sql);

            $success = $stmt->execute(array_values($data));
            
            if (!$success) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Database error: " . ($errorInfo[2] ?? 'Unknown error'));
            }

            $insertedId = $this->conn->lastInsertId();

            return $insertedId;
        } catch (PDOException $e) {
            error_log("SQL Error: {$e->getMessage()} | Query: {$sql} | Data: " . json_encode($data));
            throw $e;
        }
    }

    // Common update method
    public function update($where, $data) {
        if (!is_array($where) || !is_array($data)) {
            throw new InvalidArgumentException("Both 'where' and 'data' must be arrays.");
        }
    
        // Build SET clause
        $setClause = '';
        foreach ($data as $key => $value) {
            $setClause .= "$key = :set_$key, ";
        }
        $setClause = rtrim($setClause, ', ');
    
        // Build WHERE clause
        $whereClause = '';
        foreach ($where as $key => $value) {
            $whereClause .= "$key = :where_$key AND ";
        }
        $whereClause = rtrim($whereClause, ' AND ');
    
        // Prepare final query
        $query = "UPDATE $this->table SET $setClause WHERE $whereClause";
        $stmt = $this->conn->prepare($query);
    
        // Bind SET values
        foreach ($data as $key => $value) {
            $stmt->bindValue(':set_' . $key, is_array($value) ? json_encode($value) : $value);
        }
    
        // Bind WHERE values
        foreach ($where as $key => $value) {
            $stmt->bindValue(':where_' . $key, $value);
        }
    
        return $stmt->execute();
    }
    

    public function exists($conditions) {
        $where = '';
        $params = [];
        foreach ($conditions as $key => $value) {
            $where .= "$key = :$key AND ";
            $params[":$key"] = $value;
        }
        $where = rtrim($where, 'AND ');
    
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE $where";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Common delete method
    public function delete($id) {
        $query = "DELETE FROM $this->table WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id);
        
        return $stmt->execute();
    }

    public function deleteBy($conditions) {
        if (empty($conditions)) {
            throw new Exception('Conditions for deletion must be provided.');
        }

        // Build the WHERE clause based on conditions
        $where = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "DELETE FROM {$this->table} WHERE $whereSql";

        // Prepare and execute the query
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception('Error executing delete query: ' . $e->getMessage());
        }
    }

    // Common get all method
    public function getAll($where = '', $params = []) {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($where)) {
            $sql .= " $where";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Common get by ID method
    public function getById($id) {
        $query = "SELECT * FROM $this->table WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function updateByCouponId($couponId, $data) {
        $setClause = '';
        foreach ($data as $key => $value) {
            $setClause .= "$key = :$key, ";
        }
        $setClause = rtrim($setClause, ', ');
    
        $query = "UPDATE {$this->table} SET $setClause WHERE coupon_id = :coupon_id";
        $stmt = $this->conn->prepare($query);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':coupon_id', $couponId);
        return $stmt->execute();
    }
    
    public function deleteByCouponId($couponId) {
        $query = "DELETE FROM {$this->table} WHERE coupon_id = :coupon_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':coupon_id', $couponId);
        return $stmt->execute();
    }

      // This is the getBy method
    public function getBy($conditions) {
        if (empty($conditions)) {
            throw new Exception('Conditions for fetching records must be provided.');
        }

        // Build the WHERE clause based on conditions
        $where = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table} WHERE $whereSql";

        // Prepare and execute the query
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Returns an array of results
        } catch (PDOException $e) {
            throw new Exception('Error executing get query: ' . $e->getMessage());
        }
    }

    // This is the getOneBy method
    public function getOneBy($conditions) {
        if (empty($conditions)) {
            throw new Exception('Conditions for fetching record must be provided.');
        }

        // Build the WHERE clause based on conditions
        $where = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $where[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table} WHERE $whereSql LIMIT 1";

        // Prepare and execute the query
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC); // Returns a single row
        } catch (PDOException $e) {
            throw new Exception('Error executing getOneBy query: ' . $e->getMessage());
        }
    }

    public function getWhere($conditions) {
        $whereParts = [];
        $params = [];
        $paramCount = 0;
        
        foreach ($conditions as $condition) {
            if (is_array($condition) && count($condition) === 3) {
                $paramName = ':param' . $paramCount++;
                $whereParts[] = "{$condition[0]} {$condition[1]} $paramName";
                $params[$paramName] = $condition[2];
            } else {
                $paramName = ':param' . $paramCount++;
                $whereParts[] = "$condition[0] = $paramName";
                $params[$paramName] = $condition[1];
            }
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereParts) . " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
   
    public function getAllWhere($conditions) {
        $whereParts = [];
        $params = [];
        $paramCount = 0;
        
        foreach ($conditions as $condition) {
            if (is_array($condition) && count($condition) === 3) {
                // Handle [column, operator, value] format
                $paramName = ':param' . $paramCount++;
                $whereParts[] = "{$condition[0]} {$condition[1]} $paramName";
                $params[$paramName] = $condition[2];
            } else {
                // Handle simple key-value format (backward compatibility)
                $paramName = ':param' . $paramCount++;
                $whereParts[] = "$condition[0] = $paramName";
                $params[$paramName] = $condition[1];
            }
        }
        
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
}