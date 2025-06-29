<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $password = trim($_POST['password']);

    // Check if username/email exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Username or email already exists";
        header('Location: dashboard.php');
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, full_name, specialization) 
                          VALUES (?, ?, ?, 'worker', ?, ?)");
    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $specialization])) {
        $_SESSION['success'] = "Worker added successfully";
    } else {
        $_SESSION['error'] = "Failed to add worker";
    }
}

header('Location: dashboard.php');
exit();
?>