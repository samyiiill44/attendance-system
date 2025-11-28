<?php
class AttendanceRecordRepository {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function createBatch($session_id, $students) {
        if (empty($students)) return true;
        
        $values = [];
        $types = "";
        $params = [];

        foreach ($students as $student_id) {
            $values[] = "(?, ?, ?)";
            $params[] = $session_id;
            $params[] = $student_id;
            $params[] = 'absent';
            $types .= "iss";
        }

        $query = "INSERT INTO attendance_record (session_id, student_id, attendance_status) VALUES " . implode(",", $values);
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function create($session_id, $student_id) {
        $status = 'absent';
        $stmt = $this->conn->prepare("INSERT INTO attendance_record (session_id, student_id, attendance_status) VALUES (?, ?, ?)");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iis", $session_id, $student_id, $status);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function updateStatus($record_id, $status) {
        $stmt = $this->conn->prepare("UPDATE attendance_record SET attendance_status = ? WHERE record_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $status, $record_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function updateBatch($updates) {
        if (empty($updates)) return true;
        
        foreach ($updates as $record_id => $status) {
            $this->updateStatus($record_id, $status);
        }
        
        return true;
    }

    public function getBySession($session_id) {
        $stmt = $this->conn->prepare("SELECT * FROM attendance_record WHERE session_id = ?");
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = [];

        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();
        return $records;
    }

    public function recordBelongsToSession($record_id, $session_id) {
        $stmt = $this->conn->prepare("SELECT record_id FROM attendance_record WHERE record_id = ? AND session_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $record_id, $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function getRecordById($record_id) {
        $stmt = $this->conn->prepare("SELECT * FROM attendance_record WHERE record_id = ?");
        
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row;
    }
}
?>