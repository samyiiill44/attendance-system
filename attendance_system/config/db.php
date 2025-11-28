<?php
class Database {
    private $host = 'localhost';
    private $user = 'root';
    private $password = '';
    private $database = 'attendance_system';
    private $conn;

    public function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die(json_encode(['error' => 'Connection failed: ' . $this->conn->connect_error]));
        }

        $this->conn->set_charset("utf8mb4");
        return $this->conn;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

$database = new Database();
$conn = $database->connect();
?>