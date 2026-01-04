USE event_platform;

-- Add status column to events
ALTER TABLE events ADD COLUMN status ENUM('pending', 'published', 'rejected') DEFAULT 'pending';

-- Update all existing events to 'published' so they remain visible
UPDATE events SET status = 'published';
