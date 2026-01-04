<?php
require_once __DIR__ . '/../config/database.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/schema/schema_issue_detection.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "✅ Issue Detection System schema updated successfully!\n";
    echo "Added:\n";
    echo "  - Payment tracking columns to bookings table\n";
    echo "  - support_tickets table\n";
    echo "  - activity_logs table\n";
    echo "  - Performance indexes\n";
    
} catch (PDOException $e) {
    echo "❌ Error updating schema: " . $e->getMessage() . "\n";
}
?>
