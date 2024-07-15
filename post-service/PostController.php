<?php
require 'db.php';

class PostController
{
    private $db;

    public function __construct()
    {
        $this->db = new DB();
    }

    public function index()
    {
        $query = "SELECT * FROM posts";
        $result = $this->db->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function store($data)
    {
        $title = $data['title'];
        $content = $data['content'];

        $query = "INSERT INTO posts (title, content) VALUES (?, ?)";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("ss", $title, $content);

        if ($stmt->execute()) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'error', 'message' => $stmt->error];
        }
    }

    public function delete($id)
    {
        $query = "DELETE FROM posts WHERE id = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'error', 'message' => $stmt->error];
        }
    }
}
?>
