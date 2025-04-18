<?php
class Database {
    private $host = "localhost";
    private $port = "3306";
    private $db_name = "mecaiwbu_tasks";
    private $username = "mecaiwbu_user2";
    private $password = "4hLPF]8J622D";
    public $conn;
 
    public function getConnection() {
        $this->conn = null;
 
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . 
                ";port=" . $this->port . 
                ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
 
        return $this->conn;
    }
}