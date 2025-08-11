<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Api {
    private static $instance = null;
    private $db;
    private $auth;
    private $document;
    private $analytics;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->document = Document::getInstance();
        $this->analytics = Analytics::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function handleRequest() {
        try {
            // Check API authentication
            $this->authenticateRequest();
            
            // Parse request
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = trim(str_replace('/api', '', $path), '/');
            $segments = explode('/', $path);
            $resource = $segments[0] ?? '';
            
            // Route request
            switch ($resource) {
                case 'auth':
                    $this->handleAuthEndpoints($method, array_slice($segments, 1));
                    break;
                    
                case 'documents':
                    $this->handleDocumentEndpoints($method, array_slice($segments, 1));
                    break;
                    
                case 'users':
                    $this->handleUserEndpoints($method, array_slice($segments, 1));
                    break;
                    
                case 'analytics':
                    $this->handleAnalyticsEndpoints($method, array_slice($segments, 1));
                    break;
                    
                default:
                    throw new Exception('Unknown API endpoint');
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), $e->getCode() ?: 400);
        }
    }
    
    private function authenticateRequest() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if (!$apiKey) {
            throw new Exception('API key required', 401);
        }
        
        // Validate API key (implement your own validation logic)
        if (!$this->isValidApiKey($apiKey)) {
            throw new Exception('Invalid API key', 401);
        }
    }
    
    private function handleAuthEndpoints($method, $segments) {
        $action = $segments[0] ?? '';
        
        switch ($method) {
            case 'POST':
                switch ($action) {
                    case 'login':
                        $data = $this->getRequestData();
                        $token = $this->auth->apiLogin($data['email'], $data['password']);
                        $this->sendResponse(['token' => $token]);
                        break;
                        
                    default:
                        throw new Exception('Unknown auth action');
                }
                break;
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function handleDocumentEndpoints($method, $segments) {
        $action = $segments[0] ?? '';
        
        switch ($method) {
            case 'GET':
                if (empty($action)) {
                    // List documents
                    $filters = $_GET;
                    $documents = $this->document->getDocuments($filters);
                    $this->sendResponse($documents);
                } else {
                    // Get specific document
                    $document = $this->document->getDocument($action);
                    $this->sendResponse($document);
                }
                break;
                
            case 'POST':
                $data = $this->getRequestData();
                switch ($action) {
                    case 'request':
                        $requestId = $this->document->createRequest(
                            $this->getCurrentUserId(),
                            $data
                        );
                        $this->sendResponse(['request_id' => $requestId]);
                        break;
                        
                    case 'upload':
                        if (empty($_FILES['document'])) {
                            throw new Exception('No file uploaded');
                        }
                        $filename = $this->document->uploadDocument(
                            $data['request_id'],
                            $this->getCurrentUserId(),
                            $_FILES['document']
                        );
                        $this->sendResponse(['filename' => $filename]);
                        break;
                        
                    default:
                        throw new Exception('Unknown document action');
                }
                break;
                
            case 'PUT':
                if (empty($action)) {
                    throw new Exception('Document ID required');
                }
                $data = $this->getRequestData();
                $this->document->updateRequest($action, $this->getCurrentUserId(), $data);
                $this->sendResponse(['success' => true]);
                break;
                
            case 'DELETE':
                if (empty($action)) {
                    throw new Exception('Document ID required');
                }
                $this->document->cancelRequest($action, $this->getCurrentUserId());
                $this->sendResponse(['success' => true]);
                break;
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function handleUserEndpoints($method, $segments) {
        $action = $segments[0] ?? '';
        
        switch ($method) {
            case 'GET':
                if ($action === 'profile') {
                    $user = $this->auth->getCurrentUser();
                    unset($user['password']); // Remove sensitive data
                    $this->sendResponse($user);
                } else {
                    throw new Exception('Unknown user action');
                }
                break;
                
            case 'PUT':
                if ($action === 'profile') {
                    $data = $this->getRequestData();
                    $this->auth->updateProfile($this->getCurrentUserId(), $data);
                    $this->sendResponse(['success' => true]);
                } else {
                    throw new Exception('Unknown user action');
                }
                break;
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function handleAnalyticsEndpoints($method, $segments) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }
        
        $action = $segments[0] ?? '';
        
        switch ($action) {
            case 'dashboard':
                $stats = $this->analytics->getDashboardStats();
                $this->sendResponse($stats);
                break;
                
            case 'audit':
                $filters = $_GET;
                $logs = $this->analytics->getAuditLog($filters);
                $this->sendResponse($logs);
                break;
                
            case 'requests':
                $filters = $_GET;
                $analytics = $this->analytics->getRequestAnalytics($filters);
                $this->sendResponse($analytics);
                break;
                
            default:
                throw new Exception('Unknown analytics action');
        }
    }
    
    private function getRequestData() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON payload');
        }
        
        return $data;
    }
    
    private function getCurrentUserId() {
        // Implement your own logic to get user ID from API token
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$token) {
            throw new Exception('Authorization required', 401);
        }
        
        // Validate token and get user ID
        $userId = $this->validateToken($token);
        if (!$userId) {
            throw new Exception('Invalid token', 401);
        }
        
        return $userId;
    }
    
    private function isValidApiKey($apiKey) {
        // Implement your own API key validation logic
        // For example, check against a database of valid API keys
        return true; // Temporary
    }
    
    private function validateToken($token) {
        // Implement your own token validation logic
        // For example, verify JWT token and extract user ID
        return null; // Temporary
    }
    
    private function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    private function sendError($message, $status = 400) {
        http_response_code($status);
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
}
