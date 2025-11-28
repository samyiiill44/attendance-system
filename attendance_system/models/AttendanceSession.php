<?php
class AttendanceSession {
    public $session_id;
    public $course_id;
    public $group_id;
    public $session_date;
    public $status = 'open';

    public function __construct($course_id = null, $group_id = null, $session_date = null) {
        $this->course_id = $course_id;
        $this->group_id = $group_id;
        $this->session_date = $session_date;
    }

    public function toArray() {
        return [
            'session_id' => $this->session_id,
            'course_id' => $this->course_id,
            'group_id' => $this->group_id,
            'session_date' => $this->session_date,
            'status' => $this->status
        ];
    }
}
?>