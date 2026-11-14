<?php
// database.php

$host   = "sql211.infinityfree.com";
$port   = 3306;
$user   = "if0_43438499";
$pass   = "z3LVDJuBkc41";
$dbname = "if0_43438499_XXX";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Optional: create tables if they don't exist
    // $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     name VARCHAR(100) NOT NULL
    // )");

} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die("Database connection failed.");
}
