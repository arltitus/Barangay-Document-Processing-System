<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

class Document {
    private static $instance = null;
    private $db;
    private $security;
    private $notification;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->security = Security::getInstance();
        $this->notification = Notification::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function createRequest($userId, $data) {
        try {
            // Validate required fields
            if (empty($data['document_type_id']) || empty($data['purpose'])) {
                throw new Exception('Document type and purpose are required');
            }
            
            // Validate appointment date/time
            if (!empty($data['appointment_date'])) {
                $appointmentDate = strtotime($data['appointment_date']);
                if ($appointmentDate < strtotime('today')) {
                    throw new Exception('Appointment date cannot be in the past');
                }
            }
            
            // Get user details
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ?",
                [$userId],
                'i'
            );
            
            if (!$user['is_verified']) {
                throw new Exception('Your account must be verified before requesting documents');
            }
            
            // Create request
            $requestData = [
                'user_id' => $userId,
                'document_type_id' => $data['document_type_id'],
                'purpose' => $data['purpose'],
                'appointment_date' => $data['appointment_date'] ?? null,
                'appointment_time' => $data['appointment_time'] ?? null,
                'status' => 'pending'
            ];
            
            $requestId = $this->db->insert('requests', $requestData);
            
            // Notify admin of new request
            $this->notification->notifyAdminNewRequest($requestId);
            
            logAudit($userId, 'create_request', 'requests', $requestId, $requestData);
            return $requestId;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function updateRequestStatus($requestId, $adminId, $status, $notes = null) {
        try {
            $request = $this->db->fetchOne(
                "SELECT * FROM requests WHERE id = ?",
                [$requestId],
                'i'
            );
            
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            $data = [
                'status' => $status,
                'admin_notes' => $notes
            ];
            
            $this->db->update('requests',
                $data,
                'id = ?',
                [$requestId],
                'i'
            );
            
            // Send notification
            $this->notification->sendAppointmentNotification($requestId, $status);
            
            logAudit($adminId, 'update_request_status', 'requests', $requestId, $data);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function uploadDocument($requestId, $adminId, $file) {
        try {
            $request = $this->db->fetchOne(
                "SELECT * FROM requests WHERE id = ?",
                [$requestId],
                'i'
            );
            
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Validate and upload file
            $filename = $this->security->secureUpload($file, 'doc_');
            
            // Update request with document
            $this->db->update('requests',
                [
                    'softcopy_filename' => $filename,
                    'status' => 'completed'
                ],
                'id = ?',
                [$requestId],
                'i'
            );
            
            // Notify user
            $this->notification->sendDocumentReady($request['user_id'], $requestId);
            
            logAudit($adminId, 'upload_document', 'requests', $requestId, ['filename' => $filename]);
            return $filename;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function cancelRequest($requestId, $userId, $reason = null) {
        try {
            $request = $this->db->fetchOne(
                "SELECT * FROM requests WHERE id = ? AND user_id = ? AND status = 'pending'",
                [$requestId, $userId],
                'ii'
            );
            
            if (!$request) {
                throw new Exception('Request not found or cannot be cancelled');
            }
            
            $this->db->update('requests',
                [
                    'status' => 'cancelled',
                    'admin_notes' => $reason
                ],
                'id = ?',
                [$requestId],
                'i'
            );
            
            logAudit($userId, 'cancel_request', 'requests', $requestId, ['reason' => $reason]);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function rescheduleRequest($requestId, $userId, $newDate, $newTime) {
        try {
            $request = $this->db->fetchOne(
                "SELECT * FROM requests WHERE id = ? AND user_id = ? AND status = 'pending'",
                [$requestId, $userId],
                'ii'
            );
            
            if (!$request) {
                throw new Exception('Request not found or cannot be rescheduled');
            }
            
            // Validate new date
            $newDatetime = strtotime($newDate . ' ' . $newTime);
            if ($newDatetime < time()) {
                throw new Exception('New appointment time must be in the future');
            }
            
            $this->db->update('requests',
                [
                    'appointment_date' => $newDate,
                    'appointment_time' => $newTime
                ],
                'id = ?',
                [$requestId],
                'i'
            );
            
            logAudit($userId, 'reschedule_request', 'requests', $requestId, [
                'new_date' => $newDate,
                'new_time' => $newTime
            ]);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function generateDocument($requestId, $templateId) {
        try {
            // Get request details
            $request = $this->db->fetchOne(
                "SELECT r.*, u.*, dt.* 
                FROM requests r 
                JOIN users u ON r.user_id = u.id 
                JOIN document_types dt ON r.document_type_id = dt.id 
                WHERE r.id = ?",
                [$requestId],
                'i'
            );
            
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Get template
            $template = $this->db->fetchOne(
                "SELECT * FROM document_templates WHERE id = ?",
                [$templateId],
                'i'
            );
            
            if (!$template) {
                throw new Exception('Template not found');
            }
            
            // Replace variables in template
            $content = $template['content'];
            $variables = json_decode($template['variables'], true);
            
            foreach ($variables as $var) {
                $value = $request[$var] ?? '';
                $content = str_replace('{{' . $var . '}}', $value, $content);
            }
            
            // Generate PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($content);
            $dompdf->render();
            
            // Save PDF
            $filename = 'doc_' . time() . '_' . $requestId . '.pdf';
            file_put_contents(DOCUMENT_DIR . $filename, $dompdf->output());
            
            return $filename;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
