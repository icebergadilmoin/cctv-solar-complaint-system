<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $userId = (int)$_POST['id'];
    
    // Prevent deleting admin accounts
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user && $user['role'] != 'admin') {
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($deleteStmt->execute([$userId])) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
?>