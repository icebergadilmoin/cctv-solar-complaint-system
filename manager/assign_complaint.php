<?php
session_start();
require '../includes/config.php';

// Verify authorization - both admin and manager can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager')) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaintId = (int)$_POST['complaint_id'];
    $workerIds = is_array($_POST['worker_id']) ? array_map('intval', $_POST['worker_id']) : [(int)$_POST['worker_id']];
    $assignedBy = $_SESSION['user_id'];

    // Validate complaint exists and isn't resolved
    $stmt = $pdo->prepare("SELECT id, title, status FROM complaints WHERE id = ?");
    $stmt->execute([$complaintId]);
    $complaint = $stmt->fetch();
    
    if (!$complaint) {
        $_SESSION['error'] = "Complaint not found";
        header('Location: dashboard.php');
        exit();
    }

    if ($complaint['status'] == 'Resolved') {
        $_SESSION['error'] = "Cannot assign workers to a resolved complaint";
        header('Location: dashboard.php');
        exit();
    }

    $pdo->beginTransaction();
    try {
        // Update complaint status if it's pending
        if ($complaint['status'] == 'Pending') {
            $pdo->prepare("UPDATE complaints SET status = 'In Progress' WHERE id = ?")
                ->execute([$complaintId]);
        }

        // Get current assignments
        $currentAssignments = $pdo->prepare("SELECT worker_id FROM assignments WHERE complaint_id = ?");
        $currentAssignments->execute([$complaintId]);
        $currentWorkerIds = array_column($currentAssignments->fetchAll(), 'worker_id');

        // Add new assignments
        foreach (array_diff($workerIds, $currentWorkerIds) as $workerId) {
            $pdo->prepare("INSERT INTO assignments (complaint_id, worker_id, assigned_by) VALUES (?, ?, ?)")
                ->execute([$complaintId, $workerId, $assignedBy]);
            
            // Create notification for worker
            $message = "You've been assigned to Complaint #{$complaintId}: {$complaint['title']}";
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                ->execute([$workerId, $message]);
        }

        // Remove unselected assignments
        foreach (array_diff($currentWorkerIds, $workerIds) as $workerId) {
            $pdo->prepare("DELETE FROM assignments WHERE complaint_id = ? AND worker_id = ?")
                ->execute([$complaintId, $workerId]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Worker assignments updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Redirect back to previous page
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit();
?>