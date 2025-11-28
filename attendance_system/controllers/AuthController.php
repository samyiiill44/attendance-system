<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';

class AuthController {
    private $authService;

    public function __construct() {
        global $conn;
        $this->authService = new AuthService($conn);
    }

    public function register() {
        Auth::checkRole('admin');

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['full_name']) || !isset($data['role'])) {
            Response::error("Missing required fields", 400);
        }

        $result = $this->authService->register(
            $data['email'],
            $data['password'],
            $data['full_name'],
            $data['role']
        );

        if ($result['success']) {
            Response::success(null, $result['message'], 201);
        } else {
            if (isset($result['errors'])) {
                Response::validation($result['errors']);
            } else {
                Response::error($result['message'], 400);
            }
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error("Email and password required", 400);
        }

        $result = $this->authService->login($data['email'], $data['password']);

        if ($result['success']) {
            Response::success($result['user'], $result['message'], 200);
        } else {
            if (isset($result['errors'])) {
                Response::validation($result['errors']);
            } else {
                Response::error($result['message'], 401);
            }
        }
    }

    public function logout() {
        $result = $this->authService->logout();
        Response::success(null, $result['message'], 200);
    }
}
?>