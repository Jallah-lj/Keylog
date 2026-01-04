USE event_platform;

-- Update role ENUM to include 'admin'
ALTER TABLE users MODIFY COLUMN role ENUM('organizer', 'attendee', 'admin') DEFAULT 'attendee';

-- Insert default admin user (password: admin123)
-- Using a hardcoded hash for 'admin123'
-- Hash generated via password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password, role) 
VALUES ('System Admin', 'admin@eventplatform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
