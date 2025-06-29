<?php
$host = 'localhost';
$dbname = 'complaint_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create default admin if not exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $hashed_password = password_hash('Admin@1234', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, email, password, role, full_name) 
                   VALUES (?, ?, ?, ?, ?)")
        ->execute(['admin', 'admin@system.com', $hashed_password, 'admin', 'System Administrator']);
}

// Add this function to send notifications
function sendNotification($userId, $message, $pdo) {
    $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)")
        ->execute([$userId, $message]);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>