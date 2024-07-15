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
