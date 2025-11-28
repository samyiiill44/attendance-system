<?php
class StudentRepository {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getStudentsByGroup($group_id) {
        $stmt = $this->conn->prepare("
            SELECT u.user_id, u.full_name, u.email 
            FROM users u
            JOIN student_program_group spg ON u.user_id = spg.student_id
            WHERE spg.group_id = ? AND u.role = 'student'
        ");
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return $students;
    }
}
?>