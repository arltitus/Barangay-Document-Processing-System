<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            throw new Exception("Database connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function prepare($query) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $this->conn->error);
        }
        return $stmt;
    }
    
    public function execute($query, $params = [], $types = '') {
        $stmt = $this->prepare($query);
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        return $stmt;
    }
    
    public function query($query, $params = [], $types = '') {
        if (empty($params)) {
            $result = $this->conn->query($query);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            return $result;
        }
        
        $stmt = $this->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }
    
    public function fetchAll($query, $params = [], $types = '') {
        $result = $this->query($query, $params, $types);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function fetchOne($query, $params = [], $types = '') {
        $result = $this->query($query, $params, $types);
        return $result->fetch_assoc();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        $types = str_repeat('s', count($data));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
        $stmt = $this->prepare($query);
        $stmt->bind_param($types, ...array_values($data));
        
        if (!$stmt->execute()) {
            throw new Exception("Insert failed: " . $stmt->error);
        }
        
        $id = $stmt->insert_id;
        $stmt->close();
        
        return $id;
    }
    
    public function update($table, $data, $where, $whereParams = [], $whereTypes = '') {
        $set = implode(', ', array_map(function($col) {
            return "{$col} = ?";
        }, array_keys($data)));
        
        $query = "UPDATE {$table} SET {$set}";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        
        $stmt = $this->prepare($query);
        
        $allParams = array_values($data);
        if (!empty($whereParams)) {
            $allParams = array_merge($allParams, $whereParams);
        }
        
        $types = str_repeat('s', count($data)) . $whereTypes;
        $stmt->bind_param($types, ...$allParams);
        
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }
    
    public function delete($table, $where, $params = [], $types = '') {
        $query = "DELETE FROM {$table}";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        
        $stmt = $this->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Delete failed: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }
    
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    public function commit() {
        $this->conn->commit();
    }
    
    public function rollback() {
        $this->conn->rollback();
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    public function close() {
        $this->conn->close();
    }
}
