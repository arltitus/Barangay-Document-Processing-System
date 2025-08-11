<?php
// Only define BDPS_SYSTEM if not already defined
if (!defined('BDPS_SYSTEM')) {
    define('BDPS_SYSTEM', true);
}
require_once 'includes/init.php';

// Redirect if already logged in
if (Auth::getInstance()->isLoggedIn()) {
    // Check user role and redirect accordingly
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!$security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request');
        }
        
        // Get email for the query
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // First check if this is an admin account
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Attempt login
        Auth::getInstance()->login($email, $password);
        
        // After successful login, check role and redirect
        if (isset($user['role']) && $user['role'] === 'admin') {
            $_SESSION['role'] = 'admin';
            header('Location: admin_dashboard.php');
        } else {
            $_SESSION['role'] = 'user';
            header('Location: dashboard.php');
        }
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
    <title>Login - Barangay Document System</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="login-page">
        <nav class="navbar">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">BDPS</a>
            </div>
        </nav>

        <div class="login-container">
            <div class="login-card card">
                <div class="auth-header">
                    <img src="images/logo.png" alt="BDPS Logo" class="auth-logo">
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to continue to your account</p>
                </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful! Please check your email for verification.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['verified'])): ?>
                <div class="alert alert-success">Email verified! You can now log in.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['password_reset'])): ?>
                <div class="alert alert-success">Password has been reset successfully.</div>
            <?php endif; ?>
            
            <form method="post" id="loginForm" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="email" name="email" required 
                               class="form-control"
                               placeholder="Enter your email address"
                               autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="password-label">
                        <label for="password" class="form-label">Password</label>
                        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    <div class="input-group">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password" required 
                               class="form-control"
                               placeholder="Enter your password"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <span>Sign In</span>
                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="link-primary">Create an Account</a></p>
            </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        
        if (!email || !password) {
            e.preventDefault();
            showError('Please fill in all fields');
            return;
        }
        
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            showError('Please enter a valid email address');
            return;
        }

        // Show loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.querySelector('.eye-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            `;
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        }
    }

    function showError(message) {
        const alertBox = document.createElement('div');
        alertBox.className = 'alert alert-danger slide-in';
        alertBox.textContent = message;

        const form = document.getElementById('loginForm');
        form.insertBefore(alertBox, form.firstChild);

        setTimeout(() => {
            alertBox.classList.add('fade-out');
            setTimeout(() => alertBox.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>
