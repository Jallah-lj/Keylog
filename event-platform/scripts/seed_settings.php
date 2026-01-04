<?php
require_once __DIR__ . '/../config/database.php';

$sql = "
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
";

try {
    $pdo->exec($sql);
    echo "Settings table created and seeded successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}
?>
