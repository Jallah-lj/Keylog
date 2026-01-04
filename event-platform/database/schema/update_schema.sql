USE event_platform;

-- Add avatar to users
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL;

-- Add category to events
ALTER TABLE events ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'General';

-- Add ticket code and status to bookings
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS ticket_code VARCHAR(50) UNIQUE;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS status ENUM('confirmed', 'checked_in', 'cancelled') DEFAULT 'confirmed';
