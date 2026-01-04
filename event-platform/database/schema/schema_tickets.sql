USE event_platform;

-- Create ticket_types table
CREATE TABLE IF NOT EXISTS ticket_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 100,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Add ticket_type_id to bookings
-- We allow NULL temporarily to migrate existing data, then we can enforce it if we want
ALTER TABLE bookings ADD COLUMN ticket_type_id INT DEFAULT NULL;
ALTER TABLE bookings ADD CONSTRAINT fk_ticket_type FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE SET NULL;

-- MIGRATE EXISTING DATA
-- 1. For every existing event, create a 'Standard' ticket type using its current price/capacity
INSERT INTO ticket_types (event_id, name, price, quantity)
SELECT id, 'Standard Access', price, max_capacity FROM events;

-- 2. Update existing bookings to point to the new 'Standard' ticket type for their respective event
UPDATE bookings b
JOIN ticket_types tt ON b.event_id = tt.event_id
SET b.ticket_type_id = tt.id;
