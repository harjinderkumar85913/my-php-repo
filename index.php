<?php
// index.php

if (php_sapi_name() === 'cli') {
    echo "This is a web API and cannot be executed directly via 'php index.php'.\n\n";
    echo "To run it:\n";
    echo "1. Through XAMPP: Keep Apache running and open http://localhost/newphpproject/ in your browser or Postman.\n";
    echo "2. Through PHP Built-in Server: Run 'php -S localhost:8000' in terminal and open http://localhost:8000\n";
    exit(0);
}

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';        // gives $pdo
require_once __DIR__ . '/config/admin.php';    // default admin
require_once __DIR__ . '/config/jwt.php';

$routes = require __DIR__ . '/routes/api.php';

function respond($code, $body)
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

function getBearerToken()
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    return preg_match('/Bearer\s+(\S+)/i', $header, $m) ? $m[1] : null;
}

// ---------- Find the requested path (works inside a subfolder too) ----------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if ($base !== '' && strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
if ($path === '/index.php') {
    $path = '/';
}

// ---------- Match the route ----------
$route = $routes[$method][$path] ?? null;

if (!$route) {
    foreach ($routes as $m => $list) {
        if (isset($list[$path])) {
            respond(405, ['success' => false, 'message' => 'Method not allowed']);
        }
    }
    respond(404, ['success' => false, 'message' => 'Route not found']);
}

[$class, $function] = $route;
$access = $route[2] ?? 'auth';

// ---------- Read input ----------
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

// ---------- Token check (skipped for public routes) ----------
$currentUser = null;

if ($access !== 'public') {
    $token = getBearerToken();
    if (!$token) {
        respond(401, ['success' => false, 'message' => 'Token required']);
    }

    $payload = jwt_verify($token);
    if (!$payload) {
        respond(401, ['success' => false, 'message' => 'Invalid or expired token']);
    }

    // Load fresh from the database so deleted users / changed roles are respected
    $currentUser = (new User($pdo))->getById($payload['id']);
    if (!$currentUser) {
        respond(401, ['success' => false, 'message' => 'User no longer exists']);
    }

    if ($access === 'admin' && $currentUser['role'] !== 'admin') {
        respond(403, ['success' => false, 'message' => 'Admin access required']);
    }
}

// ---------- Call the controller method ----------
try {
    $controller = new $class($pdo);
    $result     = $controller->$function($data, $currentUser);

    $status = $result['status'] ?? ($result['success'] ? 200 : 400);
    unset($result['status']);

    respond($status, $result);

} catch (Exception $e) {
    respond(500, ['success' => false, 'message' => 'Server error']);
}