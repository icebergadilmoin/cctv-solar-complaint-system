<?php
session_start();
require 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Basic info update
    $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?")
        ->execute([$full_name, $phone, $address, $userId]);
    
    // Password change if requested
    if (!empty($current_password) && !empty($new_password)) {
        $user = $pdo->prepare("SELECT password FROM users WHERE id = ?")->execute([$userId])->fetch();
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([$hashed_password, $userId]);
            $_SESSION['success'] = "Profile and password updated successfully!";
        } else {
            $_SESSION['error'] = "Current password is incorrect";
        }
    } else {
        $_SESSION['success'] = "Profile updated successfully!";
    }
    header('Location: profile.php');
    exit();
}

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?")->execute([$userId])->fetch();
?>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1 class="my-4">My Profile</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
                        </div>
                        
                        <h5 class="mt-4">Change Password</h5>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>