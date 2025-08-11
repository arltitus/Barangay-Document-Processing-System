<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Security {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // CSRF Protection
    public function generateCSRFToken() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public function validateCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    // Rate Limiting
    public function checkRateLimit($ip, $action = 'login') {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts, MIN(timestamp) as first_attempt 
                                    FROM login_attempts 
                                    WHERE ip_address = ? AND action = ? 
                                    AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->bind_param('ssi', $ip, $action, LOGIN_TIMEOUT);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $timeLeft = LOGIN_TIMEOUT - (time() - strtotime($result['first_attempt']));
            if ($timeLeft > 0) {
                throw new Exception("Too many attempts. Please try again in " . ceil($timeLeft/60) . " minutes.");
            }
            // Clear old attempts if timeout has passed
            $this->clearRateLimit($ip, $action);
        }
    }

    public function logAttempt($ip, $action = 'login', $success = false) {
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (ip_address, action, success) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $ip, $action, $success);
        $stmt->execute();
        
        if ($success) {
            $this->clearRateLimit($ip, $action);
        }
    }

    private function clearRateLimit($ip, $action) {
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND action = ?");
        $stmt->bind_param('ss', $ip, $action);
        $stmt->execute();
    }

    // File Upload Security
    public function validateUpload($file, $allowedTypes = null) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameters.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $file['error']);
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('File size exceeds limit of ' . (MAX_UPLOAD_SIZE/1024/1024) . 'MB');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedTypes = $allowedTypes ?? ALLOWED_MIME_TYPES;
        if (!isset($allowedTypes[$mimeType])) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', array_keys($allowedTypes)));
        }

        return [
            'mime_type' => $mimeType,
            'extension' => $allowedTypes[$mimeType]
        ];
    }

    public function secureUpload($file, $prefix = '', $dir = null) {
        $fileInfo = $this->validateUpload($file);
        $dir = $dir ?? UPLOAD_DIR;
        
        // Generate unique filename
        $filename = $prefix . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileInfo['extension'];
        $filepath = $dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file.');
        }
        
        return $filename;
    }

    // XSS Protection
    public function sanitizeOutput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeOutput($value);
            }
            return $data;
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // Session Security
    public function secureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            ini_set('session.use_strict_mode', 1);
            
            session_name(SESSION_NAME);
            session_start();
        }
        
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > SESSION_LIFETIME) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }

    public function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
    }
}

// Initialize security
$security = Security::getInstance();
$security->secureSession();
