<?php
session_start();
require '../includes/config.php';

// Verify admin/manager access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager')) {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: ../login.php');
    exit();
}

// Validate inputs
if (!isset($_POST['complaint_id']) || !isset($_POST['priority'])) {
    $_SESSION['error'] = "Invalid request";
    header('Location: dashboard.php');
    exit();
}

$complaintId = intval($_POST['complaint_id']);
$priority = in_array($_POST['priority'], ['normal', 'urgent']) ? $_POST['priority'] : 'normal';

try {
    // Update priority in database
    $stmt = $pdo->prepare("UPDATE complaints SET priority = ?, prioritized_by = ?, priority_updated_at = NOW() WHERE id = ?");
    $stmt->execute([$priority, $_SESSION['user_id'], $complaintId]);

    // Log this action
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([
        $_SESSION['user_id'],
        'priority_update',
        json_encode([
            'complaint_id' => $complaintId,
            'new_priority' => $priority
        ])
    ]);

    $_SESSION['success'] = "Priority updated successfully";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to the complaint view
header("Location: view_complaint.php?id=" . $complaintId);
exit();
?>