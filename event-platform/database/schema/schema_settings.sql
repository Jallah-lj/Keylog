USE event_platform;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Event Platform'),
('contact_email', 'support@eventplatform.com'),
('contact_phone', '+1 (555) 123-4567'),
('contact_address', '123 Event Street, Tech City, IL'),
('facebook_url', '#'),
('twitter_url', '#'),
('instagram_url', '#'),
('copyright_text', '© 2025 Event Platform. All rights reserved.')
ON DUPLICATE KEY UPDATE setting_key=setting_key;
