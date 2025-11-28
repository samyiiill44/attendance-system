<?php
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../utils/Validation.php';

class AuthService {
    private $userRepository;

    public function __construct($connection) {
        $this->userRepository = new UserRepository($connection);
    }

    public function register($email, $password, $full_name, $role) {
        $validator = new Validation();
        $isValid = $validator->validate([
            'email' => $email,
            'password' => $password,
            'full_name' => $full_name
        ], [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'full_name' => 'required'
        ]);

        if (!$isValid) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        $user = new User($email, $password, $full_name, $role);
        $user->hashPassword();

        if ($this->userRepository->create($user)) {
            return ['success' => true, 'message' => 'Account created successfully'];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    public function login($email, $password) {
        $validator = new Validation();
        $isValid = $validator->validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!$isValid) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'user not found'];
        }

        if (!$user->verifyPassword($password)) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        session_start();
        $_SESSION['user_id'] = $user->user_id;
        $_SESSION['email'] = $user->email;
        $_SESSION['role'] = $user->role;
        $_SESSION['full_name'] = $user->full_name;

        return ['success' => true, 'message' => 'Login successful', 'user' => $user->toArray()];
    }

    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }
}
?>