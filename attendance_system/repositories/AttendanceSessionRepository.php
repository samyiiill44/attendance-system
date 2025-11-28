<?php
require_once __DIR__ . '/../models/AttendanceSession.php';

class AttendanceSessionRepository {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function create(AttendanceSession $session) {
        $stmt = $this->conn->prepare("INSERT INTO attendance_session (course_id, group_id, session_date, status) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iiss", $session->course_id, $session->group_id, $session->session_date, $session->status);
        $result = $stmt->execute();
        $session->session_id = $this->conn->insert_id;
        $stmt->close();

        return $result;
    }

    public function findById($session_id) {
        $stmt = $this->conn->prepare("SELECT * FROM attendance_session WHERE session_id = ?");
        
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            return $this->mapToObject($row);
        }

        return null;
    }

    public function getSessionsByCourseAndGroup($course_id, $group_id) {
        $stmt = $this->conn->prepare("SELECT * FROM attendance_session WHERE course_id = ? AND group_id = ? ORDER BY session_date DESC");
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ii", $course_id, $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessions = [];

        while ($row = $result->fetch_assoc()) {
            $sessions[] = $this->mapToObject($row);
        }

        $stmt->close();
        return $sessions;
    }

    public function updateStatus($session_id, $status) {
        $stmt = $this->conn->prepare("UPDATE attendance_session SET status = ? WHERE session_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $status, $session_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    private function mapToObject($row) {
        $session = new AttendanceSession();
        $session->session_id = $row['session_id'];
        $session->course_id = $row['course_id'];
        $session->group_id = $row['group_id'];
        $session->session_date = $row['session_date'];
        $session->status = $row['status'];
        return $session;
    }
}
?>