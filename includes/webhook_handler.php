<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class WebhookHandler {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function registerWebhook($url, $events, $secret) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid webhook URL');
        }
        
        // Validate events
        $validEvents = [
            'document.requested',
            'document.approved',
            'document.cancelled',
            'document.completed',
            'user.verified'
        ];
        
        foreach ($events as $event) {
            if (!in_array($event, $validEvents)) {
                throw new Exception('Invalid event type: ' . $event);
            }
        }
        
        return $this->db->insert('webhooks', [
            'url' => $url,
            'events' => json_encode($events),
            'secret' => $secret
        ]);
    }
    
    public function dispatchEvent($event, $data) {
        // Get all webhooks subscribed to this event
        $webhooks = $this->db->fetchAll(
            "SELECT * FROM webhooks WHERE JSON_CONTAINS(events, ?)",
            [json_encode($event)],
            's'
        );
        
        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $data);
        }
    }
    
    private function sendWebhook($webhook, $event, $data) {
        $payload = json_encode([
            'event' => $event,
            'timestamp' => date('c'),
            'data' => $data
        ]);
        
        // Generate signature
        $signature = hash_hmac('sha256', $payload, $webhook['secret']);
        
        // Send webhook asynchronously
        $cmd = 'curl -X POST ' . escapeshellarg($webhook['url']) . 
               ' -H ' . escapeshellarg('Content-Type: application/json') .
               ' -H ' . escapeshellarg('X-Webhook-Signature: ' . $signature) .
               ' -d ' . escapeshellarg($payload) . 
               ' > /dev/null 2>&1 &';
        
        exec($cmd);
        
        // Log webhook attempt
        $this->logWebhook($webhook['id'], $event, $payload, $signature);
    }
    
    private function logWebhook($webhookId, $event, $payload, $signature) {
        $this->db->insert('webhook_logs', [
            'webhook_id' => $webhookId,
            'event' => $event,
            'payload' => $payload,
            'signature' => $signature
        ]);
    }
    
    public function validateWebhook($url, $payload, $signature, $secret) {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
