To create a raw PHP microservice-based blog project, we'll separate the project into different services: User Service and Post Service. Additionally, we'll provide an API Gateway for routing requests. Below is the step-by-step process and source code for each part, along with the database schema.

### Project Structure

```
php-microservices/
    user-service/
        index.php
        UserController.php
        db.php
        Dockerfile
    post-service/
        index.php
        PostController.php
        db.php
        Dockerfile
    api-gateway/
        index.php
        Dockerfile
    docker-compose.yml
    db/
        init.sql
```

### Step-by-Step Guide

1. **User Service**: This service will handle user registration and authentication.

   **a. Create `user-service/index.php`:**
    ```php
    <?php
    require 'UserController.php';

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    header('Content-Type: application/json');

    $userController = new UserController();

    switch ($uri) {
        case '/register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode($userController->register($_POST));
            }
            break;
        case '/login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode($userController->login($_POST));
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);
            break;
    }
    ?>
    ```

   **b. Create `user-service/UserController.php`:**
    ```php
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
    ```

   **c. Create `user-service/db.php`:**
    ```php
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
    ```

   **d. Create `user-service/Dockerfile`:**
    ```Dockerfile
    FROM php:5.7-apache

    COPY . /var/www/html

    RUN docker-php-ext-install mysqli
    ```

2. **Post Service**: This service will manage blog posts.

   **a. Create `post-service/index.php`:**
    ```php
    <?php
    require 'PostController.php';

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    header('Content-Type: application/json');

    $postController = new PostController();

    switch ($uri) {
        case '/posts':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo json_encode($postController->index());
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode($postController->store($_POST));
            }
            break;
        case '/posts/delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode($postController->delete($_POST['id']));
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);
            break;
    }
    ?>
    ```

   **b. Create `post-service/PostController.php`:**
    ```php
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
    ```

   **c. Create `post-service/db.php`:**
    ```php
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
    ```

   **d. Create `post-service/Dockerfile`:**
    ```Dockerfile
    FROM php:5.7-apache

    COPY . /var/www/html

    RUN docker-php-ext-install mysqli
    ```

3. **API Gateway**: This will route requests to appropriate microservices.

   **a. Create `api-gateway/index.php`:**
    ```php
    <?php

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    $data = $_POST;

    header('Content-Type: application/json');

    if (strpos($uri, '/user') === 0) {
        $ch = curl_init("http://user-service" . str_replace('/user', '', $uri));
    } elseif (strpos($uri, '/post') === 0) {
        $ch = curl_init("http://post-service" . str_replace('/post', '', $uri));
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        exit;
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;
    ?>
    ```

   **b. Create `api-gateway/Dockerfile`:**
    ```Dockerfile
    FROM php:5.7-apache

    COPY . /var/www/html
    ```

4. **Database Initialization Script**:

   **a. Create `db/init.sql`:**
    ```sql
    CREATE DATABASE IF NOT EXISTS blog;

    USE blog;

    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    );

    CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL
    );
    ```

5. **Docker Compose**:

   **a. Create `docker-compose.yml` in the root of the project:**
    ```yaml
    version: '3.8'

    services:
      user-service:
        build: ./user-service
        ports:
          - 8001:80
        environment:
          - DB_HOST=db
          - DB_USER=root
          - DB_PASS=root
          - DB_NAME=blog
        depends_on:
          - db

      post-service:
        build: ./post-service
        ports:
          - 8002:80
        environment:
          - DB_HOST=db
          - DB_USER=root
          - DB_PASS=root
          - DB_NAME=blog
        depends_on:
          - db

      api-gateway:
        build: ./api-gateway
        ports:
          - 8000:80
        depends_on:
          - user-service
          - post-service

      db:
        image: mysql:5.7
        environment:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: blog
        volumes:
          - ./db/init.sql:/docker-entrypoint-initdb.d/init.sql
        ports:
          - 3306:3306
    ```

### Running the Project

1. **Build and Run the Containers:**
    ```bash
    docker-compose up --build
    ```

2. **Access the Services:**
   - User Service: `http://localhost:8001`
   - Post Service: `http://localhost:8002`
   - API Gateway: `http://localhost:8000`
