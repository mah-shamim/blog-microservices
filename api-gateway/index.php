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
