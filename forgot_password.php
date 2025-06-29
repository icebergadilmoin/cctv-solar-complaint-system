<?php
require 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $user['id']]);
        
        // In a real system, send this link via email
        $resetLink = "http://localhost/complaint_system/reset_password.php?token=$token";
        $_SESSION['info'] = "Password reset link: $resetLink"; // For testing
    } else {
        $_SESSION['error'] = "Email not found";
    }
    header('Location: login.php');
    exit();
}
?>

<?php include 'includes/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Forgot Password</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>