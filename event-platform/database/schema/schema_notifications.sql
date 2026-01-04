USE event_platform;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('system', 'user', 'security') NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some dummy notifications
INSERT INTO notifications (type, title, message, is_read) VALUES 
('user', 'New User Signup', 'John Doe just joined the platform.', 0),
('system', 'System Update', 'Maintenance scheduled for tonight.', 0),
('security', 'Login Alert', 'New login from unknown device.', 0);
