<?php
class ProfessorService {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function getCoursesWithStats($professor_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.course_id,
                c.name as course_name,
                g.group_id,
                g.name as group_name,
                COUNT(DISTINCT spg.student_id) as student_count,
                COUNT(DISTINCT s.session_id) as total_sessions,
                MAX(CASE WHEN s.session_date > NOW() THEN s.session_date END) as next_session_date
            FROM professor_course_group pcg
            JOIN course c ON pcg.course_id = c.course_id
            JOIN `group` g ON pcg.group_id = g.group_id
            LEFT JOIN student_program_group spg ON g.group_id = spg.group_id
            LEFT JOIN attendance_session s ON c.course_id = s.course_id AND g.group_id = s.group_id
            WHERE pcg.professor_id = ?
            GROUP BY c.course_id, g.group_id
            ORDER BY c.name ASC
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("i", $professor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];

        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $courses];
    }

    public function getSessionsForCourseGroup($course_id, $group_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                session_id,
                session_date,
                status,
                (SELECT COUNT(*) FROM attendance_record WHERE session_id = attendance_session.session_id) as marked_count,
                (SELECT COUNT(*) FROM attendance_record ar 
                 JOIN student_program_group spg ON ar.student_id = spg.student_id 
                 WHERE ar.session_id = attendance_session.session_id AND spg.group_id = ?) as total_students
            FROM attendance_session
            WHERE course_id = ? AND group_id = ?
            ORDER BY session_date DESC
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("iii", $group_id, $course_id, $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessions = [];

        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $sessions];
    }

    public function getSessionDetails($session_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                s.session_id,
                s.session_date,
                s.status,
                c.name as course_name,
                g.name as group_name
            FROM attendance_session s
            JOIN course c ON s.course_id = c.course_id
            JOIN `group` g ON s.group_id = g.group_id
            WHERE s.session_id = ?
        ");
        
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        return $session;
    }

    public function getStudentsForMarking($session_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                ar.record_id,
                ar.student_id,
                ar.attendance_status,
                u.full_name
            FROM attendance_record ar
            JOIN users u ON ar.student_id = u.user_id
            WHERE ar.session_id = ?
            ORDER BY u.full_name ASC
        ");
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return $students;
    }

    public function getAttendanceSummaryByCourseGroup($professor_id, $course_id, $group_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.user_id,
                u.full_name,
                COUNT(ar.record_id) as total_sessions,
                SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN ar.attendance_status = 'justified' THEN 1 ELSE 0 END) as justified_count,
                ROUND(SUM(CASE WHEN ar.attendance_status IN ('present', 'justified') THEN 1 ELSE 0 END) * 100.0 / COUNT(ar.record_id), 1) as attendance_rate
            FROM users u
            JOIN student_program_group spg ON u.user_id = spg.student_id
            LEFT JOIN attendance_record ar ON u.user_id = ar.student_id
            LEFT JOIN attendance_session s ON ar.session_id = s.session_id
            WHERE spg.group_id = ? AND u.role = 'student' AND s.course_id = ?
            GROUP BY u.user_id, u.full_name
            ORDER BY u.full_name ASC
        ");
        
        if (!$stmt) {
            return ['success' => false, 'data' => []];
        }

        $stmt->bind_param("ii", $group_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $students];
    }

    public function getGroupsForCourse($professor_id, $course_id) {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT g.group_id, g.name, c.name as course_name
            FROM `group` g
            JOIN professor_course_group pcg ON g.group_id = pcg.group_id
            JOIN course c ON pcg.course_id = c.course_id
            WHERE pcg.professor_id = ? AND pcg.course_id = ?
            ORDER BY g.name ASC
        ");
        
        if (!$stmt) {
            return ['success' => false, 'data' => []];
        }

        $stmt->bind_param("ii", $professor_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = [];

        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $groups];
    }
}
?>