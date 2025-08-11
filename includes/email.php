<?php
if(!defined('BDPS_SYSTEM')) exit('Direct access denied');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private static $instance = null;
    private $mailer;
    
    private function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USER;
        $this->mailer->Password = SMTP_PASS;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = SMTP_PORT;
        
        // Default settings
        $this->mailer->isHTML(true);
        $this->mailer->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function send($to, $subject, $body, $altBody = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags($body);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: {$e->getMessage()}");
            return false;
        }
    }
    
    public function sendVerificationEmail($user, $token) {
        $subject = 'Verify Your Email - Barangay Document System';
        $url = 'https://' . $_SERVER['HTTP_HOST'] . '/verify_email.php?token=' . urlencode($token);
        
        $body = <<<HTML
        <h2>Email Verification</h2>
        <p>Dear {$user['full_name']},</p>
        <p>Thank you for registering with the Barangay Document System. Please click the link below to verify your email address:</p>
        <p><a href="{$url}">{$url}</a></p>
        <p>This link will expire in 24 hours.</p>
        <p>If you did not create an account, please ignore this email.</p>
        HTML;
        
        return $this->send($user['email'], $subject, $body);
    }
    
    public function sendPasswordResetEmail($user, $token) {
        $subject = 'Password Reset - Barangay Document System';
        $url = 'https://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . urlencode($token);
        
        $body = <<<HTML
        <h2>Password Reset Request</h2>
        <p>Dear {$user['full_name']},</p>
        <p>You have requested to reset your password. Click the link below to set a new password:</p>
        <p><a href="{$url}">{$url}</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request a password reset, please ignore this email.</p>
        HTML;
        
        return $this->send($user['email'], $subject, $body);
    }
    
    public function sendAppointmentConfirmation($user, $request) {
        $subject = 'Appointment Confirmed - Barangay Document System';
        
        $body = <<<HTML
        <h2>Appointment Confirmation</h2>
        <p>Dear {$user['full_name']},</p>
        <p>Your appointment request has been confirmed:</p>
        <ul>
            <li>Document: {$request['document_title']}</li>
            <li>Date: {$request['appointment_date']}</li>
            <li>Time: {$request['appointment_time']}</li>
        </ul>
        <p>Please arrive 15 minutes before your appointment time and bring valid ID.</p>
        HTML;
        
        return $this->send($user['email'], $subject, $body);
    }
    
    public function sendDocumentReady($user, $request) {
        $subject = 'Document Ready - Barangay Document System';
        $downloadUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/download_document.php?id=' . $request['id'];
        
        $body = <<<HTML
        <h2>Document Ready for Download</h2>
        <p>Dear {$user['full_name']},</p>
        <p>Your requested document is now ready. You can download it using the link below:</p>
        <p><a href="{$downloadUrl}">Download Document</a></p>
        <p>The document will be available for download for the next 7 days.</p>
        HTML;
        
        return $this->send($user['email'], $subject, $body);
    }
    
    public function sendAppointmentCancellation($user, $request) {
        $subject = 'Appointment Cancelled - Barangay Document System';
        
        $body = <<<HTML
        <h2>Appointment Cancellation Notice</h2>
        <p>Dear {$user['full_name']},</p>
        <p>Your appointment has been cancelled:</p>
        <ul>
            <li>Document: {$request['document_title']}</li>
            <li>Date: {$request['appointment_date']}</li>
            <li>Time: {$request['appointment_time']}</li>
        </ul>
        <p>If you have any questions, please contact the barangay office.</p>
        HTML;
        
        return $this->send($user['email'], $subject, $body);
    }
}
