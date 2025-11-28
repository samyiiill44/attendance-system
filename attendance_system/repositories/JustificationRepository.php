<?php
class JustificationRepository {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function create($record_id, $student_id, $file_path) {
        $status = 'pending';
        $submitted_date = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("
            INSERT INTO justification (record_id, student_id, file_path, status, submitted_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iisss", $record_id, $student_id, $file_path, $status, $submitted_date);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function findByRecordId($record_id) {
        $stmt = $this->conn->prepare("SELECT * FROM justification WHERE record_id = ?");
        
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

    public function approve($justification_id, $approved_by) {
        $status = 'approved';
        $approval_date = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("
            UPDATE justification 
            SET status = ?, approved_by = ?, approval_date = ?
            WHERE justification_id = ?
        ");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sisi", $status, $approved_by, $approval_date, $justification_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function reject($justification_id, $approved_by) {
        $status = 'rejected';
        $approval_date = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("
            UPDATE justification 
            SET status = ?, approved_by = ?, approval_date = ?
            WHERE justification_id = ?
        ");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sisi", $status, $approved_by, $approval_date, $justification_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function getPendingJustifications() {
        $stmt = $this->conn->prepare("
            SELECT j.*, u.full_name as student_name, c.name as course_name, ar.attendance_status
            FROM justification j
            JOIN users u ON j.student_id = u.user_id
            JOIN attendance_record ar ON j.record_id = ar.record_id
            JOIN attendance_session s ON ar.session_id = s.session_id
            JOIN course c ON s.course_id = c.course_id
            WHERE j.status = 'pending'
            ORDER BY j.submitted_date ASC
        ");
        
        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $justifications = [];

        while ($row = $result->fetch_assoc()) {
            $justifications[] = $row;
        }

        $stmt->close();
        return $justifications;
    }

    public function getByStudent($student_id) {
        $stmt = $this->conn->prepare("
            SELECT j.*, c.name as course_name, s.session_date
            FROM justification j
            JOIN attendance_record ar ON j.record_id = ar.record_id
            JOIN attendance_session s ON ar.session_id = s.session_id
            JOIN course c ON s.course_id = c.course_id
            WHERE j.student_id = ?
            ORDER BY j.submitted_date DESC
        ");
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $justifications = [];

        while ($row = $result->fetch_assoc()) {
            $justifications[] = $row;
        }

        $stmt->close();
        return $justifications;
    }
}
?>