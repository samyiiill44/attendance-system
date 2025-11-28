@echo off
mkdir config
mkdir controllers
mkdir services
mkdir repositories
mkdir models
mkdir utils
mkdir uploads
mkdir uploads\justifications
mkdir routes
type nul > config\db.php
type nul > controllers\AuthController.php
type nul > controllers\ProfessorController.php
type nul > controllers\StudentController.php
type nul > controllers\AdminController.php
type nul > controllers\JustificationController.php
type nul > services\AuthService.php
type nul > services\AttendanceService.php
type nul > services\JustificationService.php
type nul > services\StudentService.php
type nul > services\AdminService.php
type nul > repositories\UserRepository.php
type nul > repositories\AttendanceSessionRepository.php
type nul > repositories\AttendanceRecordRepository.php
type nul > repositories\JustificationRepository.php
type nul > repositories\StudentRepository.php
type nul > repositories\CourseRepository.php
type nul > models\User.php
type nul > models\AttendanceSession.php
type nul > models\AttendanceRecord.php
type nul > models\Justification.php
type nul > models\Course.php
type nul > utils\Response.php
type nul > utils\Validation.php
type nul > utils\ErrorHandler.php
type nul > routes\api.php
type nul > index.php
echo ✅ All folders and files created successfully!
pause