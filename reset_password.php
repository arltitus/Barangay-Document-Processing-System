<?php
define('BDPS_SYSTEM', true);
require_once 'includes/init.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';
if (!$token) {
    header('Location: login.php');
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request');
        }
        
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        // Reset password
        Auth::getInstance()->resetPassword($token, $password);
        
        // Redirect to login
        header('Location: login.php?password_reset=1');
        exit;
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
    <title>Reset Password - Barangay Document System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/pages/password-reset.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Barangay Document System</title>
    <link rel="stylesheet" href="css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Barangay Document System</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" id="resetPasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter new password"
                           autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm new password"
                           autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
            
            <div class="text-center links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
            return;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match');
            return;
        }
    });
    </script>
</body>
</html>
