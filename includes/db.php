<?php
require_once 'config.php';

class Database {
    private $connection;
    
    public function __construct() {
        $this->connection = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        // Enable SSL
        $this->connection->ssl_set(
            null,
            null,
            null,
            null,
            null
        );
    }
    
    public function query($sql) {
        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Query failed: " . $this->connection->error);
        }
        return $result;
    }
    
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    public function close() {
        $this->connection->close();
    }
}
?>
