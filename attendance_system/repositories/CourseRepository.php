<?php
class CourseRepository {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }


    public function courseExists($course_id) {
        $stmt = $this->conn->prepare("SELECT course_id FROM course WHERE course_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function groupExists($group_id) {
        $stmt = $this->conn->prepare("SELECT group_id FROM `group` WHERE group_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function professorTeachesCourseGroup($professor_id, $course_id, $group_id) {
        $stmt = $this->conn->prepare("
            SELECT professor_id FROM professor_course_group 
            WHERE professor_id = ? AND course_id = ? AND group_id = ?
        ");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iii", $professor_id, $course_id, $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function sessionBelongsToCourse($session_id, $course_id, $group_id) {
        $stmt = $this->conn->prepare("
            SELECT session_id FROM attendance_session 
            WHERE session_id = ? AND course_id = ? AND group_id = ?
        ");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iii", $session_id, $course_id, $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function getSessionStatus($session_id) {
        $stmt = $this->conn->prepare("SELECT status FROM attendance_session WHERE session_id = ?");
        
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? $row['status'] : null;
    }
}
?>