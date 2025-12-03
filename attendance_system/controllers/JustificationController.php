<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/StudentService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';

class JustificationController {
    private $studentService;
    private $uploadDir = __DIR__ . '/../../uploads/justifications/';
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    private $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    private $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];

    public function __construct() {
        global $conn;
        $this->studentService = new StudentService($conn);
    }

    public function submitJustification() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $record_id = $_POST['record_id'] ?? null;
        $reason = $_POST['reason'] ?? null;
        $file = $_FILES['file'] ?? null;

        if (!$record_id || !$reason) {
            Response::error("Record ID and reason are required", 400);
            return;
        }

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            Response::error("File is required", 400);
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error("File upload error", 400);
            return;
        }

        if ($file['size'] > $this->maxFileSize) {
            Response::error("File size exceeds 5MB limit", 400);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimes)) {
            Response::error("File type not allowed. Allowed: PDF, Images, Word documents", 400);
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions)) {
            Response::error("Invalid file extension", 400);
            return;
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $fileName = $student_id . '_' . $record_id . '_' . time() . '.' . $ext;
        $filePath = $this->uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            Response::error("Failed to save file", 500);
            return;
        }

        $relativeFilePath = 'uploads/justifications/' . $fileName;
        $result = $this->studentService->submitJustification($student_id, $record_id, $reason, $relativeFilePath);

        if ($result['success']) {
            Response::success(null, $result['message'], $result['code']);
        } else {
            unlink($filePath);
            Response::error($result['message'], $result['code']);
        }
    }
}
?>