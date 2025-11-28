<?php
require_once __DIR__ . '/Response.php';

class Auth {
    public static function checkLogin() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            Response::unauthorized("You must be logged in");
        }
        
        return $_SESSION;
    }

    public static function checkRole($requiredRole) {
        $session = self::checkLogin();
        
        if ($session['role'] !== $requiredRole) {
            Response::forbidden("You don't have permission. Required role: " . $requiredRole);
        }
        
        return $session;
    }

    public static function checkRoles($requiredRoles) {
        $session = self::checkLogin();
        
        if (!in_array($session['role'], $requiredRoles)) {
            Response::forbidden("You don't have permission");
        }
        
        return $session;
    }
}
?>