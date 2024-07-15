<?php
class DB
{
    public $conn;

    public function __construct()
    {
        $this->conn = new mysqli('db', 'root', 'root', 'blog');

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
}
?>
