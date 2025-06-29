<?php
session_start();
require 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$userId = $_SESSION['user_id'];
$unread = $pdo->query("
    SELECT COUNT(*) FROM messages m
    JOIN complaints c ON m.complaint_id = c.id
    WHERE (c.client_id = $userId OR EXISTS (
        SELECT 1 FROM assignments a 
        WHERE a.complaint_id = c.id AND a.worker_id = $userId
    )) AND m.sender_id != $userId AND m.read_at IS NULL
")->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['unread' => $unread]);