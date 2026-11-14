<?php
// config/admin.php
// Runs automatically every time the project starts (loaded from api.php)

require_once __DIR__ . '/../models/User.php';

// Default admin details (change these)
$adminName     = "Admin";
$adminEmail    = "admin@example.com";
$adminPassword = "admin123";
$adminRole     = "admin";

try {
    // Creating the model also creates the users table if it doesn't exist
    $userModel = new User($pdo);

    // Check if admin already exists in the users table
    $existingAdmin = $userModel->getByEmail($adminEmail);

    if (!$existingAdmin) {
        // Not available, so create it
        $userModel->create($adminName, $adminEmail, $adminPassword, $adminRole);
    }
    // If available, do nothing

} catch (PDOException $e) {
    die("Admin setup failed: " . $e->getMessage());
}