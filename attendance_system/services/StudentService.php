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
            SELECT 
                c.course_id, 
                c.name,
                p.program_id, 
                p.name as program_name,
                g.group_id,
                g.name as group_name,
                u.full_name as professor_name,
                COUNT(s.session_id) as total_sessions,
                SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as attended_sessions,
                SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count
            FROM course c
            JOIN program p ON c.program_id = p.program_id
            JOIN student_program_group spg ON p.program_id = spg.program_id
            JOIN `group` g ON spg.group_id = g.group_id
            LEFT JOIN professor_course_group pcg ON c.course_id = pcg.course_id AND g.group_id = pcg.group_id
            LEFT JOIN users u ON pcg.professor_id = u.user_id
            LEFT JOIN attendance_session s ON c.course_id = s.course_id AND g.group_id = s.group_id
            LEFT JOIN attendance_record ar ON s.session_id = ar.session_id AND ar.student_id = ?
            WHERE spg.student_id = ?
            GROUP BY c.course_id, g.group_id
            ORDER BY c.name
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("ii", $student_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];

        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $courses];
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


    public function getAttendanceDetails($student_id, $course_id) {
        $stmt = $this->studentRepo->getConnection()->prepare("
            SELECT 
                ar.record_id,
                DATE(s.session_date) as session_date,
                ar.attendance_status,
                j.status as justification_status
            FROM attendance_record ar
            JOIN attendance_session s ON ar.session_id = s.session_id
            LEFT JOIN justification j ON ar.record_id = j.record_id
            WHERE ar.student_id = ? AND s.course_id = ?
            ORDER BY s.session_date DESC
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = [];

        while ($row = $result->fetch_assoc()) {
            $details[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $details];
    }



public function submitJustification($student_id, $record_id, $reason, $file_path) {
    $conn = $this->studentRepo->getConnection();
    
    // Check if justification already exists for this record
    $checkStmt = $conn->prepare("SELECT justification_id FROM justification WHERE record_id = ?");
    $checkStmt->bind_param("i", $record_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        return ['success' => false, 'message' => 'Justification already submitted for this record', 'code' => 400];
    }
    $checkStmt->close();
    
    // Verify the record belongs to the student
    $verifyStmt = $conn->prepare("SELECT record_id FROM attendance_record WHERE record_id = ? AND student_id = ?");
    $verifyStmt->bind_param("ii", $record_id, $student_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        $verifyStmt->close();
        return ['success' => false, 'message' => 'Record not found', 'code' => 404];
    }
    $verifyStmt->close();
    
    // Insert justification
    $stmt = $conn->prepare("
        INSERT INTO justification (record_id, student_id, file_path, reason, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Query failed', 'code' => 500];
    }
    
    $stmt->bind_param("iiss", $record_id, $student_id, $file_path, $reason);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Justification submitted successfully', 'code' => 200];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to submit justification', 'code' => 500];
    }
}






}
?>