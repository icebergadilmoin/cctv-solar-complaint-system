<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'worker') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

$workerId = $_SESSION['user_id'];

// Fetch assigned complaints
$assignedComplaints = $pdo->query("
    SELECT c.*, u.full_name as client_name
    FROM complaints c
    JOIN assignments a ON c.id = a.complaint_id
    JOIN users u ON c.client_id = u.id
    WHERE a.worker_id = $workerId
    ORDER BY c.status, c.created_at DESC
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">Worker Dashboard</h1>
    
    <!-- Assigned Complaints -->
    <div class="card">
        <div class="card-header">
            <h5>Your Assigned Complaints</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignedComplaints as $complaint): ?>
                        <tr>
                            <td><?= $complaint['id'] ?></td>
                            <td><?= htmlspecialchars($complaint['title']) ?></td>
                            <td><?= htmlspecialchars($complaint['client_name']) ?></td>
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
                                <a href="view_complaint.php?id=<?= $complaint['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    View/Update
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>