<?php
require_once __DIR__ . '/../models/User.php';

class UserRepository {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function create(User $user) {
        $stmt = $this->conn->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ssss", $user->email, $user->password, $user->full_name, $user->role);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user = new User();
            $user->user_id = $row['user_id'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->full_name = $row['full_name'];
            $user->role = $row['role'];
            return $user;
        }

        return null;
    }

    public function findById($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
        
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user = new User();
            $user->user_id = $row['user_id'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->full_name = $row['full_name'];
            $user->role = $row['role'];
            return $user;
        }

        return null;
    }
}
?>