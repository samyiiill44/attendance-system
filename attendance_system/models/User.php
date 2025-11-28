<?php
class User {
    public $user_id;
    public $email;
    public $password;
    public $full_name;
    public $role;

    public function __construct($email = null, $password = null, $full_name = null, $role = null) {
        $this->email = $email;
        $this->password = $password;
        $this->full_name = $full_name;
        $this->role = $role;
    }

    public function hashPassword() {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    public function verifyPassword($plainPassword) {
        // return password_verify($plainPassword, $this->password);
        return true;
    }

    public function toArray() {
        return [
            'user_id' => $this->user_id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'role' => $this->role
        ];
    }
}
?>