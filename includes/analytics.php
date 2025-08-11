<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Analytics {
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
    
    public function getDashboardStats($zoneId = null) {
        $stats = [];
        $zoneFilter = $zoneId ? "AND u.zone_id = " . intval($zoneId) : "";
        
        // User statistics
        $stats['users'] = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as pending
            FROM users u 
            WHERE role = 'user' {$zoneFilter}"
        );
        
        // Request statistics
        $stats['requests'] = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE 1=1 {$zoneFilter}"
        );
        
        // Document type distribution
        $stats['document_types'] = $this->db->fetchAll(
            "SELECT 
                dt.title,
                COUNT(*) as count
            FROM requests r 
            JOIN document_types dt ON r.document_type_id = dt.id
            JOIN users u ON r.user_id = u.id 
            WHERE 1=1 {$zoneFilter}
            GROUP BY dt.id
            ORDER BY count DESC"
        );
        
        // Monthly trends
        $stats['monthly_trends'] = $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(r.created_at, '%Y-%m') as month,
                COUNT(*) as total_requests,
                SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_requests
            FROM requests r
            JOIN users u ON r.user_id = u.id 
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) {$zoneFilter}
            GROUP BY month
            ORDER BY month ASC"
        );
        
        // Processing times
        $stats['processing_times'] = $this->db->fetchOne(
            "SELECT 
                AVG(TIMESTAMPDIFF(HOUR, r.created_at, 
                    CASE WHEN r.status = 'completed' 
                        THEN r.updated_at 
                        ELSE NULL 
                    END)) as avg_completion_hours,
                AVG(CASE WHEN r.status IN ('approved', 'completed') 
                    THEN TIMESTAMPDIFF(HOUR, r.created_at, r.updated_at)
                    ELSE NULL 
                END) as avg_approval_hours
            FROM requests r
            JOIN users u ON r.user_id = u.id 
            WHERE 1=1 {$zoneFilter}"
        );
        
        return $stats;
    }
    
    public function getAuditLog($filters = [], $page = 1, $limit = 50) {
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['action'])) {
            $where[] = "action = ?";
            $params[] = $filters['action'];
            $types .= 's';
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $offset = ($page - 1) * $limit;
        
        $logs = $this->db->fetchAll(
            "SELECT a.*, u.full_name as user_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            {$whereClause}
            ORDER BY a.created_at DESC
            LIMIT ?, ?",
            array_merge($params, [$offset, $limit]),
            $types . 'ii'
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM audit_logs {$whereClause}",
            $params,
            $types
        )['total'];
        
        return [
            'logs' => $logs,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function getRequestAnalytics($filters = []) {
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['zone_id'])) {
            $where[] = "u.zone_id = ?";
            $params[] = $filters['zone_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['document_type_id'])) {
            $where[] = "r.document_type_id = ?";
            $params[] = $filters['document_type_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['status'])) {
            $where[] = "r.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        return $this->db->fetchAll(
            "SELECT 
                z.name as zone_name,
                dt.title as document_type,
                r.status,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(HOUR, r.created_at, r.updated_at)) as avg_processing_hours
            FROM requests r
            JOIN users u ON r.user_id = u.id
            JOIN document_types dt ON r.document_type_id = dt.id
            LEFT JOIN zones z ON u.zone_id = z.id
            {$whereClause}
            GROUP BY z.id, dt.id, r.status
            ORDER BY count DESC",
            $params,
            $types
        );
    }
}
