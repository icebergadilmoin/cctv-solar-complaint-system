<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager')) {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="complaints_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Title', 'Client', 'Type', 'Status', 'Date']);

$complaints = $pdo->query("
    SELECT c.id, c.title, u.full_name as client_name, c.type, c.status, c.created_at
    FROM complaints c
    JOIN users u ON c.client_id = u.id
")->fetchAll();

foreach ($complaints as $complaint) {
    fputcsv($output, [
        $complaint['id'],
        $complaint['title'],
        $complaint['client_name'],
        $complaint['type'],
        $complaint['status'],
        $complaint['created_at']
    ]);
}

fclose($output);
exit();
?>