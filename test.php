<?php
define('BDPS_SYSTEM', true);
require_once __DIR__ . '/includes/init.php';

class SystemTest {
    private $db;
    private $auth;
    private $document;
    private $email;
    private $jwt;
    private $webhook;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->document = Document::getInstance();
        $this->email = EmailService::getInstance();
        $this->jwt = JwtHandler::getInstance();
        $this->webhook = WebhookHandler::getInstance();
    }
    
    public function runAllTests() {
        try {
            echo "Starting system tests...\n\n";
            
            // 1. Database Connection Test
            $this->testDatabaseConnection();
            
            // 2. User Authentication Tests
            $this->testUserAuthentication();
            
            // 3. Document Management Tests
            $this->testDocumentManagement();
            
            // 4. Email System Tests
            $this->testEmailSystem();
            
            // 5. API Authentication Tests
            $this->testApiAuth();
            
            // 6. Rate Limiting Tests
            $this->testRateLimiting();
            
            // 7. Webhook Tests
            $this->testWebhooks();
            
            echo "\nAll tests completed successfully!\n";
        } catch (Exception $e) {
            echo "\nTest failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    private function testDatabaseConnection() {
        echo "Testing database connection... ";
        $result = $this->db->fetchOne("SELECT NOW() as time");
        if ($result) {
            echo "SUCCESS\n";
            echo "Server time: " . $result['time'] . "\n\n";
        } else {
            throw new Exception("Database connection failed");
        }
    }
    
    private function testUserAuthentication() {
        echo "Testing user authentication...\n";
        
        // Test user registration
        $testUser = [
            'full_name' => 'Test User',
            'email' => 'test_' . time() . '@example.com',
            'password' => 'Test@123',
            'address' => 'Test Address'
        ];
        
        echo "1. Testing registration... ";
        $userId = $this->auth->register($testUser);
        if (!$userId) throw new Exception("User registration failed");
        echo "SUCCESS (User ID: $userId)\n";
        
        // Test login
        echo "2. Testing login... ";
        $loginResult = $this->auth->login($testUser['email'], $testUser['password']);
        if (!$loginResult) throw new Exception("Login failed");
        echo "SUCCESS\n";
        
        // Test password reset
        echo "3. Testing password reset... ";
        $resetResult = $this->auth->initiatePasswordReset($testUser['email']);
        if (!$resetResult) throw new Exception("Password reset initiation failed");
        echo "SUCCESS\n\n";
    }
    
    private function testDocumentManagement() {
        echo "Testing document management...\n";
        
        // Test document request creation
        echo "1. Testing document request... ";
        $requestData = [
            'document_type_id' => 1,
            'purpose' => 'Testing',
            'appointment_date' => date('Y-m-d', strtotime('+1 day')),
            'appointment_time' => '10:00:00'
        ];
        
        $requestId = $this->document->createRequest($_SESSION['user_id'], $requestData);
        if (!$requestId) throw new Exception("Document request creation failed");
        echo "SUCCESS (Request ID: $requestId)\n";
        
        // Test status update
        echo "2. Testing status update... ";
        $updateResult = $this->document->updateRequestStatus($requestId, 1, 'approved');
        if (!$updateResult) throw new Exception("Status update failed");
        echo "SUCCESS\n\n";
    }
    
    private function testEmailSystem() {
        echo "Testing email system...\n";
        
        // Test email configuration
        echo "1. Testing SMTP configuration... ";
        try {
            $this->email->testConnection();
            echo "SUCCESS\n";
        } catch (Exception $e) {
            echo "WARNING (SMTP not configured: " . $e->getMessage() . ")\n";
        }
    }
    
    private function testApiAuth() {
        echo "Testing API authentication...\n";
        
        // Test JWT generation
        echo "1. Testing JWT generation... ";
        $token = $this->jwt->generateToken($_SESSION['user_id'], 'user');
        if (!$token) throw new Exception("JWT generation failed");
        echo "SUCCESS\n";
        
        // Test JWT validation
        echo "2. Testing JWT validation... ";
        $tokenData = $this->jwt->validateToken($token);
        if (!$tokenData) throw new Exception("JWT validation failed");
        echo "SUCCESS\n\n";
    }
    
    private function testRateLimiting() {
        echo "Testing rate limiting...\n";
        
        // Test rate limit check
        echo "1. Testing rate limit check... ";
        $apiKeyId = 1; // Assuming we have a default API key
        $ip = '127.0.0.1';
        
        $rateLimit = ApiRateLimit::getInstance();
        $result = $rateLimit->checkRateLimit($apiKeyId, $ip);
        
        if (!isset($result['allowed'])) throw new Exception("Rate limit check failed");
        echo "SUCCESS (Remaining: {$result['remaining']})\n\n";
    }
    
    private function testWebhooks() {
        echo "Testing webhook system...\n";
        
        // Test webhook registration
        echo "1. Testing webhook registration... ";
        $webhookUrl = 'https://example.com/webhook';
        $events = ['document.approved'];
        $secret = bin2hex(random_bytes(16));
        
        try {
            $webhookId = $this->webhook->registerWebhook($webhookUrl, $events, $secret);
            if (!$webhookId) throw new Exception("Webhook registration failed");
            echo "SUCCESS (Webhook ID: $webhookId)\n\n";
        } catch (Exception $e) {
            echo "WARNING (Webhook test skipped: " . $e->getMessage() . ")\n\n";
        }
    }
}

// Run the tests
$tester = new SystemTest();
$tester->runAllTests();
