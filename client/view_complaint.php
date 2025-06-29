<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

$complaintId = $_GET['id'] ?? 0;
$clientId = $_SESSION['user_id'];

// Verify client owns this complaint
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as client_name
    FROM complaints c
    JOIN users u ON c.client_id = u.id
    WHERE c.id = ? AND c.client_id = ?
");
$stmt->execute([$complaintId, $clientId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $_SESSION['error'] = "Complaint not found or you don't have permission to view it";
    header('Location: dashboard.php');
    exit();
}

// Fetch messages and mark as read
$messages = $pdo->query("
    SELECT m.*, u.username as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.complaint_id = $complaintId
    ORDER BY m.sent_at
")->fetchAll();

// Mark messages as read
$pdo->prepare("UPDATE messages SET read_at = NOW() WHERE complaint_id = ? AND sender_id != ? AND read_at IS NULL")
    ->execute([$complaintId, $clientId]);

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $pdo->prepare("
            INSERT INTO messages (complaint_id, sender_id, message)
            VALUES (?, ?, ?)
        ")->execute([$complaintId, $clientId, $message]);

        header("Location: view_complaint.php?id=$complaintId");
        exit();
    }
}

// Fetch any media attachments
$media = $pdo->query("
    SELECT * FROM media WHERE complaint_id = $complaintId
")->fetchAll();
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
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= 
                            $complaint['status'] == 'Pending' ? 'warning' : 
                            ($complaint['status'] == 'Resolved' ? 'success' : 'primary')
                        ?>">
                            <?= $complaint['status'] ?>
                        </span>
                    </p>
                    <p><strong>Type:</strong> <?= $complaint['type'] ?></p>
                    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                    <p><strong>Created:</strong> <?= date('d M Y H:i', strtotime($complaint['created_at'])) ?></p>
                    
                    <?php if (!empty($media)): ?>
                        <div class="mt-3">
                            <h6>Attachments:</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($media as $file): ?>
                                    <?php if (strpos($file['file_path'], '.jpg') !== false || strpos($file['file_path'], '.png') !== false): ?>
                                        <a href="../uploads/<?= $file['file_path'] ?>" target="_blank">
                                            <img src="../uploads/<?= $file['file_path'] ?>" class="img-thumbnail" style="max-width: 100px;">
                                        </a>
                                    <?php else: ?>
                                        <a href="../uploads/<?= $file['file_path'] ?>" class="btn btn-sm btn-outline-primary" download>
                                            Download File
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
                            <div class="mb-3 <?= $message['sender_id'] == $clientId ? 'text-end' : '' ?>">
                                <div class="d-flex justify-content-<?= $message['sender_id'] == $clientId ? 'end' : 'start' ?>">
                                    <div class="bg-<?= $message['sender_id'] == $clientId ? 'primary' : 'light' ?> text-<?= $message['sender_id'] == $clientId ? 'white' : 'dark' ?> p-3 rounded" style="max-width: 70%;">
                                        <small class="d-block fw-bold">
                                            <?= $message['sender_name'] ?> (<?= ucfirst($message['sender_role']) ?>)
                                        </small>
                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                        <small class="d-block text-<?= $message['sender_id'] == $clientId ? 'white-50' : 'muted' ?> mt-1">
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