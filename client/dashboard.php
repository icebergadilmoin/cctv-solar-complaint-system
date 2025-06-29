<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

$clientId = $_SESSION['user_id'];

// Fetch client's complaints
$complaints = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM messages WHERE complaint_id = c.id AND sender_id != $clientId AND read_at IS NULL) as unread_messages
    FROM complaints c
    WHERE c.client_id = $clientId
    ORDER BY c.status, c.created_at DESC
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">Client Dashboard</h1>
    
    <!-- New Complaint Button -->
    <div class="mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
            + Submit New Complaint
        </button>
    </div>
    
    <!-- Complaints List -->
    <div class="card">
        <div class="card-header">
            <h5>Your Complaints</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Messages</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td><?= $complaint['id'] ?></td>
                            <td><?= htmlspecialchars($complaint['title']) ?></td>
                            <td><?= $complaint['type'] ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $complaint['status'] == 'Pending' ? 'warning' : 
                                    ($complaint['status'] == 'Resolved' ? 'success' : 'primary')
                                ?>">
                                    <?= $complaint['status'] ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($complaint['created_at'])) ?></td>
                            <td>
                                <?php if ($complaint['unread_messages'] > 0): ?>
                                    <span class="badge bg-danger"><?= $complaint['unread_messages'] ?> new</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Complaint Modal -->
<div class="modal fade" id="newComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit New Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="submit_complaint.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="CCTV">CCTV Issue</option>
                            <option value="Solar">Solar System Issue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Image/Video (Optional)</label>
                        <input type="file" name="media" class="form-control" accept="image/*,video/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>