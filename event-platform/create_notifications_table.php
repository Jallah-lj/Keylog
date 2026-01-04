<?php
require_once 'config/database.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('system', 'user', 'security') NOT NULL DEFAULT 'system',
        title VARCHAR(255) NOT NULL,
        message TEXT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $pdo->exec($sql);
    echo "Notifications table created successfully.\n";

    // Seed data
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
    if ($stmt->fetchColumn() == 0) {
        $sqlvec = "INSERT INTO notifications (type, title, message, is_read) VALUES 
        ('user', 'New User Signup', 'John Doe just joined the platform.', 0),
        ('system', 'System Update', 'Maintenance scheduled for tonight.', 0),
        ('security', 'Login Alert', 'New login from unknown device.', 0)";
        $pdo->exec($sqlvec);
        echo "Seeded fake notifications.\n";
    }

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
