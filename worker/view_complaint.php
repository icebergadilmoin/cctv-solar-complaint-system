<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'worker') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

$complaintId = $_GET['id'] ?? 0;
$workerId = $_SESSION['user_id'];

// Worker can only view complaints assigned to them
$complaint = $pdo->query("
    SELECT c.*, u.full_name as client_name
    FROM complaints c
    JOIN assignments a ON c.id = a.complaint_id
    JOIN users u ON c.client_id = u.id
    WHERE c.id = $complaintId AND a.worker_id = $workerId
")->fetch();

if (!$complaint) {
    $_SESSION['error'] = "Complaint not found or not assigned to you";
    header('Location: dashboard.php');
    exit();
}

// Fetch messages
$messages = $pdo->query("
    SELECT m.*, u.username as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.complaint_id = $complaintId
    ORDER BY m.sent_at
")->fetchAll();

// Mark messages as read when viewed by worker
$pdo->prepare("UPDATE messages SET read_at = NOW() WHERE complaint_id = ? AND sender_id != ? AND read_at IS NULL")
    ->execute([$complaintId, $workerId]);
    
// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    $remarks = $_POST['remarks'] ?? '';

    $pdo->prepare("UPDATE complaints SET status = ?, remarks = ? WHERE id = ?")
        ->execute([$newStatus, $remarks, $complaintId]);

    $_SESSION['success'] = "Status updated successfully!";
    header("Location: view_complaint.php?id=$complaintId");
    exit();
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $pdo->prepare("
            INSERT INTO messages (complaint_id, sender_id, message)
            VALUES (?, ?, ?)
        ")->execute([$complaintId, $workerId, $message]);

        header("Location: view_complaint.php?id=$complaintId");
        exit();
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">Complaint #<?= $complaint['id'] ?></h1>
    
    <div class="row">
        <!-- Complaint Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Complaint Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Title:</strong> <?= htmlspecialchars($complaint['title']) ?></p>
                    <p><strong>Client:</strong> <?= htmlspecialchars($complaint['client_name']) ?></p>
                    <p><strong>Type:</strong> <?= $complaint['type'] ?></p>
                    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= 
                            $complaint['status'] == 'Pending' ? 'warning' : 
                            ($complaint['status'] == 'Resolved' ? 'success' : 'primary')
                        ?>">
                            <?= $complaint['status'] ?>
                        </span>
                    </p>
                    <p><strong>Created:</strong> <?= date('d M Y H:i', strtotime($complaint['created_at'])) ?></p>
                    
                    <!-- Status Update Form -->
                    <form method="POST" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select name="status" class="form-select" required>
                                <option value="In Progress" <?= $complaint['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved" <?= $complaint['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($complaint['remarks'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Chat Section -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Communication</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($messages)): ?>
                        <p>No messages yet.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="mb-3 <?= $message['sender_id'] == $workerId ? 'text-end' : '' ?>">
                                <div class="d-flex justify-content-<?= $message['sender_id'] == $workerId ? 'end' : 'start' ?>">
                                    <div class="bg-<?= $message['sender_id'] == $workerId ? 'primary' : 'light' ?> text-<?= $message['sender_id'] == $workerId ? 'white' : 'dark' ?> p-3 rounded" style="max-width: 70%;">
                                        <small class="d-block fw-bold">
                                            <?= $message['sender_name'] ?> (<?= ucfirst($message['sender_role']) ?>)
                                        </small>
                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                        <small class="d-block text-<?= $message['sender_id'] == $workerId ? 'white-50' : 'muted' ?> mt-1">
                                            <?= date('d M H:i', strtotime($message['sent_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <form method="POST">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>