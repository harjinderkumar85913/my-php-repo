<?php
// Load .env file
function loadEnv($path)
{
    if (!file_exists($path)) {
        die(".env file not found");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and invalid lines
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/.env');

$host   = $_ENV['DB_HOST'] ?? 'localhost';
$user   = $_ENV['DB_USER'] ?? 'root';
$pass   = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'my_database';

try {
    // Step 1: Connect to MySQL server (without selecting a database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 2: Create the database only if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");

    // Step 3: Select the database
    $pdo->exec("USE `$dbname`");

    // Optional: create tables if they don't exist

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
