<?php
require 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords don't match";
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
            ->execute([$hashed_password, $user['id']]);
        $_SESSION['success'] = "Password updated successfully!";
        header('Location: login.php');
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired token";
        header('Location: forgot_password.php');
        exit();
    }
}

$token = $_GET['token'] ?? '';
?>
<?php include 'includes/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Reset Password</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>