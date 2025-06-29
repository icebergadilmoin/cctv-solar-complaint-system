<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="complaints_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['ID', 'Title', 'Client', 'Type', 'Status', 'Created At', 'Assigned To']);

// Write data
$stmt = $pdo->query("
    SELECT c.id, c.title, u.full_name as client, c.type, c.status, c.created_at, 
           GROUP_CONCAT(w.full_name) as workers
    FROM complaints c
    JOIN users u ON c.client_id = u.id
    LEFT JOIN assignments a ON c.id = a.complaint_id
    LEFT JOIN users w ON a.worker_id = w.id
    GROUP BY c.id
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['title'],
        $row['client'],
        $row['type'],
        $row['status'],
        $row['created_at'],
        $row['workers'] ?? 'Unassigned'
    ]);
}

fclose($output);
exit();