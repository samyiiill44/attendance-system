<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/JustificationService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';

class JustificationController {
    private $justificationService;

    public function __construct() {
        global $conn;
        $this->justificationService = new JustificationService($conn);
    }

    public function submitJustification() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['record_id']) || !isset($data['file_path'])) {
            Response::error("Record ID and file path required", 400);
        }

        $result = $this->justificationService->submitJustification(
            $student_id,
            $data['record_id'],
            $data['file_path']
        );

        if ($result['success']) {
            Response::success(null, $result['message'], 201);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function approveJustification() {
        $session = Auth::checkRoles(['professor', 'admin']);
        $approver_id = $session['user_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['justification_id'])) {
            Response::error("Justification ID required", 400);
        }

        $result = $this->justificationService->approveJustification(
            $data['justification_id'],
            $approver_id
        );

        if ($result['success']) {
            Response::success(null, $result['message'], 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function rejectJustification() {
        $session = Auth::checkRoles(['professor', 'admin']);
        $approver_id = $session['user_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['justification_id'])) {
            Response::error("Justification ID required", 400);
        }

        $result = $this->justificationService->rejectJustification(
            $data['justification_id'],
            $approver_id
        );

        if ($result['success']) {
            Response::success(null, $result['message'], 200);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }

    public function getPendingJustifications() {
        Auth::checkRoles(['professor', 'admin']);

        $result = $this->justificationService->getPendingJustifications();
        Response::success($result['data'], "Pending justifications", 200);
    }

    public function getMyJustifications() {
        $session = Auth::checkRole('student');
        $student_id = $session['user_id'];

        $result = $this->justificationService->getStudentJustifications($student_id);
        Response::success($result['data'], "Your justifications", 200);
    }
}
?>