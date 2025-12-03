<?php
require_once __DIR__ . '/../repositories/StudentRepository.php';

class AdminService {
    private $connection;
    private $studentRepo;

    public function __construct($connection) {
        $this->connection = $connection;
        $this->studentRepo = new StudentRepository($connection);
    }

    public function addStudent($email, $password, $full_name, $program_id, $group_id) {
        // Check if email exists
        $checkStmt = $this->connection->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Email already exists', 'code' => 400];
        }
        $checkStmt->close();

        // Verify program and group exist
        $verifyStmt = $this->connection->prepare("
            SELECT g.group_id FROM `group` g 
            WHERE g.group_id = ? AND g.program_id = ?
        ");
        $verifyStmt->bind_param("ii", $group_id, $program_id);
        $verifyStmt->execute();
        
        if ($verifyStmt->get_result()->num_rows === 0) {
            $verifyStmt->close();
            return ['success' => false, 'message' => 'Invalid program or group', 'code' => 400];
        }
        $verifyStmt->close();

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $role = 'student';

        // Begin transaction
        $this->connection->begin_transaction();

        try {
            // Insert user
            $userStmt = $this->connection->prepare("
                INSERT INTO users (email, password, full_name, role) 
                VALUES (?, ?, ?, ?)
            ");
            $userStmt->bind_param("ssss", $email, $hashedPassword, $full_name, $role);
            
            if (!$userStmt->execute()) {
                throw new Exception("Failed to create user");
            }
            
            $student_id = $this->connection->insert_id;
            $userStmt->close();

            // Enroll student in program/group
            $enrollStmt = $this->connection->prepare("
                INSERT INTO student_program_group (student_id, program_id, group_id) 
                VALUES (?, ?, ?)
            ");
            $enrollStmt->bind_param("iii", $student_id, $program_id, $group_id);
            
            if (!$enrollStmt->execute()) {
                throw new Exception("Failed to enroll student");
            }
            $enrollStmt->close();

            $this->connection->commit();
            
            return [
                'success' => true, 
                'message' => 'Student added successfully',
                'data' => ['student_id' => $student_id],
                'code' => 201
            ];
        } catch (Exception $e) {
            $this->connection->rollback();
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }

    public function getAllStudents($limit = 50, $offset = 0, $search = '', $program_id = null, $group_id = null) {
        $query = "
            SELECT 
                u.user_id,
                u.email,
                u.full_name,
                p.program_id,
                p.name as program_name,
                g.group_id,
                g.name as group_name,
                COUNT(DISTINCT s.session_id) as total_sessions,
                SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN ar.attendance_status = 'justified' THEN 1 ELSE 0 END) as justified_count
            FROM users u
            LEFT JOIN student_program_group spg ON u.user_id = spg.student_id
            LEFT JOIN program p ON spg.program_id = p.program_id
            LEFT JOIN `group` g ON spg.group_id = g.group_id
            LEFT JOIN attendance_session s ON p.program_id = s.course_id
            LEFT JOIN attendance_record ar ON s.session_id = ar.session_id AND ar.student_id = u.user_id
            WHERE u.role = 'student'
        ";

        $params = [];
        $types = '';

        if (!empty($search)) {
            $query .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        if ($program_id) {
            $query .= " AND p.program_id = ?";
            $params[] = $program_id;
            $types .= "i";
        }

        if ($group_id) {
            $query .= " AND g.group_id = ?";
            $params[] = $group_id;
            $types .= "i";
        }

        $query .= " GROUP BY u.user_id, p.program_id, g.group_id ORDER BY u.full_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query failed', 'code' => 500];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $students, 'code' => 200];
    }

    public function getPendingJustifications($limit = 50, $offset = 0) {
        $stmt = $this->connection->prepare("
            SELECT 
                j.justification_id,
                j.record_id,
                j.student_id,
                j.file_path,
                j.submitted_date,
                j.status,
                u.full_name as student_name,
                u.email as student_email,
                c.name as course_name,
                g.name as group_name,
                DATE(s.session_date) as session_date,
                ar.attendance_status
            FROM justification j
            JOIN users u ON j.student_id = u.user_id
            JOIN attendance_record ar ON j.record_id = ar.record_id
            JOIN attendance_session s ON ar.session_id = s.session_id
            JOIN course c ON s.course_id = c.course_id
            JOIN `group` g ON s.group_id = g.group_id
            WHERE j.status = 'pending'
            ORDER BY j.submitted_date DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $justifications = [];

        while ($row = $result->fetch_assoc()) {
            $justifications[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $justifications, 'code' => 200];
    }

    public function approveJustification($justification_id, $admin_id) {
        $this->connection->begin_transaction();

        try {
            // Get the record_id from justification
            $getStmt = $this->connection->prepare("SELECT record_id FROM justification WHERE justification_id = ?");
            $getStmt->bind_param("i", $justification_id);
            $getStmt->execute();
            $result = $getStmt->get_result();
            
            if ($result->num_rows === 0) {
                $getStmt->close();
                throw new Exception("Justification not found");
            }
            
            $justData = $result->fetch_assoc();
            $record_id = $justData['record_id'];
            $getStmt->close();

            // Update justification status
            $justStmt = $this->connection->prepare("
                UPDATE justification 
                SET status = 'approved', approved_by = ?, approval_date = NOW()
                WHERE justification_id = ?
            ");
            $justStmt->bind_param("ii", $admin_id, $justification_id);
            
            if (!$justStmt->execute()) {
                throw new Exception("Failed to update justification");
            }
            $justStmt->close();

            // Update attendance record to 'justified'
            $attStmt = $this->connection->prepare("
                UPDATE attendance_record 
                SET attendance_status = 'justified'
                WHERE record_id = ?
            ");
            $attStmt->bind_param("i", $record_id);
            
            if (!$attStmt->execute()) {
                throw new Exception("Failed to update attendance record");
            }
            $attStmt->close();

            $this->connection->commit();
            
            return ['success' => true, 'message' => 'Justification approved', 'code' => 200];
        } catch (Exception $e) {
            $this->connection->rollback();
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }

    public function rejectJustification($justification_id, $admin_id) {
        $stmt = $this->connection->prepare("
            UPDATE justification 
            SET status = 'rejected', approved_by = ?, approval_date = NOW()
            WHERE justification_id = ?
        ");
        $stmt->bind_param("ii", $admin_id, $justification_id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Justification rejected', 'code' => 200];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Failed to reject justification', 'code' => 500];
        }
    }

    public function getPrograms() {
        $stmt = $this->connection->prepare("SELECT program_id, name FROM program ORDER BY name");
        $stmt->execute();
        $result = $stmt->get_result();
        $programs = [];

        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $programs, 'code' => 200];
    }

    public function getGroupsByProgram($program_id) {
        $stmt = $this->connection->prepare("
            SELECT group_id, name FROM `group` 
            WHERE program_id = ? 
            ORDER BY name
        ");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = [];

        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }

        $stmt->close();
        return ['success' => true, 'data' => $groups, 'code' => 200];
    }
}
?>