<?php
require_once __DIR__ . '/../repositories/AttendanceSessionRepository.php';
require_once __DIR__ . '/../repositories/StudentRepository.php';
require_once __DIR__ . '/../repositories/AttendanceRecordRepository.php';
require_once __DIR__ . '/../repositories/CourseRepository.php';
require_once __DIR__ . '/../models/AttendanceSession.php';

class AttendanceService {
    private $sessionRepo;
    private $studentRepo;
    private $recordRepo;
    private $courseRepo;

    public function __construct($connection) {
        $this->sessionRepo = new AttendanceSessionRepository($connection);
        $this->studentRepo = new StudentRepository($connection);
        $this->recordRepo = new AttendanceRecordRepository($connection);
        $this->courseRepo = new CourseRepository($connection);
    }

    public function openSession($professor_id, $course_id, $group_id, $session_date) {
        if (!$this->courseRepo->courseExists($course_id)) {
            return ['success' => false, 'message' => 'Course does not exist', 'code' => 404];
        }

        if (!$this->courseRepo->groupExists($group_id)) {
            return ['success' => false, 'message' => 'Group does not exist', 'code' => 404];
        }

        if (!$this->courseRepo->professorTeachesCourseGroup($professor_id, $course_id, $group_id)) {
            return ['success' => false, 'message' => 'You are not assigned to teach this course/group', 'code' => 403];
        }

        if (strtotime($session_date) < strtotime(date('Y-m-d H:i:s'))) {
            return ['success' => false, 'message' => 'Session date cannot be in the past', 'code' => 400];
        }

        $session = new AttendanceSession($course_id, $group_id, $session_date);
        
        if (!$this->sessionRepo->create($session)) {
            return ['success' => false, 'message' => 'Failed to create session', 'code' => 500];
        }

        $students = $this->studentRepo->getStudentsByGroup($group_id);
        $student_ids = array_column($students, 'user_id');

        $this->recordRepo->createBatch($session->session_id, $student_ids);

        return ['success' => true, 'message' => 'Session opened', 'session_id' => $session->session_id];
    }

    public function markAttendance($professor_id, $session_id, $updates) {
        $session = $this->sessionRepo->findById($session_id);
        if (!$session) {
            return ['success' => false, 'message' => 'Session not found', 'code' => 404];
        }

        if ($session->status !== 'open') {
            return ['success' => false, 'message' => 'Session is not open', 'code' => 400];
        }

        if (!$this->courseRepo->professorTeachesCourseGroup($professor_id, $session->course_id, $session->group_id)) {
            return ['success' => false, 'message' => 'You are not authorized to mark attendance for this session', 'code' => 403];
        }

        $validStatuses = ['present', 'absent'];
        foreach ($updates as $record_id => $status) {
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid attendance status', 'code' => 400];
            }
        }

        $this->recordRepo->updateBatch($updates);
        return ['success' => true, 'message' => 'Attendance marked'];
    }

    public function closeSession($professor_id, $session_id) {
        $session = $this->sessionRepo->findById($session_id);
        if (!$session) {
            return ['success' => false, 'message' => 'Session not found', 'code' => 404];
        }

        if ($session->status !== 'open') {
            return ['success' => false, 'message' => 'Session is already closed', 'code' => 400];
        }

        if (!$this->courseRepo->professorTeachesCourseGroup($professor_id, $session->course_id, $session->group_id)) {
            return ['success' => false, 'message' => 'You are not authorized to close this session', 'code' => 403];
        }

        if (!$this->sessionRepo->updateStatus($session_id, 'closed')) {
            return ['success' => false, 'message' => 'Failed to close session', 'code' => 500];
        }

        return ['success' => true, 'message' => 'Session closed'];
    }

    public function getSessionAttendance($professor_id, $session_id) {
        $session = $this->sessionRepo->findById($session_id);
        if (!$session) {
            return ['success' => false, 'message' => 'Session not found', 'code' => 404];
        }

        if (!$this->courseRepo->professorTeachesCourseGroup($professor_id, $session->course_id, $session->group_id)) {
            return ['success' => false, 'message' => 'You are not authorized to view this session', 'code' => 403];
        }

        $records = $this->recordRepo->getBySession($session_id);
        return ['success' => true, 'data' => $records];
    }

    public function getSessionsByCourseAndGroup($professor_id, $course_id, $group_id) {
        if (!$this->courseRepo->professorTeachesCourseGroup($professor_id, $course_id, $group_id)) {
            return ['success' => false, 'message' => 'You are not assigned to this course/group', 'code' => 403];
        }

        $sessions = $this->sessionRepo->getSessionsByCourseAndGroup($course_id, $group_id);
        return ['success' => true, 'data' => $sessions];
    }
}
?>