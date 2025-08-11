<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class ApiRateLimit {
    private static $instance = null;
    private $db;
    private $redis;
    
    private function __construct() {
        $this->db = Database::getInstance();
        // Initialize Redis connection if available
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
            } catch (Exception $e) {
                logError('Redis connection failed: ' . $e->getMessage());
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function checkRateLimit($apiKeyId, $ip) {
        // Get API key details
        $apiKey = $this->db->fetchOne(
            "SELECT * FROM api_keys WHERE id = ?",
            [$apiKeyId],
            'i'
        );
        
        if (!$apiKey) {
            throw new Exception('Invalid API key');
        }
        
        $rateLimit = $apiKey['rate_limit'];
        $window = API_RATE_WINDOW;
        
        // Check rate limit using Redis if available
        if ($this->redis) {
            return $this->checkRedisRateLimit($apiKeyId, $ip, $rateLimit, $window);
        }
        
        // Fallback to database rate limiting
        return $this->checkDatabaseRateLimit($apiKeyId, $ip, $rateLimit, $window);
    }
    
    private function checkRedisRateLimit($apiKeyId, $ip, $rateLimit, $window) {
        $key = "ratelimit:{$apiKeyId}:{$ip}";
        $current = $this->redis->get($key);
        
        if (!$current) {
            $this->redis->setex($key, $window, 1);
            return [
                'allowed' => true,
                'remaining' => $rateLimit - 1,
                'reset' => time() + $window
            ];
        }
        
        if ($current >= $rateLimit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset' => time() + $this->redis->ttl($key)
            ];
        }
        
        $this->redis->incr($key);
        return [
            'allowed' => true,
            'remaining' => $rateLimit - $current - 1,
            'reset' => time() + $this->redis->ttl($key)
        ];
    }
    
    private function checkDatabaseRateLimit($apiKeyId, $ip, $rateLimit, $window) {
        $startTime = date('Y-m-d H:i:s', time() - $window);
        
        $count = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
            FROM api_request_logs 
            WHERE api_key_id = ? 
            AND ip_address = ? 
            AND created_at > ?",
            [$apiKeyId, $ip, $startTime],
            'iss'
        )['count'];
        
        return [
            'allowed' => $count < $rateLimit,
            'remaining' => max(0, $rateLimit - $count),
            'reset' => strtotime($startTime) + $window
        ];
    }
    
    public function logRequest($apiKeyId, $userId, $endpoint, $method, $ip, $requestData, $responseCode, $responseTime) {
        $this->db->insert('api_request_logs', [
            'api_key_id' => $apiKeyId,
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $ip,
            'request_data' => is_array($requestData) ? json_encode($requestData) : $requestData,
            'response_code' => $responseCode,
            'response_time' => $responseTime
        ]);
        
        // Update API key last used timestamp
        $this->db->update('api_keys',
            ['last_used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$apiKeyId],
            'i'
        );
    }
    
    public function cleanupOldLogs($days = 30) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->delete('api_request_logs',
            'created_at < ?',
            [$cutoff],
            's'
        );
    }
}
