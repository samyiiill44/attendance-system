<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/StudentService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';

class StudentController {
    private $studentService;

    public function __construct() {
        global $conn;
        $this->studentService = new StudentService($conn);
    }

    public function getEnrolledCourses() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $result = $this->studentService->getEnrolledCourses($student_id);

        if ($result['success']) {
            Response::success($result['data'], "Enrolled courses", 200);
        } else {
            Response::error("Failed to retrieve courses", 400);
        }
    }

    public function getDashboardCourses() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $result = $this->studentService->getEnrolledCourses($student_id);

        if ($result['success']) {
            Response::success($result['data'], "Courses retrieved", 200);
        } else {
            Response::error("Failed to retrieve courses", 400);
        }
    }

    public function getStudentInfo() {
        $session = Auth::checkRole('student');
        Response::success([
            'student_id' => $session['user_id'],
            'full_name' => $session['full_name']
        ], "Student info retrieved", 200);
    }



    public function getAttendanceSummary() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;

        if (!$course_id) {
            Response::error("Course ID required", 400);
        }

        $result = $this->studentService->getAttendanceSummary($student_id, $course_id);

        if ($result['success']) {
            Response::success($result['data'], "Attendance summary retrieved", 200);
        } else {
            Response::error("Failed to retrieve attendance summary", 400);
        }
    }


    public function getCourseInfo() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;

        if (!$course_id) {
            Response::error("Course ID required", 400);
        }

        global $conn;
        $stmt = $conn->prepare("
            SELECT c.course_id, c.name, p.full_name as professor_name, g.name as group_name, pr.name as program_name
            FROM course c
            JOIN student_program_group spg ON c.program_id = spg.program_id
            JOIN `group` g ON spg.group_id = g.group_id
            JOIN program pr ON c.program_id = pr.program_id
            LEFT JOIN professor_course_group pcg ON c.course_id = pcg.course_id AND pcg.group_id = g.group_id
            LEFT JOIN users p ON pcg.professor_id = p.user_id
            WHERE c.course_id = ? AND spg.student_id = ?
            LIMIT 1
        ");
        
        $stmt->bind_param("ii", $course_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courseInfo = $result->fetch_assoc();
        $stmt->close();

        if ($courseInfo) {
            Response::success($courseInfo, "Course info retrieved", 200);
        } else {
            Response::error("Course not found", 404);
        }
    }


    public function getAttendanceDetails() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;

        if (!$course_id) {
            Response::error("Course ID required", 400);
            return;
        }

        $result = $this->studentService->getAttendanceDetails($student_id, $course_id);

        if ($result['success']) {
            Response::success($result['data'], "Attendance details retrieved", 200);
        } else {
            Response::error("Failed to retrieve attendance details", 400);
        }
    }
}
?>