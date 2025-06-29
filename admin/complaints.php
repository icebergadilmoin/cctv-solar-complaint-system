<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

$status = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build the query based on filters
$query = "SELECT c.*, u.full_name as client_name FROM complaints c JOIN users u ON c.client_id = u.id WHERE 1=1";
$params = [];

if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

if ($dateFilter === 'today') {
    $query .= " AND DATE(c.created_at) = CURDATE()";
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">
        <?= 
            $status === 'Pending' ? 'Pending Complaints' : 
            ($status === 'In Progress' ? 'In Progress Complaints' : 
            ($dateFilter === 'today' ? 'Today\'s Resolved Complaints' : 'All Complaints'))
        ?>
    </h1>
    
    <div class="card">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint): ?>
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
                                <a href="view_complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>