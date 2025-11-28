<?php
require_once __DIR__ . '/../repositories/JustificationRepository.php';
require_once __DIR__ . '/../repositories/AttendanceRecordRepository.php';
require_once __DIR__ . '/../repositories/CourseRepository.php';

class JustificationService {
    private $justificationRepo;
    private $recordRepo;
    private $courseRepo;

    public function __construct($connection) {
        $this->justificationRepo = new JustificationRepository($connection);
        $this->recordRepo = new AttendanceRecordRepository($connection);
        $this->courseRepo = new CourseRepository($connection);
    }

    public function submitJustification($student_id, $record_id, $file_path) {
        $record = $this->recordRepo->getRecordById($record_id);
        
        if (!$record) {
            return ['success' => false, 'message' => 'Attendance record not found', 'code' => 404];
        }

        if ($record['student_id'] != $student_id) {
            return ['success' => false, 'message' => 'You can only justify your own absences', 'code' => 403];
        }

        if ($record['attendance_status'] !== 'absent') {
            return ['success' => false, 'message' => 'Can only justify absent records', 'code' => 400];
        }

        $existingJustification = $this->justificationRepo->findByRecordId($record_id);
        if ($existingJustification) {
            return ['success' => false, 'message' => 'Justification already submitted for this absence', 'code' => 400];
        }

        if (!$this->justificationRepo->create($record_id, $student_id, $file_path)) {
            return ['success' => false, 'message' => 'Failed to submit justification', 'code' => 500];
        }

        return ['success' => true, 'message' => 'Justification submitted'];
    }

    public function approveJustification($justification_id, $approver_id) {
        if (!$this->justificationRepo->approve($justification_id, $approver_id)) {
            return ['success' => false, 'message' => 'Failed to approve justification', 'code' => 500];
        }

        $justification = $this->justificationRepo->findByRecordId($justification_id);
        if ($justification) {
            $this->recordRepo->updateStatus($justification['record_id'], 'justified');
        }

        return ['success' => true, 'message' => 'Justification approved'];
    }

    public function rejectJustification($justification_id, $approver_id) {
        if (!$this->justificationRepo->reject($justification_id, $approver_id)) {
            return ['success' => false, 'message' => 'Failed to reject justification', 'code' => 500];
        }

        return ['success' => true, 'message' => 'Justification rejected'];
    }

    public function getPendingJustifications() {
        $justifications = $this->justificationRepo->getPendingJustifications();
        return ['success' => true, 'data' => $justifications];
    }

    public function getStudentJustifications($student_id) {
        $justifications = $this->justificationRepo->getByStudent($student_id);
        return ['success' => true, 'data' => $justifications];
    }
}
?>