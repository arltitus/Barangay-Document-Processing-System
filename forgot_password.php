<?php
define('BDPS_SYSTEM', true);
require_once 'includes/init.php';

$error = '';
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request');
        }
        
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
        
        // Initiate password reset
        Auth::getInstance()->initiatePasswordReset($email);
        
        // Always show success to prevent email enumeration
        $success = 'If an account exists with this email, you will receive password reset instructions.';
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Generate new CSRF token
$csrfToken = $security->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Barangay Document System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/pages/password-reset.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">BDPS</a>
            <div class="navbar-links">
                <a href="login.php" class="btn btn-primary">Back to Login</a>
            </div>
        </div>
    </nav>

    <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 80px);">
        <div class="card" style="max-width: 400px; width: 100%;">
            <div class="card-header">
                <h2 class="card-title">Reset Password</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php else: ?>
            
            <p class="text-secondary mb-4" style="padding: 0 1rem;">Enter your email address and we'll send you instructions to reset your password.</p>
            
            <form method="post" id="forgotPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           required placeholder="Enter your email address"
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Instructions</button>
                </div>
            </form>
            
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="login.php" class="text-secondary">Return to Login</a>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('forgotPasswordForm')?.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        
        if (!email) {
            e.preventDefault();
            alert('Please enter your email address');
            return;
        }
        
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return;
        }
    });
    </script>
</body>
</html>
