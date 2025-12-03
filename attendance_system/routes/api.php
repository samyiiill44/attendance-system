<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProfessorController.php';
require_once __DIR__ . '/../controllers/StudentController.php';
require_once __DIR__ . '/../controllers/JustificationController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$adminController = new AdminController();
$request_method = $_SERVER['REQUEST_METHOD'];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_path = str_replace('/attendance_system', '', $request_path);

$authController = new AuthController();
$professorController = new ProfessorController();
$studentController = new StudentController();
$justificationController = new JustificationController();

if ($request_path == '/api/auth/login' && $request_method == 'POST') {
    $authController->login();
} elseif ($request_path == '/api/auth/register' && $request_method == 'POST') {
    $authController->register();
} elseif ($request_path == '/api/auth/logout' && $request_method == 'POST') {
    $authController->logout();
} elseif ($request_path == '/api/professor/open-session' && $request_method == 'POST') {
    $professorController->openSession();
} elseif ($request_path == '/api/professor/mark-attendance' && $request_method == 'POST') {
    $professorController->markAttendance();
} elseif ($request_path == '/api/professor/session-details' && $request_method == 'GET') {
    $professorController->getSessionDetails();
} elseif ($request_path == '/api/professor/attendance-summary' && $request_method == 'GET') {
    $professorController->getAttendanceSummary();
} elseif ($request_path == '/api/professor/groups-for-course' && $request_method == 'GET') {
    $professorController->getGroupsForCourse();
} elseif ($request_path == '/api/professor/close-session' && $request_method == 'POST') {
    $professorController->closeSession();
} elseif ($request_path == '/api/professor/get-attendance' && $request_method == 'GET') {
    $professorController->getSessionAttendance();
} elseif ($request_path == '/api/professor/get-sessions' && $request_method == 'GET') {
    $professorController->getCourseSessions();
} elseif ($request_path == '/api/professor/dashboard-courses' && $request_method == 'GET') {
    $professorController->getDashboardCourses();
} elseif ($request_path == '/api/professor/course-group-sessions' && $request_method == 'GET') {
    $professorController->getCourseGroupSessions();
} elseif ($request_path == '/api/professor/mark-attendance' && $request_method == 'POST') {
    $professorController->markAttendance();
} elseif ($request_path == '/api/student/get-courses' && $request_method == 'GET') {
    $studentController->getEnrolledCourses();
} elseif ($request_path == '/api/student/dashboard-courses' && $request_method == 'GET') {
    $studentController->getDashboardCourses();
} elseif ($request_path == '/api/student/info' && $request_method == 'GET') {
    $studentController->getStudentInfo();
} 
 elseif ($request_path == '/api/student/get-summary' && $request_method == 'GET') {
    $studentController->getAttendanceSummary();
} elseif ($request_path == '/api/student/get-attendance-details' && $request_method == 'GET') {
    $studentController->getAttendanceDetails();
}
 elseif ($request_path == '/api/student/course-info' && $request_method == 'GET') {
    $studentController->getCourseInfo();
} elseif ($request_path == '/api/justification/submit' && $request_method == 'POST') {
    $justificationController->submitJustification();
}
 elseif ($request_path == '/api/admin/add-student' && $request_method == 'POST') {
    $adminController->addStudent();
} elseif ($request_path == '/api/admin/get-students' && $request_method == 'GET') {
    $adminController->getStudents();
} elseif ($request_path == '/api/admin/pending-justifications' && $request_method == 'GET') {
    $adminController->getPendingJustifications();
} elseif ($request_path == '/api/admin/approve-justification' && $request_method == 'POST') {
    $adminController->approveJustification();
} elseif ($request_path == '/api/admin/reject-justification' && $request_method == 'POST') {
    $adminController->rejectJustification();
} elseif ($request_path == '/api/admin/programs' && $request_method == 'GET') {
    $adminController->getPrograms();
} elseif ($request_path == '/api/admin/groups' && $request_method == 'GET') {
    $adminController->getGroups();
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}
?>