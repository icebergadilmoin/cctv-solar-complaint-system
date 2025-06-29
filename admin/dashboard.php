<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}
require '../includes/config.php';

// Get all workers
$workers = $pdo->query("SELECT * FROM users WHERE role = 'worker'")->fetchAll();

// Get all managers
$managers = $pdo->query("SELECT * FROM users WHERE role = 'manager'")->fetchAll();

// Get all clients
$clients = $pdo->query("SELECT * FROM users WHERE role = 'client'")->fetchAll();

// Get all complaints
$complaints = $pdo->query("SELECT c.*, u.full_name as client_name 
                          FROM complaints c 
                          JOIN users u ON c.client_id = u.id")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">Admin Dashboard</h1>
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Workers</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($workers); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Managers</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($managers); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Clients</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($clients); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Complaints</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($complaints); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="workers-tab" data-bs-toggle="tab" data-bs-target="#workers" type="button">Workers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managers" type="button">Managers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="complaints-tab" data-bs-toggle="tab" data-bs-target="#complaints" type="button">Complaints</button>
                </li>
            </ul>
            
            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                <!-- Workers Tab -->
                <div class="tab-pane fade show active" id="workers" role="tabpanel">
                    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addWorkerModal">
                        Add New Worker
                    </button>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workers as $worker): ?>
                            <tr>
                                <td><?php echo $worker['id']; ?></td>
                                <td><?php echo htmlspecialchars($worker['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($worker['username']); ?></td>
                                <td><?php echo htmlspecialchars($worker['email']); ?></td>
                                <td><?php echo htmlspecialchars($worker['specialization']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-worker" data-id="<?php echo $worker['id']; ?>">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-worker" data-id="<?php echo $worker['id']; ?>">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Managers Tab -->
                <div class="tab-pane fade" id="managers" role="tabpanel">
                    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addManagerModal">
                        Add New Manager
                    </button>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($managers as $manager): ?>
                            <tr>
                                <td><?php echo $manager['id']; ?></td>
                                <td><?php echo htmlspecialchars($manager['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($manager['username']); ?></td>
                                <td><?php echo htmlspecialchars($manager['email']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger delete-manager" data-id="<?php echo $manager['id']; ?>">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Complaints Tab -->
                <div class="tab-pane fade" id="complaints" role="tabpanel">
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
                                <td><?php echo $complaint['id']; ?></td>
                                <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['client_name']); ?></td>
                                <td><?php echo $complaint['type']; ?></td>
                                <td>
                                    <span class="badge 
                                        <?php echo $complaint['status'] == 'Pending' ? 'bg-warning' : 
                                              ($complaint['status'] == 'Resolved' ? 'bg-success' : 'bg-primary'); ?>">
                                        <?php echo $complaint['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($complaint['created_at'])); ?></td>
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
    </div>
</div>

<!-- Add Worker Modal -->
<div class="modal fade" id="addWorkerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Worker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addWorkerForm" action="add_worker.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="worker_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="worker_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="worker_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="worker_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="worker_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="worker_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="worker_specialization" class="form-label">Specialization</label>
                        <select class="form-select" id="worker_specialization" name="specialization" required>
                            <option value="CCTV">CCTV Specialist</option>
                            <option value="Solar">Solar Specialist</option>
                            <option value="General">General Technician</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="worker_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="worker_password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Manager Modal -->
<div class="modal fade" id="addManagerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addManagerForm" action="add_manager.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="manager_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="manager_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="manager_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="manager_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="manager_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="manager_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="manager_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="manager_password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-header">Pending Complaints</div>
            <div class="card-body">
                <h5 class="card-title">
                    <?= $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn() ?>
                </h5>
                <a href="complaints.php?status=Pending" class="text-white">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header">In Progress</div>
            <div class="card-body">
                <h5 class="card-title">
                    <?= $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn() ?>
                </h5>
                <a href="complaints.php?status=In+Progress" class="text-white">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Resolved Today</div>
            <div class="card-body">
                <h5 class="card-title">
                    <?= $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved' AND DATE(created_at) = CURDATE()")->fetchColumn() ?>
                </h5>
                <a href="complaints.php?status=Resolved&date=today" class="text-white">View All</a>
            </div>
        </div>
    </div>
</div>
<a href="export_complaints.php" class="btn btn-success">Export to CSV</a>

<script src="../assets/js/admin.js"></script>
<?php include '../includes/footer.php'; ?>