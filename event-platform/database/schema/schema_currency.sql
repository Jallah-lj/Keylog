USE event_platform;

-- Add currency column to events
ALTER TABLE events ADD COLUMN currency ENUM('USD', 'LD') DEFAULT 'USD';
