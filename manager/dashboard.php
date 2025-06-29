<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

// Fetch all complaints
$complaints = $pdo->query("
    SELECT c.*, u.full_name as client_name 
    FROM complaints c 
    JOIN users u ON c.client_id = u.id
    WHERE c.status != 'Resolved'
    ORDER BY c.status, c.created_at DESC
")->fetchAll();

// Fetch all workers
$workers = $pdo->query("SELECT * FROM users WHERE role = 'worker'")->fetchAll();

// Fetch assigned complaints
$assignedComplaints = $pdo->query("
    SELECT a.*, c.title, c.status, u.full_name as worker_name
    FROM assignments a
    JOIN complaints c ON a.complaint_id = c.id
    JOIN users u ON a.worker_id = u.id
    ORDER BY a.assigned_at DESC
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">Manager Dashboard</h1>
    
    <!-- Complaints Overview -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Complaints</h5>
                    <p class="card-text">
                        <?= count(array_filter($complaints, fn($c) => $c['status'] == 'Pending')) ?>
                    </p>
                    <a href="complaints.php?status=Pending" class="text-white">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">In Progress</h5>
                    <p class="card-text">
                        <?= count(array_filter($complaints, fn($c) => $c['status'] == 'In Progress')) ?>
                    </p>
                    <a href="complaints.php?status=In+Progress" class="text-white">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Resolved Today</h5>
                    <p class="card-text">
                        <?= $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved' AND DATE(created_at) = CURDATE()")->fetchColumn() ?>
                    </p>
                    <a href="complaints.php?status=Resolved&date=today" class="text-white">View All</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Complaints Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Pending Complaints</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint): ?>
                        <?php if ($complaint['status'] == 'Pending'): ?>
                            <tr>
                                <td><?= $complaint['id'] ?></td>
                                <td><?= htmlspecialchars($complaint['title']) ?></td>
                                <td><?= htmlspecialchars($complaint['client_name']) ?></td>
                                <td><?= $complaint['type'] ?></td>
                                <td><?= date('d M Y', strtotime($complaint['created_at'])) ?></td>
                                <td>
                                    <a href="view_complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info">View</a>
                                    <button class="btn btn-sm btn-primary assign-btn" 
                                            data-complaint-id="<?= $complaint['id'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#assignModal">
                                        Assign
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assigned Complaints Table -->
    <div class="card">
        <div class="card-header">
            <h5>Assigned Complaints</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Complaint ID</th>
                        <th>Title</th>
                        <th>Worker</th>
                        <th>Status</th>
                        <th>Assigned On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignedComplaints as $assignment): ?>
                        <tr>
                            <td><?= $assignment['complaint_id'] ?></td>
                            <td><?= htmlspecialchars($assignment['title']) ?></td>
                            <td><?= htmlspecialchars($assignment['worker_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $assignment['status'] == 'Pending' ? 'warning' : 
                                    ($assignment['status'] == 'Resolved' ? 'success' : 'primary')
                                ?>">
                                    <?= $assignment['status'] ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($assignment['assigned_at'])) ?></td>
                            <td>
                                <a href="view_complaint.php?id=<?= $assignment['complaint_id'] ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Complaint Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignForm" action="assign_complaint.php" method="POST">
                <input type="hidden" name="complaint_id" id="modalComplaintId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Worker</label>
                        <select name="worker_id" class="form-select" required>
                            <?php foreach ($workers as $worker): ?>
                                <option value="<?= $worker['id'] ?>">
                                    <?= htmlspecialchars($worker['full_name']) ?> (<?= $worker['specialization'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pass complaint ID to modal
document.querySelectorAll('.assign-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const complaintId = this.getAttribute('data-complaint-id');
        document.getElementById('modalComplaintId').value = complaintId;
    });
});
</script>

<?php include '../includes/footer.php'; ?>