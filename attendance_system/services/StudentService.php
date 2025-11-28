<?php
require_once __DIR__ . '/../repositories/StudentRepository.php';
require_once __DIR__ . '/../repositories/CourseRepository.php';

class StudentService {
    private $studentRepo;
    private $courseRepo;

    public function __construct($connection) {
        $this->studentRepo = new StudentRepository($connection);
        $this->courseRepo = new CourseRepository($connection);
    }

    public function getEnrolledCourses($student_id) {
        $stmt = $this->studentRepo->getConnection()->prepare("
            SELECT DISTINCT c.course_id, c.name, p.program_id, p.name as program_name
            FROM course c
            JOIN program p ON c.program_id = p.program_id
            JOIN student_program_group spg ON p.program_id = spg.program_id
            WHERE spg.student_id = ?
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];

        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $courses];
    }

    public function getAttendanceForCourse($student_id, $course_id) {
        $stmt = $this->studentRepo->getConnection()->prepare("
            SELECT ar.record_id, ar.attendance_status, s.session_date, s.session_id
            FROM attendance_record ar
            JOIN attendance_session s ON ar.session_id = s.session_id
            JOIN course c ON s.course_id = c.course_id
            WHERE ar.student_id = ? AND c.course_id = ?
            ORDER BY s.session_date DESC
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = [];

        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $records];
    }

    public function getAttendanceSummary($student_id, $course_id) {
        $stmt = $this->studentRepo->getConnection()->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN attendance_status = 'justified' THEN 1 ELSE 0 END) as justified
            FROM attendance_record ar
            JOIN attendance_session s ON ar.session_id = s.session_id
            WHERE ar.student_id = ? AND s.course_id = ?
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        $stmt->close();

        return ['success' => true, 'data' => $summary];
    }
}
?>