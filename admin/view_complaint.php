<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager')) {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

$complaintId = $_GET['id'] ?? 0;

// Get complaint details
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as client_name 
    FROM complaints c 
    JOIN users u ON c.client_id = u.id 
    WHERE c.id = ?
");
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $_SESSION['error'] = "Complaint not found";
    header('Location: dashboard.php');
    exit();
}

// Get assigned workers
$assignedWorkers = $pdo->prepare("
    SELECT u.id, u.full_name, u.specialization 
    FROM assignments a
    JOIN users u ON a.worker_id = u.id
    WHERE a.complaint_id = ?
");
$assignedWorkers->execute([$complaintId]);
$assignedWorkerIds = array_column($assignedWorkers->fetchAll(), 'id');

// Get all workers for assignment dropdown
$allWorkers = $pdo->query("SELECT id, full_name, specialization FROM users WHERE role = 'worker' ORDER BY full_name")->fetchAll();

// Get messages
$messages = $pdo->prepare("
    SELECT m.*, u.username as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.complaint_id = ?
    ORDER BY m.sent_at
");
$messages->execute([$complaintId]);
$messages = $messages->fetchAll();

// Get media attachments
$media = $pdo->prepare("SELECT * FROM media WHERE complaint_id = ?");
$media->execute([$complaintId]);
$media = $media->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<!-- Add these in head section -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.10.5/viewer.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="container">
    <h1 class="my-4">Complaint #<?= $complaint['id'] ?></h1>
    
    <div class="row">
        <!-- Left Column - Complaint Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Complaint Details</h5>
                    <span class="badge bg-<?= 
                        $complaint['status'] == 'Pending' ? 'warning' : 
                        ($complaint['status'] == 'Resolved' ? 'success' : 'primary')
                    ?>">
                        <?= $complaint['status'] ?>
                    </span>
                </div>
                <div class="card-body">
                    <p><strong>Client:</strong> <?= htmlspecialchars($complaint['client_name']) ?></p>
                    <p><strong>Title:</strong> <?= htmlspecialchars($complaint['title']) ?></p>
                    <p><strong>Type:</strong> <?= $complaint['type'] ?></p>
                    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                    <p><strong>Created:</strong> <?= date('d M Y H:i', strtotime($complaint['created_at'])) ?></p>
                    
                    <!-- Worker Assignment Form -->
                    <div class="mt-4 border-top pt-3">
                        <h5>Assign Workers</h5>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Select Workers</label>
                                <select name="worker_id[]" class="form-select" multiple size="5" required>
                                    <?php foreach ($allWorkers as $worker): ?>
                                        <option value="<?= $worker['id'] ?>" <?= 
                                            in_array($worker['id'], $assignedWorkerIds) ? 'selected' : '' 
                                        ?>>
                                            <?= htmlspecialchars($worker['full_name']) ?> 
                                            (<?= $worker['specialization'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple workers</small>
                            </div>
                            <button type="submit" name="assign_worker" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Assignments
                            </button>
                        </form>
                        
                        <?php if (!empty($assignedWorkerIds)): ?>
                            <div class="assigned-workers">
                                <h6>Currently Assigned:</h6>
                                <ul class="list-group">
                                    <?php foreach ($allWorkers as $worker): ?>
                                        <?php if (in_array($worker['id'], $assignedWorkerIds)): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($worker['full_name']) ?>
                                                <span class="badge bg-secondary"><?= $worker['specialization'] ?></span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Media Attachments Section -->
                    <div class="mt-4 border-top pt-3">
                        <h5>Attachments</h5>
                        <div class="media-gallery d-flex flex-wrap gap-3 mt-3">
                            <?php foreach ($media as $file): 
                                $fileExt = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif']);
                                $isVideo = in_array($fileExt, ['mp4', 'webm', 'mov']);
                            ?>
                                <?php if ($isImage): ?>
                                    <a href="../uploads/<?= $file['file_path'] ?>" class="media-thumbnail" title="Image Attachment">
                                        <img src="../uploads/<?= $file['file_path'] ?>" 
                                             class="img-thumbnail rounded" 
                                             style="width: 120px; height: 120px; object-fit: cover;">
                                    </a>
                                <?php elseif ($isVideo): ?>
                                    <a href="../uploads/<?= $file['file_path'] ?>" class="video-preview" title="Video Attachment">
                                        <div class="position-relative">
                                            <div class="img-thumbnail bg-dark" style="width: 120px; height: 120px;"></div>
                                            <div class="position-absolute top-50 start-50 translate-middle">
                                                <i class="fas fa-play-circle fs-3 text-white"></i>
                                            </div>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <a href="../uploads/<?= $file['file_path'] ?>" class="btn btn-outline-primary" download title="Download File">
                                        <i class="fas fa-file-download me-1"></i> File
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Chat Section -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Communication</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="chat-messages flex-grow-1" style="overflow-y: auto; max-height: 400px;">
                        <?php if (empty($messages)): ?>
                            <p class="text-muted text-center mt-4">No messages yet. Start the conversation!</p>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="mb-3 <?= $message['sender_id'] == $_SESSION['user_id'] ? 'text-end' : 'text-start' ?>">
                                    <div class="d-inline-block">
                                        <div class="bg-<?= $message['sender_id'] == $_SESSION['user_id'] ? 'primary' : 'light' ?> 
                                            text-<?= $message['sender_id'] == $_SESSION['user_id'] ? 'white' : 'dark' ?> 
                                            p-3 rounded" style="max-width: 70%;">
                                            <small class="d-block fw-bold">
                                                <?= $message['sender_name'] ?> (<?= ucfirst($message['sender_role']) ?>)
                                            </small>
                                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                                            <small class="d-block text-<?= $message['sender_id'] == $_SESSION['user_id'] ? 'white-50' : 'muted' ?> mt-1">
                                                <?= date('d M H:i', strtotime($message['sent_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-auto">
                        <form method="POST" class="mt-3">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Video Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <video id="videoSource" controls style="max-width: 100%; max-height: 70vh;">
                    Your browser doesn't support HTML5 video
                </video>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.10.5/viewer.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize image viewer
    const gallery = document.querySelector('.media-gallery');
    if (gallery) {
        new Viewer(gallery, {
            navbar: false,
            title: false,
            toolbar: {
                zoomIn: 1,
                zoomOut: 1,
                oneToOne: 1,
                reset: 1,
                prev: 1,
                next: 1,
                rotateLeft: 1,
                rotateRight: 1
            }
        });
    }

    // Video preview handler
    document.querySelectorAll('.video-preview').forEach(video => {
        video.addEventListener('click', function(e) {
            e.preventDefault();
            const videoModal = new bootstrap.Modal(document.getElementById('videoModal'));
            const videoSource = document.getElementById('videoSource');
            videoSource.src = this.href;
            videoSource.load();
            videoModal.show();
        });
    });

    // Auto-scroll chat to bottom
    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
});
</script>

<?php include '../includes/footer.php'; ?>