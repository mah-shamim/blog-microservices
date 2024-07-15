<?php
require 'db.php';

class UserController
{
    private $db;

    public function __construct()
    {
        $this->db = new DB();
    }

    public function register($data)
    {
        $name = $data['name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        $query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'error', 'message' => $stmt->error];
        }
    }

    public function login($data)
    {
        $email = $data['email'];
        $password = $data['password'];

        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return ['status' => 'success', 'user' => $user];
        } else {
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
    }
}
?>
