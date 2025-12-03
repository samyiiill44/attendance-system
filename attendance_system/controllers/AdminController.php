<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';

class AdminController {
    private $adminService;

    public function __construct() {
        global $conn;
        $this->adminService = new AdminService($conn);
    }

    public function addStudent() {
        $session = Auth::checkRole('admin');
        $admin_id = $session['user_id'];

        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $full_name = $_POST['full_name'] ?? null;
        $program_id = $_POST['program_id'] ?? null;
        $group_id = $_POST['group_id'] ?? null;

        if (!$email || !$password || !$full_name || !$program_id || !$group_id) {
            Response::error("All fields are required", 400);
            return;
        }

        if (strlen($password) < 6) {
            Response::error("Password must be at least 6 characters", 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error("Invalid email format", 400);
            return;
        }

        $result = $this->adminService->addStudent($email, $password, $full_name, $program_id, $group_id);

        if ($result['success']) {
            Response::success($result['data'], $result['message'], $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getStudents() {
        Auth::checkRole('admin');

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = $_GET['search'] ?? '';
        $program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
        $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;

        $result = $this->adminService->getAllStudents($limit, $offset, $search, $program_id, $group_id);

        if ($result['success']) {
            Response::success($result['data'], 'Students retrieved', $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getPendingJustifications() {
        Auth::checkRole('admin');

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $result = $this->adminService->getPendingJustifications($limit, $offset);

        if ($result['success']) {
            Response::success($result['data'], 'Justifications retrieved', $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function approveJustification() {
        $session = Auth::checkRole('admin');
        $admin_id = $session['user_id'];

        $justification_id = $_POST['justification_id'] ?? null;

        if (!$justification_id) {
            Response::error("Justification ID is required", 400);
            return;
        }

        $result = $this->adminService->approveJustification($justification_id, $admin_id);

        if ($result['success']) {
            Response::success(null, $result['message'], $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function rejectJustification() {
        $session = Auth::checkRole('admin');
        $admin_id = $session['user_id'];

        $justification_id = $_POST['justification_id'] ?? null;

        if (!$justification_id) {
            Response::error("Justification ID is required", 400);
            return;
        }

        $result = $this->adminService->rejectJustification($justification_id, $admin_id);

        if ($result['success']) {
            Response::success(null, $result['message'], $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getPrograms() {
        Auth::checkRole('admin');

        $result = $this->adminService->getPrograms();

        if ($result['success']) {
            Response::success($result['data'], 'Programs retrieved', $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getGroups() {
        Auth::checkRole('admin');

        $program_id = $_GET['program_id'] ?? null;

        if (!$program_id) {
            Response::error("Program ID is required", 400);
            return;
        }

        $result = $this->adminService->getGroupsByProgram($program_id);

        if ($result['success']) {
            Response::success($result['data'], 'Groups retrieved', $result['code']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }
}
?>