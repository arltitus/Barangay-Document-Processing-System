<?php
if (!defined('BDPS_SYSTEM')) {
    define('BDPS_SYSTEM', true);
}
require_once 'includes/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
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

        // Validate input
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $address = trim($_POST['address'] ?? '');

        if (empty($full_name)) throw new Exception('Full name is required');
        if (empty($email)) throw new Exception('Email is required');
        if (empty($password)) throw new Exception('Password is required');
        if (empty($address)) throw new Exception('Address is required');

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        // Check for email duplicates
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('This email is already registered');
        }

        // Handle ID upload
        $id_filename = null;
        if (isset($_FILES['id_file']) && $_FILES['id_file']['error'] === 0) {
            $allowed = ['image/png', 'image/jpeg', 'application/pdf'];
            if (!in_array($_FILES['id_file']['type'], $allowed)) {
                throw new Exception('Invalid ID file type. Use PNG/JPG/PDF');
            }

            $target = UPLOADS_DIR . time() . '_' . basename($_FILES['id_file']['name']);
            if (!move_uploaded_file($_FILES['id_file']['tmp_name'], $target)) {
                throw new Exception('Failed to upload ID file');
            }
            $id_filename = basename($target);
        }

        // Create user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO users (full_name, email, password, address, id_filename, user_type) 
             VALUES (?, ?, ?, ?, ?, 'resident')"
        );
        $stmt->bind_param("sssss", $full_name, $email, $hash, $address, $id_filename);
        
        if (!$stmt->execute()) {
            throw new Exception('Registration failed. Please try again.');
        }

        // Registration successful
        header('Location: login.php?registered=1');
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Barangay Document System</title>
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="register-page">
        <nav class="navbar">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">BDPS</a>
                <div class="navbar-links">
                    <a href="login.php" class="btn btn-outline">Sign In</a>
                </div>
            </div>
        </nav>

        <div class="register-container">
            <div class="register-card card">
                <div class="auth-header">
                    <img src="images/logo.png" alt="BDPS Logo" class="auth-logo">
                    <h1 class="auth-title">Create an Account</h1>
                    <p class="auth-subtitle">Register to access barangay document services</p>
                </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" id="registerForm" class="auth-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input type="text" id="full_name" name="full_name" required 
                                   class="form-control"
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                   placeholder="Enter your full name">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" name="email" required 
                                   class="form-control"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="Enter your email address">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="password" name="password" required 
                                   class="form-control"
                                   placeholder="Create a password">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-meter"></div>
                            <small class="strength-text">Password must be at least 8 characters</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Complete Address</label>
                        <div class="input-group">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <textarea id="address" name="address" required 
                                    class="form-control"
                                    placeholder="Enter your complete address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="id_file" class="form-label">Valid ID</label>
                        <div class="file-upload">
                            <input type="file" id="id_file" name="id_file" 
                                   class="file-input"
                                   accept=".png,.jpg,.jpeg,.pdf"
                                   onchange="updateFileLabel(this)">
                            <div class="upload-content">
                                <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <span id="fileLabel">Upload your valid ID (PNG, JPG, or PDF)</span>
                            </div>
                        </div>
                        <small class="form-text text-secondary">Required for identity verification</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <span>Create Account</span>
                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="link-primary">Sign In</a></p>
            </div>
            </div>
        </div>
    </div>

    <script>
    const passwordInput = document.getElementById('password');
    const strengthMeter = document.querySelector('.strength-meter');
    const strengthText = document.querySelector('.strength-text');

    passwordInput.addEventListener('input', updatePasswordStrength);

    function updatePasswordStrength() {
        const password = passwordInput.value;
        let strength = 0;
        let feedback = 'Password must be at least 8 characters';

        if (password.length >= 8) {
            strength += 25;
            feedback = 'Password is weak';
            
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                strength += 25;
                feedback = 'Password is medium';
            }
            
            if (password.match(/\d/)) {
                strength += 25;
                feedback = 'Password is strong';
            }
            
            if (password.match(/[!@#$%^&*]/)) {
                strength += 25;
                feedback = 'Password is very strong';
            }
        }

        strengthMeter.style.width = strength + '%';
        strengthText.textContent = feedback;

        // Update color based on strength
        if (strength <= 25) {
            strengthMeter.style.backgroundColor = '#dc2626';
        } else if (strength <= 50) {
            strengthMeter.style.backgroundColor = '#d97706';
        } else if (strength <= 75) {
            strengthMeter.style.backgroundColor = '#059669';
        } else {
            strengthMeter.style.backgroundColor = '#16a34a';
        }
    }

    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const fullName = document.getElementById('full_name').value.trim();
        const address = document.getElementById('address').value.trim();
        const idFile = document.getElementById('id_file').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        
        let hasError = false;
        
        if (!email || !password || !fullName || !address) {
            showError('Please fill in all required fields');
            hasError = true;
        }
        
        if (!hasError && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showError('Please enter a valid email address');
            hasError = true;
        }

        if (!hasError && password.length < 8) {
            showError('Password must be at least 8 characters long');
            hasError = true;
        }

        if (!hasError && !idFile) {
            showError('Please upload a valid ID');
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
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

    function updateFileLabel(input) {
        const label = document.getElementById('fileLabel');
        if (input.files.length > 0) {
            label.textContent = input.files[0].name;
        } else {
            label.textContent = 'Upload your valid ID (PNG, JPG, or PDF)';
        }
    }

    function showError(message) {
        const alertBox = document.createElement('div');
        alertBox.className = 'alert alert-danger slide-in';
        alertBox.textContent = message;

        const form = document.getElementById('registerForm');
        form.insertBefore(alertBox, form.firstChild);

        setTimeout(() => {
            alertBox.classList.add('fade-out');
            setTimeout(() => alertBox.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>
</body></html>
