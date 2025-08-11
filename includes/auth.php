<?php
defined('BDPS_SYSTEM') or die('Direct access not permitted');
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Auth {
    private static $instance = null;
    private $db;
    private $security;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->security = Security::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($email, $password) {
        try {
            // Check for too many failed attempts
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE email = ? 
                AND success = 0 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$email, LOGIN_TIMEOUT],
                "si"
            );
            
            if ($result['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                throw new Exception('Too many failed attempts. Please try again later.');
            }
            
            // Get user
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE email = ?",
                [$email],
                "s"
            );
            
            if (!$user || !password_verify($password, $user['password'])) {
                // Log failed attempt
                $this->db->execute(
                    "INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)",
                    [$email, $_SERVER['REMOTE_ADDR'], 0],
                    "ssi"
                );
                throw new Exception('Invalid email or password');
            }
            
            // Log successful attempt
            $this->db->execute(
                "INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)",
                [$email, $_SERVER['REMOTE_ADDR'], 1],
                "ssi"
            );
            
            // Check if account is locked
            $locked_until = $user['locked_until'] ?? null;
            if ($locked_until && strtotime($locked_until) > time()) {
                throw new Exception('Account is locked. Try again later.');
            }
            
            // Update user session and login info
            $sessionId = session_id();
            $this->db->execute(
                "UPDATE users SET 
                    session_id = ?,
                    last_login = NOW(),
                    failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = ?",
                [$sessionId, $user['id']],
                "si"
            );
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['full_name'];
            
            return true;
        } catch (Exception $e) {
            // Increment failed attempts if user exists
            if (isset($user)) {
                $current_attempts = (int)($user['failed_login_attempts'] ?? 0);
                $attempts = $current_attempts + 1;
                $lockedUntil = ($attempts >= MAX_LOGIN_ATTEMPTS) ? 
                    date('Y-m-d H:i:s', strtotime('+15 minutes')) : 
                    null;
                
                $this->db->execute(
                    "UPDATE users SET 
                        failed_login_attempts = ?,
                        locked_until = ?
                    WHERE id = ?",
                    [$attempts, $lockedUntil, $user['id']],
                    "ssi"
                );
            }
            
            throw $e;
        }
    }
    
    public function register($data) {
        try {
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Check if email exists
            $exists = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']],
                's'
            );
            
            if ($exists) {
                throw new Exception('Email already registered');
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set default role and verification status
            $data['role'] = 'user';
            $data['is_verified'] = 0;
            
            // Insert user
            $userId = $this->db->insert('users', $data);
            
            // Log registration
            logAudit($userId, 'register', 'users', $userId);
            
            return $userId;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function logout() {
        // Clear session data
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if ($_SESSION['role'] !== $role) {
            header('Location: /dashboard.php');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']],
            'i'
        );
    }
    
    public function initiatePasswordReset($email) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email],
            's'
        );
        
        if (!$user) {
            // Return true anyway to prevent email enumeration
            return true;
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token
        $this->db->insert('password_resets', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires
        ]);
        
        // Send email
        $email = EmailService::getInstance();
        return $email->sendPasswordResetEmail($user, $token);
    }
    
    public function resetPassword($token, $newPassword) {
        // Get valid token
        $reset = $this->db->fetchOne(
            "SELECT * FROM password_resets 
            WHERE token = ? AND expires_at > NOW() 
            ORDER BY created_at DESC LIMIT 1",
            [$token],
            's'
        );
        
        if (!$reset) {
            throw new Exception('Invalid or expired token');
        }
        
        // Update password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users',
            [
                'password' => $hash,
                'last_password_change' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$reset['user_id']],
            'i'
        );
        
        // Delete all reset tokens for this user
        $this->db->delete('password_resets', 
            'user_id = ?',
            [$reset['user_id']],
            'i'
        );
        
        logAudit($reset['user_id'], 'password_reset', 'users', $reset['user_id']);
        return true;
    }

    public function updateProfile($userId, $data) {
        try {
            // Get current user data
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ?",
                [$userId],
                'i'
            );
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Remove protected fields
            unset($data['email']); // Email can't be changed
            unset($data['password']);
            unset($data['role']);
            unset($data['is_verified']);
            
            // Validate data
            if (empty($data['full_name'])) {
                throw new Exception('Name is required');
            }
            
            if (empty($data['address'])) {
                throw new Exception('Address is required');
            }
            
            // Update user
            $this->db->update('users',
                $data,
                'id = ?',
                [$userId],
                'i'
            );
            
            logAudit($userId, 'profile_update', 'users', $userId, $data);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function verifyEmail($token) {
        try {
            $verification = $this->db->fetchOne(
                "SELECT * FROM email_verifications 
                WHERE token = ? AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1",
                [$token],
                's'
            );
            
            if (!$verification) {
                throw new Exception('Invalid or expired verification token');
            }
            
            // Update user verification status
            $this->db->update('users',
                [
                    'email_verified' => 1,
                    'email_verified_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$verification['user_id']],
                'i'
            );
            
            // Delete verification tokens
            $this->db->delete('email_verifications',
                'user_id = ?',
                [$verification['user_id']],
                'i'
            );
            
            logAudit($verification['user_id'], 'email_verified', 'users', $verification['user_id']);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ?",
                [$userId],
                'i'
            );
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }
            
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users',
                [
                    'password' => $hash,
                    'last_password_change' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$userId],
                'i'
            );
            
            logAudit($userId, 'password_change', 'users', $userId);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateUserZone($userId, $zoneId) {
        try {
            // Verify zone exists
            $zone = $this->db->fetchOne(
                "SELECT id FROM zones WHERE id = ?",
                [$zoneId],
                'i'
            );
            
            if (!$zone) {
                throw new Exception('Invalid zone');
            }
            
            $this->db->update('users',
                ['zone_id' => $zoneId],
                'id = ?',
                [$userId],
                'i'
            );
            
            logAudit($userId, 'zone_update', 'users', $userId, ['zone_id' => $zoneId]);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
