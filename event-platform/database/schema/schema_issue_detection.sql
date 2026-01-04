-- Issue Detection System Schema Updates
USE event_platform;

-- 1. Update bookings table with payment tracking
ALTER TABLE bookings 
ADD COLUMN payment_status ENUM('pending_payment', 'paid', 'failed', 'refunded', 'cancelled', 'disputed') DEFAULT 'paid' AFTER status,
ADD COLUMN payment_method VARCHAR(50) AFTER payment_status,
ADD COLUMN payment_reference VARCHAR(255) AFTER payment_method,
ADD COLUMN email_sent BOOLEAN DEFAULT TRUE AFTER payment_reference,
ADD COLUMN email_sent_at TIMESTAMP NULL AFTER email_sent,
ADD COLUMN payment_attempts INT DEFAULT 1 AFTER email_sent_at,
ADD COLUMN last_payment_error TEXT NULL AFTER payment_attempts;

-- 2. Create support_tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    issue_type ENUM('no_email', 'wrong_ticket', 'payment_issue', 'other') NOT NULL,
    description TEXT,
    screenshot VARCHAR(255),
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    performed_by INT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. Add indexes for performance
CREATE INDEX idx_payment_status ON bookings(payment_status);
CREATE INDEX idx_support_status ON support_tickets(status);
CREATE INDEX idx_activity_booking ON activity_logs(booking_id, created_at);
