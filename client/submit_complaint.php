<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $clientId = $_SESSION['user_id'];

    // Insert complaint
    $stmt = $pdo->prepare("
        INSERT INTO complaints (client_id, title, description, type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$clientId, $title, $description, $type]);
    $complaintId = $pdo->lastInsertId();

    // Handle file upload
    $allowedTypes = ['image/jpeg', 'image/png', 'video/mp4'];
if (!in_array($_FILES['media']['type'], $allowedTypes)) {
    $_SESSION['error'] = "Only JPEG, PNG, or MP4 files allowed.";
    header('Location: dashboard.php');
    exit();
}
    if (isset($_FILES['media']) && $_FILES['media']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['media']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $targetPath)) {
            $pdo->prepare("
                INSERT INTO media (complaint_id, file_path, uploaded_by)
                VALUES (?, ?, ?)
            ")->execute([$complaintId, $fileName, $clientId]);
        }
    }

    $_SESSION['success'] = "Complaint submitted successfully!";
    header('Location: dashboard.php');
    exit();
}

header('Location: dashboard.php');
exit();
?>