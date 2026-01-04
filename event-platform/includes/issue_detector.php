<?php
/**
 * Issue Detection Engine
 * Analyzes bookings and identifies potential problems
 */

class IssueDetector {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Detect issues for a specific booking
     */
    public function detectIssues($booking) {
        $issues = [];
        
        // 1. Payment Failed
        if ($booking['payment_status'] == 'failed') {
            $issues[] = [
                'type' => 'payment_failed',
                'severity' => 'high',
                'message' => 'Payment failed',
                'action' => 'retry_payment',
                'details' => $booking['last_payment_error']
            ];
        }
        
        // 2. Pending Payment > 30 mins
        if ($booking['payment_status'] == 'pending_payment') {
            $created = strtotime($booking['booking_date']);
            $now = time();
            $minutes = ($now - $created) / 60;
            
            if ($minutes > 30) {
                $issues[] = [
                    'type' => 'pending_timeout',
                    'severity' => 'medium',
                    'message' => 'Payment pending for ' . round($minutes) . ' minutes',
                    'action' => 'send_reminder',
                    'details' => null
                ];
            }
        }
        
        // 3. Email Not Sent
        if (!$booking['email_sent']) {
            $issues[] = [
                'type' => 'no_email',
                'severity' => 'high',
                'message' => 'Confirmation email not sent',
                'action' => 'resend_email',
                'details' => null
            ];
        }
        
        // 4. Multiple Failed Attempts
        if ($booking['payment_attempts'] > 3) {
            $issues[] = [
                'type' => 'multiple_failures',
                'severity' => 'high',
                'message' => $booking['payment_attempts'] . ' payment attempts',
                'action' => 'flag_fraud',
                'details' => 'Possible bot or fraud'
            ];
        }
        
        // 5. Disputed Payment
        if ($booking['payment_status'] == 'disputed') {
            $issues[] = [
                'type' => 'disputed',
                'severity' => 'critical',
                'message' => 'Payment disputed/chargeback',
                'action' => 'contact_support',
                'details' => null
            ];
        }
        
        return $issues;
    }
    
    /**
     * Get all bookings with issues for an event
     */
    public function getEventIssues($event_id) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.name, u.email, u.avatar,
                   tt.name as ticket_name, tt.price as ticket_price
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
            WHERE b.event_id = ?
            ORDER BY b.booking_date DESC
        ");
        $stmt->execute([$event_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($bookings as $booking) {
            $issues = $this->detectIssues($booking);
            if (!empty($issues)) {
                $results[] = [
                    'booking' => $booking,
                    'issues' => $issues
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get issue statistics for an event
     */
    public function getIssueStats($event_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN payment_status = 'pending_payment' THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN email_sent = FALSE THEN 1 ELSE 0 END) as no_email,
                SUM(CASE WHEN payment_attempts > 3 THEN 1 ELSE 0 END) as multiple_failures,
                SUM(CASE WHEN payment_status = 'disputed' THEN 1 ELSE 0 END) as disputed
            FROM bookings
            WHERE event_id = ?
        ");
        $stmt->execute([$event_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get open support tickets for an event
     */
    public function getSupportTickets($event_id, $status = 'open') {
        $stmt = $this->pdo->prepare("
            SELECT st.*, u.name as user_name, u.email as user_email,
                   b.ticket_code
            FROM support_tickets st
            JOIN users u ON st.user_id = u.id
            JOIN bookings b ON st.booking_id = b.id
            WHERE st.event_id = ? AND st.status = ?
            ORDER BY st.created_at DESC
        ");
        $stmt->execute([$event_id, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
