<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Notification {
    private static $instance = null;
    private $db;
    private $email;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->email = EmailService::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function sendVerificationEmail($userId) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$userId],
            'i'
        );
        
        if (!$user) {
            return false;
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $this->db->insert('email_verifications', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires
        ]);
        
        return $this->email->sendVerificationEmail($user, $token);
    }
    
    public function sendAppointmentNotification($requestId, $status) {
        $request = $this->db->fetchOne(
            "SELECT r.*, u.*, dt.title as document_title 
             FROM requests r 
             JOIN users u ON r.user_id = u.id 
             JOIN document_types dt ON r.document_type_id = dt.id 
             WHERE r.id = ?",
            [$requestId],
            'i'
        );
        
        if (!$request) {
            return false;
        }
        
        switch ($status) {
            case 'approved':
                return $this->email->sendAppointmentConfirmation($request['user_id'], $request);
            case 'cancelled':
                return $this->email->sendAppointmentCancellation($request['user_id'], $request);
            case 'completed':
                if ($request['softcopy_filename']) {
                    return $this->email->sendDocumentReady($request['user_id'], $request);
                }
                break;
        }
        
        return false;
    }
    
    public function notifyAdminNewRequest($requestId) {
        $request = $this->db->fetchOne(
            "SELECT r.*, u.full_name, dt.title 
             FROM requests r 
             JOIN users u ON r.user_id = u.id 
             JOIN document_types dt ON r.document_type_id = dt.id 
             WHERE r.id = ?",
            [$requestId],
            'i'
        );
        
        if (!$request) {
            return false;
        }
        
        // Get all admin emails
        $admins = $this->db->fetchAll(
            "SELECT email FROM users WHERE role = 'admin'"
        );
        
        foreach ($admins as $admin) {
            $this->email->sendNewRequestNotification($admin['email'], $request);
        }
        
        return true;
    }
    
    public function notifyUserVerified($userId) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$userId],
            'i'
        );
        
        if (!$user) {
            return false;
        }
        
        return $this->email->sendVerificationConfirmation($user);
    }
}
