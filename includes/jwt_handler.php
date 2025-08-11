<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler {
    private static $instance = null;
    private $secretKey;
    private $algorithm;
    private $db;
    
    private function __construct() {
        $this->secretKey = JWT_SECRET_KEY;
        $this->algorithm = 'HS256';
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function generateToken($userId, $role) {
        $issuedAt = time();
        $expire = $issuedAt + JWT_EXPIRY;
        $tokenId = bin2hex(random_bytes(16));
        
        $payload = [
            'iss' => APP_URL, // issuer
            'aud' => APP_URL, // audience
            'iat' => $issuedAt, // issued at
            'nbf' => $issuedAt, // not before
            'exp' => $expire, // expire
            'jti' => $tokenId, // JWT ID
            'data' => [
                'user_id' => $userId,
                'role' => $role
            ]
        ];
        
        // Store token in database for invalidation capability
        $this->db->insert('api_tokens', [
            'token_id' => $tokenId,
            'user_id' => $userId,
            'expires_at' => date('Y-m-d H:i:s', $expire),
            'is_revoked' => 0
        ]);
        
        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
    
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            // Check if token is revoked
            $tokenId = $decoded->jti;
            $token = $this->db->fetchOne(
                "SELECT is_revoked FROM api_tokens WHERE token_id = ?",
                [$tokenId],
                's'
            );
            
            if (!$token || $token['is_revoked']) {
                throw new Exception('Token is revoked');
            }
            
            return $decoded->data;
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }
    
    public function revokeToken($tokenId) {
        return $this->db->update('api_tokens',
            ['is_revoked' => 1],
            'token_id = ?',
            [$tokenId],
            's'
        );
    }
    
    public function revokeAllUserTokens($userId) {
        return $this->db->update('api_tokens',
            ['is_revoked' => 1],
            'user_id = ?',
            [$userId],
            'i'
        );
    }
    
    public function cleanExpiredTokens() {
        return $this->db->delete('api_tokens',
            'expires_at < NOW()'
        );
    }
}
