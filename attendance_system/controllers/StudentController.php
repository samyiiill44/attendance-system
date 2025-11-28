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
            Response::error($result['message'], $result['code']);
        }
    }

    public function getAttendance() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;

        if (!$course_id) {
            Response::error("Course ID required", 400);
        }

        $result = $this->studentService->getAttendanceForCourse($student_id, $course_id);

        if ($result['success']) {
            Response::success($result['data'], "Attendance records", 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
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
            Response::success($result['data'], "Attendance summary", 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }
}
?>