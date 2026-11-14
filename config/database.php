<?php
// database.php

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "my_database";   // change to your database name

try {
    // Step 1: Connect to MySQL server (without selecting a database)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 2: Create the database only if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");

    // Step 3: Select / connect to the database
    $pdo->exec("USE `$dbname`");

    // Optional: create tables if they don't exist
    

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}