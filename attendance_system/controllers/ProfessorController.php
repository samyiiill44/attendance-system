<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/AttendanceService.php';
require_once __DIR__ . '/../services/ProfessorService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';

class ProfessorController {
    private $attendanceService;
    private $professorService;

    public function __construct() {
        global $conn;
        $this->attendanceService = new AttendanceService($conn);
        $this->professorService = new ProfessorService($conn);
    }

    public function openSession() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['course_id']) || !isset($data['group_id']) || !isset($data['session_date'])) {
            Response::error("Missing required fields", 400);
        }

        $result = $this->attendanceService->openSession(
            $professor_id,
            $data['course_id'],
            $data['group_id'],
            $data['session_date']
        );

        if ($result['success']) {
            Response::success($result, $result['message'], 201);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function markAttendance() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['session_id']) || !isset($data['updates'])) {
            Response::error("Missing required fields", 400);
        }

        $result = $this->attendanceService->markAttendance($professor_id, $data['session_id'], $data['updates']);

        if ($result['success']) {
            Response::success(null, $result['message'], 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function closeSession() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['session_id'])) {
            Response::error("Session ID required", 400);
        }

        $result = $this->attendanceService->closeSession($professor_id, $data['session_id']);

        if ($result['success']) {
            Response::success(null, $result['message'], 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getSessionAttendance() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $session_id = $_GET['session_id'] ?? null;

        if (!$session_id) {
            Response::error("Session ID required", 400);
        }

        $result = $this->attendanceService->getSessionAttendance($professor_id, $session_id);
        
        if ($result['success']) {
            Response::success($result['data'], "Attendance records", 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getCourseSessions() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;
        $group_id = $_GET['group_id'] ?? null;

        if (!$course_id || !$group_id) {
            Response::error("Course ID and Group ID required", 400);
        }

        $result = $this->attendanceService->getSessionsByCourseAndGroup($professor_id, $course_id, $group_id);
        
        if ($result['success']) {
            Response::success($result['data'], "Sessions retrieved", 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getDashboardCourses() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $result = $this->professorService->getCoursesWithStats($professor_id);

        if ($result['success']) {
            Response::success($result['data'], "Courses retrieved", 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getCourseGroupSessions() {
        $session = Auth::checkRole('professor');

        $course_id = $_GET['course_id'] ?? null;
        $group_id = $_GET['group_id'] ?? null;

        if (!$course_id || !$group_id) {
            Response::error("Course ID and Group ID required", 400);
        }

        $result = $this->professorService->getSessionsForCourseGroup($course_id, $group_id);

        if ($result['success']) {
            Response::success($result['data'], "Sessions retrieved", 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getSessionDetails() {
        Auth::checkRole('professor');

        $session_id = $_GET['session_id'] ?? null;

        if (!$session_id) {
            Response::error("Session ID required", 400);
        }

        $sessionDetails = $this->professorService->getSessionDetails($session_id);
        
        if (!$sessionDetails) {
            Response::error("Session not found", 404);
        }

        $students = $this->professorService->getStudentsForMarking($session_id);

        Response::success([
            'session' => $sessionDetails,
            'students' => $students
        ], "Session details retrieved", 200);
    }

    public function getAttendanceSummary() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;
        $group_id = $_GET['group_id'] ?? null;

        if (!$course_id || !$group_id) {
            Response::error("Course ID and Group ID required", 400);
        }

        $result = $this->professorService->getAttendanceSummaryByCourseGroup($professor_id, $course_id, $group_id);

        if ($result['success']) {
            Response::success($result['data'], "Attendance summary retrieved", 200);
        } else {
            Response::error("Failed to retrieve attendance summary", 400);
        }
    }

    public function getGroupsForCourse() {
        $session = Auth::checkRole('professor');
        $professor_id = $session['user_id'];

        $course_id = $_GET['course_id'] ?? null;

        if (!$course_id) {
            Response::error("Course ID required", 400);
        }

        $result = $this->professorService->getGroupsForCourse($professor_id, $course_id);

        if ($result['success']) {
            Response::success($result['data'], "Groups retrieved", 200);
        } else {
            Response::error("Failed to retrieve groups", 400);
        }
    }
}
?>